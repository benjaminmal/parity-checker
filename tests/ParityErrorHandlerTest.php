<?php

declare(strict_types=1);

namespace Benjaminmal\ParityChecker\Tests;

use Benjaminmal\ParityChecker\ParityError;
use Benjaminmal\ParityChecker\ParityErrorHandler;
use PHPUnit\Framework\TestCase;

class ParityErrorHandlerTest extends TestCase
{
    /** @test */
    public function constructWithElements(): void
    {
        $a = $this->createMock(ParityError::class);
        $b = $this->createMock(ParityError::class);

        $handler = new ParityErrorHandler([$a, $b]);

        $this->assertCount(2, $handler);
        $this->assertSame($a, $handler[0]);
        $this->assertSame($b, $handler[1]);
    }

    /** @test */
    public function constructWithElementsWithKey(): void
    {
        $a = $this->createMock(ParityError::class);
        $b = $this->createMock(ParityError::class);

        $handler = new ParityErrorHandler(['key1' => $a, 'key2' => $b]);

        $this->assertCount(2, $handler);
        $this->assertSame($a, $handler['key1']);
        $this->assertSame($b, $handler['key2']);
    }

    /** @test */
    public function addElementsInArrayStyle(): void
    {
        $a = $this->createMock(ParityError::class);
        $b = $this->createMock(ParityError::class);

        $handler = new ParityErrorHandler();
        $handler[] = $a;
        $handler[] = $b;

        $this->assertCount(2, $handler);
        $this->assertSame($a, $handler[0]);
        $this->assertSame($b, $handler[1]);
    }

    /** @test */
    public function addElementsWithKeyInArrayStyle(): void
    {
        $a = $this->createMock(ParityError::class);
        $b = $this->createMock(ParityError::class);

        $handler = new ParityErrorHandler();
        $handler['key1'] = $a;
        $handler['key2'] = $b;

        $this->assertCount(2, $handler);
        $this->assertSame($a, $handler['key1']);
        $this->assertSame($b, $handler['key2']);
    }

    /** @test */
    public function issetElements(): void
    {
        $a = $this->createMock(ParityError::class);
        $b = $this->createMock(ParityError::class);

        $handler = new ParityErrorHandler([$a, $b]);

        $this->assertTrue(isset($handler[0]));
        $this->assertTrue(isset($handler[1]));
    }

    /** @test */
    public function issetElementsWithKeys(): void
    {
        $a = $this->createMock(ParityError::class);
        $b = $this->createMock(ParityError::class);

        $handler = new ParityErrorHandler(['key1' => $a, 'key2' => $b]);

        $this->assertTrue(isset($handler['key1']));
        $this->assertTrue(isset($handler['key2']));
    }

    /** @test */
    public function unsetElements(): void
    {
        $a = $this->createMock(ParityError::class);
        $b = $this->createMock(ParityError::class);

        $handler = new ParityErrorHandler([$a, $b]);

        $this->assertTrue(isset($handler[0]));
        $this->assertTrue(isset($handler[1]));

        unset($handler[0], $handler[1]);
        
        $this->assertFalse(isset($handler[0]));
        $this->assertFalse(isset($handler[1]));
    }

    /** @test */
    public function unsetElementWithKey(): void
    {
        $a = $this->createMock(ParityError::class);
        $b = $this->createMock(ParityError::class);

        $handler = new ParityErrorHandler(['key1' => $a, 'key2' => $b]);

        $this->assertTrue(isset($handler['key1']));
        $this->assertTrue(isset($handler['key2']));

        unset($handler['key1'], $handler['key2']);
        
        $this->assertFalse(isset($handler['key1']));
        $this->assertFalse(isset($handler['key2']));
    }
}
