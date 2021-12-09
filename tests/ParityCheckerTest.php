<?php

declare(strict_types=1);

namespace Elodgy\ParityChecker\Tests;

use Elodgy\ParityChecker\ParityChecker;
use Elodgy\ParityChecker\ParityError;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Webmozart\Assert\InvalidArgumentException;

class ParityCheckerTest extends TestCase
{
    /** @test */
    public function checkParitySuccessWithSameProperty(): void
    {
        $object1 = new class () {
            public string $param1 = 'hello';
            public int $param2 = 12;
        };

        $object2 = new class () {
            public string $param1 = 'hello';
            public int $param2 = 12;
        };

        $parityChecker = ParityChecker::create();
        $parityErrors = $parityChecker->checkParity([$object1, $object2]);

        $this->assertFalse($parityErrors->hasErrors());
    }

    /** @test */
    public function checkParityFailsWithSamePropertyName(): void
    {
        $object1 = new class () {
            public string $param1 = 'hell';
            public int $param2 = 12;
        };

        $object2 = new class () {
            public string $param1 = 'hello';
            public int $param2 = 10;
        };

        $parityChecker = ParityChecker::create();
        $parityErrors = $parityChecker->checkParity([$object1, $object2]);

        $this->assertTrue($parityErrors->hasErrors());
        $this->assertCount(2, $parityErrors);

        foreach ($parityErrors as $parityError) {
            $this->assertNotNull($parityError);
            $this->assertSame($object1, $parityError->getObject1());
            $this->assertSame($object2, $parityError->getObject2());
        }

        $this->assertSame('param1', $parityErrors[0]->getProperty());
        $this->assertSame('param2', $parityErrors[1]->getProperty());
    }

    /** @test */
    public function checkParityFailsWithNotSameProperty(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot found common properties.');

        $object1 = new class () {
            public string $param1 = 'hell';
            public int $param2 = 12;
        };

        $object2 = new class () {
            public string $param3 = 'hello';
            public int $param4 = 10;
        };

        $parityChecker = ParityChecker::create();
        $parityChecker->checkParity([$object1, $object2]);
    }

    /** @test */
    public function checkParityFailsWithOneObject(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('You must set at least 2 object to check.');

        $object1 = new class () {
            public string $param1 = 'hell';
            public int $param2 = 12;
        };

        $parityChecker = ParityChecker::create();
        $parityChecker->checkParity([$object1]);
    }

    /**
     * @test
     * @dataProvider noCheckOnTypesProvider
     */
    public function checkParityErrorsWithNoCheckOn($types, array $expectedErrorParams): void
    {
        $object1 = new class () {
            public string $param1 = 'mock_string';
            public int $param2 = 12;
            public float $param3 = 10.0;
            public array $param4 = [];
            public iterable $param5 = [];
            public object $param6;
        };
        $object1->param6 = new \StdClass();

        $object2 = new class () {
            public string $param1 = 'mock_string_1';
            public int $param2 = 10;
            public float $param3 = 10.1;
            public array $param4 = ['key' => 'value'];
            public iterable $param5 = ['key2' => 'value2'];
            public object $param6;
        };
        $object2->param6 = new \StdClass();

        $parityChecker = ParityChecker::create();
        $errors = $parityChecker->checkParity([$object1, $object2], [ParityChecker::IGNORE_TYPES_KEY => $types]);

        $this->assertCount(count($expectedErrorParams), $errors);

        /** @var ParityError $error */
        foreach ($errors as $error) {
            $this->assertTrue(in_array($error->getProperty(), $expectedErrorParams, true));
            $this->assertSame($object1, $error->getObject1());
            $this->assertSame($object2, $error->getObject2());
            $this->assertSame($object1->{$error->getProperty()}, $error->getObject1Value());
            $this->assertSame($object2->{$error->getProperty()}, $error->getObject2Value());
        }

        foreach ($expectedErrorParams as $param) {
            $properties = array_map(fn (ParityError $error) => $error->getProperty(), $errors->toArray());
            $this->assertTrue(in_array($param, $properties, true));
        }
    }

