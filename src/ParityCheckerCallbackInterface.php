<?php

declare(strict_types=1);

namespace Elodgy\ParityChecker;

interface ParityCheckerCallbackInterface
{
    public function getTypes(): array;
    public function setTypes($types): ParityCheckerCallback;
    public function getClosure(): \Closure;
    public function setClosure(\Closure $closure): ParityCheckerCallback;
}
