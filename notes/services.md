

# What is it?

A YeAPF2 service uses Swoole and can be a Restful or a GRPC one.

Here is a skeleton of what you need to write in order to build a YeAPF2 service.

```php
<?php
namespace YeAPF\Services;
require_once '/core/yeapf-core.php';

class MyService extends YeAPF\Services\HTTP2Service {
  function startup() {
  }

  function shutdown() {
  }

  function answerQuery(\YeAPF\Bulletin&$bulletin, string $uri) {
  }
}

$myService = new MyService();
$myService->start();
```

`startup()` is responsible to initialise all the structures your service will need to correctly respond to the requests. For example, if you need some sort of database, this is the correct point to prepare all.

On the other hand, `shutdown()` is the correct point to restore used resources. Here is an example of both using the Service database context.

```php
  \YeAPF\ORM\PersistentCollection $clients = null;

  function startup() {
    // DB connections and so on
    $context = $this->getContext();
    // Data constraints
    $clientModel = new \YeAPF\ORM\DocumentModel($context, "translations");
    $clientModel->setConstraint(
      keyName:"id",
      keyType:YeAPF_TYPE_STRING,
      length:36,
      primary:true,
      protobufOrder:0
    );
    // ...
    $this->clients = new \YeAPF\ORM\PersistentCollection(
      self::$context,
      "clients",
      "id",
      $clientModel
    );
  }

function shutdown() {
  $this->clients = null;
}
```



### Example 1

This is a default handler that always answer with 501 with a simple message: "Not implemented"

Of course, you can change it to implement some sort of path manager.

```php
function answerQuery(\YeAPF\Bulletin&$bulletin, string $uri) {
    // We will return 501 http code
    $ret = 501;

    // obtain an splited and sanitized path from URI
    $path = $this->getPathFromURI($uri);

    // explain that this path is not implemented
    $bulletin->message = "Not implemented /".implode("/", $path);
    return $ret;
}
```



### Example 2

A route to attend `/hello` and answer "Woooow!" always.

```PHP
  function hello(\YeAPF\Bulletin&$bulletin) {
      $ret = 200;
      $bulletin->message="Woooow!";
      return $ret;
  }

  function startup() {
      $this->setHandler("/hello", [$this, 'hello']);
  }

```



## Using patterns in online parameters

### Example 3

A route to attend `/*/clients` and answer with a json containing a list.

`*`means "whatever" and here just serve to introduce the concept.

```php
function clients(\YeAPF\Bulletin&$bulletin) {
  $ret= 200;
  $bulletin->list = [
    [
      'id' => 100,
      'name' => 'John Smith'
    ],[
      'id '=> 451,
      'name' => 'Ann Roberts'
    ]
  ];
  return $ret;
}

function startup() {
   $this->setHandler("/*/clients", [$this, 'clients']);
}
```



## Selecting method that can access the path

### Example 4

The same path can be accesses through `POST` and `GET`. The default is `GET` when not declared.

```php
  function myEventDriver(\YeAPF\Bulletin&$bulletin) {
    $ret = 200;
    $bulletin->message="Ok";
    return $ret;
  }

  function startup() {
    $this->setHandler("/event",[$this, 'myEventDriver'], [ 'GET', 'POST']);
  }
```



## Named inline parameters

### Example 5

Using inline parameters from URI and accept it as parameters in event handler

```php
  function anEventHandler(\YeAPF\Bulletin&$bulletin, string $partner_id) {
    $ret = 200;
    $bulletin->message = "The partner ID sent is '$partner_id'";
    return $ret;
  }

  function startup() {
    $this->setHandler("/edit/{{partner_id}}", [$this, 'anEventHandler'])
  }
```



### Example 6

Same than example 5 but using more than one inline parameters from URI and accept them as parameters in event handler

```php
  function anotherEventHandler(\YeAPF\Bulletin&$bulletin, string $partner_id, string $city_code) {
    $ret = 200;
    $bulletin->message = "The partner ID sent is '$partner_id' at '$city_code'";
    return $ret;
  }

  function startup() {
    $this->setHandler("/edit/{{partner_id}}/{{city_code}}", [$this, 'anotherEventHandler'])
  }
```



