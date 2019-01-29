<?php declare(strict_types=1);

namespace App\Service;

use App\Model\Collection\Collection;
use App\Model\Collection\CollectionInterface;
use App\Model\RemoteEntityInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use League\OAuth1\Client\Credentials\CredentialsInterface;
use League\OAuth1\Client\Credentials\TemporaryCredentials;
use League\OAuth1\Client\Server\Server as OAuthServer;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Due to the nature of OAuth, the order in which the methods inside this class
 * are called matters. Therefore, make this class stateless and require the
 * credentials to be passed in as mandatory arguments to the methods that need
 * them to reduce the likelihood that errors will occur due to an invalid
 * internal state.
 */
class ApiServer implements ApiServerInterface, CollectionFetcherInterface
{
    /** @var \League\OAuth1\Client\Server\Server $oauth */
    private $oauth;
    /** @var \GuzzleHttp\ClientInterface $guzzle */
    private $guzzle;
    /** @var \Symfony\Component\Serializer\Encoder\DecoderInterface $decoder */
    private $decoder;
    /** @var \Symfony\Component\Serializer\Normalizer\DenormalizerInterface $denormalizer */
    private $denormalizer;

    public function __construct(OAuthServer $oauth, DecoderInterface $decoder, DenormalizerInterface $denormalizer)
    {
        $this->oauth = $oauth;
        $this->guzzle = $this->oauth->createHttpClient();
        $this->decoder = $decoder;
        $this->denormalizer = $denormalizer;
    }

    /**
     * Although it would be more convenient to generate temporary credentials in
     * the constructor so we could skip this step altogether, it means making a
     * request to the API server which is a waste of resources if a token is
     * already saved in the cache.
     */
    public function generateTemporaryCredentials(): TemporaryCredentials
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

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \RuntimeException
     */
    public function fetchCollection(string $entityClass, CredentialsInterface $token): CollectionInterface
    {
        if (!\is_a($entityClass, RemoteEntityInterface::class, true)) {
            throw new \RuntimeException(\sprintf(
                'Cannot fetch collection "%s" as it is not a remote entity.',
                $entityClass
            ));
        }
        /** @var \App\Model\RemoteEntityInterface|string $entityClass */
        $response = $this->request($apiUrl = $entityClass::getRemoteUrl(), $token);
        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            throw new \RuntimeException(\sprintf('API Error requesting GET "%s".', $apiUrl));
        }
        $data = $entityClass::extract($this->decoder->decode(
            $response->getBody()->getContents(),
            'xml'
        ));
        $collection = new Collection([], $entityClass);
        foreach ($data as $entity) {
            $entity = $this->fixXmlDecodedTypes($entity);
            $entity = $this->denormalizer->denormalize($entity, $entityClass, 'xml');
            $collection->add($entity);
        }
        return $collection;
    }

    /**
     * XML is a horrible encoding to use if not schema'd properly (like Xero has
     * failed to do with their API). Everything is a string, and Symfony's
     * Serializer component cannot correctly detect the string-representation of
     * a boolean so we'll have to manually check here (since the component will
     * only call custom denormalizers if the target's built-in type is an
     * object. Also, arrays of child entities result in double-nested arrays
     * because of how XML works, so we'll have to pick out the first child if
     * this is the case.
     *
     * I couldn't find an elegant way to solve these problems (we can disable
     * the type check with ObjectNormalizer::DISABLE_TYPE_ENFORCEMENT but that
     * causes just as many problems). This workaround is the best I've got on
     * short notice.
     */
    private function fixXmlDecodedTypes(array $data): array
    {
        return \array_map(function ($value) {
            if (\is_string($value)) {
                $boolean = \strtolower($value);
                if ($boolean === 'true') {
                    return true;
                } elseif ($boolean === 'false') {
                    return false;
                }
            } elseif (\is_array($value)) {
                return \reset($value);
            }
            // Not a special case, return as-is.
            return $value;
        }, $data);
    }
}
