<?php

declare(strict_types=1);

namespace Elodgy\ParityChecker;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Webmozart\Assert\Assert;

class ParityChecker
{
    public const IGNORE_TYPES_KEY = 'ignore_types';
    public const ONLY_TYPES_KEY = 'only_types';
    public const LOOSE_CHECK_TYPES_KEY = 'loose_types';
    public const DEEP_OBJECT_LIMIT_KEY = 'deep_object_limit';
    public const CALLBACK_CHECKER_KEY = 'callback_checker';
    public const CALLBACK_TYPES_KEY = 'types';
    public const CALLBACK_CLOSURE_KEY = 'closure';
    public const DATA_MAPPER_KEY = 'data_mapper';
    public const DATA_MAPPER_TYPES_KEY = 'types';
    public const DATA_MAPPER_CLOSURE_KEY = 'closure';

    private const TYPES_ALLOWED_TYPES = ['string[]', 'string'];

    protected PropertyAccessorInterface $propertyAccessor;
    protected PropertyInfoExtractorInterface $propertyInfoExtractor;

    public function __construct(
        PropertyAccessorInterface $propertyAccessor,
        PropertyInfoExtractorInterface $propertyInfoExtractor
    ) {
        $this->propertyAccessor = $propertyAccessor;
        $this->propertyInfoExtractor = $propertyInfoExtractor;
    }

    public static function create(): ParityChecker
    {
        $phpDocExtractor = new PhpDocExtractor();
        $reflectionExtractor = new ReflectionExtractor();

        return new static(
            PropertyAccess::createPropertyAccessor(),
            new PropertyInfoExtractor(
                [$reflectionExtractor],
                [$phpDocExtractor, $reflectionExtractor],
                [$phpDocExtractor],
                [$reflectionExtractor],
                [$reflectionExtractor],
            ),
        );
    }

    /**
     * @param object[] $objects
     * @param array<string, mixed> $options
     */
    public function checkParity(array $objects, array $options = []): ParityErrorHandler
    {
        // Initialize options
        $resolver = new OptionsResolver();
        $this->configureOption($resolver);

        return $this->doCheckParity($objects, $resolver->resolve($options));
    }

    /**
     * @param mixed $value1
     * @param mixed $value2
     * @param array<string, mixed> $options
     */
    protected function checks($value1, $value2, string $property, array $options): bool
    {
        if (array_key_exists(self::IGNORE_TYPES_KEY, $options)
            && $this->isTypeOrProperty($options[self::IGNORE_TYPES_KEY], $value1, $value2, $property)
        ) {
            return true;
        }

        if (array_key_exists(self::ONLY_TYPES_KEY, $options)
            && ! $this->isTypeOrProperty($options[self::ONLY_TYPES_KEY], $value1, $value2, $property)
        ) {
            return true;
        }

        if (array_key_exists(self::DATA_MAPPER_KEY, $options)) {
            foreach ($options[self::DATA_MAPPER_KEY] as $mapper) {
                if ($this->isTypeOrProperty($mapper[self::DATA_MAPPER_TYPES_KEY], $value1, $value2, $property)) {
                    $value1 = $mapper[self::DATA_MAPPER_CLOSURE_KEY]($value1, $property, $options);
                    $value2 = $mapper[self::DATA_MAPPER_CLOSURE_KEY]($value2, $property, $options);

                    break;
                }
            }
        }

        if (array_key_exists(self::LOOSE_CHECK_TYPES_KEY, $options)
            && $this->isTypeOrProperty($options[self::LOOSE_CHECK_TYPES_KEY], $value1, $value2, $property)
        ) {
            return $value1 == $value2;
        }

        if (array_key_exists(self::CALLBACK_CHECKER_KEY, $options)) {
            foreach ($options[self::CALLBACK_CHECKER_KEY] as $checker) {
                if ($this->isTypeOrProperty($checker[self::CALLBACK_TYPES_KEY], $value1, $value2, $property)) {
                    return $checker[self::CALLBACK_CLOSURE_KEY]($value1, $value2, $property, $options);
                }
            }
        }

        return $value1 === $value2;
    }

