<?php declare(strict_types=1);

namespace App\Tests\Outputter\Format;

use App\Model\Collection\CollectionInterface;
use App\Outputter\Format\JSON;
use App\Outputter\OutputFile;
use Mockery as m;
use Mockery\MockInterface;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamFile;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class JSONTest extends TestCase
{
    /** @var CollectionInterface|MockInterface $collection */
    private $collection;
    /** @var array $collections */
    private $collections;
    /** @var ParameterBagInterface|MockInterface $parameterBag */
    private $parameterBag;
    /** @var JSON|MockInterface $outputter */
    private $outputter;

    public function setUp(): void
    {
        $this->collection = m::mock(CollectionInterface::class);
        $this->collections = [$this->collection];
        $this->parameterBag = m::mock(ParameterBagInterface::class);
        $this->outputter = m::mock(JSON::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->outputter->setParameterBag($this->parameterBag);
        $this->outputter->shouldReceive('getCollections')->once()->withNoArgs()->andReturn($this->collections);
    }

    public function testCollectionsAreFlushedToDisk(): void
    {
        $this->collection->shouldReceive('getCollectionName')->once()->withNoArgs()->andReturn('entity-name');
        $mockFileSystem = vfsStream::setup();
        $this->parameterBag
            ->shouldReceive('resolveValue')
            ->once()
            ->with('%kernel.project_dir%/var/imports')
            ->andReturn($mockFileSystem->url());
        $this->collection->shouldReceive('map')->with(m::type(\Closure::class))->andReturn($this->collection);
        $this->collection->shouldReceive('toArray')->once()->withNoArgs()->andReturn(['my' => ['data' => 'structure']]);
        $files = $this->outputter->flushToDisk();
        $this->assertCount(1, $files);
        $this->assertContainsOnlyInstancesOf(OutputFile::class, $files);
        /** @var \App\Outputter\OutputFile $file */
        $file = \reset($files);
        $this->assertRegExp('/^[a-z\d]{40}$/', $file->getImportHash());
        $this->assertTrue($mockFileSystem->hasChild($file->getImportHash()));
        $this->assertTrue($mockFileSystem->hasChild($file->getImportHash() . '/entity-name.json'));
        $outputtedFile = $mockFileSystem->getChild($file->getImportHash() . '/entity-name.json');
        $this->assertInstanceOf(vfsStreamFile::class, $outputtedFile, 'Output of JSON outputter to mock filesystem is not a file.');
        /** @var \org\bovigo\vfs\vfsStreamFile $outputtedFile*/
        $this->assertSame('{"my":{"data":"structure"}}', $outputtedFile->getContent());
    }
}
