Payloads
=

You are able to test your application by sending a specific payload with the `in` option and check how it reacts.

All the payloads are store as `json` files inside the `tests/FeatureFolder/Payloads` folder.

In order to attache a payload to a request, simply add a `in` key with the name of the file containing the payload  to your test :

```
["kind" => "unit", "test" => ['method'=> 'POST'  , 'url' => 'api/foos', 'token' => 'token_user', 'status' => 201, 'in' => 'postValidFoo' , 'out' => 'postedFoo'] ],
```