### Example 7

Using values sent by `Multipart form` via `POST`. In this example, each value sent is just displayed again.

```php
  function myPOSTEventHandler(\YeAPF\Bulletin&$bulletin, array $params=[], string $partner_id, $city_code) {
    $ret = 200;
    $post = $params['post']??[];
    $bulletin->message = "The partner ID sent is '$partner_id' at '$city_code'. ";
    foreach($post as $k=>$v) {
      $bulletin->message .= " $k = '$v'";
    }
    return $ret;
  }

  function startup() {
    $this->setHandler("/edit/{{partner_id}}/{{city_code}}", [$this, 'myPOSTEventHandler'], ['POST'])
  }
```

This can be easly tested using `curl`

```bash
curl --request POST \
  --url https://localhost:8443/edit/28a49h9875/19600 \
  --header 'Content-Type: multipart/form-data' \
  --form name=urano \
  --form type=planet
```



## OpenAPI 3.0 related examples

### Example 8

Building service constraints and OpenAPI related information. In the next sample, `customersListConstraints()` will be called by `setHandler()` when the handler is being registered.

That means, that it's not a good idea to do something that will waste too many time.

Ther are only five request being done: `DESCRIPTION`, `RESPONSES`, `DESCRIPTION`, `OPERATION_ID`, `SECURITY` and `CONSTRAINTS`. For brevity, we'll talk about `CONSTRAINTS` and `SECURITY` later in  next examples.

```php
  function customersListConstraints(int $verb) {
        $ret = null;
        switch($verb) {
            case YeAPF_GET_DESCRIPTION:
                $ret="Return a list of customers belonging to the enterprise.";
                break;
            case YeAPF_GET_OPERATION_ID:
                $ret="customersListConstraints";
                break;
            case YeAPF_GET_RESPONSES:
                $ret = [
                  200 => 'Success',
                  401 => 'Unauthorized',
                  403 => 'Forbidden'
                ];
                break;
            case YeAPF_GET_PRIVATE_PATH_FLAG:
                $ret = false;
                break;
            case YeAPF_GET_CONSTRAINTS:
                //...
                break;
            case YeAPF_GET_SECURITY:
                //...
                break;
        }
        return $ret;
  }

  function customersList(\YeAPF\Bulletin &$bulletin, $enterprise, $start, $limit) {
    // ...
  }

  function startup() {
    $this->setHandler(
      "/{{enterprise}}/customers/list/{{start}}/{{limit}}",
      [$this, 'customersList'],
      ['GET'],
      [$this, 'customersListConstraints']
    );
  }
```



### Example 9

Setting the basic information of your API in order to export to OpenAPI.

```php
    function startup() {

        $this->setAPIDetail('info','title', 'My First API');
        $this->setAPIDetail('info','version', '1.0');

    }
```



### Example 10

Exporting your API to a json file.

That file can be used to be imported in software like Insomnia or Postman.

```bash
curl https://example.com/api/openapi/export
```



## Constraints

### Example 11

Defining constraints to be used with inline parameters in GET method and form values in POST.

Let's start with no constraint in order to see the difference. Even if it is a not recomended way, maybe you can use it in a very special case.

```php
<?php
  require_once '/core/yeapf-core.php';
  class MyService2 extends YeAPF\Services\HTTP2Service {
    function hello(\YeAPF\Bulletin &$bulletin, string $uri) {
        $bulletin->message="Hello";
        return 200;
    }

    function helloWorld(\YeAPF\Bulletin &$bulletin, string $uri) {
        $ret = 200;
        $path = $this->getPathFromURI($uri);
        $bulletin->message = "Hello world!";
        $bulletin->receivedPath = json_encode($path);
        return $ret;
    }

    function answerQuery(\YeAPF\Bulletin&$bulletin, string $uri) {
        // We will return 501 http code
        $ret = 501;

        // obtain an splited and sanitized path from URI
        $path = $this->getPathFromURI($uri);

        // explain that this path is not implemented
        $bulletin->message = "Not implemented /".implode("/", $path);
        return $ret;
    }

    function startup() {

        $this->setAPIDetail('info','title', 'My First API');
        $this->setAPIDetail('info','version', '1.0');


        $this->setHandler("/hello", [$this, 'hello']);
        $this->setHandler("/hello/*/world", [$this, 'helloWorld']);
    }
  }

  $myService2 = new MyService2();
  $myService2->start();
```

