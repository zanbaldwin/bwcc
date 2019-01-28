<?php declare(strict_types=1);

namespace App\Service;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use League\OAuth1\Client\Credentials\CredentialsInterface;
use League\OAuth1\Client\Credentials\TemporaryCredentials;
use League\OAuth1\Client\Server\Server as OAuthServer;

/**
 * Due to the nature of OAuth, the order in which the methods inside this class
 * are called matters. Therefore, make this class stateless and require the
 * credentials to be passed in as mandatory arguments to the methods that need
 * them to reduce the likelihood that errors will occur due to an invalid
 * internal state.
 */
class ApiServer implements ApiServerInterface
{
    /** @var \League\OAuth1\Client\Server\Server $oauth */
    private $oauth;
    /** @var \GuzzleHttp\ClientInterface $guzzle */
    private $guzzle;

    public function __construct(OAuthServer $oauth)
    {
        $this->oauth = $oauth;
        $this->guzzle = $this->oauth->createHttpClient();
    }

    /**
     * Although it would be more convenient to generate temporary credentials in
     * the constructor so we could skip this step altogether, it means making a
     * request to the API server which is a waste of resources if a token is
     * already saved in the cache.
     */
    public function generateTemporaryCredentials(): CredentialsInterface
    {
        return $this->oauth->getTemporaryCredentials();
    }

    public function getAuthorizationUrl(CredentialsInterface $temporary): string
    {
        return $this->oauth->getAuthorizationUrl($temporary);
    }

    public function exchangeAuthCodeForToken(
        TemporaryCredentials $temporary,
        string $authorizationCode
    ): CredentialsInterface {
        return $this->oauth->getTokenCredentials($temporary, $temporary->getIdentifier(), $authorizationCode);
    }

    public function signRequest(Request $request, CredentialsInterface $token): Request
    {
        $headers = $this->oauth->getHeaders($token, \strtoupper($request->getMethod()), $request->getUri());
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }
        return $request;
    }

    /**
     * Make an authenticated request to the API. This is a data import tool, so
     * hard-code the requests to *only* be GET requests preventing accidental
     * modification of data on the remote server.
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function request(string $url, CredentialsInterface $token): Response
    {
        $request = new Request('GET', $url);
        $request = $this->signRequest($request, $token);
        return $this->guzzle->send($request);
    }
}
