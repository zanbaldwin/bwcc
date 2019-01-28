<?php declare(strict_types=1);

namespace App\Service;

use League\OAuth1\Client\Credentials\CredentialsInterface;
use League\OAuth1\Client\Credentials\TemporaryCredentials;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException as CacheException;

/**
 * This cached version of the API server should be used for the console, whereas
 * the parent (non-cached) version of the API server should be used for the web
 * because the tokens should be saved in sessions.
 */
class CachedApiServer extends ApiServer implements CachedApiServerInterface
{
    protected const OAUTH_TOKEN_CACHE_KEY = 'api_oauth_token';
    protected const OAUTH_TOKEN_CACHE_TTL = 'PT25M';

    /** @var \Psr\SimpleCache\CacheInterface $cache */
    private $cache;

    /** @required */
    public function setCache(CacheInterface $cache): void
    {
        $this->cache = $cache;
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

    public function exchangeAuthCodeForToken(
        TemporaryCredentials $temporary,
        string $authorizationCode
    ): CredentialsInterface {
        $token = parent::exchangeAuthCodeForToken($temporary, $authorizationCode);
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
}