Here is a good example where you can view the process of two paths that're very similar. `/hello` is attended by `hello()` while `/hello/*/world`  will be attended by `helloWorld()`

Between `/hello` and `/world` we accept anything.

You can see the difference using `curl`

```bash
curl https://example.com/api/hello
```

Will obtain just `hello`

```bash
curl https://example.com/api/hello/world
```

Will obtain `Not implemented /hello/world`

```bash
curl https://example.com/api/hello/small/world
```

Will obtain two fields: one with `Hello world` and other with `[\"hello\",\"small\",\"world\"]"`

Whatever you put in the second element of the path, will be accepted and used by your software.

You can limit that by using constraint. A constraint is applyable to inline paramenters but you need to remember to declare them in the order they will be used.

You can even mix them (inline and POST) and the rule will be the same with inline parameters. That is because you use `*` to say "whatever" in the path.

### Example 12

Let's change our `startup()`. As in prior example, we will only focus in one property this time. In your code, you will need to put all properties together. I only put `YeAPF_GET_DESCRIPTION` for reference. Here we're only defining one parameter constraint, but you can declare all the parameters you need to use constraints. And this usually are all the parameters and POST fields.

By the way, we are defining that the `*` element in the path `/hello/*/world` has a name: `adjetive` and is a string with no more than 30 characters.

```php
    function helloWorldConstraints($verb) {
      $ret = null;
      switch($verb) {
        case YeAPF_GET_DESCRIPTION:
          $ret="Constrainted Hello World"
          break;
        //...
        case YeAPF_GET_CONSTRAINTS:
          $ret = new \YeAPF\SanitizedKeyData();
          $ret->setConstraint(
            keyName: "adjetive",
            keyType: YeAPF_TYPE_STRING,
            length: 30
          );
          break;
      }
      return $ret;
    }

    function startup() {

        $this->setAPIDetail('info','title', 'My First API');
        $this->setAPIDetail('info','version', '1.0');


        $this->setHandler("/hello", [$this, 'hello']);
        $this->setHandler("/hello/*/world", [$this, 'helloWorld'], ['GET'] [$this, 'helloWorldConstraints']);
    }
```



### Example 13

Now, let's add some security for only one path.  The first we need to do is to declare the security methods that are allowed across all the applciation. The second is to define which endpoint will use which of the declared methods.

```php
    function helloWorldConstraints($verb) {
      $ret = null;
      switch($verb) {

        //...
        case YeAPF_GET_SECURITY:
          $ret = ["bearerAuth", "basicAuth"];
          break;
      }
      return $ret;
    }

    function startup() {
        // ...
        $this->setAPIDetail('security', 'bearerAuth', []);
        $this->setAPIDetail('security', 'basicAuth', []);
        $this->setAPIDetail('security', 'jwtAuth', []);

        // ...
        $this->setHandler("/hello/*/world", [$this, 'helloWorld'], ['GET'] [$this, 'helloWorldConstraints']);
        // ...
    }
```



### Example 14

But maybe you need something more complex like use another security scheme that not belongs to the defined (`bearerAuth`, `basicAuth`, `jwtAuth` and `apiKeyHeader`). In such case you'll need to add a specific schema like this:

```php
   function startup() {
    // ...
    $this->setAPIDetail(
        'components',
        'securitySchemes',
        [
            'apiKeyQueryParam' => [
                'type' => 'apiKey',
                'name' => 'api_key',
                'in' => 'query',
            ]
        ]
    );
    //..
    $this->setAPIDetail('security', 'apiKeyQueryParam', []);
   }
```
