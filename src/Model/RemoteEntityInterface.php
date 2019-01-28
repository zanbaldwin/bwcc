<?php declare(strict_types=1);

namespace App\Model;

interface RemoteEntityInterface extends EntityInterface
{
    public static function getCollectionName(): string;
    public static function getRemoteUrl(): string;

    /**
     * The actual entity data is nested inside the XML response, this method
     * should extract the nested data from the decoded response.
     */
    public static function extract(array $data): array;
}
