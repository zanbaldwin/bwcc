<?php declare(strict_types=1);

namespace App\Service;

use League\OAuth1\Client\Credentials\CredentialsInterface;

interface CachedApiServerInterface extends ApiServerInterface
{
    public function getCachedToken(): ?CredentialsInterface;
}
