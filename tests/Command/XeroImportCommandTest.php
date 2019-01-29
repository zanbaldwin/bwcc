<?php declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\XeroImportCommand;
use App\Model\Collection\CollectionInterface;
use App\Model\RemoteEntityInterface;
use App\Outputter\OutputFile;
use App\Outputter\OutputterFactoryInterface;
use App\Outputter\OutputterInterface;
use App\Service\CachedApiServerInterface;
use App\Service\CollectionFetcherInterface;
use GuzzleHttp\Exception\ConnectException;
use League\OAuth1\Client\Credentials\CredentialsException;
use League\OAuth1\Client\Credentials\TemporaryCredentials;
use League\OAuth1\Client\Credentials\TokenCredentials;
use Mockery as m;
use Mockery\MockInterface;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @runTestsInSeparateProcesses
 */
class XeroImportCommandTest extends TestCase
{
    /** @var CachedApiServerInterface|MockInterface $api */
    private $api;
    /** @var CollectionFetcherInterface|MockInterface $fetcher */
    private $fetcher;
    /** @var OutputterFactoryInterface|MockInterface $outputterFactory */
    private $outputterFactory;
    /** @var \App\Model\RemoteEntityInterface $entity */
    private $entity;
    /** @var string[] $entities */
    private $entities;
    /** @var SymfonyStyle|MockInterface $style */
    private $style;
    /** @var \App\Command\XeroImportCommand $command */
    private $command;

    public function setUp(): void
    {
        $this->api = m::mock(CachedApiServerInterface::class);
        $this->fetcher = m::mock(CollectionFetcherInterface::class);
        $this->outputterFactory = m::mock(OutputterFactoryInterface::class);
        $this->entity = new class implements RemoteEntityInterface {
            public function getData(): array
            {
                return ['normalized' => 'data'];
            }

            public static function getCollectionName(): string
            {
                return 'collection-name';
            }

            public static function getRemoteUrl(): string
            {
                return 'remote-url';
            }

            public static function extract(array $data): array
            {
                return $data;
            }
        };
        $this->entities = [\get_class($this->entity)];
        $this->style = m::mock(\sprintf('overload:\\%s', SymfonyStyle::class));
        $this->command = new XeroImportCommand($this->api, $this->fetcher, $this->outputterFactory, $this->entities);
    }

    private function runCommand(Command $command, InputInterface $input, OutputInterface $output): int
    {
        $reflect = new \ReflectionClass($command);
        $method = $reflect->getMethod('execute');
        $method->setAccessible(true);
        return $method->invoke($command, $input, $output) ?? 0;
    }

    public function testCommandCompletesSuccessfullyWhenCacheIsEmpty(): void
    {
        /** @var InputInterface|MockInterface $input */
        $input = m::mock(InputInterface::class);
        /** @var OutputInterface|MockInterface $output */
        $output = m::mock(OutputInterface::class);

        // Outputter
        $input->shouldReceive('getArgument')->once()->with('format')->andReturn('my-format');
        $outputter = m::mock(OutputterInterface::class);
        $this->outputterFactory->shouldReceive('createForFormat')->once()->with('my-format')->andReturn($outputter);

        // Authorize
        $this->api->shouldReceive('getCachedToken')->once()->andReturn(null);
        $temporary = m::mock(TemporaryCredentials::class);
        $this->api->shouldReceive('generateTemporaryCredentials')->once()->andReturn($temporary);
        $this->api->shouldReceive('getAuthorizationUrl')->once()->with($temporary)->andReturn('api-auth-url');
        $this->style->shouldReceive('note')->once()->with([
            'Please obtain an authorization code from the following URL:',
            'api-auth-url',
        ]);
        $this->style->shouldReceive('ask')->once()->with('Authorization Code:')->andReturn('my-auth-code');
        $token = m::mock(TokenCredentials::class);
        $this->api
            ->shouldReceive('exchangeAuthCodeForToken')
            ->once()
            ->with($temporary, 'my-auth-code')
            ->andReturn($token);

        // Collection
        $collection = m::mock(CollectionInterface::class);
        $this->fetcher->shouldReceive('fetchCollection')->once()->with(m::on(function ($arg): bool {
            return \is_string($arg) && \class_exists($arg);
        }), $token)->andReturn($collection);
        $outputter->shouldReceive('persistCollection')->once()->with($collection);

        $mockFileSystem = vfsStream::setup('root/import-hash');
        $files = [$file = new class($mockFileSystem->url(), 'import-hash', 'filename.ext') extends OutputFile {
            public function getRealPath() {
                return 'real-path-to-file';
            }
        }];
        $this->assertTrue($mockFileSystem->hasChild('import-hash/filename.ext'));
        $outputter->shouldReceive('flushToDisk')->once()->andReturn($files);
        $this->style
            ->shouldReceive('success')
            ->once()
            ->with('Entities (collection-name) from Xero API successfully saved to disk in the following files:');
        $this->style->shouldReceive('table')->once()->with(['Files:'], [['real-path-to-file']]);

        $exitCode = $this->runCommand($this->command, $input, $output);
        $this->assertSame(0, $exitCode);
    }

