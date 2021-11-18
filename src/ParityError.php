<?php

declare(strict_types=1);

namespace Elodgy\ParityChecker;

class ParityError
{
    protected object $object1;
    protected object $object2;
    protected string $property;

    public function __construct(object $object1, object $object2, string $property)
    {
        $this->object1 = $object1;
        $this->object2 = $object2;
        $this->property = $property;
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

    /**
     * @return mixed
     */
    public function getObject1Value()
    {
        return $this->object1->{$this->property};
    }

    /**
     * @return mixed
     */
    public function getObject2Value()
    {
        return $this->object2->{$this->property};
    }
}
