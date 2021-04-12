# Basis

The *basis* folder is a special one ( among *modules* and *plugins* ) because it can has *config.ini* in it and it doesn't allow to contain any folder into it.

It was created with application startup configuration in mind. In other words: it is the first plugin loaded and run.

The common usage is to put there things that **need** to be present **before** the *modules* and *plugins* (in that order) be loaded and run. For example, tell the ***api*** what is his name as this:

```php
<?php
class MyBasis extends YApiProducer {
  private $domain;
  private $gateway;

  public function initialize($domain, $gateway, $contexto) {
    global $api;
    $this->$domain  = $domain;
    $this->$gateway = $gateway;

    $api -> defineAPIName("MyApplication");
    return true;
  }

  function do($subject, $action, ...$params) {
  }

}
```

As in others plugin, you *need* a config.ini present indicating the name of the php file as this:

```ini
[config]
plugin = "MyBasicConfig"

[MyBasicConfig]
enabled=1
domains=*
level=*
script="my-basic-config.php"
class=MyBasis
```

