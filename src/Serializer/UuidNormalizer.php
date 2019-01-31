<?php declare(strict_types=1);

namespace App\Serializer;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Exception;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class UuidNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /** {@inheritdoc} */
    public function normalize($object, $format = null, array $context = []): string
    {
        if (!$object instanceof UuidInterface) {
            throw new Exception\InvalidArgumentException('Cannot normalize a non-UUID object into a string.');
        }
        return $object->toString();
    }

    /** {@inheritdoc} */
    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof UuidInterface;
    }

    /** {@inheritdoc} */
    public function denormalize($data, $class, $format = null, array $context = []): UuidInterface
    {
        if (!\is_string($data)) {
            throw new Exception\InvalidArgumentException('UUID objects can only be constructed from strings.');
        }
        if ($class === UuidInterface::class) {
            $class = Uuid::class;
        }
        if (!\class_exists($class)) {
            throw new Exception\RuntimeException(\sprintf('Target denormalization type "%s" does not exist.', $class));
        }
        if (!\is_a($class, UuidInterface::class, true)) {
            throw new Exception\RuntimeException(\sprintf(
                'Target denormalization type "%s" does not implement "%s".',
                $class,
                UuidInterface::class
            ));
        }
        if (!\method_exists($class, 'fromString') || !\is_callable([$class, 'fromString'])) {
            throw new Exception\RuntimeException(\sprintf(
                'Target denormalization type "%s" does not publicly implement "fromString" method.',
                $class
            ));
        }
        try {
            return $class::fromString($data);
        } catch (\Ramsey\Uuid\Exception\InvalidUuidStringException $e) {
            throw new Exception\UnexpectedValueException('Value provided is not a valid UUID.', 0, $e);
        }
    }

    /** {@inheritdoc} */
    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return \is_a($type, UuidInterface::class, true)
            && \is_string($data)
            && \preg_match('/' . Uuid::VALID_PATTERN . '/D', $data);
    }
}
