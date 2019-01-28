<?php declare(strict_types=1);

namespace App\Entity;

use App\Model\RemoteEntityInterface;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\SerializedName;

class Vendor implements RemoteEntityInterface
{
    /**
     * @SerializedName("ContactID")
     * @var \Ramsey\Uuid\UuidInterface $id
     */
    private $id;

    /**
     * @SerializedName("ContactStatus")
     * @var string $status
     */
    private $status;

    /**
     * @SerializedName("Name")
     * @var string $name
     */
    private $name;

    /**
     * @SerializedName("Addresses")
     * @var \App\Entity\Address[] $addresses
     */
    private $addresses = [];

    /**
     * @SerializedName("Phones")
     * @var \App\Entity\Phone[] $phones
     */
    private $phones = [];

    /**
     * @SerializedName("UpdatedDateUTC")
     * @var \DateTimeInterface $lastUpdated
     */
    private $lastUpdated;

    /**
     * @SerializedName("IsSupplier")
     * @var boolean $supplier
     */
    private $supplier = false;

    /**
     * @SerializedName("IsCustomer")
     * @var boolean $customer
     */
    private $customer = false;

    /**
     * @SerializedName("HasAttachments")
     * @var boolean $attachments
     */
    private $attachments = false;

    public static function getRemoteUrl(): string
    {
        return API_BASE_URL . 'contacts';
    }

    public static function extract(array $data): array
    {
        return $data['Contacts']['Contact'];
    }

    public static function getCollectionName(): string
    {
        return 'vendors';
    }

    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $id): void
    {
        $this->id = $id;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = \strtolower($status);
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getAddresses(): array
    {
        return $this->addresses;
    }

    public function setAddresses(iterable $addresses): void
    {
        $this->addresses = [];
        foreach ($addresses as $address) {
            $this->addAddress($address);
        }
    }

    public function addAddress(Address $address): void
    {
        $this->addresses[] = $address;
    }

    public function getPhones(): array
    {
        return $this->phones;
    }

    public function setPhones(iterable $phones): void
    {
        $this->phones = [];
        foreach ($phones as $phone) {
            $this->addPhone($phone);
        }
    }

    public function addPhone(Phone $phone): void
    {
        $this->phones[] = $phone;
    }

    public function getLastUpdated(): ?\DateTimeInterface
    {
        return $this->lastUpdated;
    }

    public function setLastUpdated(\DateTimeInterface $lastUpdated): void
    {
        $this->lastUpdated = $lastUpdated;
    }

    public function isSupplier(): bool
    {
        return $this->supplier;
    }

    public function setSupplier(bool $supplier): void
    {
        $this->supplier = $supplier;
    }

    public function isCustomer(): bool
    {
        return $this->customer;
    }

    public function setCustomer(bool $customer): void
    {
        $this->customer = $customer;
    }

    public function hasAttachments(): bool
    {
        return $this->attachments;
    }

    public function setAttachments(bool $attachments): void
    {
        $this->attachments = $attachments;
    }

    public function getData(): array
    {
        return [
            'id' => $this->id->toString(),
            'status' => $this->status,
            'name' => $this->name,
            'addresses' => \array_map(function (Address $address): array {
                return $address->getData();
            }, $this->addresses),
            'phones' => \array_map(function (Phone $phone): array {
                return $phone->getData();
            }, $this->phones),
            'lastUpdated' => $this->lastUpdated instanceof \DateTimeInterface
                ? $this->lastUpdated->format(RFC3339)
                : null,
            'supplier' => $this->supplier,
            'customer' => $this->customer,
            'attachments' => $this->attachments,
        ];
    }
}
