# Test folder

This folder contains a set of individual test.

The target is to test te structure and not only a certain functionallty.

In other words, if the test loads, then all the structure is doing well.

In order to the postgresql and redis be used in the tests, they must to be running (`cd v1; ./start-base.sh`) and two hosts need to be present in `/etc/hosts`: `redis` and `db`  both of them pointing to 127.0.0.1

## Sample

This is a simple example that can be used to build the tests

```php
<?php
declare(strict_types=1);
require_once __DIR__.'/../yeapf-core.php';

```
