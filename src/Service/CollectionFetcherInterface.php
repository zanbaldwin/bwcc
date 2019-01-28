<?php declare(strict_types=1);

namespace App\Service;

use App\Model\Collection\CollectionInterface;
use League\OAuth1\Client\Credentials\CredentialsInterface;

interface CollectionFetcherInterface
{
    public function fetchCollection(string $entityClass, CredentialsInterface $token): CollectionInterface;
}
