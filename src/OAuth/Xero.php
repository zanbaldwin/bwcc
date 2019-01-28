<?php declare(strict_types=1);

namespace App\OAuth;

use League\OAuth1\Client\Credentials\TokenCredentials;
use League\OAuth1\Client\Server\Server as OAuthServer;
use League\OAuth1\Client\Server\User;

class Xero extends OAuthServer
{
    public function urlTemporaryCredentials(): string
    {
        return 'https://api.xero.com/oauth/RequestToken';
    }

    public function urlAuthorization(): string
    {
        return 'https://api.xero.com/oauth/Authorize';
    }

    public function urlTokenCredentials(): string
    {
        return 'https://api.xero.com/oauth/AccessToken';
    }

    public function urlUserDetails(): string
    {
        return '';
    }

    public function userDetails($data, TokenCredentials $tokenCredentials): User
    {
        throw new \RuntimeException('Not implemented.');
    }

    public function userUid($data, TokenCredentials $tokenCredentials): string
    {
        throw new \RuntimeException('Not implemented.');
    }

    public function userEmail($data, TokenCredentials $tokenCredentials): string
    {
        throw new \RuntimeException('Not implemented.');
    }

    public function userScreenName($data, TokenCredentials $tokenCredentials): string
    {
        throw new \RuntimeException('Not implemented.');
    }
}
