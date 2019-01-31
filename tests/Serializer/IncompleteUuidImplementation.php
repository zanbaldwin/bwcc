<?php declare(strict_types=1);

namespace App\Tests\Serializer;

use Ramsey\Uuid\UuidInterface;

class IncompleteUuidImplementation implements UuidInterface
{
    public function serialize() {}
    public function unserialize($serialized) {}
    public function compareTo(UuidInterface $other) {}
    public function equals($other) {}
    public function getBytes() {}
    public function getNumberConverter() {}
    public function getHex() {}
    public function getFieldsHex() {}
    public function getClockSeqHiAndReservedHex() {}
    public function getClockSeqLowHex() {}
    public function getClockSequenceHex() {}
    public function getDateTime() {}
    public function getInteger() {}
    public function getLeastSignificantBitsHex() {}
    public function getMostSignificantBitsHex() {}
    public function getNodeHex() {}
    public function getTimeHiAndVersionHex() {}
    public function getTimeLowHex() {}
    public function getTimeMidHex() {}
    public function getTimestampHex() {}
    public function getUrn() {}
    public function getVariant() {}
    public function getVersion() {}
    public function toString() {}
    public function jsonSerialize() {}
    protected static function fromString() {}
}
