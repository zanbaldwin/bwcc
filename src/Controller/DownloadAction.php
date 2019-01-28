<?php declare(strict_types=1);

namespace App\Controller;

use App\Outputter\AbstractOutputter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DownloadAction
{
    /** @var \Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface $parameterBag */
    private $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    public function __invoke(string $import, string $file): Response
    {
        $filepath = $this->parameterBag->resolveValue(
            \sprintf('%s/%s/%s', AbstractOutputter::IMPORT_DIRECTORY_ROOT, $import, $file)
        );
        if (\file_exists($filepath) && \is_file($filepath) && \is_readable($filepath)) {
            $file = new \SplFileObject($filepath);
            $response = new BinaryFileResponse($file);
            $this->setResponseCaching($response);
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $file->getBasename());
            return $response;
        }
        throw new NotFoundHttpException('File not found.');
    }

    private function setResponseCaching(BinaryFileResponse $response): Response
    {
        $response->setAutoLastModified();
        $response->setAutoEtag();
        // We only want the response cached by the end-user, it contains sensitive information that should not be
        // saved into shared cache such as web proxies. This does *NOT* count as security, that particular
        // responsibility lies with the end-user.
        $response->setPrivate();
        $response->headers->addCacheControlDirective('immutable', true);
        return $response;
    }
}
