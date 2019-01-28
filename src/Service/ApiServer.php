<?php declare(strict_types=1);

namespace App\Service;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use League\OAuth1\Client\Credentials\CredentialsInterface;
use League\OAuth1\Client\Credentials\TemporaryCredentials;
use League\OAuth1\Client\Server\Server as OAuthServer;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException as CacheException;

/**
 * Due to the nature of OAuth, the order in which the methods inside this class
 * are called matters. Therefore, make this class stateless and require the
 * credentials to be passed in as mandatory arguments to the methods that need
 * them to reduce the likelihood that errors will occur due to an invalid
 * internal state.
 */
class ApiServer implements ApiServerInterface
{
    protected const OAUTH_TOKEN_CACHE_KEY = 'api_oauth_token';
    protected const OAUTH_TOKEN_CACHE_TTL = 'PT25M';

    /** @var \League\OAuth1\Client\Server\Server $oauth */
    private $oauth;
    /** @var \Psr\SimpleCache\CacheInterface $cache */
    private $cache;
    /** @var \GuzzleHttp\ClientInterface $guzzle */
    private $guzzle;

    public function __construct(OAuthServer $oauth, CacheInterface $cache)
    {
        $this->oauth = $oauth;
        $this->cache = $cache;
        $this->guzzle = $this->oauth->createHttpClient();
    }

    public function getCachedToken(): ?CredentialsInterface
    {
        try {
            if ($this->cache->has(static::OAUTH_TOKEN_CACHE_KEY)) {
                return $this->cache->get(static::OAUTH_TOKEN_CACHE_KEY);
            }
        } catch (CacheException $e) {
        }
        return null;
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
        $token = $this->oauth->getTokenCredentials($temporary, $temporary->getIdentifier(), $authorizationCode);
        try {
            $this->cache->set(static::OAUTH_TOKEN_CACHE_KEY, $token, new \DateInterval(static::OAUTH_TOKEN_CACHE_TTL));
        } catch (CacheException $e) {
            // Do nothing, we'll just have to re-authenticate on every run.
        } catch (\Exception $e) {
            // Shouldn't ever get here, but add a helpful message just in case.
            throw new \RuntimeException('Internal value for token cache TTL is malformed.', 0, $e);
        }
        return $token;
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
