<h1 align="center">Parity-checker</h1>

[![Continuous integration](https://github.com/benjaminmal/parity-checker/actions/workflows/ci.yaml/badge.svg)](https://github.com/benjaminmal/parity-checker/actions/workflows/ci.yaml)

A PHP parity checker. Useful when you want to check if many objects are having the same datas. It has many options so you can configure the behavior you need, especially speaking of object recursion.

Installation
------------
```bash
$ composer require elodgy/parity-checker
```

Getting started
---------------

### Create the Parity checker using the factory:
```php
$parityChecker = ParityChecker::create();
```

### Or using your `PropertyAccessorInterface` and `PropertyInfoExtractorInterface` implementations
```php
$parityChecker = new ParityChecker($propertyAccessor, $propertyInfoExtractor);
```

### Check your objects
```php
$errors = $parityChecker->checkParity([$object1, $object2]);
if (! $errors->hasErrors()) {
    // You're all set !
}
```

Usages
-----
### Options
```php
$errors = $parityChecker->checkParity([$object1, $object2], [
    // Do not perform check on these types
    'no_check_on' => ['object', 'resource', \DateTimeInterface::class, '$objectProperty1'],
    
    // Perform a loose check ('==' instead of '===') on theses types
    'loose_check_on' => ['array', 'float'],

    // Set the recursion limit for objects
    'deep_object_limit' => 0,
    
    // Custom checkers. You can set you own checker which replace other.
    'custom_checkers' => [
        'my-checker' => [
            // Required. Types to perform the closure on
            'types_or_properties' => [],
            
            // Required. Closure with $values, $property, $options. Must return bool.
            'closure' => function ($value1, $value2, string $property, array $options): bool {
                return true; // Your condition
            },
        ]
    ],
]);
```
|Option|Description|Accepted types|Default values|
|------|-----------|--------------|--------------|
|`no_check_on`|Do not perform check on these types|Any types. Checked by the `is_` functions. Classes/interfaces names. Object properties must be prefixed by `$` |[`'object'`]|
|`loose_check_on`|On which type to perform loose check (`==`) instead of (`===`)|Any types. Checked by the `is_` functions. Classes/interfaces names. Object properties must be prefixed by `$`|[]|
|`deep_object_limit`|The object recursion limit|`int`|`0`|
|`custom_checkers`|You can set you own checker which replace other|['my-own-checker' => ['types_or_properties' => [], 'closure' => fn (): bool => true]|[]|

### Errors
```php
$errors = $parityChecker->checkParity([$object1, $object2], $options);

if ($errors->hasError()) {
    foreach ($errors as $error) {
        $property = $error->getProperty();
        $object1 = $error->getObject1();
        $object2 = $error->getObject2();

        $errorValue1 = $error->getObject1Value();
        $errorValue2 = $error->getObject2Value();
    }
}
```
