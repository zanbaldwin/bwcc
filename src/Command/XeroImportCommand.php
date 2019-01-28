<?php declare(strict_types=1);

namespace App\Command;

use App\Outputter\OutputterFactoryInterface;
use App\Service\CachedApiServerInterface;
use App\Service\CollectionFetcherInterface;
use League\OAuth1\Client\Credentials\CredentialsInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class XeroImportCommand extends Command
{
    private const EXIT_SUCCESS = 0;
    private const EXIT_ERROR_INTERNAL = 1;
    private const EXIT_ERROR_EXTERNAL = 2;
    private const EXIT_ERROR_UNKNOWN = 3;

    protected static $defaultName = 'xero:import';

    /** @var \App\Service\CachedApiServerInterface $api */
    private $api;
    /** @var \App\Service\CollectionFetcherInterface $fetcher */
    private $fetcher;
    /** @var \App\Outputter\OutputterFactoryInterface $outputterFactory */
    private $outputterFactory;
    /** @var \App\Model\RemoteEntityInterface[]|string[] $entities */
    private $entities;
    /** @var string $projectDir */
    private $projectDir;

    public function __construct(
        CachedApiServerInterface $api,
        CollectionFetcherInterface $fetcher,
        OutputterFactoryInterface $outputterFactory,
        array $entities,
        string $projectDir
    ) {
        $this->api = $api;
        $this->fetcher = $fetcher;
        $this->outputterFactory = $outputterFactory;
        $this->entities = $entities;
        $this->projectDir = $projectDir;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Import data from Xero')
            ->addArgument('format', InputArgument::REQUIRED, 'Output format')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var \Symfony\Component\Console\Output\ConsoleOutputInterface $output */
        $io = new SymfonyStyle($input, $output->getErrorOutput());

        try {
            $outputter = $this->outputterFactory->createForFormat($input->getArgument('format'));
        } catch (\InvalidArgumentException $e) {
            $io->error([
                'Invalid output format, please choose from one of the following:',
                \implode(', ', $this->outputterFactory->getValidFormats()),
            ]);
            return static::EXIT_ERROR_INTERNAL;
        }

        if (null === $token = $this->authorize($io)) {
            $io->error('Unable to authorize with Xero API.');
            return static::EXIT_ERROR_EXTERNAL;
        }

        try {
            foreach ($this->entities as $entity) {
                $collection = $this->fetcher->fetchCollection($entity, $token);
                $outputter->persistCollection($collection);
            }
        } catch (\Symfony\Component\Serializer\Exception\ExceptionInterface $e) {
            $io->error('Error encountered while processing API response.');
            return static::EXIT_ERROR_INTERNAL;
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $io->error('Error encountered communicating with Xero API.');
            return static::EXIT_ERROR_EXTERNAL;
        } catch (\Exception $e) {
            $io->error('Unknown error occured.');
            return static::EXIT_ERROR_UNKNOWN;
        }

        $files = $outputter->flushToDisk();

        $successMessage = \sprintf(
            'Entities (%s) from Xero API successfully saved to disk in the following files:',
            \implode(', ', \array_map(function (string $entityClass): string {
                /** @var \App\Model\RemoteEntityInterface $entityClass */
                return $entityClass::getCollectionName();
            }, $this->entities))
        );
        $io->success($successMessage);
        $io->table(['Files:'], \array_map(function (\SplFileInfo $file): array {
            return [$this->getRealPathInCaseOfContainer($file->getRealPath())];
        }, $files));

        return static::EXIT_SUCCESS;
    }

    private function authorize(StyleInterface $io): ?CredentialsInterface
    {
        if (null !== $token = $this->api->getCachedToken()) {
            return $token;
        }

        try {
            $temporary = $this->api->generateTemporaryCredentials();
            $authUrl = $this->api->getAuthorizationUrl($temporary);
            $io->note([
                'Please obtain an authorization code from the following URL:',
                $authUrl,
            ]);
            $authCode = $io->ask('Authorization Code:');
            return $this->api->exchangeAuthCodeForToken($temporary, $authCode);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getRealPathInCaseOfContainer(string $filepath): string
    {
        $hostMachineDir = \getenv('HOST_MACHINE_DIR');
        if (\is_string($hostMachineDir) && $hostMachineDir !== '') {
            return \str_replace($this->projectDir, $hostMachineDir, $filepath);
        }
        return $filepath;
    }
}
