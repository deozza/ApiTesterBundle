Responses
=

You are able to check the response of the requests you have sent with the option `out`.

All the expected responses are store as `json` files inside the `tests/FeatureFolder/Responses` folder.

In order to check the response to a request, simply add a `out` key with the name of the file containing the expected response to your test :

```
["kind" => "unit", "test" => ['method'=> 'GET'  , 'url' => 'api/foos', 'token' => 'token_user', 'status' => 200, 'out' => 'listOfFoos'] ],
```