    /** @test */
    public function checkParityWithLooseCheckOn(): void
    {
        $object1 = new class () {
            public int $param1 = 12;
            public float $param2 = 10.0;
            public array $param3 = ['key1' => 'value1', 'key2' => 'value2'];
        };

        $object2 = new class () {
            public string $param1 = "12";
            public string $param2 = "10.0";
            public array $param3 = ['key2' => 'value2', 'key1' => 'value1'];
        };

        $parityChecker = ParityChecker::create();
        $strictErrors = $parityChecker->checkParity([$object1, $object2]);

        $this->assertCount(3, $strictErrors);

        $parityChecker = ParityChecker::create();
        $errors = $parityChecker->checkParity([$object1, $object2], [ParityChecker::LOOSE_CHECK_TYPES_KEY => ['string', 'int', 'array', 'float']]);

        $this->assertCount(0, $errors);
    }

    /** @test */
    public function checkParityWithCustomChecker(): void
    {
        $object1 = new class () {
            public int $param1 = 12;
            public float $param2 = 10.0;
            public array $param3 = ['key1' => 'value1', 'key2' => 'value2'];
        };

        $object2 = new class () {
            public int $param1 = 12;
            public float $param2 = 10.0;
            public array $param3 = ['key1' => 'value1', 'key2' => 'value2'];
        };

        $parityChecker = ParityChecker::create();
        $strictErrors = $parityChecker->checkParity([$object1, $object2]);

        $this->assertCount(0, $strictErrors);

        $parityChecker = ParityChecker::create();
        $errors = $parityChecker->checkParity([$object1, $object2], [
            ParityChecker::CALLBACK_CHECKER_KEY => [
                'checker1' => [
                    ParityChecker::CALLBACK_TYPES_KEY => ['$param1', '$param2', '$param3'],
                    ParityChecker::CALLBACK_CLOSURE_KEY => fn ($value1, $value2, string $property, array $options): bool => false,
                ]
            ]
        ]);

        $this->assertCount(3, $errors);
    }

    /** @test */
    public function checkParityWithCustomCheckerAndMissingType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The callback closure must return only a boolean.');

        $object = new class() {
            public int $param1;
        };

        $parityChecker = ParityChecker::create();
        $parityChecker->checkParity([$object, clone $object], [
            ParityChecker::CALLBACK_CHECKER_KEY => [
                'checker1' => [
                    ParityChecker::CALLBACK_TYPES_KEY => ['$param1', '$param2', '$param3'],
                    ParityChecker::CALLBACK_CLOSURE_KEY => fn ($value1, $value2, string $property, array $options) => false,
                ]
            ]
        ]);
    }

