<p align="center">
    <img src="https://raw.githubusercontent.com/Hi-Folks/data-block/main/cover-data-block.png" alt="PHP Data Block package">
</p>

<h1 align="center">
    Data Block Package
</h1>


<p align=center>
    <a href="https://packagist.org/packages/hi-folks/data-block">
        <img src="https://img.shields.io/packagist/v/hi-folks/data-block.svg?style=for-the-badge" alt="Latest Version">
    </a>
    <a href="https://packagist.org/packages/hi-folks/data-block">
        <img src="https://img.shields.io/packagist/dt/hi-folks/data-block.svg?style=for-the-badge" alt="Total Downloads">
    </a>
    <br />
    <img src="https://img.shields.io/packagist/l/hi-folks/data-block?style=for-the-badge" alt="Packagist License">
    <img src="https://img.shields.io/packagist/php-v/hi-folks/data-block?style=for-the-badge" alt="Supported PHP Versions">
    <img src="https://img.shields.io/github/last-commit/hi-folks/data-block?style=for-the-badge" alt="GitHub last commit">
    <br />
        <img src="https://img.shields.io/github/actions/workflow/status/hi-folks/data-block/run-tests.yml?style=for-the-badge&label=Test" alt="Tests">
</p>


<p align=center>
    <i>
        PHP Package for Managing Nested Data
    </i>
</p>
This PHP package provides classes and methods for easily managing, querying, filtering, and setting nested data structures.
The PHP Data Block package offers a streamlined approach to handling nested data, whether you're working with complex JSON data, hierarchical configurations, or deeply nested arrays.

## What you can do with PHP Data Block

For example, with PHP Data Block, you can retrieve complex JSON from an API and then filter, sort, and handle the data.
For example, here you can:

- Retrieve the list of the repository from GitHub
- Order the repository by the number of stars received (ascending, starting from the repos with more stars)
- Loop the results
- Get values from elements

```php
<?php

use HiFolks\DataType\Block;

require './vendor/autoload.php';
$url = 'https://api.github.com/orgs/hi-folks/repos';

Block::fromJsonUrl($url)
    ->orderBy('stargazers_count', 'desc')
    ->forEach(
        function ($item) {
            echo $item->get('full_name').' : ';
            echo $item->get('stargazers_count').PHP_EOL;
        }
    );
```

Then, you can do more, you can:
- Extract only the fields you need using the `select()` method
- Filter the elements with the `where()` method and using the `Operator` class

Here is an example:

```php
<?php

use HiFolks\DataType\Block;
use HiFolks\DataType\Enums\Operator;

require './vendor/autoload.php';
$url = 'https://api.github.com/orgs/hi-folks/repos';

Block::fromJsonUrl($url)
    ->select('full_name', 'stargazers_count')
    ->where('stargazers_count', Operator::GREATER_THAN, 0)
    ->orderBy('stargazers_count', 'desc')
    ->forEach(
        function ($item) {
            echo $item->get('full_name').' : ';
            echo $item->get('stargazers_count').PHP_EOL;
        }
    );
```
This is just an overview as an appetizer :)

Now, let's explore the classes and methods PHP Data Block provides.

One "core" element of the PHP Data Block package is the Block PHP class.

## The Block class

The **Block** class offers comprehensive methods to create, manage, and access nested data structures.

The **Block** class provides various methods, including:

- Creating structures from Arrays, JSON, and YAML files.
- Querying nested data with ease.
- Filtering data based on specific criteria.
- Setting and updating values within deeply nested structures.

## Installing and using the Block class

For adding to your projects, the Block class with its methods and helpers, you can run the `composer require` command:

```php
composer require hi-folks/data-block
```

> To support the development, you can "star" ⭐ the repository: https://github.com/Hi-Folks/data-block

Then, in your PHP files, you can import the `HiFolks\DataType\Block` Namespace:

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

