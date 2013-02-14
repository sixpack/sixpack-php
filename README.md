# Sixpack

PHP client library for SeatGeak's Sixpack ab testing framework.

## Installation

Simply clone the project and include `sixpack.php` to your PHP Project

## Usage

Basic example:

The PHP client stores a unique client id in the current user's cookie by default. The `simple_participate` and `simple_convert` methods are helper methods to easily allow Sixpack to be called from your views. They also handle cookie storage.

```php
include 'sixpack.php';

// Participate in a test (creates the test if necessary)
$alt = Sixpack::simple_participate('new-test', array('alternative-1', 'alternative-2'));

// Convert
Sixpack::simple_convert('new-test');
```

Each session has a `client_id` associated with it that must be preserved across requests. The PHP client handles this automatically. If you'd wish to change that behavoir, you can do so like this:

```php
$sp = new Sixpack;
$resp = $sp->participate("new-test", array("alternative-1", "alternative-2"));
store_in_database("sixpack-id", $resp->getClientId());
```

For future requests, create the `Session` using the `client_id` stored in the cookie:

```php
$client_id = get_from_database("sixpack-id")
$sp = new Sixpack;
$sp->setClientId($client_id);

$sp->convert('new-test');
```

If you'd like to force the Sixpack server to return a specific alternative for development or test, you can do so by passing a query parameter named `sixpack-force` to that page being tested.

`http://example.com/?sixpack-force=<alternative name>`

## Contributing

1. Fork it
2. Create your feature branch (`git checkout -b my-new-feature`)
3. Commit your changes (`git commit -am 'Added some feature'`)
4. Push to the branch (`git push origin my-new-feature`)
5. Create new Pull Request