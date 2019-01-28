<?php declare(strict_types=1);

namespace App\Entity;

use App\Model\RemoteEntityInterface;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\SerializedName;

class Account implements RemoteEntityInterface
{
    /**
     * @SerializedName("AccountID")
     * @var \Ramsey\Uuid\UuidInterface $id
     */
    private $id;

    /**
     * @SerializedName("Code")
     * @var string $code
     */
    private $code;

    /**
     * @SerializedName("Name")
     * @var string $name
     */
    private $name;

    /**
     * @SerializedName("Status")
     * @var string $status
     */
    private $status;

    /**
     * @SerializedName("Type")
     * @var string $type
     */
    private $type;

    /**
     * @SerializedName("TaxType")
     * @var string $taxType
     */
    private $taxType;

    /**
     * @SerializedName("Class")
     * @var string $class
     */
    private $class;

    /**
     * @SerializedName("EnablePaymentsToAccount")
     * @var boolean $paymentsToAccountEnabled
     */
    private $paymentsToAccountEnabled = false;

    /**
     * @SerializedName("ShowInExpenseClaims")
     * @var boolean $inExpenseClaimsShown
     */
    private $inExpenseClaimsShown = false;

    /**
     * @SerializedName("BankAccountNumber")
     * @var string $bankAccountNumber
     */
    private $bankAccountNumber;

    /**
     * @SerializedName("BankAccountType")
     * @var string $bankAccountType
     */
    private $bankAccountType;

    /**
     * @SerializedName("CurrencyCode")
     * @var string $currencyCode
     */
    private $currencyCode;

    /**
     * @SerializedName("ReportingCode")
     * @var string $reportingCode
     */
    private $reportingCode;

    /**
     * @SerializedName("ReportingCodeName")
     * @var string $reportingName
     */
    private $reportingName;

    /**
     * @SerializedName("HasAttachments")
     * @var boolean $attachments
     */
    private $attachments = false;

    /**
     * @SerializedName("UpdatedDateUTC")
     * @var \DateTimeInterface $lastUpdated
     */
    private $lastUpdated;

    public static function getRemoteUrl(): string
    {
        return API_BASE_URL . 'accounts';
    }

    public static function extract(array $data): array
    {
        return $data['Accounts']['Account'];
    }

    public static function getCollectionName(): string
    {
        return 'accounts';
    }

    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $id): void
    {
        $this->id = $id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getTaxType(): ?string
    {
        return $this->taxType;
    }

    public function setTaxType(string $taxType): void
    {
        $this->taxType = $taxType;
    }

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function setClass(string $class): void
    {
        $this->class = $class;
    }

    public function isPaymentsToAccountEnabled(): bool
    {
        return $this->paymentsToAccountEnabled;
    }

    public function setPaymentsToAccountEnabled(bool $paymentsToAccountEnabled): void
    {
        $this->paymentsToAccountEnabled = $paymentsToAccountEnabled;
    }

    public function isInExpenseClaimsShown(): bool
    {
        return $this->inExpenseClaimsShown;
    }

    public function setInExpenseClaimsShown(bool $inExpenseClaimsShown): void
    {
        $this->inExpenseClaimsShown = $inExpenseClaimsShown;
    }

    public function getBankAccountNumber(): ?string
    {
        return $this->bankAccountNumber;
    }

    public function setBankAccountNumber(string $bankAccountNumber): void
    {
        $this->bankAccountNumber = $bankAccountNumber;
    }

    public function getBankAccountType(): ?string
    {
        return $this->bankAccountType;
    }

    public function setBankAccountType(string $bankAccountType): void
    {
        $this->bankAccountType = $bankAccountType;
    }

    public function getCurrencyCode(): ?string
    {
        return $this->currencyCode;
    }

    public function setCurrencyCode(string $currencyCode): void
    {
        $this->currencyCode = $currencyCode;
    }

    public function getReportingCode(): ?string
    {
        return $this->reportingCode;
    }

    public function setReportingCode(string $reportingCode): void
    {
        $this->reportingCode = $reportingCode;
    }

    public function getReportingName(): ?string
    {
        return $this->reportingName;
    }

    public function setReportingName(string $reportingName): void
    {
        $this->reportingName = $reportingName;
    }

    public function hasAttachments(): bool
    {
        return $this->attachments;
    }

    public function setAttachments(bool $attachments): void
    {
        $this->attachments = $attachments;
    }

    public function getLastUpdated(): ?\DateTimeInterface
    {
        return $this->lastUpdated;
    }

    public function setLastUpdated(\DateTimeInterface $lastUpdated): void
    {
        $this->lastUpdated = $lastUpdated;
    }

    public function getData(): array
    {
        return [
            'id' => $this->id instanceof UuidInterface
                ? $this->id->toString()
                : null,
            'code' => $this->code,
            'name' => $this->name,
            'status' => $this->status,
            'type' => $this->type,
            'taxType' => $this->taxType,
            'class' => $this->class,
            'paymentsToAccountEnabled' => $this->paymentsToAccountEnabled,
            'inExpenseClaimsShown' => $this->inExpenseClaimsShown,
            'bankAccountNumber' => $this->bankAccountNumber,
            'bankAccountType' => $this->bankAccountType,
            'currencyCode' => $this->currencyCode,
            'reportingCode' => $this->reportingCode,
            'reportingName' => $this->reportingName,
            'attachments' => $this->attachments,
            'lastUpdated' => $this->lastUpdated instanceof \DateTimeInterface
                ? $this->lastUpdated->format(RFC3339)
                : null,
        ];
    }
}
