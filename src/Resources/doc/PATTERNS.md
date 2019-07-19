Patterns
=

Sometimes, value sent by your app are hard to test because they are unpredictable. Testing with patterns will allow PhilarmonyApiTester to assert these values are in a specific scope and even manipulate them for future tests.

## Asserting patterns

To assert a value sent in a response matches a 

All the available asserting patterns are here :

|    Key    |           Description          |
|:---------:|:------------------------------:|
| @string@  | Assert the value is a string   |
| @int@     | Assert the value is an integer |
| @integer@ | Assert the value is an integer |
| @float@   | Assert the value is a float    |
| @double@  | Assert the value is a float    |
| @bool@    | Assert the value is a boolean  |
| @boolean@ | Assert the value is a boolean  |
| @date@    | Asset the value is a \DateTime |


## Function pattern

Function patterns are used for asserting precisely a dynamic value or to store a dynamic value of a response. To use a function pattern, follow this example :

```json
{
  "uuid": "@string@.isUuid()"
}
```

In this example, we ensure the value is a string and matches the UUID4 pattern.

Function patterns can be chained to form a complex assertion :

```json
{
  "date_of_creation": "@date@.before().after(-P10D)",
  "expiry_date": "@date@.after(P01D).before(P05D)"
}
```

All the available function patterns are here :

|       Function       |                             Description                             |
|:--------------------:|:-------------------------------------------------------------------:|
| isUuid               | Assert the value is a uuid                                          |
| isGreaterThan        | Assert the value tested is greater than the value provided          |
| isGreaterThanOrEqual | Assert the value tested is greater than or equal the value provided |
| isLessThan           | Assert the value tested is less than the value provided             |
| isLessThanOrEqual    | Assert the value tested is less than or equal the value provided    |
| after                | Assert the date tested is after the date provided                   |
| before               | Assert the date tested is before the date provider                  |
| catchAs              | Catch the value and store it for the next tests                     |

### Store a variable

In the case of a scenario testing, you may need to store values for ulterior tests in the scenario. To do that, use the `catchAs` function :

```json
{
  "uuid": "@string@.catchAs(uuid)"
}
```

The value will be stored under the name provided in the function.

### Reuse a variable

You can reuse a value stored with the `catchAs` by writing it like this : `#nameOfTheVariable#`. 

You can reuse a variable in the token :

```
[
    "kind" => "scenario", 
    "test" => [
        ['method'=> 'POST', 'url' => 'api/login'                     , 'status' => 201, 'in' => 'postValidLogin', 'out' => 'postedLogin'],
        ['method'=> 'GET' , 'url' => 'api/user', 'token' => '#token#', 'status' => 200     , 'out' => 'profile'],
    ]
]
```

You can reuse a variable in the url :

```
[
    "kind" => "scenario", 
    "test" => [
        ['method'=> 'POST', 'url' => 'api/foo'         , 'token' => 'token_user', 'status' => 201, 'in' => 'postValidFoo', 'out' => 'postedFoo'],
        ['method'=> 'GET' , 'url' => 'api/foo/#foo_id#', 'token' => 'token_user', 'status' => 200     , 'out' => 'foo'],
    ]
]
```

You can reuse a variable in the payload :

```json
{
  "uuid": "#foo_uuid#"
}
```
