<?php declare(strict_types=1);

namespace App\Controller;

use Psr\Container\ContainerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Environment as Twig;

abstract class AbstractController implements ServiceSubscriberInterface
{
    protected const AUTH_SESSION_KEY_TEMP = 'auth.credentials.temporary';

    /** @var \Psr\Container\ContainerInterface $container */
    private $container;

    /** @required */
    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    /**
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    final protected function render(string $view, array $parameters = [], ?Response $response = null): Response
    {
        $response = $response ?: new Response;
        $content = $this->container->get(Twig::class)->render($view, $parameters);
        $response->setContent($content);
        $response->headers->set(
            'Content-Length',
            \function_exists('\\mb_strlen') ? \mb_strlen($content, '8bit') : \strlen($content)
        );
        return $response;
    }

    final protected function createForm(string $type, $data = null, array $options = []): FormInterface
    {
        $options['method'] = $options['method']
            ?? $this->container->get(RequestStack::class)->getCurrentRequest()->getMethod();
        return $this->container->get(FormFactoryInterface::class)->create($type, $data, $options);
    }

    final protected function getSession(): SessionInterface
    {
        return $this->container->get(SessionInterface::class);
    }

    final protected function addFlashError(string $message): void
    {
        $this->container->get(FlashBagInterface::class)->set('error', $message);
    }

    public static function getSubscribedServices()
    {
        return [
            FlashBagInterface::class => FlashBagInterface::class,
            FormFactoryInterface::class => FormFactoryInterface::class,
            RequestStack::class => RequestStack::class,
            SessionInterface::class => SessionInterface::class,
            Twig::class => Twig::class,
        ];
    }
}
