<?php declare(strict_types=1);

namespace App\Tests\Outputter;

use App\Model\Collection\CollectionInterface;
use App\Outputter\AbstractOutputter;
use Mockery as m;
use Mockery\MockInterface;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AbstractOutputterTest extends TestCase
{
    /** @var CollectionInterface|MockInterface */
    private $collection;
    /** @var ParameterBagInterface|MockInterface $parameterBag */
    private $parameterBag;
    /** @var AbstractOutputter */
    private $outputter;

    public function setUp()
    {
        $this->collection = m::mock(CollectionInterface::class);
        $this->parameterBag = m::mock(ParameterBagInterface::class);
        $this->outputter = new class extends AbstractOutputter {
            public static function getFormat(): string
            {
                return 'my-format';
            }

            public function flushToDisk(): array
            {
                $outputFiles = [];
                /** @var \App\Model\Collection\CollectionInterface $collection */
                foreach ($this->getCollections() as $collection) {
                    $outputFiles[] = $this->createFileHandle($collection->getCollectionName());
                }
                return $outputFiles;
            }
        };
        $this->outputter->setParameterBag($this->parameterBag);
    }

    public function testExceptionIsThrownIfImportDirectoryIsNotADirectory(): void
    {
        $this->collection->shouldReceive('getCollectionName')->once()->withNoArgs()->andReturn('entity-name');
        $mockFileSystem = vfsStream::setup('root', null, ['file' => 'not a directory']);
        $this->parameterBag
            ->shouldReceive('resolveValue')
            ->once()
            ->with('%kernel.project_dir%/var/imports')
            ->andReturn($mockFileSystem->url() . '/file');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Import directory "vfs://root/file" is not a writable directory.');
        $this->outputter->persistCollection($this->collection);
        $this->outputter->flushToDisk();
    }

    public function testExceptionIsThrownIfRootImportDirectoryIsNotWritable(): void
    {
        $this->collection->shouldReceive('getCollectionName')->once()->withNoArgs()->andReturn('entity-name');
        $mockFileSystem = vfsStream::setup('root', 0);
        $this->parameterBag
            ->shouldReceive('resolveValue')
            ->once()
            ->with('%kernel.project_dir%/var/imports')
            ->andReturn($mockFileSystem->url() . '/imports');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Could not create root import directory "vfs://root/imports" for writing.');
        $this->outputter->persistCollection($this->collection);
        $this->outputter->flushToDisk();
    }
}
