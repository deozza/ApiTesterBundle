Test kinds
=

With ApiTesterBundle, you are able to test your application by sending requests one by one ("unit" testing) and by sending a group of request ("scenario").

## Unit testing

We call a unit test when the test consists of sending only one request, then immediately reset the database after.

To write a unit test, follow this example :

```
["kind" => "unit", "test" => ['method'=> 'DELETE', 'url' => 'api/foo/00400000-0000-5000-a000-000000000000', 'token' => 'token_user', 'status' => 204] ]
```

## Scenario testing

We call a scenario test when the test consists of sending multiple request corresponding to the normal use of the application. It can be viewed as an End-To-End test. The database will be reset only after the scenario is complete.

To write a scenario test, follow the example :

```
[
    "kind" => "scenario", 
    "test" => [
        ['method'=> 'GET' , 'url' => 'api/foos', 'token' => 'token_user', 'status' => 200                        , 'out' => 'listOfFoos'],
        ['method'=> 'POST', 'url' => 'api/foos', 'token' => 'token_user', 'status' => 201, 'in' => 'postValidFoo', 'out' => 'postedFoo'],
        ['method'=> 'GET' , 'url' => 'api/foos', 'token' => 'token_user', 'status' => 200                        , 'out' => 'listOfMoreFoos'],
    ]
]
```