In the case you want to initialize an empty `Block` object, you can call the `make()` method with no parameters:
```php
$data = Block::make();
```
Once you initialize the Block object, you can use its methods.

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
- has five elements;

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
$value = $data->get(
    'apple.notexists',
    '🫠'
); // 🫠
```

And you can combine the default value and the nested character:
```php
$value = $data->get(
    'apple#notexists',
    '🫠',
    '#'
); // 🫠
```

### The `getFormattedDateTime()` for getting and formatting a date-time field value

When working with date-time fields, consider utilizing the `getFormattedDateTime()` method instead of relying solely on `get()`. This approach not only retrieves the value but also formats it according to the specified date-time format, as defined by the second optional parameter `$format`.

By default, this formatting is set to "Y-m-d H:i:s", providing a convenient and standardized output. However, customizing the format allows for more flexibility in presenting your data.

Here are some key points:

*   `getFormattedDateTime()` combines the functionality of `get()` with date-time formatting.
*   The second parameter `$format` controls the date-time format used for formatting.
*   Custom formats can be applied to provide tailored outputs for different use cases.

### The `getFormattedByte()` for getting and formatting a 'byte' field value

The `getFormattedByte()` method retrieves and formats a byte value from a specified field in a data block. It converts the raw byte count into a more human-readable format (e.g., GB, MB, etc.), with an optional precision parameter to control the number of decimal places displayed.

Parameters:
- `$path` (string): The path to the field containing the byte value (e.g., "assets.0.total_bytes").
- `$precision` (int): (Optional) Number of decimal places to include in the formatted result. The default is 2.

Example usage:

```php
$data1->getFormattedByte("assets.0.total_bytes");  // Returns "5.98 GB"
$data1->getFormattedByte("assets.1.total_bytes");  // Returns "2.18 GB"
$data1->getFormattedByte("assets.1.total_bytes", 5);  // Returns "2.18288 GB"
$data1->getFormattedByte("assets.1.total_bytes", 0);  // Returns "2 GB"
```

Key Features:
- Automatic unit conversion: converts bytes into appropriate units (e.g., KB, MB, GB) based on the size.
- Customizable precision: you can specify the number of decimal places for the output, making it flexible for various use cases.

### The `getString()` method

The `getString()` method retrieves the value of a specified field as a string from a data block. If the field does not exist or is null, it returns a default value, which can be customized.
Parameters:

- `$path` (string): The path to the field (e.g., "0.commit.author.date").
- `$default` (string): (Optional) The default value to return if the field doesn't exist. Defaults to an empty string ("").

Example Usage:
```php
$data1->getString("0.commit.author.date");  // Returns the field value as a string
$data1->getString("0.commit.author.notexists");  // Returns ""
$data1->getString("0.commit.author.notexists", "AA");  // Returns "AA"
$data1->getString("0.commit.comment_count");  // Returns "0" as a string even if the field value is an integer
```

### The `getInt()` method

The `getInt()` method retrieves the value of a specified field as a integer from a data block. If the field does not exist or is null, it returns a default value, which can be customized (null by default).
Parameters:

- `$path` (string): The path to the field (e.g., "0.author.id").
- `$default` (null|int): (Optional) The default value to return if the field doesn't exist. Defaults to null.
- `$charNestedKey` (string): the character separator for nested field names.  The default is ".".

Example usage:
```php
$data1->getInt("0.author.id"); // Returns the field value as an integer, for example 678434
$data1->getInt("0.author.idx"); // Returns null because the field doesn't exists
$data1->getInt("0.author.idx", 44); // Returns 44 because the field doesn't exists, and you set a default, in this case 44
```

### The `getBoolean()` method

The `getBoolean()` method retrieves the value of a specified field as a boolean from a data block. If the field does not exist or is null, it returns a default value, which can be customized (null by default).
Parameters:

- `$path` (string): The path to the field (e.g., "0.author.id").
- `$default` (null|bool): (Optional) The default value to return if the field doesn't exist. Defaults to null.
- `$charNestedKey` (string): the character separator for nested field names.  The default is ".".

Example usage:
```php
$data1->getBoolean("0.author.site_admin"); // Returns the field value as an boolean, for example true
$data1->getBoolean("0.author.site_admin_notexists"); // Returns null because the field doesn't exists
$data1->getBoolean("0.author.site_admin_notexists", true); // Returns true because the field doesn't exists, and you set a default, in this case true
```


### The `getBlock()` method
If you need to manage a complex array (nested array) or an array obtained from a complex JSON structure, you can access a portion of the array and obtain the `Block` object via the `getBlock()` method.

Let's see an example:

```php
$appleData = $data->getBlock("apple")
// $data is the Block instance so that you can access
// to the Block methods like count()
$data->getBlock("apple")->count();
```

If the element accessed via `getBlock()` is a scalar type (integer, float, string, etc.), a Block object (with just one element) will be returned using `getBlock()`.

For example, `$data->getBlock("avocado")` returns a Block object with five elements.

For example, `$data->getBlock("avocado.color")` returns a Block object with just one element.

If you are going to access a non-valid key, an empty Block object is returned, so the `$data->getBlock("avocado.notexists")` returns a Block object with a length equal to 0.

### The `set()` method
The `set()` method supports keys with the dot (or custom) notation for setting values for nested data.
If a key doesn't exist, the `set()` method creates one and sets the value.
If a key already exists, the `set()` method will replace the value related to the key.

#### Parameters

- `key` (int|string): The key to which the value should be assigned. If a string is provided, you can use dot notation to set nested values.
- `value` (mixed): The value to be assigned to the specified key.
- `charNestedKey` (string, optional): The character used for dot notation in nested keys. Defaults to `.`.

#### Returns

- `self`: Returns the instance of the class for method chaining.

#### Example Usage

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

### Extracting Keys

Via the `keys()` method, you can retrieve the list of the keys:

```php
$data = Block::make($fruitsArray);
$keys = $data->keys();
/*
Array
(
    [0] => avocado
    [1] => apple
    [2] => banana
    [3] => cherry
)
*/
```

You can retrieve the keys of a nested element, combining the usage of `getBlock()` and the `keys()`:

```php
$data = Block::make($fruitsArray);
$keys = $data->getBlock("avocado")->keys();

