<?php declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\ImportAction;
use App\Exception\MisdirectedRequestHttpException;
use App\Form\Type\ImportType;
use App\Model\Collection\CollectionInterface;
use App\Model\FormResponse;
use App\Outputter\OutputterFactoryInterface;
use App\Outputter\OutputterInterface;
use App\Service\ApiServerInterface;
use App\Service\CollectionFetcherInterface;
use GuzzleHttp\Exception\ServerException;
use League\OAuth1\Client\Credentials\CredentialsException;
use League\OAuth1\Client\Credentials\TemporaryCredentials;
use League\OAuth1\Client\Credentials\TokenCredentials;
use Mockery as m;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerException;
use Twig\Environment as Twig;

/** @runTestsInSeparateProcesses */
class ImportActionTest extends TestCase
{
    /** @var ApiServerInterface|MockInterface $api */
    private $api;
    /** @var CollectionFetcherInterface|MockInterface $fetcher */
    private $fetcher;
    /** @var OutputterFactoryInterface|MockInterface $outputterFactory */
    private $outputterFactory;
    /** @var \App\Model\RemoteEntityInterface $entities */
    private $entityClass;
    /** @var UrlGeneratorInterface|MockInterface $urlGenerator */
    private $urlGenerator;
    /** @var ContainerInterface|MockInterface $container */
    private $container;
    /** @var \App\Controller\ImportAction $controller */
    private $controller;
    /** @var FormResponse|MockInterface $submission */
    private $submission;

    public function setUp(): void
    {
        $this->api = m::mock(ApiServerInterface::class);
        $this->fetcher = m::mock(CollectionFetcherInterface::class);
        $this->outputterFactory = m::mock(OutputterFactoryInterface::class);
        $this->entityClass = 'entity-class';
        $this->urlGenerator = m::mock(UrlGeneratorInterface::class);
        $this->controller = new ImportAction(
            $this->api,
            $this->fetcher,
            $this->outputterFactory,
            [$this->entityClass],
            $this->urlGenerator
        );
        $this->container = m::mock(ContainerInterface::class);
        $this->controller->setContainer($this->container);
        $this->submission = m::mock(\sprintf('overload:\\%s', FormResponse::class));
    }

