<?php

declare(strict_types=1);

namespace Benjaminmal\ParityChecker;

interface ParityCheckerCallbackInterface
{
    /**
     * @return string[]
     */
    public function getTypes(): array;

    public function getClosure(): \Closure;
}
