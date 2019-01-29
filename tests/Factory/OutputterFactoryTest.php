<?php declare(strict_types=1);

namespace App\Tests\Factory;

use App\Factory\OutputterFactory;
use App\Outputter\OutputterInterface;
use Mockery as m;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class OutputterFactoryTest extends TestCase
{
    /** @var ContainerInterface|MockInterface $serviceLocator */
    private $serviceLocator;
    /** @var string[] $formats */
    private $formats = ['json', 'yaml'];
    /** @var OutputterFactory $factory */
    private $factory;

    public function setUp(): void
    {
        $this->serviceLocator = m::mock(ContainerInterface::class);
        $this->factory = new OutputterFactory($this->serviceLocator, $this->formats);
    }

    public function testInvalidFormatThrowsException(): void
    {
        $format = 'invalid-format';
        $this->serviceLocator->shouldReceive('has')->once()->with($format)->andReturn(false);
        $this->expectException(\InvalidArgumentException::class);
        $this->factory->createForFormat($format);
    }

    public function testValidFormatReturnsServiceFromContainer(): void
    {
        $format = 'valid-format';
        $this->serviceLocator->shouldReceive('has')->once()->with($format)->andReturn(true);
        $service = m::mock(OutputterInterface::class);
        $this->serviceLocator->shouldReceive('get')->once()->with($format)->andReturn($service);
        $outputter = $this->factory->createForFormat($format);
        $this->assertSame($service, $outputter);
    }
}
