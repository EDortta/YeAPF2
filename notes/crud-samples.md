

```php
function customersConstraints(int $verb) {
  // Define the constraints
  switch ($verb) {
    case YeAPF_GET_DESCRIPTION:
      $ret = "Customers constraints";
      break;
    case YeAPF_GET_CONSTRAINTS:
      $ret = new \YeAPF\SanitizedKeyData();
      $ret->setConstraint(
        keyName: "name",
        keyType: YeAPF_TYPE_STRING,
        length: 100
      );
      $ret->setConstraint(
        keyName: "email",
        keyType: YeAPF_TYPE_STRING,
        length: 255,
        email: true
      );
      break;
  }

  // Return the constraints
  return $ret;
}
```

```php
function createCustomer(\YeAPF\Bulletin &$bulletin, array $params) {
  // Get the data from the request
  $name = $params['name'];
  $email = $params['email'];

  // Create a new customer
  $customer = new Customer($name, $email);

  // Save the customer
  $this->customers->insert($customer);

  // Return the status code
  return 201;
}
```


```php
function getCustomerById(\YeAPF\Bulletin &$bulletin, int $id) {
  // Get the customer from the database
  $customer = $this->customers->getById($id);

  // If the customer is not found, return a 404 error
  if ($customer === null) {
    return 404;
  }

  // Return the customer
  $bulletin->customer = $customer;
  return 200;
}
```


```php
function updateCustomer(\YeAPF\Bulletin &$bulletin, int $id, array $params) {
  // Get the customer from the database
  $customer = $this->customers->getById($id);

  // If the customer is not found, return a 404 error
  if ($customer === null) {
    return 404;
  }

  // Update the customer with the given data
  $customer->setName($params['name']);
  $customer->setEmail($params['email']);

  // Save the customer
  $this->customers->update($customer);

  // Return the status code
  return 200;
}
```


```php
function deleteCustomer(\YeAPF\Bulletin &$bulletin, int $id) {
  // Get the customer from the database
  $customer = $this->customers->getById($id);

  // If the customer is not found, return a 404 error
  if ($customer === null) {
    return 404;
  }

  // Delete the customer
  $this->customers->delete($customer);

  // Return the status code
  return 200;
}
```

