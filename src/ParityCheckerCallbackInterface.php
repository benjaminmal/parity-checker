<?php

declare(strict_types=1);

namespace Elodgy\ParityChecker;

interface ParityCheckerCallbackInterface
{
    public function getTypes(): array;
    public function getClosure(): \Closure;
}