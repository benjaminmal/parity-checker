<?php

declare(strict_types=1);

namespace Elodgy\ParityChecker;

class ParityCheckerCallback implements ParityCheckerCallbackInterface
{
    /**
     * @var string[]
     */
    protected array $types;

    /**
     * @param string|string[] $types
     */
    public function __construct(
        array|string $types,
        private \Closure $closure
    ) {
        $this->types = is_string($types) ? [$types] : $types;
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
