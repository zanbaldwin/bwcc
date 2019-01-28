<?php declare(strict_types=1);

namespace App\Service;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use League\OAuth1\Client\Credentials\CredentialsInterface;
use League\OAuth1\Client\Credentials\TemporaryCredentials;

interface ApiServerInterface
{
    public const BASE_URL = 'https://api.xero.com/api.xro/2.0/';

    public function generateTemporaryCredentials(): TemporaryCredentials;
    public function getAuthorizationUrl(CredentialsInterface $temporary): string;
    public function exchangeAuthCodeForToken(
        TemporaryCredentials $temporary,
        string $authorizationCode
    ): CredentialsInterface;
    public function signRequest(Request $request, CredentialsInterface $token): Request;
    public function request(string $url, CredentialsInterface $token): Response;
}
