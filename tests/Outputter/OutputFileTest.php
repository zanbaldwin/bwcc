<?php declare(strict_types=1);

namespace App\Tests\Outputter;

use App\Outputter\OutputFile;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class OutputFileTest extends TestCase
{
    public function testOutputFileReturnsCorrectImportHash(): void
    {
        $mockFileSystem = vfsStream::create(['import-hash-to-test' => ['filename.ext' => '']], vfsStream::setup());
        $outputFile = new OutputFile($mockFileSystem->url(), 'import-hash-to-test', 'filename.ext');
        $this->assertSame('import-hash-to-test', $outputFile->getImportHash());
    }
}