/*
Array
(
    [0] => name
    [1] => fruit
    [2] => wikipedia
    [3] => color
    [4] => rating
)
*/
```

## Exporting data

### Exporting to array with `toArray()`
The `toArray()` method can access the native array (associative and nested).

This is helpful when manipulating data with the Block class, and at a certain point, you need to send the data to your function or a function from a third-party package that expects to receive a native array as a parameter.

```php
$file = "./composer.json";
$composerContent = Block::fromJsonFile($file);
// here you can manage $composerContent with Block methods
// end then exports the Block data into a native array
$array = $composerContent->toArray();
```

### Exporting to JSON string with `toJson()`
If you need to generate a valid JSON string using the content of the Block object, you can use the `toJson()` method.

This is helpful when you are manipulating data with the Block class and at a certain point need to send the data in JSON string format to your own function or a function from a third-party package that expects to receive a JSON string as a parameter.

```php
$data = Block::make($fruitsArray);
$jsonString = $data->toJson(); // JSON string with "pretty print"
```

### Exporting to YAML string with `toYaml()`
If you need to generate a valid YAML string using the content of the Block object, you can use the `toYaml()` method.

This is helpful when manipulating data with the Block class and, at a certain point, need to send the data in YAML string format to your function or a function from a third-party package that expects to receive a YAML string as a parameter.

```php
$data = Block::make($fruitsArray);
$yamlString = $data->toYaml(); // YAML string
```

### Saving JSON to a file with `saveToJson()`
If you need to save the JSON string in a file using the content of the Block object, you can use the `saveToJson()` method.

This is helpful when you are manipulating data with the Block class and at a certain point need to save the data in JSON string format to a file.
The `saveToJson()` method has two parameters:

- `filename`: the first parameter (mandatory) with the filename;
- `overwrite`: the second parameter (optional), If the file exists, the file is not saved by default, unless you set the overwrite parameter as true.

```php
$data = Block::make($fruitsArray);
$jsonString = $data->saveToJson('./fruits.json', true);
```


## Loading Data

### Loading Data from JSON file

```php
$file = "./composer.json";
$composerContent = Block::fromJsonFile($file);
echo $composerContent->get("name"); // for example: "hi-folks/data-block"
echo $composerContent->get("authors.0.name"); // for example: "Roberto B."
```

### Loading Data from JSON URL

You can build your Block data from a remote JSON (like an API).
For example, you can use the `fromJsonUrl()` method to build a Block object from the latest commits via GitHub API.
Retrieving JSON API into a Block object is useful for applying the methods provided by the Block class, for example, filtering the data. In the example, I'm going to filter the commit based on the name of the author of the commit:

```php
$url = "https://api.github.com/repos/hi-folks/data-block/commits";
$commits = Block::fromJsonUrl($url);
$myCommits = $commits->where("commit.author.name", Operator::LIKE, "Roberto");
foreach ($myCommits as $value) {
    echo $value->get("commit.message") . PHP_EOL;
}
```

### Loading Data from YAML file

```php
$file = "./.github/workflows/run-tests.yml";
$workflow = Block::fromYamlFile($file);
echo $workflow->get("name"); // Name of the GitHub Action Workflow
echo $workflow->get("jobs.test.runs-on");
echo $workflow->get("on.0"); // push , the first event
```

## Adding and appending elements

### Appending the elements of a Block object to another Block object
If you have a Block object, you can add elements from another Block object.
One use case is if you have multiple JSON files and want to retrieve paginated content from an API. In this case, you want to create one Block object with all the elements from every JSON file.

```php
$data1 = Block::fromJsonFile("./data/commits-10-p1.json");
$data2 = Block::fromJsonFile("./data/commits-10-p2.json");
$data1->count(); // 10
$data2->count(); // 10
$data1->append($data2);
$data1->count(); // 20
$data2->count(); // 10
```

### Appending the elements of an array to a Block object

If you have an array, you can add elements to a Block object.
Under the hood, a Block object is an array (that potentially can be a nested array). Appending an array will add elements at the root level:


```php
$data1 = Block::make(["a","b"]);
$arrayData2 = ["c","d"];
$data1->count(); // 2
$data1->append($arrayData2);
$data1->count(); // 4
```

### Appending an element
If you need to append an element as a single element (even if it is an array or a Block object), you can use the `appendItem()` function:

```php
$data1 = Block::make(["a", "b"]);
$arrayData2 = ["c", "d"];
$data1->appendItem($arrayData2);
$data1->count(); // 3 because a, b, and the whole array c,d as single element
$data1->toArray();
/*
[
    'a',
    'b',
    [
        'c',
        'd',
    ],
]
*/
```

## Querying, sorting data

### The `where()` method
You can filter data elements for a specific key with a specific value.
You can also set the operator

```php
$composerContent = Block::fromJsonString($jsonString);
$banners = $composerContent->getBlock("story.content.body")->where(
    "component",
    Operator::EQUAL,
    "banner",
);
```

With the `where()` method, the filtered data keeps the original keys.
If you want to avoid preserving the keys and set new integer keys starting from 0, you can set the fourth parameter (`preserveKeys`) as `false`.

```diff
    $composerContent = Block::fromJsonString($jsonString);
    $banners = $composerContent->getBlock("story.content.body")->where(
        "component",
        Operator::NOT_EQUAL,
        "banner",
+        false
    );
