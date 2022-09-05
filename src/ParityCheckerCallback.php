<?php

declare(strict_types=1);

namespace Benjaminmal\ParityChecker;

class ParityCheckerCallback implements ParityCheckerCallbackInterface
{
    /**
     * @var string[]
     */
    protected array $types;

    protected \Closure $closure;

    /**
     * @param string[]|string $types
     */
    public function __construct($types, \Closure $closure)
    {
        $this->types = is_string($types) ? [$types] : $types;
        $this->closure = $closure;
    }

    public function getTypes(): array
    {
        return $this->types;
    }

    public function getClosure(): \Closure
    {
        return $this->closure;
    }
}
