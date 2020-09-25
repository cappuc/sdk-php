<?php

/**
 * This file is part of Temporal package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Temporal\Client\Meta\Native;

use Symfony\Component\Console\Exception\LogicException;
use Temporal\Client\Meta\ReaderInterface;

class NativeReader implements ReaderInterface
{
    /**
     * @var string
     */
    private const DEFAULT_PROPERTY_NAME = 'value';

    /**
     * @psalm-suppress UndefinedClass
     *
     * @param \ReflectionAttribute $attribute
     * @return array
     */
    private function getArguments(\ReflectionAttribute $attribute): array
    {
        $arguments = [];

        foreach ($attribute->getArguments() as $name => $value) {
            if (\is_int($name)) {
                if ($name !== 0) {
                    throw new \LogicException(\sprintf('Expected Value, got %s', \get_debug_type($value)));
                }

                $name = self::DEFAULT_PROPERTY_NAME;
            }

            $arguments[$name] = $value;
        }

        return $arguments;
    }

    /**
     * @param \ReflectionClass $class
     * @return bool
     */
    private function containsConstructor(\ReflectionClass $class): bool
    {
        if ($class->hasMethod('__construct')) {
            return true;
        }

        foreach ($class->getTraits() as $trait) {
            if ($this->containsConstructor($trait)) {
                return true;
            }
        }

        if ($parent = $class->getParentClass()) {
            return $this->containsConstructor($parent);
        }

        return false;
    }

    /**
     * @psalm-suppress UndefinedClass
     *
     * @param \ReflectionAttribute $attribute
     * @return object
     * @throws \ReflectionException
     */
    private function instance(\ReflectionAttribute $attribute): object
    {
        $arguments = $this->getArguments($attribute);

        $reflection = new \ReflectionClass($attribute->getName());

        // Using constructor
        if ($this->containsConstructor($reflection)) {
            return $reflection->newInstance($arguments);
        }

        // Using direct insert
        $instance = $reflection->newInstanceWithoutConstructor();

        foreach ($arguments as $name => $value) {
            try {
                $property = $reflection->getProperty($name);

                if (! $property->isPublic()) {
                    throw $this->noPropertyError($reflection, $name);
                }

                $instance->$name = $value;
            } catch (\Throwable $e) {
                throw $this->noPropertyError($reflection, $name);
            }
        }

        return $instance;
    }

    /**
     * @param \ReflectionClass $context
     * @param string $name
     * @return \LogicException
     */
    private function noPropertyError(\ReflectionClass $context, string $name): \LogicException
    {
        $available = \implode(', ', \get_class_vars($context->getName()));

        $message = 'The attribute #[%s] does not have a public property named "%s". Available properties: %s';
        $message = \sprintf($message, $context->getName(), $name, $available);

        return new LogicException($message);
    }

    /**
     * {@inheritDoc}
     * @throws \ReflectionException
     */
    public function getClassMetadata(\ReflectionClass $class, string $name = null): iterable
    {
        /** @psalm-suppress UndefinedClass */
        $result = $class->getAttributes($name, \ReflectionAttribute::IS_INSTANCEOF);

        foreach ($result as $attribute) {
            yield $this->instance($attribute);
        }
    }

    /**
     * {@inheritDoc}
     * @throws \ReflectionException
     */
    public function getMethodMetadata(\ReflectionMethod $method, string $name = null): iterable
    {
        /** @psalm-suppress UndefinedClass */
        $result = $method->getAttributes($name, \ReflectionAttribute::IS_INSTANCEOF);

        foreach ($result as $attribute) {
            yield $this->instance($attribute);
        }
    }

    /**
     * {@inheritDoc}
     * @throws \ReflectionException
     */
    public function getPropertyMetadata(\ReflectionProperty $property, string $name = null): iterable
    {
        /** @psalm-suppress UndefinedClass */
        $result = $property->getAttributes($name, \ReflectionAttribute::IS_INSTANCEOF);

        foreach ($result as $attribute) {
            yield $this->instance($attribute);
        }
    }
}
