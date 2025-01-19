# street-name-parser

Loads a CSV file, and parses the content split given names with title, initials, first and last names. 

### Usage

Start by setting the csv file path in `src/Consts.php`, then follow either method 1 or 2 below

#### Method 1: 

Instantiate Class with file path:


```php
$data = (new NameParser('path-to-file'))->run();
```

#### Method 2

Instantiate, then set file path:

```php
$data = (new NameParser())->setImportPath('path-to-csv-file')->run()
```


### Run the Project Locally

```php
$ composer install 
$ composer dumpautoload
```

### Run Tests

[Optional --coverage]

```php
$ ./vendor/bin/phpunit tests --coverage
```
Or view the test coverage in `coverage-report` directory.  

### Helper

For debugging, `dd()` is available