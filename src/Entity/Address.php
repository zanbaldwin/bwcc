<?php declare(strict_types=1);

namespace App\Entity;

use App\Model\EntityInterface;
use Symfony\Component\Serializer\Annotation\SerializedName;

class Address implements EntityInterface
{
    /**
     * @SerializedName("AddressType")
     * @var string $type
     */
    private $type;

    /**
     * @SerializedName("AddressLine1")
     * @var string $line1
     */
    private $line1;

    /**
     * @SerializedName("AddressLine2")
     * @var string $line2
     */
    private $line2;

    /**
     * @SerializedName("AddressLine3")
     * @var string $line3
     */
    private $line3;

    /**
     * @SerializedName("AddressLine4")
     * @var string $line4
     */
    private $line4;

    /**
     * @SerializedName("City")
     * @var string $city
     */
    private $city;

    /**
     * @SerializedName("Region")
     * @var string $region
     */
    private $region;

    /**
     * @SerializedName("PostalCode")
     * @var string $postalCode
     */
    private $postalCode;

    /**
     * @SerializedName("Country")
     * @var string $country
     */
    private $country;

    /**
     * @SerializedName("AttentionTo")
     * @var string $attentionTo
     */
    private $attentionTo;

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getLine1(): ?string
    {
        return $this->line1;
    }

    public function setLine1(string $line): void
    {
        $this->line1 = $line;
    }

    public function getLine2(): ?string
    {
        return $this->line2;
    }

    public function setLine2(string $line): void
    {
        $this->line2 = $line;
    }

    public function getLine3(): ?string
    {
        return $this->line3;
    }

    public function setLine3(string $line): void
    {
        $this->line3 = $line;
    }

    public function getLine4(): ?string
    {
        return $this->line4;
    }

    public function setLine4(string $line): void
    {
        $this->line4 = $line;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(string $region): void
    {
        $this->region = $region;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): void
    {
        $this->postalCode = $postalCode;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): void
    {
        $this->country = $country;
    }

    public function getAttentionTo(): ?string
    {
        return $this->attentionTo;
    }

    public function setAttentionTo(string $attentionTo): void
    {
        $this->attentionTo = $attentionTo;
    }

    public function getData(): array
    {
        return [
            'type' => $this->type,
            'line1' => $this->line1,
            'line2' => $this->line2,
            'line3' => $this->line3,
            'line4' => $this->line4,
            'city' => $this->city,
            'region' => $this->region,
            'postalCode' => $this->postalCode,
            'country' => $this->country,
            'attentionTo' => $this->attentionTo,
        ];
    }
}
