<?php

declare(strict_types=1);

namespace Benjaminmal\ParityChecker;

use Webmozart\Assert\Assert;

/**
 * @template-implements \ArrayAccess<int, ParityError>
 * @template-implements \IteratorAggregate<int, ParityError>
 */
class ParityErrorHandler implements \IteratorAggregate, \ArrayAccess
{
    /**
     * @var ParityError[]
     */
    protected array $container = [];

    /**
     * @param ParityError[] $parityErrors
     */
    public function __construct(iterable $parityErrors = [])
    {
        $this->addMultiples($parityErrors);
    }

    public function hasErrors(): bool
    {
        return ! empty($this->container);
    }

    /**
     * @param ParityError[] $elements
     */
    public function addMultiples(iterable $elements): void
    {
        foreach ($elements as $key => $element) {
            $this[$key] = $element;
        }
    }

    /**
     * @return \ArrayIterator<int, ParityError>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->container);
    }

    public function offsetExists($offset): bool
    {
        return isset($this->container[$offset]) || array_key_exists($offset, $this->container);
    }

    public function offsetGet($offset): ?ParityError
    {
        return $this->container[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        Assert::isInstanceOf($value, ParityError::class);

        if (null === $offset) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    public function offsetUnset($offset): void
    {
        Assert::keyExists($this->container, $offset);

        unset($this->container[$offset]);
    }

    /**
     * @return ParityError[]
     */
    public function toArray(): array
    {
        return $this->container;
    }
}