```

With `where()` method you can use different operators, like "==", ">", "<" etc.



You can use also the `has` operator in the case your nested data contains arrays or `in` operator in the case you want to check if your data field value is included in an array of elements.

#### The operators

The `Operator` class provides a set of predefined constants that represent comparison and logical operators. This ensures type safety and prevents errors from using invalid or misspelled operators in your data comparisons.

Supported Operators:
- `Operator::EQUAL` (==)
- `Operator::GREATER_THAN` (>)
- `Operator::LESS_THAN` (<)
- `Operator::GREATER_THAN_OR_EQUAL` (>=)
- `Operator::LESS_THAN_OR_EQUAL` (<=)
- `Operator::NOT_EQUAL` (!=)
- `Operator::STRICT_NOT_EQUAL` (!==)
- `Operator::IN` (array inclusion)
- `Operator::HAS` (array containment)
- `Operator::LIKE` (string contains)

> The `Operator` class is defined in the `use HiFolks\DataType\Enums\Operator` namespace.

#### The `in` operator

The `in` operator is used within the where method to filter elements from a data collection based on whether a specific field's value exists within a given array of values.
The behavior is as follows:

```php
$data->where("field", Operator::IN, ["value1", "value2", ...])
```

If the field's value exists in the provided array, the element is included in the result.
Example: Filtering fruits by color that match either "green" or "black"
```php
$greenOrBlack = $data->where("color", Operator::IN, ["green", "black"]);
```

You should use the `in` operator if your field is a scalar type (for example string or number) and you need to check if it is included in a list of values (array).

#### The `has` operator

The `has` operator is used within the where method to filter elements from a data collection based on whether a specific field contains a given value, typically in cases where the field holds an array or a collection of tags or attributes. The behavior is as follows:

```php
$data->where("field", Operator::HAS, "value")
```

For example if you have posts and each post can have multiple tags, you can filter posts with a specific tag:

```php
$url = "https://dummyjson.com/posts";
$posts = Block
    ::fromJsonUrl($url)
    ->getBlock("posts");

