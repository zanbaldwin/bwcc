<?php declare(strict_types=1);

namespace App\Controller;

use App\Exception\MisdirectedRequestHttpException;
use App\Form\Type\ImportType;
use App\Model\FormResponse;
use App\Outputter\OutputterFactoryInterface;
use App\Outputter\OutputterInterface;
use App\Service\ApiServerInterface;
use App\Service\CollectionFetcherInterface;
use League\OAuth1\Client\Credentials\CredentialsException;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ImportAction extends AbstractController
{
    protected const ROUTE_SUBMISSION = 'import';
    protected const ROUTE_DOWNLOAD_FILE = 'download';

    /** @var \App\Service\ApiServerInterface $api */
    private $api;
    /** @var \App\Service\CollectionFetcherInterface $fetcher */
    private $fetcher;
    /** @var \App\Outputter\OutputterFactoryInterface $outputterFactory */
    private $outputterFactory;
    /** @var \App\Model\RemoteEntityInterface[]|string[] $entities */
    private $entities;
    /** @var \Symfony\Component\Routing\Generator\UrlGeneratorInterface $urlGenerator */
    private $urlGenerator;

    public function __construct(
        ApiServerInterface $api,
        CollectionFetcherInterface $fetcher,
        OutputterFactoryInterface $outputterFactory,
        array $entities,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->api = $api;
        $this->fetcher = $fetcher;
        $this->outputterFactory = $outputterFactory;
        $this->entities = $entities;
        $this->urlGenerator = $urlGenerator;
    }

    public function __invoke(Request $request): Response
    {
        $form = $this->createForm(ImportType::class, $submission = new FormResponse, [
            'action' => $this->urlGenerator->generate(static::ROUTE_SUBMISSION),
            'method' => 'POST',
        ]);
        $form->handleRequest($request);

        try {
            if ($form->isSubmitted() && $form->isValid()) {
                if (!$this->getSession()->has(static::AUTH_SESSION_KEY_TEMP)) {
                    // If there's no temporary credentials in the session it
                    // either means the end-user directly accessed this
                    // controller with a POST request (bypassing the web form)
                    // or the session cannot be accessed. At this point we don't
                    // really want to recover gracefully.
                    $this->throwExpiredCredentialsException();
                }
                /** @var \League\OAuth1\Client\Credentials\TemporaryCredentials $temporary */
                $temporary = $this->getSession()->get(static::AUTH_SESSION_KEY_TEMP);
                $outputter = $this->getOutputter($submission);
                $token = $this->api->exchangeAuthCodeForToken($temporary, $submission->getAuthCode());
                foreach ($this->entities as $entity) {
                    $collection = $this->fetcher->fetchCollection($entity, $token);
                    $outputter->persistCollection($collection);
                }
                return $this->render('imported.html.twig', [
                    'files' => $outputter->flushToDisk(),
                ], $this->createNonCacheableResponse());
            }
        } catch (CredentialsException $e) {
            $error = new FormError('There was a problem authenticating with Xero, please try again.');
            $form->get('authCode')->addError($error);
        } catch (\Symfony\Component\Serializer\Exception\ExceptionInterface $e) {
            $this->addFlashError('Error encountered while processing API response.');
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $this->addFlashError('Error encountered communicating with Xero API.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new \Error('Unknown error occured.', 0, $e);
        }

        try {
            // Temporary credentials can only be used once, generate new ones.
            $temporary = $this->api->generateTemporaryCredentials();
            $this->getSession()->set(static::AUTH_SESSION_KEY_TEMP, $temporary);
        } catch (CredentialsException $e) {
            $this->throwExpiredCredentialsException($e);
        }

        return $this->render('authorize.html.twig', [
            'form' => $form->createView(),
            'authUrl' => $this->api->getAuthorizationUrl($temporary),
        ], $this->createNonCacheableResponse());
    }

    private function getOutputter(FormResponse $submission): OutputterInterface
    {
        try {
            return $this->outputterFactory->createForFormat($submission->getFormat());
        } catch (\InvalidArgumentException $e) {
            // If the validation hasn't picked up that the format is wrong
            // here then there's not much else we can do. Throw an error.
            throw new BadRequestHttpException('Invalid format requested.', $e);
        }
    }

    private function throwExpiredCredentialsException(?\Exception $previous = null): void
    {
        throw new MisdirectedRequestHttpException(
            'Temporary credentials are not longer valid, please make another GET request.',
            $previous
        );
    }

    /**
     * Set the correct headers to prevent the response from being cached by the
     * browser as each request should result in a different authorization URL.
     */
    private function createNonCacheableResponse(): Response
    {
        $response = new Response;
        $response->setPrivate();
        $response->headers->addCacheControlDirective('no-cache');
        $response->headers->addCacheControlDirective('no-store');
        return $response;
    }
}
