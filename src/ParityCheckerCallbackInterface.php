<?php

declare(strict_types=1);

namespace Elodgy\ParityChecker;

interface ParityCheckerCallbackInterface
{
    /**
     * @return string[]
     */
    public function getTypes(): array;

    public function getClosure(): \Closure;
}