    public function testCommandCompletesSuccessfullyGettingTokenFromCache(): void
    {
        /** @var InputInterface|MockInterface $input */
        $input = m::mock(InputInterface::class);
        /** @var OutputInterface|MockInterface $output */
        $output = m::mock(OutputInterface::class);

        // Outputter
        $input->shouldReceive('getArgument')->once()->with('format')->andReturn('my-format');
        $outputter = m::mock(OutputterInterface::class);
        $this->outputterFactory->shouldReceive('createForFormat')->once()->with('my-format')->andReturn($outputter);

        // Authorize
        $token = m::mock(TokenCredentials::class);
        $this->api->shouldReceive('getCachedToken')->once()->andReturn($token);

        // Collection
        $collection = m::mock(CollectionInterface::class);
        $this->fetcher->shouldReceive('fetchCollection')->once()->with(m::on(function ($arg): bool {
            return \is_string($arg) && \class_exists($arg);
        }), $token)->andReturn($collection);
        $outputter->shouldReceive('persistCollection')->once()->with($collection);

        $mockFileSystem = vfsStream::setup('root/import-hash');
        $files = [$file = new class($mockFileSystem->url(), 'import-hash', 'filename.ext') extends OutputFile {
            public function getRealPath() {
                return 'real-path-to-file';
            }
        }];
        $this->assertTrue($mockFileSystem->hasChild('import-hash/filename.ext'));
        $outputter->shouldReceive('flushToDisk')->once()->andReturn($files);
        $this->style
            ->shouldReceive('success')
            ->once()
            ->with('Entities (collection-name) from Xero API successfully saved to disk in the following files:');
        $this->style->shouldReceive('table')->once()->with(['Files:'], [['real-path-to-file']]);

        $exitCode = $this->runCommand($this->command, $input, $output);
        $this->assertSame(0, $exitCode);
    }

    public function testErrorIsHandledWhenDirectoryIsNotWritable(): void
    {
        /** @var InputInterface|MockInterface $input */
        $input = m::mock(InputInterface::class);
        /** @var OutputInterface|MockInterface $output */
        $output = m::mock(OutputInterface::class);

        // Outputter
        $input->shouldReceive('getArgument')->once()->with('format')->andReturn('my-format');
        $outputter = m::mock(OutputterInterface::class);
        $this->outputterFactory->shouldReceive('createForFormat')->once()->with('my-format')->andReturn($outputter);

        // Authorize
        $token = m::mock(TokenCredentials::class);
        $this->api->shouldReceive('getCachedToken')->once()->andReturn($token);

        // Collection
        $collection = m::mock(CollectionInterface::class);
        $this->fetcher->shouldReceive('fetchCollection')->once()->with(m::on(function ($arg): bool {
            return \is_string($arg) && \class_exists($arg);
        }), $token)->andReturn($collection);
        $outputter->shouldReceive('persistCollection')->once()->with($collection);
        $outputter->shouldReceive('flushToDisk')->once()->andThrow(new \RuntimeException);
        $this->style
            ->shouldReceive('error')
            ->once()
            ->with('An error occurred persisting data to disk.');

        $exitCode = $this->runCommand($this->command, $input, $output);
        $this->assertSame(1, $exitCode);
    }

    public function testErrorIsHandledWhenApiRequestFails(): void
    {
        /** @var InputInterface|MockInterface $input */
        $input = m::mock(InputInterface::class);
        /** @var OutputInterface|MockInterface $output */
        $output = m::mock(OutputInterface::class);

        // Outputter
        $input->shouldReceive('getArgument')->once()->with('format')->andReturn('my-format');
        $outputter = m::mock(OutputterInterface::class);
        $this->outputterFactory->shouldReceive('createForFormat')->once()->with('my-format')->andReturn($outputter);

        // Authorize
        $token = m::mock(TokenCredentials::class);
        $this->api->shouldReceive('getCachedToken')->once()->andReturn($token);

        // Collection
        $this->fetcher->shouldReceive('fetchCollection')->once()->with(m::on(function ($arg): bool {
            return \is_string($arg) && \class_exists($arg);
        }), $token)->andThrows(new \RuntimeException);
        $this->style
            ->shouldReceive('error')
            ->once()
            ->with('The Xero API did not return usable data.');

        $exitCode = $this->runCommand($this->command, $input, $output);
        $this->assertSame(2, $exitCode);
    }

