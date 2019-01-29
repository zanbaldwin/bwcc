<?php declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Account;
use App\Entity\Phone;
use App\Service\ApiServer;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use League\OAuth1\Client\Credentials\CredentialsException;
use League\OAuth1\Client\Credentials\TemporaryCredentials;
use League\OAuth1\Client\Credentials\TokenCredentials;
use League\OAuth1\Client\Server\Server;
use Mockery as m;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class ApiServerTest extends TestCase
{
    /** @var Server|MockInterface $oauth */
    private $oauth;
    /** @var ClientInterface|MockInterface $guzzle */
    private $guzzle;
    /** @var DecoderInterface|MockInterface $decoder */
    private $decoder;
    /** @var DenormalizerInterface|MockInterface $denormalizer */
    private $denormalizer;
    /** @var ApiServer $apiServer */
    private $apiServer;

    public function setUp(): void
    {
        $this->oauth = m::mock(Server::class);
        $this->guzzle = m::mock(ClientInterface::class);
        $this->oauth->shouldReceive('createHttpClient')->once()->andReturn($this->guzzle);
        $this->decoder = m::mock(DecoderInterface::class);
        $this->denormalizer = m::mock(DenormalizerInterface::class);
        $this->apiServer = new ApiServer($this->oauth, $this->decoder, $this->denormalizer);
    }

    public function testGenerateTemporaryCredentialsReturnsTemporaryCredentials(): void
    {
        $temporaryCredentials = m::mock(TemporaryCredentials::class);
        $this->oauth->shouldReceive('getTemporaryCredentials')->once()->andReturn($temporaryCredentials);
        $return = $this->apiServer->generateTemporaryCredentials();
        $this->assertSame($temporaryCredentials, $return);
    }

    public function testErrorGeneratingTemporaryCredentialsThrowsException(): void
    {
        $this->expectException(CredentialsException::class);
        $e = m::mock(CredentialsException::class);
        $this->oauth->shouldReceive('getTemporaryCredentials')->once()->andThrow($e);
        $this->apiServer->generateTemporaryCredentials();
    }

    public function testAuthUrlIsGenerated(): void
    {
        $temporary = m::mock(TemporaryCredentials::class);
        $url = 'authorization-url';
        $this->oauth->shouldReceive('getAuthorizationUrl')->once()->with($temporary)->andReturn($url);
        $return = $this->apiServer->getAuthorizationUrl($temporary);
        $this->assertSame($url, $return);
    }

    public function testExchangingAuthCodeGetsToken(): void
    {
        $temporary = m::mock(TemporaryCredentials::class);
        $identifier = 'tempoary-identifier';
        $temporary->shouldReceive('getIdentifier')->once()->andReturn($identifier);
        $authCode = 'auth-code';
        $token = m::mock(TokenCredentials::class);
        $this->oauth
            ->shouldReceive('getTokenCredentials')
            ->once()
            ->with($temporary, $identifier, $authCode)
            ->andReturn($token);
        $return = $this->apiServer->exchangeAuthCodeForToken($temporary, $authCode);
        $this->assertSame($token, $return);
    }

    public function testBadAuthCodeThrowsException(): void
    {
        $this->expectException(CredentialsException::class);
        $temporary = m::mock(TemporaryCredentials::class);
        $identifier = 'temporary-identifier';
        $temporary->shouldReceive('getIdentifier')->once()->andReturn($identifier);
        $authCode = 'auth-code';
        $e = m::mock(CredentialsException::class);
        $this->oauth
            ->shouldReceive('getTokenCredentials')
            ->once()
            ->with($temporary, $identifier, $authCode)
            ->andThrow($e);
        $this->apiServer->exchangeAuthCodeForToken($temporary, $authCode);
    }

    public function testRequestGetsSignedWithHeaders(): void
    {
        $request = m::mock(Request::class);
        $request->shouldReceive('getMethod')->once()->andReturn('get');
        $request->shouldReceive('getUri')->once()->andReturn('api-url');
        $token = m::mock(TokenCredentials::class);
        $headers = ['header1' => 'value1', 'header2' => 'value2'];
        $this->oauth->shouldReceive('getHeaders')->once()->with($token, 'GET', 'api-url')->andReturn($headers);
        $request->shouldReceive('withHeader')->once()->with('header1', 'value1')->andReturn($request);
        $request->shouldReceive('withHeader')->once()->with('header2', 'value2')->andReturn($request);
        $return = $this->apiServer->signRequest($request, $token);
        $this->assertSame($request, $return);
    }

    public function testReturnedRequestIsFromWithHeaderWhenSigned(): void
    {
        $request = m::mock(Request::class);
        $request->shouldReceive('getMethod')->once()->andReturn('get');
        $request->shouldReceive('getUri')->once()->andReturn('api-url');
        $token = m::mock(TokenCredentials::class);
        $headers = ['header1' => 'value1'];
        $this->oauth->shouldReceive('getHeaders')->once()->with($token, 'GET', 'api-url')->andReturn($headers);
        $newRequest = m::mock(Request::class);
        $request->shouldReceive('withHeader')->once()->with('header1', 'value1')->andReturn($newRequest);
        $return = $this->apiServer->signRequest($request, $token);
        $this->assertSame($newRequest, $return);
    }

    public function testSendingRequest(): void
    {
        $url = 'https://localhost/api-url';
        $token = m::mock(TokenCredentials::class);
        $apiServer = $this->createMock(ApiServer::class, ['signRequest'], [
            $this->oauth,
            $this->decoder,
            $this->denormalizer,
        ]);
        $request = m::mock(Request::class);
        /** @var ApiServer|MockInterface $apiServer */
        $apiServer
            ->method('signRequest')
            ->with($this->callback(function ($value) use ($url): bool {
                return $value instanceof Request
                    && $value->getMethod() === 'GET'
                    && ((string) $value->getUri()) === $url;
            }), $this->equalTo($token))
            ->will($this->returnValue($request));
        $response = m::mock(Response::class);
        $this->guzzle->shouldReceive('send')->once()->with($request)->andReturn($response);
        $return = $apiServer->request($url, $token);
        $this->assertInstanceOf(Response::class, $return);
    }

    public function testFetchNonCollectionThrowsException(): void
    {
        $token = m::mock(TokenCredentials::class);
        $entityClass = Phone::class;
        $this->expectException(\RuntimeException::class);
        $this->apiServer->fetchCollection($entityClass, $token);
    }

    public function testFetchCollectionWithLowStatusCodeThrowsException(): void
    {
        $token = m::mock(TokenCredentials::class);
        $entityClass = Account::class;
        /** @var ApiServer|MockInterface $apiServer */
        $apiServer = m::mock(ApiServer::class)->makePartial();
        $response = m::mock(Response::class);
        $apiServer->shouldReceive('request')->once()->with(m::type('string'), $token)->andReturn($response);
        $response->shouldReceive('getStatusCode')->once()->andReturn(101);
        $this->expectException(\RuntimeException::class);
        $apiServer->fetchCollection($entityClass, $token);
    }
}
