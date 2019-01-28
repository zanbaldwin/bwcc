<?php declare(strict_types=1);

namespace App\Model;

class FormResponse
{
    /**
     * @var string $authCode
     */
    private $authCode;

    /**
     * @var string $format
     */
    private $format;

    public function getAuthCode()
    {
        return $this->authCode;
    }

    public function setAuthCode($authCode): void
    {
        $this->authCode = $authCode;
    }

    public function getFormat()
    {
        return $this->format;
    }

    public function setFormat($format): void
    {
        $this->format = $format;
    }
}