$lovePosts = $posts->where("tags", Operator::HAS, "love");
```
#### Summary `in` VS `has`

The `in` operator filters elements by matching a field's value against an array of possible values. If the value exists in the array, the element is included in the result. An empty array returns no results.

The `has` operator filters elements by checking if a specific value exists within a field (usually an array or a collection). If the value exists, the element is included in the result. Non-existent values return no matches.

### The `orderBy()` method
You can order or sort data for a specific key.
For example, if you want to retrieve the data at `story.content.body` key and sort them by `component` key:

```php
$composerContent = Block::fromJsonString($jsonString);
$bodyComponents = $composerContent->getBlock("story.content.body")->orderBy(
    "component", "asc"
);
```

You can also order data for a nested attribute.
Consider retrieving a remote JSON like the dummy JSON posts and then ordering the posts via the `reactions.likes` nested field in descending order:

```php
use HiFolks\DataType\Block;

$posts = Block
    ::fromJsonUrl("https://dummyjson.com/posts")
    ->getBlock("posts");
echo $posts->count(); // 30
$mostLikedPosts = $posts->orderBy("reactions.likes", "desc");
$mostLikedPosts->dump();
```

### The `select()` method
The `select()` method allows you to select only the needed fields.
You can list the field names you need as parameters for the `select()` method.
For example:

```php
use HiFolks\DataType\Block;
$dataTable = [
    ['product' => 'Desk', 'price' => 200, 'active' => true],
    ['product' => 'Chair', 'price' => 100, 'active' => true],
    ['product' => 'Door', 'price' => 300, 'active' => false],
    ['product' => 'Bookcase', 'price' => 150, 'active' => true],
    ['product' => 'Door', 'price' => 100, 'active' => true],
];
$table = Block::make($dataTable);
$data = $table
    ->select('product' , 'price');
print_r($data->toArray());
```

You can combine the `select()`, the `where()`, and the `orderBy()` method.
If you want to retrieve elements with `product` and `price` keys, with a price greater than 100 and ordered by `price`:

```php
$table = Block::make($dataTable);
$data = $table
    ->select('product' , 'price')
    ->where('price', Operator::GREATER_THAN, 100)
    ->orderBy("price");
print_r($data->toArray());
/*
Array
(
    [0] => Array
        (
            [product] => Bookcase
            [price] => 150
        )

    [1] => Array
        (
            [product] => Desk
            [price] => 200
        )

    [2] => Array
        (
            [product] => Door
            [price] => 300
        )

)
*/
```

### The `groupBy()` method

Groups the elements of the `Block` object by a specified field.

This method takes a field name as an argument and groups the elements of the `Block` object based on the values of that field. Each element is grouped into an associative array where the keys are the values of the specified field and the values are arrays of elements that share that key.

```php
use HiFolks\DataType\Block;
$data = Block::make([
    ['type' => 'fruit', 'name' => 'apple'],
    ['type' => 'fruit', 'name' => 'banana'],
    ['type' => 'vegetable', 'name' => 'carrot'],
]);
$grouped = $data->groupBy('type');
$grouped->dumpJson();
/*
{
    "fruit": [
        {
            "type": "fruit",
            "name": "apple"
        },
        {
            "type": "fruit",
            "name": "banana"
        }
    ],
    "vegetable": [
        {
            "type": "vegetable",
            "name": "carrot"
        }
    ]
}
*/
```

### The `groupByFunction()` method

The `groupByFunction()` method allows you to group items from an Block based on a grouping logic provided by a callback function (closure). The function returns an associative array where the keys represent groupings defined by the callback, and the values are arrays of elements that belong to each group.

```php
$fruits = [
    ['name' => 'Apple', 'type' => 'Citrus', 'quantity' => 15],
    ['name' => 'Banana', 'type' => 'Tropical', 'quantity' => 10],
    ['name' => 'Orange', 'type' => 'Citrus', 'quantity' => 8],
    ['name' => 'Mango', 'type' => 'Tropical', 'quantity' => 5],
    ['name' => 'Lemon', 'type' => 'Citrus', 'quantity' => 12]
];
$fruitsBlock = Block::make($fruits);
$groupedByQuantityRange = $fruitsBlock->groupByFunction(
    fn($fruit): string =>
        match (true) {
            $fruit['quantity'] < 10 => 'Low',
            $fruit['quantity'] < 15 => 'Medium',
            default => 'High',
        },
);
// It returns:
/*
{
    "High": [
        {
            "name": "Apple",
            "type": "Citrus",
            "quantity": 15
        }
    ],
    "Medium": [
        {
            "name": "Banana",
            "type": "Tropical",
            "quantity": 10
        },
        {
            "name": "Lemon",
            "type": "Citrus",
            "quantity": 12
        }
    ],
    "Low": [
        {
            "name": "Orange",
            "type": "Citrus",
            "quantity": 8
        },
        {
            "name": "Mango",
            "type": "Tropical",
            "quantity": 5
        }
    ]
}
*/
```

### The `exists()` method

You can use the `exists()` method to check if an element that meets a certain condition exists. This method is a convenient way to determine if any records match your query without needing to count them explicitly.

Here’s how you can use it:

```php
$has = $composerContent
    ->getBlock("story.content.body")->where(
        "component",
        "banner",
    )->exists();
