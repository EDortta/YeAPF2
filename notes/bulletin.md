

# What is it?

A YeAPF2 Bulletin is the base to return data to the client. The usage context is a YeAPF2 service (HTTP, HTTP2, GRPC, anyone)

Usually, you're free to put whatever you want into it, but there are some exceptions.

Let's see some examples and then explain how to use in an application context. So, don't forget to read `notes`.

## Example 1

Returning simple data as expected by the client side. Let's say you're expeting a field called `data`

```php

    private function getCustomerList(\YeAPF\Bulletin &$bulletin, int $start, int $limt) {
        $ret = 200;

        $myList = $this->getCustomerList($start, $limit);

        $bulletin->data=$myList;
        return $ret;
    }
```

## Example 2

But, let's say you want to download the list as a json file. In that case, you need to use some special fields.

```php

    private function getCustomerList(\YeAPF\Bulletin &$bulletin, int $start, int $limt) {
        $ret = 200;

        $myList = $this->getCustomerList($start, $limit);

        $bulletin->__jsonFile=json_encode($myList);
        $bulletin->__filename="MyCustomerList_".date("Y-m-d").".json";
        return $ret;
    }
```
In this case, the service will create the necessary output to provoke a download from client side. (Usually the client is a browser or equivalent)

## Example 3

By the other hand, you may need to just send a pure JSON back.

Here you can use `__json` as in the next code:

```php
    private function getCustomerList(\YeAPF\Bulletin &$bulletin, int $start, int $limt) {
        $ret = 200;

        $myList = $this->getCustomerList($start, $limit);

        $bulletin->__json=json_encode($myList);
        return $ret;
    }
```

## Notes

When you're writting an application, one common task is to define how the client will expect the data.

Relying on `Bulletin` you have at least three layers:
  1. You have the lower one where you use `http_codes` to return the state of your application. Usually 200, 201, 403, etc.
  2. On top of this, you have a single special field `__json` that can be used to return data in a plain JSON manner.
  3. But, you can determine that `data` (for example) is the "place"  where you will return data to your client. In the same way you can determine a "always present" field that will return some other information to your client further than `http_code`. For example, 200 always will mean "ok, the server processed it", but, you can use another var to *always*  return the situation of data associated with the request. Is the case when you is registering a client but it's not complete, for example.