    public function testImportGetRequestIsSuccessful(): void
    {
        $request = m::mock(Request::class);
        $this->urlGenerator->shouldReceive('generate')->once()->with('import')->andReturn('submission-url');
        $formFactory = m::mock(FormFactoryInterface::class);
        $this->container->shouldReceive('get')->once()->with(FormFactoryInterface::class)->andReturn($formFactory);
        $form = m::mock(FormInterface::class);
        $formFactory->shouldReceive('create')->once()->with(ImportType::class, m::type(FormResponse::class), [
            'action' => 'submission-url',
            'method' => 'POST',
        ])->andReturn($form);
        $form->shouldReceive('handleRequest')->once()->with($request);
        $form->shouldReceive('isSubmitted')->once()->andReturn(false);
        $temporary = m::mock(TemporaryCredentials::class);
        $this->api->shouldReceive('generateTemporaryCredentials')->once()->andReturn($temporary);
        $session = m::mock(SessionInterface::class);
        $this->container->shouldReceive('get')->once()->with(SessionInterface::class)->andReturn($session);
        $session->shouldReceive('set')->once()->with('auth.credentials.temporary', $temporary);
        $form->shouldReceive('createView')->once()->andReturn('form-view');
        $this->api->shouldReceive('getAuthorizationUrl')->once()->with($temporary)->andReturn('auth-url');
        $twig = m::mock(Twig::class);
        $this->container->shouldReceive('get')->once()->with(Twig::class)->andReturn($twig);
        $twig->shouldReceive('render')->once()->with('authorize.html.twig', [
            'form' => 'form-view',
            'authUrl' => 'auth-url',
        ])->andReturn('response-content');

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = ($this->controller)($request);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('response-content', $response->getContent());
        $this->assertInstanceOf(HeaderBag::class, $response->headers);
        $this->assertTrue($response->headers->has('Cache-Control'));
        $this->assertStringContainsString('no-cache', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
    }

    public function testErrorPageWhenTemporaryCredentialsCannotBeFetched(): void
    {
        $request = m::mock(Request::class);
        $this->urlGenerator->shouldReceive('generate')->once()->with('import')->andReturn('submission-url');
        $formFactory = m::mock(FormFactoryInterface::class);
        $this->container->shouldReceive('get')->once()->with(FormFactoryInterface::class)->andReturn($formFactory);
        $form = m::mock(FormInterface::class);
        $formFactory->shouldReceive('create')->once()->with(ImportType::class, m::type(FormResponse::class), [
            'action' => 'submission-url',
            'method' => 'POST',
        ])->andReturn($form);
        $form->shouldReceive('handleRequest')->once()->with($request);
        $form->shouldReceive('isSubmitted')->once()->andReturn(false);
        $this->api
            ->shouldReceive('generateTemporaryCredentials')
            ->once()
            ->andThrows(m::mock(CredentialsException::class));

        $this->expectException(MisdirectedRequestHttpException::class);
        try {
            ($this->controller)($request);
            $this->fail('HTTP Exception not thrown.');
        } catch (HttpException $e) {
            $this->assertSame(421, $e->getStatusCode());
            throw $e;
        }
    }

    public function testImportPostRequestIsSuccessful(): void
    {
        $request = m::mock(Request::class);
        $this->urlGenerator->shouldReceive('generate')->once()->with('import')->andReturn('submission-url');
        $formFactory = m::mock(FormFactoryInterface::class);
        $this->container->shouldReceive('get')->once()->with(FormFactoryInterface::class)->andReturn($formFactory);
        $form = m::mock(FormInterface::class);
        $formFactory->shouldReceive('create')->once()->with(ImportType::class, m::type(FormResponse::class), [
            'action' => 'submission-url',
            'method' => 'POST',
        ])->andReturn($form);
        $form->shouldReceive('handleRequest')->once()->with($request);
        $form->shouldReceive('isSubmitted')->once()->andReturn(true);
        $form->shouldReceive('isValid')->once()->andReturn(true);

        $session = m::mock(SessionInterface::class);
        $this->container->shouldReceive('get')->once()->with(SessionInterface::class)->andReturn($session);
        $session->shouldReceive('has')->once()->with('auth.credentials.temporary')->andReturn(true);
        $temporary = m::mock(TemporaryCredentials::class);
        $session->shouldReceive('get')->once()->with('auth.credentials.temporary')->andReturn($temporary);
        $outputter = m::mock(OutputterInterface::class);
        $this->submission->shouldReceive('getFormat')->once()->andReturn('custom-format');
        $this->outputterFactory->shouldReceive('createForFormat')->once()->with('custom-format')->andReturn($outputter);
        $token = m::mock(TokenCredentials::class);
        $this->submission->shouldReceive('getAuthCode')->once()->andReturn('my-auth');
        $this->api->shouldReceive('exchangeAuthCodeForToken')->once()->with($temporary, 'my-auth')->andReturn($token);
        $collection = m::mock(CollectionInterface::class);
        $this->fetcher
            ->shouldReceive('fetchCollection')
            ->once()
            ->with($this->entityClass, $token)
            ->andReturn($collection);
        $outputter->shouldReceive('persistCollection')->once()->with($collection);
        $outputter->shouldReceive('flushToDisk')->once()->andReturn($files = ['output-file']);
        $twig = m::mock(Twig::class);
        $this->container->shouldReceive('get')->once()->with(Twig::class)->andReturn($twig);
        $twig->shouldReceive('render')->once()->with('imported.html.twig', [
            'files' => $files,
        ])->andReturn('response-content');

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = ($this->controller)($request);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('response-content', $response->getContent());
        $this->assertInstanceOf(HeaderBag::class, $response->headers);
        $this->assertTrue($response->headers->has('Cache-Control'));
        $this->assertStringContainsString('no-cache', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
    }

    public function testHttpErrorWhenSessionIsEmpty(): void
    {
        $request = m::mock(Request::class);
        $this->urlGenerator->shouldReceive('generate')->once()->with('import')->andReturn('submission-url');
        $formFactory = m::mock(FormFactoryInterface::class);
        $this->container->shouldReceive('get')->once()->with(FormFactoryInterface::class)->andReturn($formFactory);
        $form = m::mock(FormInterface::class);
        $formFactory->shouldReceive('create')->once()->with(ImportType::class, m::type(FormResponse::class), [
            'action' => 'submission-url',
            'method' => 'POST',
        ])->andReturn($form);
        $form->shouldReceive('handleRequest')->once()->with($request);
        $form->shouldReceive('isSubmitted')->once()->andReturn(true);
        $form->shouldReceive('isValid')->once()->andReturn(true);

        $session = m::mock(SessionInterface::class);
        $this->container->shouldReceive('get')->once()->with(SessionInterface::class)->andReturn($session);
        $session->shouldReceive('has')->once()->with('auth.credentials.temporary')->andReturn(false);

        $this->expectException(MisdirectedRequestHttpException::class);
        try {
            ($this->controller)($request);
            $this->fail('HTTP Exception not thrown.');
        } catch (HttpException $e) {
            $this->assertSame(421, $e->getStatusCode());
            throw $e;
        }
    }

    public function testErrorIsAddedToFormWhenAuthCodeIsIncorrect(): void
    {
        $request = m::mock(Request::class);
        $this->urlGenerator->shouldReceive('generate')->once()->with('import')->andReturn('submission-url');
        $formFactory = m::mock(FormFactoryInterface::class);
        $this->container->shouldReceive('get')->once()->with(FormFactoryInterface::class)->andReturn($formFactory);
        $form = m::mock(FormInterface::class);
        $formFactory->shouldReceive('create')->once()->with(ImportType::class, m::type(FormResponse::class), [
            'action' => 'submission-url',
            'method' => 'POST',
        ])->andReturn($form);
        $form->shouldReceive('handleRequest')->once()->with($request);
        $form->shouldReceive('isSubmitted')->once()->andReturn(true);
        $form->shouldReceive('isValid')->once()->andReturn(true);

        $session = m::mock(SessionInterface::class);
        $this->container->shouldReceive('get')->once()->with(SessionInterface::class)->andReturn($session);
        $session->shouldReceive('has')->once()->with('auth.credentials.temporary')->andReturn(true);
        $temporary = m::mock(TemporaryCredentials::class);
        $session->shouldReceive('get')->once()->with('auth.credentials.temporary')->andReturn($temporary);
        $outputter = m::mock(OutputterInterface::class);
        $this->submission->shouldReceive('getFormat')->once()->andReturn('custom-format');
        $this->outputterFactory->shouldReceive('createForFormat')->once()->with('custom-format')->andReturn($outputter);
        $this->submission->shouldReceive('getAuthCode')->once()->andReturn('my-auth');
        $this->api
            ->shouldReceive('exchangeAuthCodeForToken')
            ->once()
            ->with($temporary, 'my-auth')
            ->andThrows(m::mock(CredentialsException::class));
        $formElement = m::mock(FormInterface::class);
        $form->shouldReceive('get')->once()->with('authCode')->andReturn($formElement);
        $formElement->shouldReceive('addError')->once()->with(m::on(function ($value): bool {
            $this->assertInstanceOf(FormError::class, $value);
            /** @var FormError $value */
            $this->assertSame('There was a problem authenticating with Xero, please try again.', $value->getMessage());
            return true;
        }));
        $form->shouldReceive('createView')->once()->andReturn('form-view');
        $this->api->shouldReceive('getAuthorizationUrl')->once()->with($temporary)->andReturn('auth-url');
        $twig = m::mock(Twig::class);
        $this->container->shouldReceive('get')->once()->with(Twig::class)->andReturn($twig);
        $twig->shouldReceive('render')->once()->with('authorize.html.twig', [
            'form' => 'form-view',
            'authUrl' => 'auth-url',
        ])->andReturn('response-content');

        $this->api->shouldReceive('generateTemporaryCredentials')->once()->andReturn($temporary);
        $this->container->shouldReceive('get')->once()->with(SessionInterface::class)->andReturn($session);
        $session->shouldReceive('set')->once()->with('auth.credentials.temporary', $temporary);

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = ($this->controller)($request);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('response-content', $response->getContent());
        $this->assertInstanceOf(HeaderBag::class, $response->headers);
        $this->assertTrue($response->headers->has('Cache-Control'));
        $this->assertStringContainsString('no-cache', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
    }

    public function testErrorIsAddedToPageWhenApiIsDown(): void
    {
        $request = m::mock(Request::class);
        $this->urlGenerator->shouldReceive('generate')->once()->with('import')->andReturn('submission-url');
        $formFactory = m::mock(FormFactoryInterface::class);
        $this->container->shouldReceive('get')->once()->with(FormFactoryInterface::class)->andReturn($formFactory);
        $form = m::mock(FormInterface::class);
        $formFactory->shouldReceive('create')->once()->with(ImportType::class, m::type(FormResponse::class), [
            'action' => 'submission-url',
            'method' => 'POST',
        ])->andReturn($form);
        $form->shouldReceive('handleRequest')->once()->with($request);
        $form->shouldReceive('isSubmitted')->once()->andReturn(true);
        $form->shouldReceive('isValid')->once()->andReturn(true);

        $session = m::mock(SessionInterface::class);
        $this->container->shouldReceive('get')->once()->with(SessionInterface::class)->andReturn($session);
        $session->shouldReceive('has')->once()->with('auth.credentials.temporary')->andReturn(true);
        $temporary = m::mock(TemporaryCredentials::class);
        $session->shouldReceive('get')->once()->with('auth.credentials.temporary')->andReturn($temporary);
        $outputter = m::mock(OutputterInterface::class);
        $this->submission->shouldReceive('getFormat')->once()->andReturn('custom-format');
        $this->outputterFactory->shouldReceive('createForFormat')->once()->with('custom-format')->andReturn($outputter);
        $this->submission->shouldReceive('getAuthCode')->once()->andReturn('my-auth');
        $this->api
            ->shouldReceive('exchangeAuthCodeForToken')
            ->once()
            ->with($temporary, 'my-auth')
            ->andThrows(m::mock(ServerException::class));
        $formElement = m::mock(FormInterface::class);
        $form->shouldReceive('get')->once()->with('authCode')->andReturn($formElement);
        $flashBag = m::mock(FlashBagInterface::class);
        $this->container->shouldReceive('get')->once()->with(FlashBagInterface::class)->andReturn($flashBag);
        $flashBag->shouldReceive('set')->once()->with('error', 'Error encountered communicating with Xero API.');
        $form->shouldReceive('createView')->once()->andReturn('form-view');
        $this->api->shouldReceive('getAuthorizationUrl')->once()->with($temporary)->andReturn('auth-url');
        $twig = m::mock(Twig::class);
        $this->container->shouldReceive('get')->once()->with(Twig::class)->andReturn($twig);
        $twig->shouldReceive('render')->once()->with('authorize.html.twig', [
            'form' => 'form-view',
            'authUrl' => 'auth-url',
        ])->andReturn('response-content');

        $this->api->shouldReceive('generateTemporaryCredentials')->once()->andReturn($temporary);
        $this->container->shouldReceive('get')->once()->with(SessionInterface::class)->andReturn($session);
        $session->shouldReceive('set')->once()->with('auth.credentials.temporary', $temporary);

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = ($this->controller)($request);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('response-content', $response->getContent());
        $this->assertInstanceOf(HeaderBag::class, $response->headers);
        $this->assertTrue($response->headers->has('Cache-Control'));
        $this->assertStringContainsString('no-cache', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
    }

    public function testErrorIsAddedToPageWhenApiResponseIsBad(): void
    {
        $request = m::mock(Request::class);
        $this->urlGenerator->shouldReceive('generate')->once()->with('import')->andReturn('submission-url');
        $formFactory = m::mock(FormFactoryInterface::class);
        $this->container->shouldReceive('get')->once()->with(FormFactoryInterface::class)->andReturn($formFactory);
        $form = m::mock(FormInterface::class);
        $formFactory->shouldReceive('create')->once()->with(ImportType::class, m::type(FormResponse::class), [
            'action' => 'submission-url',
            'method' => 'POST',
        ])->andReturn($form);
        $form->shouldReceive('handleRequest')->once()->with($request);
        $form->shouldReceive('isSubmitted')->once()->andReturn(true);
        $form->shouldReceive('isValid')->once()->andReturn(true);

        $session = m::mock(SessionInterface::class);
        $this->container->shouldReceive('get')->once()->with(SessionInterface::class)->andReturn($session);
        $session->shouldReceive('has')->once()->with('auth.credentials.temporary')->andReturn(true);
        $temporary = m::mock(TemporaryCredentials::class);
        $session->shouldReceive('get')->once()->with('auth.credentials.temporary')->andReturn($temporary);
        $outputter = m::mock(OutputterInterface::class);
        $this->submission->shouldReceive('getFormat')->once()->andReturn('custom-format');
        $this->outputterFactory->shouldReceive('createForFormat')->once()->with('custom-format')->andReturn($outputter);
        $this->submission->shouldReceive('getAuthCode')->once()->andReturn('my-auth');
        $this->api
            ->shouldReceive('exchangeAuthCodeForToken')
            ->once()
            ->with($temporary, 'my-auth')
            ->andThrows(new class extends \Exception implements SerializerException {});
        $formElement = m::mock(FormInterface::class);
        $form->shouldReceive('get')->once()->with('authCode')->andReturn($formElement);
        $flashBag = m::mock(FlashBagInterface::class);
        $this->container->shouldReceive('get')->once()->with(FlashBagInterface::class)->andReturn($flashBag);
        $flashBag->shouldReceive('set')->once()->with('error', 'Error encountered while processing API response.');
        $form->shouldReceive('createView')->once()->andReturn('form-view');
        $this->api->shouldReceive('getAuthorizationUrl')->once()->with($temporary)->andReturn('auth-url');
        $twig = m::mock(Twig::class);
        $this->container->shouldReceive('get')->once()->with(Twig::class)->andReturn($twig);
        $twig->shouldReceive('render')->once()->with('authorize.html.twig', [
            'form' => 'form-view',
            'authUrl' => 'auth-url',
        ])->andReturn('response-content');

        $this->api->shouldReceive('generateTemporaryCredentials')->once()->andReturn($temporary);
        $this->container->shouldReceive('get')->once()->with(SessionInterface::class)->andReturn($session);
        $session->shouldReceive('set')->once()->with('auth.credentials.temporary', $temporary);

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = ($this->controller)($request);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('response-content', $response->getContent());
        $this->assertInstanceOf(HeaderBag::class, $response->headers);
        $this->assertTrue($response->headers->has('Cache-Control'));
        $this->assertStringContainsString('no-cache', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
    }
}