```

This will return true if a banner component exists, and false if it does not.

## Looping Data
The Block class implements the Iterator interface.
While looping an array via Block, by default, if the current element should be an array, a Block is returned so that you can access the Block method for handling the current array item in the loop.
For example, with the previous code, if you loop through `$data` (which is a Block object), each element in each iteration of the loop will be an array with two elements, with the keys `product` and `price`.
If in the loop you need to manage the current element via Block class, you should manually call the `Block::make`, for example:

```php
$table = Block::make($dataTable);
foreach ($table as $key => $item) {
    echo $item->get("price");
}
```

You can apply filters and then loop into the result:

```php
$table = Block::make($dataTable);
$data = $table
    ->select('product', 'price')
    ->where('price', Operator::GREATER_THAN, 100, false);
foreach ($data as $key => $item) {
    echo $item->get("price"); // returns an integer
}

```


If you want to loop through `$data` and obtain the current `$item` variable as an array you should set `false` as a second parameter in the static `make()` method:

```php
$table = Block::make($dataTable, false);
$data = $table->select('product', 'price')->where('price', Operator::GREATER_THAN, 100, false);
foreach ($data as $key => $item) {
    print_r($item); // $item is an array
}
```

### The `iterateBlock()` method
With the `iterateBlock()` method, you can switch from array or Block for nested lists inside the main Block object if you already instanced it as a Block object.
In the example above, you have the `$table`  Block object.
You can loop across the items of the `$table` object.
If each item in the loop is itself an array (so an array of arrays), you can retrieve it as an array or a Block, depending on your needs:

```php
$table = Block::make($dataTable);
foreach ($table as $key => $item) {
    expect($item)->toBeInstanceOf(Block::class);
    expect($key)->toBeInt();
    expect($item->get("price"))->toBeGreaterThan(10);
}

// iterateBlock(false if you need array instad of a nested Block)
foreach ($table->iterateBlock(false) as $key => $item) {
    expect($item)->toBeArray();
    expect($key)->toBeInt();
    expect($item["price"])->toBeGreaterThan(10);
}
```

### Using forEach() method
The `Block` class implements the `forEach()`method.
> If you need to walk through the `Block` object, you can use the `forEach()` method.
You can specify the function as an argument of the `forEach()` method to manage each single element.

```php
$url = "https://dummyjson.com/posts";
$posts = Block::fromJsonUrl($url) // Load the Block from the remote URL
    ->getBlock("posts") // get the `posts` as Block object
    ->where(
        field:"tags",
        operator: Operator::HAS,
        value: "love",
        preseveKeys: false,
    ) // filter the posts, selecting only the posts with tags "love"
    ->forEach(fn($element): array => [
        "title" => strtoupper((string) $element->get("title")),
        "tags" => count($element->get("tags")),
    ]);
