# Data Block Package

![PHP Data Block package](https://raw.githubusercontent.com/Hi-Folks/data-block/main/cover-data-block.png)

[![Latest Version](https://img.shields.io/packagist/v/hi-folks/data-block.svg?style=for-the-badge)](https://packagist.org/packages/hi-folks/data-block)
[![PHP Unit Tests](https://img.shields.io/github/actions/workflow/status/hi-folks/data-block/run-tests.yml?branch=main&style=for-the-badge)](https://github.com/Hi-Folks/data-block/actions)
[![Total Downloads](https://img.shields.io/packagist/dt/hi-folks/data-block.svg?style=for-the-badge)](https://packagist.org/packages/hi-folks/data-block)
[![Test Coverage](https://raw.githubusercontent.com/Hi-Folks/data-block/main/badge-coverage.svg)](https://packagist.org/packages/hi-folks/data-block)

This package provides the Block class which allows you to manage nested data.
For example, it allows to simplify the handling of managing nested associative arrays or JSON data.

## The Block class

The **Block** class exposes methods to create, manage, and access the data structure of the nested structure.

The Block class provides some methods:
- Creating structure from Arrays, JSON, and YAML files.
-

## Installing and using the Block class

For adding to your projects, the Block class with its method and helper you can run the `composer require` command:

```php
composer require hi-folks/data-block
```

> For supporting the development you can star the repository: https://github.com/Hi-Folks/data-block

Then in your PHP files, you can import the right Namespace:

```php
use HiFolks\DataType\Block;
```

## Method for creating Block objects
To show the capabilities of the following methods, I will use this nested associative array:

```php
$fruitsArray = [
    "avocado" =>
    [
        'name' => 'Avocado',
        'fruit' => '🥑',
        'wikipedia' => 'https://en.wikipedia.org/wiki/Avocado',
        'color'=>'green',
        'rating' => 8
    ],
    "apple" =>
    [
        'name' => 'Apple',
        'fruit' => '🍎',
        'wikipedia' => 'https://en.wikipedia.org/wiki/Apple',
        'color' => 'red',
        'rating' => 7
    ],
    "banana" =>
    [
        'name' => 'Banana',
        'fruit' => '🍌',
        'wikipedia' => 'https://en.wikipedia.org/wiki/Banana',
        'color' => 'yellow',
        'rating' => 8.5
    ],
    "cherry" =>
    [
        'name' => 'Cherry',
        'fruit' => '🍒',
        'wikipedia' => 'https://en.wikipedia.org/wiki/Cherry',
        'color' => 'red',
        'rating' => 9
    ],
];
```

### The static `make()` method
With the static `make()` method, you can generate a Block object from an associative array:
```php
$data = Block::make($fruitsArray);
```
The `$data` object is an instance of the `Block` class.

In the case you want to initialize an empty `Block` object you can call the `make()` method with no parameters:
```php
$data = Block::make();
```
Once you have initialized the Block object you can start using its methods.

### The `get()` method
The `get()` method supports keys/indexes with the dot (or custom) notation for retrieving values from nested arrays.
It returns the original type of data. If you need to obtain a Block object, you should use the `getBlock()` method instead of `get()`.
For example:

```php
$data->get('avocado'); // returns an array
$data->get('avocado.color'); // returns the string "green"
```

For example, with the `$fruitsArray` sample data, the `$data->get("avocado")` is:
- an array;
- has 5 elements;

For example, the `$data->get("avocado.color")` is:
- a string;
- has the value "green";

The `$data->get("avocado.rating")` is:
- a numeric;
- specifically an integer;

The `$data->get("banana.rating")` is:
- a numeric;
- specifically a float;



You can customize the notation with a different character:

```php
$data->get('apple#fruit', charNestedKey: '#'); // 🍎
```

If you are going to access a not valid key, a `null` value is returned:

```php
$value = $data->get('apple.notexists'); // null
```
You can define a default value in the case the key doesn't exist:

```php
$value = $data->get('apple#notexists',
'🫠', '#'); // 🫠
```


### The `getBlock()` method
If you need to manage a complex array (nested array), or an array obtained from a complex JSON structure, you can access a portion of the array and obtain the `Block` object.
Just because in the case of a complex array the `get()` method could return a classic array.

Let's see an example:

```php
$appleData = $data->getBlock("apple")
// $data is the Block instance so that you can access
// to the Block methods like count()
$data->getBlock("apple")->count();
```

If the element accessed via getBlock is a scalar type (integer, float, string...), using `getBlock()` a Block object (with just 1 element) will be returned.

For example, `$data->getBlock("avocado")` returns a Block object, with 5 elements.

For example, `$data->getBlock("avocado.color")` returns a Block object with just 1 element.

In the case you are going to access a not valid key, an empty Block object is returned, so that the `$data->getBlock("avocado.notexists")` returns: a Block object with a length equal to 0.

### The `set()` method
The `set()` method supports keys with the dot (or custom) notation for setting values for nested data.
If a key doesn't exist, the `set()` method will create a new key and will set the value.
If a key already exists, the `set()` method will replace the value related to the key.

For example:

```php
$articleText = "Some words as a sample sentence";
$textField = Block::make();
$textField->set("type", "doc");
$textField->set("content.0.content.0.text", $articleText);
$textField->set("content.0.content.0.type", "text");
$textField->set("content.0.type", "paragraph");
```

So when you try to set a nested key as "content.0.content.0.text", it will be created elements as a nested array.

Once you set the values, you can access them via `get()` (or `getBlock()`) methods:

```php
$textField->get("content.0.content.0.text");
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Roberto B.](https://github.com/roberto-butti)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
