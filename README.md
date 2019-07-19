PhilarmonyApiTester
=

[![php](https://img.shields.io/badge/php-%5E7.2-blue.svg)]()
[![mysql](https://img.shields.io/badge/mysql-%5E5.7-blue.svg)]()
[![sqlite](https://img.shields.io/badge/sqlite-3-blue.svg)]()
[![symfony](https://img.shields.io/badge/symfony-%5E4.2-blue.svg)](https://symfony.com/doc/current/index.html#gsc.tab=0)
[![Stable](https://img.shields.io/badge/stable-1.1-brightgreen.svg)](https://github.com/deozza/Philarmony/tree/2.0.0)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)]()

## Table of contents

 * [About](#about)
 * [Installation](#installation)
 * [Database preparation](#database-preparation)
 * [How to use](#how-to-use)
 * [Folder structure](#folder-structure)
 * [Road map](#road-map)

## About

PhilarmonyApiTester is a bundle made to test your API. It ensures the quality and the sturdiness of your application via unit and scenario testing.

## Installation

You can install using composer, assuming it is already installed globally :

`composer require deozza/philarmony-api-tester-bundle`

## Database preparation

You need to create a database specifically for your tests. To ensure the tests are executed with the same environment and database, this one will be reset at the end of each test.

_We recommend the use of [doctrine/doctrine-fxtures-bundle](https://symfony.com/doc/master/bundles/DoctrineFixturesBundle/index.html) for the creation of a test database._

The database you use for the tests could be in MySQL or SQLITE3.

* In the case you are using a MySQL database, you need to export it as a file, name it `demo.sql` { and store it as a file in the `/var/data/db_test` folder.
* In the case you are using a SQLITE3 database, you need to name it `demo.sqlite` { and store it as a file in the `/var/data/db_test` folder.

_For performance reasons, we recommend using a SQLITE3 database for your tests._

## How to use

### Creating a ControllerTest

To tests a feature of your application, you need to create a folder in the `test` folder. Inside it, create a ControllerTest for each Controller related to that feature. The ControllerTest must match the following example: 

```php
<?php
namespace App\Tests\FooFeature;

use Deozza\PhilarmonyApiTester\Service\TestAsserter;

class FooControllerTest extends TestAsserter
{
    const TEST_DATABASE_PATH = __DIR__."/path/to/db.sql";
  public function setUp()
  {
      parent::setTestDatabasePath(self::TEST_DATABASE_PATH);
      parent::setUp();
  }

  /**
  * @dataProvider addDataProvider
  */
  public function testUnit($kind, $test)
  {
    parent::launchTestByKind($kind, $test);
  }

  public function addDataProvider()
  {
      return [];
  }
}
``` 

### Writing tests

The tests are written as an array following this structure:

```
["kind" => "unit", "test" => ["method" => "GET", "url" => "/path/to/the/tested/route", "status" => 200, "token" => "auth_token", "in" => "json_payload", "out" => "json_expected_response]]
```

They are written in the function `addDataProvider`.

```php
<?php

public function addDataProvider()
  {
      return
      [
        ["kind" => "unit", "test" => ['method'=> 'GET'   , 'url' => 'api/foos'                                    , 'status' => 200, 'out' => 'getAllFoos'] ],
        ["kind" => "unit", "test" => ['method'=> 'POST'  , 'url' => 'api/foos'                                    , 'token' => 'token_user', 'status' => 201, 'in' => 'postValidFoo' , 'out' => 'postedFoo'] ],
        ["kind" => "unit", "test" => ['method'=> 'PATCH' , 'url' => 'api/foo/00400000-0000-5000-a000-000000000000', 'token' => 'token_user', 'status' => 200, 'in' => 'patchValidFoo', 'out' => 'patchedFoo'] ],
        ["kind" => "unit", "test" => ['method'=> 'PUT'   , 'url' => 'api/foo/00400000-0000-5000-a000-000000000000', 'token' => 'token_user', 'status' => 405] ],
        ["kind" => "unit", "test" => ['method'=> 'DELETE', 'url' => 'api/foo/00400000-0000-5000-a000-000000000000', 'token' => 'token_user', 'status' => 204] ],
      ];
  }
```

|     Key     |                Description                | Facultative |
|:-----------:|:-----------------------------------------:|:-----------:|
| kind        | The kind of test you are executing        | No          |
| test        | The content of the test                   | No          |
| test.method | The method of the request you are sending | No          |
| test.url    | The route you are testing                 | No          |
| test.token  | The authorization token used for the test | Yes         |
| test.status | The expected http status of the response  | No          |
| test.in     | The payload sent with the request         | Yes         |
| test.out    | The expected response from the request    | Tes         |

__For now, only GET, POST, PUT, PATCH and DELETE methods are handled by PhilarmonyApiTester__

#### Different kinds of tests

With PhilarmonyApiTester, you are able to test your application by sending requests one by one ("unit" testing) and by sending a group of request ("scenario").

 * [Read more](src/Resources/doc/TEST_KINDS.md)

#### In (payloads)

You are able to test your application by sending a specific payload with the `in` option and check how it reacts.

 * [Read more](src/Resources/doc/PAYLOADS.md)
 

#### Out (expected responses)

You are able to check the response of the requests you have sent with the option `out`.

 * [Read more](src/Resources/doc/RESPONSES.md)


#### Testing with patterns

Sometimes, value sent by your app are hard to test because they are unpredictable. Testing with patterns will allow PhilarmonyApiTester to assert these values are in a specific scope and even manipulate them for future tests.

 * [Read more](src/Resources/doc/PATTERNS.md)

## Folder structure

To test your application, you need to match the following folder structure:

    .
    ├── tests                                   #   The basic Symfony test directory
    │   ├── Foo                                 #   Feature you need to test
    │   │   │── FooControllerTest.php           #   Controller of the feature you want to test
    │   │   │                                       
    │   │   ├── Payloads                            
    │   │   │   └── ...                             #   File posted to your API
    │   │   │   
    │   │   └── Responses                           #   All the expected responses coming from the endpoints of your API when testing it
    │   │       └── ...
    │   └──
    └──

## Road map

Nothing is in the road map !