[![Build Status](https://img.shields.io/travis/seatgeek/sixpack-php/master.svg?style=flat-square)](https://travis-ci.org/seatgeek/sixpack-php)
[![Latest Stable Version](https://poser.pugx.org/seatgeek/sixpack-php/version.png)](https://packagist.org/packages/seatgeek/sixpack-php)

# Sixpack

PHP client library for SeatGeak's [Sixpack](https://github.com/seatgeek/sixpack) ab testing framework.

## Installation

1. Install [Composer](https://getcomposer.org/doc/00-intro.md)
2. In the root of your project install sixpack via `composer require seatgeek/sixpack-php`
3. In your php script put `require 'vendor/autoload.php';` at the top so sixpack is loaded.

## Usage

Basic example:

The PHP client stores a unique client id in the current user's cookie by default.

```php
require 'vendor/autoload.php';
$sp = new \SeatGeek\Sixpack\Session\Base;
$alt = $sp->participate('test', array('blue', 'red'))->getAlternative();
if ($alt == 'blue') {
    /* do something blue */
} else {
    /* do somethign red */
}
```

Each session has a `client_id` associated with it that must be preserved across requests. The PHP client handles this automatically. If you'd wish to change that behavior, you can do so like this:

```php
require 'vendor/autoload.php';
$sp = new \SeatGeek\Sixpack\Session\Base;
$resp = $sp->participate("new-test", array("alternative-1", "alternative-2"));
store_in_database("sixpack-id", $resp->getClientId());
```

For future requests, create the `Session` using the `client_id` stored in the cookie:

```php
require 'vendor/autoload.php';
$client_id = get_from_database("sixpack-id")
$sp = new \SeatGeek\Sixpack\Session\Base(array('clientId' => $client_id));

$sp->convert('new-test');
```

Other possible options for the Session constructor are:
* baseUrl - Sixpack Server's location on the web
* cookiePrefix - you can set a different prefix for the cookie if you like. Default is `sixpack`

If you'd like to force the Sixpack server to return a specific alternative for development or testing, you can do so by passing a query parameter named `sixpack-force` to that page being tested.

`http://example.com/?sixpack-force-<experiment name>=<alternative name>`

## Contributing

1. Fork it
2. Create your feature branch (`git checkout -b my-new-feature`)
3. Commit your changes (`git commit -am 'Added some feature'`)
4. Push to the branch (`git push origin my-new-feature`)
5. Create new Pull Request

## License

sixpack-php is released under the [BSD 2-Clause License](http://opensource.org/licenses/BSD-2-Clause).
