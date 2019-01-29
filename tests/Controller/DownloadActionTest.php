<?php declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\DownloadAction;
use Mockery as m;
use Mockery\MockInterface;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamFile;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DownloadActionTest extends TestCase
{
    /** @var ParameterBagInterface|MockInterface $parameterBag */
    private $parameterBag;
    /** @var string $import */
    private $import;
    /** @var string $file */
    private $file;
    /** @var \App\Controller\DownloadAction $controller */
    private $controller;

    public function setUp()
    {
        $this->parameterBag = m::mock(ParameterBagInterface::class);
        $this->import = 'import-hash';
        $this->file = 'filename.ext';
        $this->controller = new DownloadAction($this->parameterBag);
    }

    private function getDownloadableFileLocation(): string
    {
        $mockFileSystem = vfsStream::setup('root', null, [
            'import-hash' => [
                'filename.ext' => 'download-file-contents',
                'nonfilelikedirectory' => [],
                new vfsStreamFile('unreadable', 0000),
            ],
        ]);
        return $mockFileSystem->url();
    }

    public function testFileGetsDownloadedWithCorrectHeaders(): void
    {
        $file = $this->getDownloadableFileLocation();
        $this->parameterBag
            ->shouldReceive('resolveValue')
            ->once()
            ->with('%kernel.project_dir%/var/imports/import-hash/filename.ext')
            ->andReturn($file . '/import-hash/filename.ext');
        /** @var \Symfony\Component\HttpFoundation\BinaryFileResponse $response */
        $response = ($this->controller)($this->import, $this->file);
        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertTrue($response->headers->has('Etag'));
        $this->assertTrue($response->headers->has('Last-Modified'));
        $this->assertSame('attachment; filename=filename.ext', $response->headers->get('Content-Disposition'));
        $this->assertTrue($response->headers->has('Cache-Control'));
        $this->assertStringContainsString('immutable', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
    }

    public function testNotFoundIsThrownWhenFileDoesNotExist(): void
    {
        $file = $this->getDownloadableFileLocation();
        $this->parameterBag
            ->shouldReceive('resolveValue')
            ->once()
            ->with('%kernel.project_dir%/var/imports/import-hash/filename.ext')
            ->andReturn($file . '/import-hash/nonfilelikedirectory');
        $this->expectException(NotFoundHttpException::class);
        ($this->controller)($this->import, $this->file);
    }

    public function testNotFoundIsThrownWhenFileIsADirectory(): void
    {
        $file = $this->getDownloadableFileLocation();
        $this->parameterBag
            ->shouldReceive('resolveValue')
            ->once()
            ->with('%kernel.project_dir%/var/imports/import-hash/filename.ext')
            ->andReturn($file . '/import-hash/doesnotexist');
        $this->expectException(NotFoundHttpException::class);
        ($this->controller)($this->import, $this->file);
    }

    public function testNotFoundIsThrownWhenFileIsNotReadable(): void
    {
        $file = $this->getDownloadableFileLocation();
        $this->parameterBag
            ->shouldReceive('resolveValue')
            ->once()
            ->with('%kernel.project_dir%/var/imports/import-hash/filename.ext')
            ->andReturn($file . '/import-hash/unreadable');
        $this->expectException(NotFoundHttpException::class);
        ($this->controller)($this->import, $this->file);
    }
}
