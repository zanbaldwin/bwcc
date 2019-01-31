<?php declare(strict_types=1);

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

/** @codeCoverageIgnore */
class MisdirectedRequestHttpException extends HttpException
{
    public function __construct(
        ?string $message = null,
        ?\Exception $previous = null,
        int $code = 0,
        array $headers = array()
    ) {
        parent::__construct(421, $message, $previous, $headers, $code);
    }
}
