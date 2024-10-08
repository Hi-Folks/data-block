# Changelog

## WIP
- Implementing a Trait for formatting data
- Implementing getFormattedData for getting value as formatted data

## 0.3.10 - 2024-10-07
- Implementing the `saveToJson()` method

## 0.3.9 - 2024-08-08
- Implementing the `exists()` method

## 0.3.8 - 2024-08-03
- Implementing the `applyField()` method

## 0.3.7 - 2024-07-28
- Implementing the `forEach()` method

## 0.3.6 - 2024-07-11
- Implementing the `groupBy()` method

## 0.3.5 - 2024-07-07
- Fix returning nested array type after `where()`

## 0.3.4 - 2024-07-07
- Add `orderBy` for nestable attributes
- Add filtering for array values (`in` parameter for `where()` method)
- Add `dump()` and `dumpJson()` method to Block class


## 0.3.3 - 2024-06-29
- Add `append()` for elements of Block and array
- Add `appendItem()` for adding a single element to a Block object

## 0.3.2 - 2024-06-28
- Add `fromJsonUrl()` method for loading Block data from a remote JSON (like APIs)
- Add `like` operator for `where()` method, so you can filter for a substring
- add more tests

## 0.3.1 - 2024-06-26
- Add `has()` and `hasKey()` method

## 0.3.0 - 2024-06-24
- Add validation with JSON Schema https://json-schema.org/

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
