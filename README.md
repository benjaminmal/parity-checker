<h1 align="center">Parity-checker</h1>

[![Continuous integration](https://github.com/benjaminmal/parity-checker/actions/workflows/ci.yaml/badge.svg)](https://github.com/benjaminmal/parity-checker/actions/workflows/ci.yaml)

A PHP parity checker. Useful when you want to check if many objects are having the same datas. It has many options so you can configure the behavior you need, especially speaking of object recursion.

Installation
------------
```bash
$ composer require benjaminmal/parity-checker
```

Getting started
---------------

### Create the Parity checker using the factory:
```php
$parityChecker = ParityChecker::create();
```

### Or using your implementations using Symfony [PropertyAccess](https://symfony.com/doc/current/components/property_access.html) & [PropertyInfo](https://symfony.com/doc/current/components/property_info.html) components
```php
/** @var PropertyAccessorInterface $propertyAccessor */
/** @var PropertyInfoExtractorInterface $propertyInfoExtractor */

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
    'ignore_types' => ['object', 'resource', \DateTimeInterface::class, '$objectProperty1'],
    
    // Perform check only on these types
    'only_types' => ['string', 'float'],

    // Perform a loose check ('==' instead of '===') on theses types
    'loose_types' => 'array',

    // Set the recursion limit for objects
    'deep_object_limit' => 0,
    
    // Set DateTime format to check
    'datetime_check_format' => false,
    
    // Set DateInterval format to check
    'date_interval_format' => '%R %Y %M %D %H %I %S %F',
    
    // Set DateTime zone mapping to name
    'datetime_zone' => true,
    
    // Set a data mapper closure
    'data_mapper' => [
        'my-mapper' => new ParityCheckerCallback(
            'array',
            fn ($value, string $property, array $options): mixed => $value['something'],
        ),
    ],
    
    // Custom checkers. You can set you own checker which replace other.
    'custom_checkers' => [
        'my-checker' => new ParityCheckerCallback(
            ['$property'],
            fn ($value1, $value2, string $property, array $options): bool => true,
        ),
    ],
]);
```
|Option|Description|Accepted types|Default values|
|------|-----------|--------------|--------------|
|`ignore_types`|Do not perform check on these types|`string[]\|string`. Can be any types. Checked by the `is_` functions, classes/interfaces names or object properties (must be prefixed by `$`)|`object`|
|`only_types`|Perform checks only on these types. `ignore_types` is evaluated before, so if you set the same type in both `ignore_types` and `only_types`, the type will be ignored.|`string[]\|string`. Can be any types. Checked by the `is_` functions, classes/interfaces names or object properties (must be prefixed by `$`)| none|
|`loose_types`| On which type to perform loose check (`==`) instead of (`===`)|`string[]\|string`. Can be any types. Checked by the `is_` functions, classes/interfaces names or object properties (must be prefixed by `$`)|none|
|`deep_object_limit`|The object recursion limit|`int`|`0`|
| `data_mapper`|Replace value before checking by your closure|`ParityCheckCallbackInterface[]`|`datetime_check_format`, `date_interval_format`, `datetime_zone` mappers|
|`datetime_check_format`|The format that `DateTime` must be check with|`bool\|string`. Has to be a [valid DateTime format](https://www.php.net/manual/en/datetime.format.php). If it's `true`, `Y-m-d H:i:s` is used.|`true`|
|`date_interval_format`|The format that `DateInterval` must be check with|`bool\|string`. Has to be a [valid DateInterval format](https://www.php.net/manual/en/dateinterval.format.php). If it's true, `%R %Y %M %D %H %I %S %F` is used.|`true`|
|`datetime_zone`|Map `DateTimeZone` objects to its timezone name in order to check it. E.g `Europe/Paris`|`bool`|`true`|
| `custom_checkers`| You can set you own checker which replace other|`ParityCheckCallbackInterface[]`|none|

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