//    Only do this check when moving to PHP >= 8
//    /** @test */
//    public function checkParityWithCustomCheckerAndInvalidType(): void
//    {
//        $this->expectException(InvalidArgumentException::class);
//        $this->expectExceptionMessage('The callback closure must return only a boolean.');
//
//        $object = new class() {
//            public int $param1;
//        };
//
//        $parityChecker = ParityChecker::create();
//        $parityChecker->checkParity([$object, clone $object], [
//            ParityChecker::CALLBACK_CHECKER_KEY => [
//                'checker1' => [
//                    ParityChecker::CALLBACK_TYPES_KEY => ['$param1', '$param2', '$param3'],
//                    ParityChecker::CALLBACK_CLOSURE_KEY => fn ($value1, $value2, string $property, array $options): bool|array => false,
//                ]
//            ]
//        ]);
//    }

    /** @test */
    public function checkParityWithDeepObjectLimitAt2(): void
    {
        $deepObject1 = new class () {
            public int $param4 = 15;
            public float $param5 = 10.2;
            public object $childDeepObject;
        };

        $deepObject2 = new class () {
            public int $param4 = 15;
            public float $param5 = 10.2;
            public object $childDeepObject;
        };

        $object1 = new class () {
            public int $param1 = 12;
            public float $param2 = 10.0;
            public array $param3 = ['key1' => 'value1', 'key2' => 'value2'];
            public object $deepObject;
        };

        $object2 = new class () {
            public int $param1 = 12;
            public float $param2 = 10.0;
            public array $param3 = ['key1' => 'value1', 'key2' => 'value2'];
            public object $deepObject;
        };

        $deepObject1->childDeepObject = new class () {
            public int $childParam1 = 20;
        };

        $deepObject2->childDeepObject = new class () {
            public int $childParam1 = 20;
        };

        $object1->deepObject = $deepObject1;
        $object2->deepObject = $deepObject2;

        $parityChecker = ParityChecker::create();
        $errors = $parityChecker->checkParity([$object1, $object2], [ParityChecker::DEEP_OBJECT_LIMIT_KEY => 2]);

        $this->assertCount(0, $errors);
    }

    /** @test */
    public function parityCheckWithFailDeepObject(): void
    {
        $deepObject1 = new class () {
            public int $param4 = 15;
            public float $param5 = 10.2;
            public object $childDeepObject;
        };

        $deepObject2 = new class () {
            public int $param4 = 15;
            public float $param5 = 10.2;
            public object $childDeepObject;
        };

        $object1 = new class () {
            public int $param1 = 12;
            public float $param2 = 10.0;
            public array $param3 = ['key1' => 'value1', 'key2' => 'value2'];
            public object $deepObject;
        };

        $object2 = new class () {
            public int $param1 = 12;
            public float $param2 = 10.0;
            public array $param3 = ['key1' => 'value1', 'key2' => 'value2'];
            public object $deepObject;
        };

        $deepObject1->childDeepObject = new class () {
            public int $childParam1 = 21;
        };

        $deepObject2->childDeepObject = new class () {
            public int $childParam1 = 20;
        };

        $object1->deepObject = $deepObject1;
        $object2->deepObject = $deepObject2;

        $parityChecker = ParityChecker::create();
        $errors = $parityChecker->checkParity([$object1, $object2], [ParityChecker::DEEP_OBJECT_LIMIT_KEY => 2]);

        $this->assertCount(1, $errors);
        $this->assertSame($deepObject1->childDeepObject, $errors[0]->getObject1());
        $this->assertSame($deepObject2->childDeepObject, $errors[0]->getObject2());
    }

    /** @test */
    public function parityCheckWithDeepObjectException(): void
    {
        $deepObject1 = new class () {
            public int $param4 = 15;
            public float $param5 = 10.2;
            public object $childDeepObject;
        };

        $deepObject2 = new class () {
            public int $param4 = 15;
            public float $param5 = 10.2;
            public object $childDeepObject;
        };

        $object1 = new class () {
            public int $param1 = 12;
            public float $param2 = 10.0;
            public array $param3 = ['key1' => 'value1', 'key2' => 'value2'];
            public object $deepObject;
        };

        $object2 = new class () {
            public int $param1 = 12;
            public float $param2 = 10.0;
            public array $param3 = ['key1' => 'value1', 'key2' => 'value2'];
            public object $deepObject;
        };

        $deepObject1->childDeepObject = new class () {};
        $deepObject2->childDeepObject = new class () {};
        $object1->deepObject = $deepObject1;
        $object2->deepObject = $deepObject2;

        $parityChecker = ParityChecker::create();
        $errors = $parityChecker->checkParity([$object1, $object2], [ParityChecker::DEEP_OBJECT_LIMIT_KEY => 2]);

        $this->assertCount(1, $errors);
        $this->assertSame($deepObject1->childDeepObject, $errors[0]->getObject1());
        $this->assertSame($deepObject2->childDeepObject, $errors[0]->getObject2());
    }

    /** @test */
    public function parityCheckWithProperty(): void
    {
        $object1 = new class () {
            public int $param1 = 12;
            public float $param2 = 10.0;
            public array $param3 = ['key1' => 'value1', 'key2' => 'value2'];
        };

        $object2 = new class () {
            public string $param1 = '12';
            public string $param2 = '10.0';
            public array $param3 = ['key2' => 'value2', 'key1' => 'value1'];
        };

        $parityChecker = ParityChecker::create();
        $errors = $parityChecker->checkParity([$object1, $object2], [ParityChecker::IGNORE_TYPES_KEY => ['$param2', '$param3']]);

        $this->assertCount(1, $errors);
        $this->assertSame('param1', $errors[0]->getProperty());
    }

    /** @test */
    public function parityCheckWithInterface(): void
    {
        $object1 = new class () {
            public int $param1 = 12;
            public float $param2 = 10.0;
            public \DateTimeImmutable $param3;
        };

        $object2 = new class () {
            public string $param1 = '12';
            public string $param2 = '10.0';
            public \DateTimeImmutable $param3;
        };

        $object1->param3 = new \DateTimeImmutable();
        $object2->param3 = new \DateTimeImmutable();

        $parityChecker = ParityChecker::create();
        $errors = $parityChecker->checkParity([$object1, $object2], [ParityChecker::IGNORE_TYPES_KEY => [\DateTimeInterface::class]]);

        $this->assertCount(2, $errors);
        $this->assertSame('param1', $errors[0]->getProperty());
        $this->assertSame('param2', $errors[1]->getProperty());
    }

    /** @test */
    public function parityCheckWithClass(): void
    {
        $object1 = new class () {
            public int $param1 = 12;
            public float $param2 = 10.0;
            public \DateTimeImmutable $param3;
        };

        $object2 = new class () {
            public string $param1 = '12';
            public string $param2 = '10.0';
            public \DateTimeImmutable $param3;
        };

        $object1->param3 = new \DateTimeImmutable();
        $object2->param3 = new \DateTimeImmutable();

        $parityChecker = ParityChecker::create();
        $errors = $parityChecker->checkParity([$object1, $object2], [ParityChecker::IGNORE_TYPES_KEY => [\DateTimeImmutable::class]]);

        $this->assertCount(2, $errors);
        $this->assertSame('param1', $errors[0]->getProperty());
        $this->assertSame('param2', $errors[1]->getProperty());
    }

    /** @test */
    public function parityCheckWithType(): void
    {
        $object1 = new class () {
            public int $param1 = 10;
            public float $param2 = 10.1;
            public array $param3 = ['value'];
            public string $param4 = 'mock_value';
            public iterable $param5 = ['key' => 'value'];
            public object $param6;
            public ?string $param7 = null;
            public $param8;
            public $param9;
        };

        $object2 = new class () {
            public int $param1 = 12;
            public float $param2 = 10.0;
            public array $param3 = [];
            public string $param4 = '';
            public iterable $param5 = [];
            public object $param6;
            public ?string $param7 = 'hey';
            public $param8;
            public $param9;
        };

        $object1->param6 = new \DateTimeImmutable();
        $object2->param6 = new \StdClass();
        $object1->param8 = [$this, 'noCheckOnTypesProvider'];
        $object2->param8 = [$this, 'parityCheckWithClass'];
        $object1->param9 = tmpfile();
        $object2->param9 = tmpfile();

        $parityChecker = ParityChecker::create();
        $errors = $parityChecker->checkParity([$object1, $object2], [ParityChecker::IGNORE_TYPES_KEY => ['int', 'float', 'array', 'callable', 'resource', 'null', 'object', 'iterable']]);

        $this->assertCount(1, $errors);
    }

    /** @test */
    public function parityCheckWithWrongType(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The option "ignore_types" with value array is invalid.');

        $parityChecker = ParityChecker::create();
        $parityChecker->checkParity([], [ParityChecker::IGNORE_TYPES_KEY => ['wrong_type_or_class_or_interface_or_property']]);
    }

    /** @test */
    public function parityCheckWithOnlyTypes(): void
    {
        $object1 = new class () {
            public int $param1 = 12;
            public float $param2 = 10.0;
            public string $param3 = 'mock';
        };

        $object2 = new class () {
            public int $param1 = 99;
            public float $param2 = 99.9;
            public string $param3 = 'mock';
        };

        $parityChecker = ParityChecker::create();
        $errors = $parityChecker->checkParity([$object1, $object2], [ParityChecker::ONLY_TYPES_KEY => '$param3']);

        $this->assertCount(0, $errors);
        $this->assertFalse($errors->hasErrors());
    }

    /** @test */
    public function parityCheckWithOnlyTypesAndIgnoreTypes(): void
    {
        $object1 = new class () {
            public int $param1 = 12;
            public float $param2 = 10.0;
            public string $param3 = 'mock';
            public array $param4 = ['mock'];
        };

        $object2 = new class () {
            public int $param1 = 99;
            public float $param2 = 99.9;
            public string $param3 = 'mock';
            public array $param4 = [];
        };

        $parityChecker = ParityChecker::create();
        $errors = $parityChecker->checkParity([$object1, $object2], [
            ParityChecker::IGNORE_TYPES_KEY => '$param4',
            ParityChecker::ONLY_TYPES_KEY => ['$param3', '$param4'],
        ]);

        $this->assertCount(0, $errors);
        $this->assertFalse($errors->hasErrors());
    }

    /** @test */
    public function parityCheckerWithDataMapper(): void
    {
        $object1 = new class () {
            public int $param1 = 99;
            public object $paramObject;

            public function __construct() {
                $this->paramObject = new class() {
                    public function getName(): string {
                        return 'hello';
                    }
                };
            }
        };

        $object2 = new class () {
            public int $param1 = 99;
            public object $paramObject;

            public function __construct() {
                $this->paramObject = new class() {
                    public function getName(): string {
                        return 'hello';
                    }
                };
            }
        };

        $parityChecker = ParityChecker::create();
        $errors = $parityChecker->checkParity([$object1, $object2], [
            ParityChecker::IGNORE_TYPES_KEY => [],
            ParityChecker::DATA_MAPPER_KEY => [
                'myMapper' => [
                    'types' => '$paramObject',
                    'closure' => fn (object $object): string => $object->getName(),
                ]
            ],
        ]);

        $this->assertCount(0, $errors);
        $this->assertFalse($errors->hasErrors());
    }

    /** @test */
    public function parityCheckerWithDataMapperFails(): void
    {
        $object1 = new class () {
            public int $param1 = 99;
            public object $paramObject;

            public function __construct() {
                $this->paramObject = new class() {
                    public function getName(): string {
                        return 'mock';
                    }
                };
            }
        };

        $object2 = new class () {
            public int $param1 = 99;
            public object $paramObject;

            public function __construct() {
                $this->paramObject = new class() {
                    public function getName(): string {
                        return 'hello';
                    }
                };
            }
        };

        $parityChecker = ParityChecker::create();
        $errors = $parityChecker->checkParity([$object1, $object2], [
            ParityChecker::IGNORE_TYPES_KEY => [],
            ParityChecker::DATA_MAPPER_KEY => [
                'myMapper' => [
                    'types' => '$paramObject',
                    'closure' => fn (object $object): string => $object->getName(),
                ]
            ],
        ]);

        $this->assertCount(1, $errors);
        /** @var ParityError $error */
        foreach ($errors as $error) {
            $this->assertSame('paramObject', $error->getProperty());
            $this->assertSame('mock', $error->getObject1Value()->getName());
            $this->assertSame('hello', $error->getObject2Value()->getName());
        }
    }

    /** @test */
    public function parityCheckerWithDateTimeDataMapperWithErrors(): void
    {
        $object1 = new class () {
            public \DateTime $dateTime;
            public \DateTimeImmutable $dateTimeImmutable;

            public function __construct() {
                $this->dateTime = new \DateTime();
                $this->dateTimeImmutable = new \DateTimeImmutable();
            }
        };

        $object2 = new class () {
            public \DateTime $dateTime;
            public \DateTimeImmutable $dateTimeImmutable;

            public function __construct() {
                $this->dateTime = new \DateTime('+2days');
                $this->dateTimeImmutable = new \DateTimeImmutable('+2days');
            }
        };

        $parityChecker = ParityChecker::create();
        $errors = $parityChecker->checkParity([$object1, $object2], [
            ParityChecker::IGNORE_TYPES_KEY => [],
        ]);

        $this->assertCount(2, $errors);

        /** @var ParityError $error */
        foreach ($errors as $error) {
            if ($error->getProperty() === 'dateTime') {
                $this->assertInstanceOf(\DateTime::class, $error->getObject1Value());
                $this->assertInstanceOf(\DateTime::class, $error->getObject2Value());

                // Check if datetime has changed
                $this->assertSame((new \DateTime())->format('d'), $error->getObject1Value()->format('d'));
                $this->assertSame((new \DateTime('+2days'))->format('d'), $error->getObject2Value()->format('d'));
            } else {
                $this->assertInstanceOf(\DateTimeImmutable::class, $error->getObject1Value());
                $this->assertInstanceOf(\DateTimeImmutable::class, $error->getObject2Value());
            }
        }
    }

    /** @test */
    public function parityCheckerWithDateTimeDataMapper(): void
    {
        $dateTime = new \DateTime();
        $dateTimeImmutable = new \DateTimeImmutable();

        $object1 = new class ($dateTime, $dateTimeImmutable) {
            public \DateTime $dateTime;
            public \DateTimeImmutable $dateTimeImmutable;

            public function __construct(\DateTime $dateTime, \DateTimeImmutable $dateTimeImmutable) {
                $this->dateTime = $dateTime;
                $this->dateTimeImmutable = $dateTimeImmutable;
            }
        };

        $object2 = new class ($dateTime, $dateTimeImmutable) {
            public \DateTime $dateTime;
            public \DateTimeImmutable $dateTimeImmutable;

            public function __construct(\DateTime $dateTime, \DateTimeImmutable $dateTimeImmutable) {
                $this->dateTime = $dateTime;
                $this->dateTimeImmutable = $dateTimeImmutable;
            }
        };

        $parityChecker = ParityChecker::create();
        $errors = $parityChecker->checkParity([$object1, $object2], [
            ParityChecker::IGNORE_TYPES_KEY => [],
        ]);

        $this->assertCount(0, $errors);
    }

    /** @test */
    public function parityCheckerWithDateTimeDataMapperWithFormat(): void
    {
        $object1 = new class () {
            public \DateTimeImmutable $dateTimeImmutable;

            public function __construct() {
                $this->dateTimeImmutable = new \DateTimeImmutable();
            }
        };

        $object2 = new class () {
            public \DateTimeImmutable $dateTimeImmutable;

            public function __construct() {
                $this->dateTimeImmutable = new \DateTimeImmutable('+2hours');
            }
        };

        $parityChecker = ParityChecker::create();
        $errors = $parityChecker->checkParity([$object1, $object2], [
            ParityChecker::IGNORE_TYPES_KEY => [],
            ParityChecker::DATETIME_CHECK_FORMAT_KEY => 'Y-m-d',
        ]);

        $this->assertCount(0, $errors);
    }

    /** @test */
    public function parityCheckerWithDateTimeDataMapperWithDifferentTimezone(): void
    {
        $dateTimeImmutable = new \DateTimeImmutable();

        $object1 = new class ($dateTimeImmutable) {
            public \DateTimeImmutable $dateTimeImmutable;

            public function __construct(\DateTimeImmutable $dateTimeImmutable) {
                $this->dateTimeImmutable = $dateTimeImmutable->setTimezone(new \DateTimeZone('Europe/Paris'));
            }
        };

        $object2 = new class ($dateTimeImmutable) {
            public \DateTimeImmutable $dateTimeImmutable;

            public function __construct(\DateTimeImmutable $dateTimeImmutable) {
                $this->dateTimeImmutable = $dateTimeImmutable->setTimezone(new \DateTimeZone('Australia/Melbourne'));
            }
        };

        $parityChecker = ParityChecker::create();
        $errors = $parityChecker->checkParity([$object1, $object2], [
            ParityChecker::IGNORE_TYPES_KEY => [],
        ]);

        $this->assertCount(0, $errors);
    }

    public function noCheckOnTypesProvider(): array
    {
        return [
            [[], ['param1', 'param2', 'param3', 'param4', 'param5', 'param6']],
            ['string', ['param2', 'param3', 'param4', 'param5', 'param6']],
            [['int'], ['param1', 'param3', 'param4', 'param5', 'param6']],
            [['float'], ['param1', 'param2', 'param4', 'param5', 'param6']],
            [['array'], ['param1', 'param2', 'param3', 'param6']],
            [['iterable'], ['param1', 'param2', 'param3', 'param6']],
            [[\StdClass::class], ['param1', 'param2', 'param3', 'param4', 'param5']],
            [['string', 'int'], ['param3', 'param4', 'param5', 'param6']],
            [['string', 'int', 'float'], ['param4', 'param5', 'param6']],
            [['string', 'int', 'float', 'array'], ['param6']],
            [['string', 'int', 'float', 'array', 'iterable'], ['param6']],
            [['string', 'int', 'float', 'array', 'iterable', \StdClass::class], []]
        ];
    }
}