    protected function configureOption(OptionsResolver $resolver): void
    {
        $typeClosure = \Closure::fromCallable([$this, 'optionsTypeValidation']);

        $resolver
            ->define(self::IGNORE_TYPES_KEY)
            ->default(['object'])
            ->allowedTypes(...self::TYPES_ALLOWED_TYPES)
            ->allowedValues($typeClosure);

        $resolver
            ->define(self::ONLY_TYPES_KEY)
            ->allowedTypes(...self::TYPES_ALLOWED_TYPES)
            ->allowedValues($typeClosure);

        $resolver
            ->define(self::LOOSE_CHECK_TYPES_KEY)
            ->allowedTypes(...self::TYPES_ALLOWED_TYPES)
            ->allowedValues($typeClosure);

        $resolver
            ->define(self::DEEP_OBJECT_LIMIT_KEY)
            ->default(0)
            ->allowedTypes('int');

        $resolver
            ->define(self::DATA_MAPPER_KEY)
            ->default(static function (OptionsResolver $mapperResolver) use ($typeClosure): void {
                $mapperResolver->setPrototype(true);
                $mapperResolver
                    ->define(self::DATA_MAPPER_TYPES_KEY)
                    ->required()
                    ->allowedTypes(...self::TYPES_ALLOWED_TYPES)
                    ->allowedValues($typeClosure);

                $mapperResolver
                    ->define(self::DATA_MAPPER_CLOSURE_KEY)
                    ->required()
                    ->allowedTypes(\Closure::class);
            });


        $resolver
            ->define(self::CALLBACK_CHECKER_KEY)
            ->default(static function (OptionsResolver $resolver) use ($typeClosure): void {
                $resolver->setPrototype(true);
                $resolver
                    ->define(self::CALLBACK_TYPES_KEY)
                    ->required()
                    ->allowedTypes(...self::TYPES_ALLOWED_TYPES)
                    ->allowedValues($typeClosure);

                $resolver
                    ->define(self::CALLBACK_CLOSURE_KEY)
                    ->required()
                    ->allowedTypes(\Closure::class)
                    ->allowedValues(static function (\Closure $closure): bool {
                        /** @var \ReflectionNamedType|\ReflectionUnionType|null $returnType */
                        $returnType = (new \ReflectionFunction($closure))->getReturnType();

                        Assert::isInstanceOf(
                            $returnType,
                            \ReflectionNamedType::class,
                            'The callback closure must return only a boolean.'
                        );

                        return 'bool' === $returnType->getName();
                    });
            });
    }

    /**
     * @param object[] $objects
     * @param array<string, mixed> $options
     */
    private function doCheckParity(array $objects, array $options, int $recursionCount = 0): ParityErrorHandler
    {
        if (count($objects) < 2) {
            throw new \RuntimeException('You must set at least 2 object to check.');
        }

        // Initialize the error handler
        $errorsHandler = new ParityErrorHandler();

        // Get values by property
        $valuesByProperty = $this->getValuesByProperty($objects, $options);

        foreach ($valuesByProperty as $property => $values) {
            $testKey = array_key_first($values);
            $valueToTest = $values[$testKey];

            foreach ($values as $objectKey => $value) {
                if ($objectKey === $testKey) {
                    continue;
                }

                if (is_object($valueToTest)
                    && is_object($value)
                    && $options[self::DEEP_OBJECT_LIMIT_KEY] > $recursionCount
                ) {
                    $recursionCount++;

                    try {
                        $errorsHandlerRecursive = $this->doCheckParity(
                            [$valueToTest, $value],
                            $options,
                            $recursionCount
                        );

                        $errorsHandler->addMultiples($errorsHandlerRecursive);
                    } catch (\RuntimeException $e) {
                        $errorsHandler[] = new ParityError(
                            $valueToTest,
                            $value,
                            $property,
                            $valueToTest,
                            $value
                        );
                    }

                    $recursionCount = 0;
                }

                if (! $this->checks($valueToTest, $value, $property, $options)) {
                    $errorsHandler[] = new ParityError(
                        $objects[$testKey],
                        $objects[$objectKey],
                        $property,
                        $valueToTest,
                        $value
                    );
                }
            }
        }

        return $errorsHandler;
    }

