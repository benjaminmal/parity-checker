<?php

declare(strict_types=1);

namespace Benjaminmal\ParityChecker;

class ParityError
{
    protected object $object1;
    protected object $object2;
    protected string $property;

    /**
     * @var mixed
     */
    private $object1Value;

    /**
     * @var mixed
     */
    private $object2Value;

    /**
     * @param mixed $object1Value
     * @param mixed $object2Value
     */
    public function __construct(object $object1, object $object2, string $property, $object1Value, $object2Value)
    {
        $this->object1 = $object1;
        $this->object2 = $object2;
        $this->property = $property;
        $this->object1Value = $object1Value;
        $this->object2Value = $object2Value;
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
        return $this->object1Value;
    }

    /**
     * @return mixed
     */
    public function getObject2Value()
    {
        return $this->object2Value;
    }
}
