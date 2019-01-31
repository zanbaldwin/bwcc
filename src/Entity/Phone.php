<?php declare(strict_types=1);

namespace App\Entity;

use App\Model\EntityInterface;
use Symfony\Component\Serializer\Annotation\SerializedName;

/** @codeCoverageIgnore */
class Phone implements EntityInterface
{
    /**
     * @SerializedName("PhoneType")
     * @var string $type
     */
    private $type;

    /**
     * @SerializedName("PhoneNumber")
     * @var string $number
     */
    private $number;

    /**
     * @SerializedName("PhoneAreaCode")
     * @var string $areaCode
     */
    private $areaCode;

    /**
     * @SerializedName("PhoneCountryCode")
     * @var string $countryCode
     */
    private $countryCode;

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(string $number): void
    {
        $this->number = $number;
    }

    public function getAreaCode(): ?string
    {
        return $this->areaCode;
    }

    public function setAreaCode(string $areaCode): void
    {
        $this->areaCode = $areaCode;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function setCountryCode(string $countryCode): void
    {
        $this->countryCode = $countryCode;
    }

    public function getData(): array
    {
        return [
            'type' => $this->type,
            'number' => $this->number,
            'areaCode' => $this->areaCode,
            'countryCode' => $this->countryCode,
        ];
    }
}