// The `$posts` object is an instance of the `Block` class.
// The `$posts` object contains the items that matches the `where` method.
// You can access to the elements via the nested keys
// $posts->get("0.title"); // "HOPES AND DREAMS WERE DASHED THAT DAY."
// $posts->get("0.tags"); // 3
```

## Validating Data

You can validate the data in the Block object with JSON schema.
JSON Schema is a vocabulary used to annotate and validate JSON documents.

> More info about JSON Schema: https://json-schema.org/learn/getting-started-step-by-step

If you need some common/popular schemas, you can find some schemas here: https://www.schemastore.org/json/
For example:

- Schema for validating the `composer.json` file: https://getcomposer.org/schema.json
- Schema for validating the GitHub Actions workflows: https://json.schemastore.org/github-workflow.json

Or you can build your own schema according to the JSON schema specifications: https://json-schema.org/learn/getting-started-step-by-step#create-a-schema-definition


```php
$file = "./.github/workflows/run-tests.yml";
$workflow = Block::fromYamlFile($file);
$workflow->validateJsonViaUrl(
    'https://json.schemastore.org/github-workflow'
    ); // TRUE if the Block is a valid GitHub Actions Workflow
```

Or you can define your own schema:

```php
$schemaJson = <<<'JSON'
{
    "type": "array",
    "items" : {
        "type": "object",
        "properties": {
            "name": {
                "type": "string"
            },
            "fruit": {
                "type": "string"
            },
            "wikipedia": {
                "type": "string"
            },
            "color": {
                "type": "string"
            },
            "rating": {
                "type": "number"
            }
        }
    }
}
JSON;
```
And then validate it with your Block object:

```php
$fruitsArray = [
    [
        'name' => 'Avocado',
        'fruit' => '🥑',
        'wikipedia' => 'https://en.wikipedia.org/wiki/Avocado',
        'color' => 'green',
        'rating' => 8,
    ],
    [
        'name' => 'Apple',
        'fruit' => '🍎',
        'wikipedia' => 'https://en.wikipedia.org/wiki/Apple',
        'color' => 'red',
        'rating' => 7,
    ],
    [
        'name' => 'Banana',
        'fruit' => '🍌',
        'wikipedia' => 'https://en.wikipedia.org/wiki/Banana',
        'color' => 'yellow',
        'rating' => 8.5,
    ],
    [
        'name' => 'Cherry',
        'fruit' => '🍒',
        'wikipedia' => 'https://en.wikipedia.org/wiki/Cherry',
        'color' => 'red',
        'rating' => 9,
    ],
];

$data = Block::make($fruitsArray);
$data->validateJsonWithSchema($schemaJson);
// true if the Block is valid.
```

If you are starting to use the Data Block and testing it just to gain confidence, implementing different scenarios, or testing a non-valid JSON, try changing the "rating" type from number to integer (the validation should fail because in the JSON, we have ratings with decimals).
And, yes, to change on the fly the schema you can use the Block object :)

```php
// load the schema as Block object...
$schemaBlock = Block::fromJsonString($schemaJson);
// so that you can change the type
$schemaBlock->set(
    "items.properties.rating.type",
    "integer"
);
// the validation should be false because integer vs number
$data->validateJsonWithSchema(
    $schemaBlock->toJson()
);
```

## Applying functions

The `applyField()` method applies a callable function to the value of a specified field and sets the result to another field. This method supports method chaining.

### Parameters

- `key` (string|int): The key of the field whose value will be processed.
- `targetKey` (string|int): The key where the result of the callable function should be stored.
- `callable` (callable): The function to apply to the field value. This function should accept a single argument (the value of the field) and return the processed value.

### Returns

- `self`: Returns the instance of the class for method chaining.

### Example Usage

```php
<?php

// Assuming $object is an instance of the class that contains the applyField method
$object
    ->set('name', 'John Doe')
    ->applyField('name', 'uppercase_name', function($value) {
        return strtoupper($value);
    });

echo $object->get('uppercase_name'); // Outputs: JOHN DOE

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on reporting security vulnerabilities.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Thanks to

Thank you to everyone who has provided feedback, opened issues, or created pull requests. A special thanks to all the contributors! You can view the full list of contributors [in this section](https://github.com/Hi-Folks/data-block/graphs/contributors).

The PHP ecosystem offers many tools that help developers enhance productivity, reliability, and efficiency. One such tool is JetBrains PhpStorm. JetBrains supports the open-source community by offering licenses for various open-source projects. More information can be found in the [Open Source section of the JetBrains website](https://jb.gg/OpenSourceSupport).

<img src="https://resources.jetbrains.com/storage/products/company/brand/logos/PhpStorm_icon.png" alt= "PhpStorm logo" width="128" height="128">


I’m thrilled to share that JetBrains has provided an Open Source license for the PHP Data Block project.
This recognition of PHP Data Block as a valuable open-source software fills me with joy.

Thank you!

[Roberto](https://github.com/roberto-butti)