    /**
     * @param string[]|string $type
     * @param mixed $value1
     * @param mixed $value2
     */
    private function isTypeOrProperty($type, $value1, $value2, string $property): bool
    {
        if (is_array($type)) {
            foreach ($type as $type1) {
                if ($this->isTypeOrProperty($type1, $value1, $value2, $property)) {
                    return true;
                }
            }

            return false;
        }

        // '$parameter' will be evaluated to 'parameter' property
        if (false !== ($resolvedProperty = $this->isProperty($type))) {
            return $resolvedProperty === $property;
        }

        if ($this->isClassOrInterface($type)) {
            return $value1 instanceof $type || $value2 instanceof $type;
        }

        if (false !== ($function = $this->isType($type))) {
            return $function($value1) || $function($value2);
        }

        throw new \RuntimeException('You should never getting here');
    }

    /**
     * @param object[] $objects
     * @param array<string, mixed> $options
     *
     * @return string[]
     */
    private function getCommonProperties(array $objects, array $options): array
    {
        $properties = [];
        foreach ($objects as $object) {
            $properties[] = $this->propertyInfoExtractor->getProperties(get_class($object)) ?? [];
        }

        if (array_key_exists(self::IGNORE_TYPES_KEY, $options)) {
            $types = $options[self::IGNORE_TYPES_KEY];
            foreach ($properties as $key => $objectProperties) {
                $properties[$key] = array_filter(
                    $objectProperties,
                    static fn (string $property): bool =>
                        is_string($types) ? $types !== $property : ! in_array($property, $types, true),
                );
            }
        }

        return array_intersect(...$properties);
    }

    /**
     * @param string[] $properties
     *
     * @return array<string, mixed>
     */
    private function getObjectValues(object $object, array $properties): array
    {
        $values = [];
        foreach ($properties as $property) {
            if ($this->propertyAccessor->isReadable($object, $property)) {
                $values[$property] = $this->propertyAccessor->getValue($object, $property);
            }
        }

        return $values;
    }

    /**
     * @param object[] $objects
     * @param array<string, mixed> $options
     *
     * @return array<string, array<string, mixed>>
     */
    private function getValuesByProperty(array $objects, array $options): array
    {
        // Get common properties
        $commonProperties = $this->getCommonProperties($objects, $options);
        if (empty($commonProperties)) {
            throw new \RuntimeException('Cannot found common properties.');
        }

        // Get all values
        $values = [];
        foreach ($objects as $key => $object) {
            $values[] = [
                'objectKey' => $key,
                'values' => $this->getObjectValues($object, $commonProperties),
            ];
        }

        // Get values by property & object key
        $valuesByProperty = [];
        $valuesByObject = array_column($values, 'values', 'objectKey');
        foreach ($commonProperties as $property) {
            foreach ($valuesByObject as $objectKey => $values) {
                $valuesByProperty[$property][$objectKey] = $values[$property];
            }
        }

        return $valuesByProperty;
    }

    /**
     * @return bool|string
     */
    private function isProperty(string $value)
    {
        return '$' === substr($value, 0, 1) ? substr($value, 1) : false;
    }

    private function isClassOrInterface(string $value): bool
    {
        return (class_exists($value) || interface_exists($value));
    }

    /**
     * @return bool|string
     */
    private function isType(string $value)
    {
        $function = "is_$value";

        return function_exists($function) ? $function : false;
    }

    /**
     * @param string[]|string $values
     */
    private function optionsTypeValidation($values): bool
    {
        if (is_array($values)) {
            foreach ($values as $value) {
                if (! $this->doOptionsTypeValidation($value)) {
                    return false;
                }
            }

            return true;
        }

        return $this->doOptionsTypeValidation($values);
    }

    private function doOptionsTypeValidation(string $value): bool
    {
        return $this->isProperty($value) || $this->isType($value) || $this->isClassOrInterface($value);
    }
}
