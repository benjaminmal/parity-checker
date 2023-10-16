<?php

declare(strict_types=1);

namespace Benjaminmal\ParityChecker;

class ParityError
{
    public function __construct(
        private object $object1,
        private object $object2,
        private string $property,
        private mixed $object1Value,
        private mixed $object2Value,
    ) {
    }

    public function getObject1(): object
    {
        return $this->object1;
    }

    public function getObject2(): object
    {
        return $this->object2;
    }

    public function getProperty(): string
    {
        return $this->property;
    }

    public function getObject1Value(): mixed
    {
        return $this->object1Value;
    }

    public function getObject2Value(): mixed
    {
        return $this->object2Value;
    }
}
