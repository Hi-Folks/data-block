# Changelog

## 0.2.0 - 2024-06-22
- Add Yaml capabilities (loading from Yaml and exporting to Yaml)

## 0.1.1 - 2024-06-22
- Add toJson() method for exporting JSON string

## 0.1.0 - 2024-06-21
- Cleaning and refactoring the behavior of returning Block or native array in loops

## 0.0.6 - 2024-06-19
- Add the `iterateBlock()` method, which allows to return of current elements as Block while looping.
- Improve the documentation and the unit tests for "query" methods like `select()`, `where()` and `orderBy()`

## 0.0.5 - 2024-06-17
- `select()` method for selecting fields

## 0.0.4 - 2024-06-16
- `where()` method supports operators
- `orderBy()` method allows you to sort Block data (ascending and descending order)

## 0.0.3 - 2024-06-16
- `toArray()` for exporting data into an array (associative and nested array)

## 0.0.2 - 2024-06-15
Fine-tuning initial release

## 0.0.1 - 2024-06-15
Initial release:
- `make`: static method for initializing the object from a nested array
- `fromJsonString`: static method for initializing the object from a JSON file
- `get`: method for retrieving elements on a nested key
- `getBlock`: method for retrieving Block on a nested key
- `set`: method for setting elements on a nested key
- `where`: method for filtering data
