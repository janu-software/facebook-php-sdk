# Facebook SDK for PHP

[![Composer](https://github.com/janu-software/facebook-php-sdk/actions/workflows/composer.yml/badge.svg)](https://github.com/janu-software/facebook-php-sdk/actions/workflows/composer.yml)
[![Code style](https://github.com/janu-software/facebook-php-sdk/actions/workflows/code_style.yml/badge.svg)](https://github.com/janu-software/facebook-php-sdk/actions/workflows/code_style.yml)
[![Tester](https://github.com/janu-software/facebook-php-sdk/actions/workflows/phpunit.yml/badge.svg)](https://github.com/janu-software/facebook-php-sdk/actions/workflows/phpunit.yml)
[![PhpStan](https://github.com/janu-software/facebook-php-sdk/actions/workflows/static_analysis.yml/badge.svg)](https://github.com/janu-software/facebook-php-sdk/actions/workflows/static_analysis.yml)

[![Latest Stable Version](https://poser.pugx.org/janu-software/facebook-php-sdk/v/stable)](https://packagist.org/packages/janu-software/facebook-php-sdk)
[![Total Downloads](https://poser.pugx.org/janu-software/facebook-php-sdk/downloads)](https://packagist.org/packages/janu-software/facebook-php-sdk)
[![License](https://poser.pugx.org/janu-software/facebook-php-sdk/license)](https://packagist.org/packages/janu-software/facebook-php-sdk)
[![Coverage Status](https://coveralls.io/repos/github/janu-software/facebook-php-sdk/badge.svg?branch=main)](https://coveralls.io/github/janu-software/facebook-php-sdk?branch=main)

This repository contains the open source PHP SDK that allows you to access the Facebook Platform from your PHP app. Based on `facebookarchive/php-graph-sdk` v6.

## Installation

The Facebook PHP SDK can be installed with [Composer](https://getcomposer.org/). Run this command:

    composer require janu-software/facebook-php-sdk

You must use some client using `php-http/client-implementation`.

For example: Using with Guzzle:

    composer require janu-software/facebook-php-sdk guzzlehttp/guzzle php-http/guzzle7-adapter

## Compatibility

| Version | PHP  |
|---------|------|
| 0.1     | ^8.0 |
| 0.2     | ^8.1 |
| 0.3     | ^8.1 |

## Usage

Simple GET example of a user's profile.

```php
require_once __DIR__ . '/vendor/autoload.php'; // change path as needed

$fb = new \JanuSoftware\Facebook\Facebook([
  'app_id' => '{app-id}',
  'app_secret' => '{app-secret}',
  'default_graph_version' => 'v19.0',
  //'default_access_token' => '{access-token}', // optional
]);

try {
  // If you provided a 'default_access_token', the '{access-token}' is optional.
  $response = $fb->get('/me', '{access-token}');
} catch(\JanuSoftware\Facebook\Exception\ResponseException $e) {
  // When Graph returns an error
  echo 'Graph returned an error: ' . $e->getMessage();
  exit;
} catch(\JanuSoftware\Facebook\Exception\SDKException $e) {
  // When validation fails or other local issues
  echo 'Facebook SDK returned an error: ' . $e->getMessage();
  exit;
}

$me = $response->getGraphNode();
echo 'Logged in as ' . $me->getField('name');
```

Complete documentation, installation instructions, and examples are available [here](docs/).


## Tests

1. [Composer](https://getcomposer.org/) is a prerequisite for running the tests. Install composer globally, then run `composer install` to install required files.
2. Create a test app on [Facebook Developers](https://developers.facebook.com), then create `tests/FacebookTestCredentials.php` from `tests/FacebookTestCredentials.php.dist` and edit it to add your credentials.
3. The tests can be executed by running this command from the root directory:

```bash
$ ./vendor/bin/phpunit
```

By default the tests will send live HTTP requests to the Graph API. If you are without an internet connection you can skip these tests by excluding the `integration` group.

```bash
$ ./vendor/bin/phpunit --exclude-group integration
```


## License

Please see the [license file](https://github.com/janu-software/facebook-php-sdk/blob/main/LICENSE) for more information.


## Security Vulnerabilities

If you have found a security issue, please contact the maintainers directly at [s@janu.software](mailto:s@janu.software).
