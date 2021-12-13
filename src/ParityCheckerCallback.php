<?php

declare(strict_types=1);

namespace Elodgy\ParityChecker;

class ParityCheckerCallback implements ParityCheckerCallbackInterface
{
    /**
     * @var string[]
     */
    protected array $types;

    protected \Closure $closure;

    public function __construct($types, \Closure $closure)
    {
        $this->setTypes($types);
        $this->setClosure($closure);
    }

    public function getTypes(): array
    {
        return $this->types;
    }

    public function setTypes($types): ParityCheckerCallback
    {
        $this->types = is_string($types) ? [$types] : $types;

        return $this;
    }

    public function getClosure(): \Closure
    {
        return $this->closure;
    }

    public function setClosure(\Closure $closure): ParityCheckerCallback
    {
        $this->closure = $closure;

        return $this;
    }
}