    public function testErrorIsHandledWhenApiIsDown(): void
    {
        /** @var InputInterface|MockInterface $input */
        $input = m::mock(InputInterface::class);
        /** @var OutputInterface|MockInterface $output */
        $output = m::mock(OutputInterface::class);

        // Outputter
        $input->shouldReceive('getArgument')->once()->with('format')->andReturn('my-format');
        $outputter = m::mock(OutputterInterface::class);
        $this->outputterFactory->shouldReceive('createForFormat')->once()->with('my-format')->andReturn($outputter);

        // Authorize
        $token = m::mock(TokenCredentials::class);
        $this->api->shouldReceive('getCachedToken')->once()->andReturn($token);

        // Collection
        $this->fetcher->shouldReceive('fetchCollection')->once()->with(m::on(function ($arg): bool {
            return \is_string($arg) && \class_exists($arg);
        }), $token)->andThrows(m::mock(ConnectException::class));
        $this->style
            ->shouldReceive('error')
            ->once()
            ->with('Error encountered communicating with Xero API.');

        $exitCode = $this->runCommand($this->command, $input, $output);
        $this->assertSame(2, $exitCode);
    }

    public function testErrorHandledWhenAuthCodeCouldNotBeExchangedForAToken(): void
    {
        /** @var InputInterface|MockInterface $input */
        $input = m::mock(InputInterface::class);
        /** @var OutputInterface|MockInterface $output */
        $output = m::mock(OutputInterface::class);

        // Outputter
        $input->shouldReceive('getArgument')->once()->with('format')->andReturn('my-format');
        $outputter = m::mock(OutputterInterface::class);
        $this->outputterFactory->shouldReceive('createForFormat')->once()->with('my-format')->andReturn($outputter);

        // Authorize
        $this->api->shouldReceive('getCachedToken')->once()->andReturn(null);
        $temporary = m::mock(TemporaryCredentials::class);
        $this->api->shouldReceive('generateTemporaryCredentials')->once()->andReturn($temporary);
        $this->api->shouldReceive('getAuthorizationUrl')->once()->with($temporary)->andReturn('api-auth-url');
        $this->style->shouldReceive('note')->once()->with([
            'Please obtain an authorization code from the following URL:',
            'api-auth-url',
        ]);
        $this->style->shouldReceive('ask')->once()->with('Authorization Code:')->andReturn('my-auth-code');
        $this->api
            ->shouldReceive('exchangeAuthCodeForToken')
            ->once()
            ->with($temporary, 'my-auth-code')
            ->andThrows(m::mock(CredentialsException::class));

        $this->style->shouldReceive('error')->once()->with('Unable to authorize with Xero API.');

        $exitCode = $this->runCommand($this->command, $input, $output);
        $this->assertSame(2, $exitCode);
    }

    public function testErrorHandledWhenTemporaryCredentialsCouldNotBeFetched(): void
    {
        /** @var InputInterface|MockInterface $input */
        $input = m::mock(InputInterface::class);
        /** @var OutputInterface|MockInterface $output */
        $output = m::mock(OutputInterface::class);

        // Outputter
        $input->shouldReceive('getArgument')->once()->with('format')->andReturn('my-format');
        $outputter = m::mock(OutputterInterface::class);
        $this->outputterFactory->shouldReceive('createForFormat')->once()->with('my-format')->andReturn($outputter);

        // Authorize
        $this->api->shouldReceive('getCachedToken')->once()->andReturn(null);
        $this->api
            ->shouldReceive('generateTemporaryCredentials')
            ->once()
            ->andThrows(m::mock(CredentialsException::class));

        $this->style->shouldReceive('error')->once()->with('Unable to authorize with Xero API.');

        $exitCode = $this->runCommand($this->command, $input, $output);
        $this->assertSame(2, $exitCode);
    }

    public function testErrorHandledWhenInvalidOutputFormatSupplied(): void
    {
        /** @var InputInterface|MockInterface $input */
        $input = m::mock(InputInterface::class);
        /** @var OutputInterface|MockInterface $output */
        $output = m::mock(OutputInterface::class);

        // Outputter
        $input->shouldReceive('getArgument')->once()->with('format')->andReturn('my-format');
        $outputter = m::mock(OutputterInterface::class);
        $this->outputterFactory
            ->shouldReceive('createForFormat')
            ->once()
            ->with('my-format')
            ->andThrows(new \InvalidArgumentException);

        $this->outputterFactory->shouldReceive('getValidFormats')->once()->andReturn(['first-format', 'second-format']);
        $this->style->shouldReceive('error')->once()->with([
            'Invalid output format, please choose from one of the following:',
            'first-format, second-format',
        ]);

        $exitCode = $this->runCommand($this->command, $input, $output);
        $this->assertSame(1, $exitCode);
    }
}
