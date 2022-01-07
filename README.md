# Facebook SDK for PHP

[![Composer](https://github.com/janu-software/facebook-php-sdk/actions/workflows/composer.yml/badge.svg)](https://github.com/janu-software/facebook-php-sdk/actions/workflows/composer.yml)
[![Code style](https://github.com/janu-software/facebook-php-sdk/actions/workflows/code_style.yml/badge.svg)](https://github.com/janu-software/facebook-php-sdk/actions/workflows/code_style.yml)
[![Tester](https://github.com/janu-software/facebook-php-sdk/actions/workflows/phpunit.yml/badge.svg)](https://github.com/janu-software/facebook-php-sdk/actions/workflows/phpunit.yml)
[![PhpStan](https://github.com/janu-software/facebook-php-sdk/actions/workflows/static_analysis.yml/badge.svg)](https://github.com/janu-software/facebook-php-sdk/actions/workflows/static_analysis.yml)

[![Latest Stable Version](https://poser.pugx.org/janu-software/facebook-php-sdk/v/stable)](https://packagist.org/packages/janu-software/facebook-php-sdk)
[![Total Downloads](https://poser.pugx.org/janu-software/facebook-php-sdk/downloads)](https://packagist.org/packages/janu-software/facebook-php-sdk)
[![License](https://poser.pugx.org/janu-software/facebook-php-sdk/license)](https://packagist.org/packages/janu-software/facebook-php-sdk)

This repository contains the open source PHP SDK that allows you to access the Facebook Platform from your PHP app. Based on `facebookarchive/php-graph-sdk` v6.

## Installation

The Facebook PHP SDK can be installed with [Composer](https://getcomposer.org/). Run this command:

    composer require janu-software/facebook-php-sdk

## Compatibility

| Version | PHP  |
|---------|------|
| 0.0-dev | ^8.0 |

## Usage

> **Note:** This version of the Facebook SDK for PHP requires PHP 5.6 or greater.

Simple GET example of a user's profile.

```php
require_once __DIR__ . '/vendor/autoload.php'; // change path as needed

$fb = new \Facebook\Facebook([
  'app_id' => '{app-id}',
  'app_secret' => '{app-secret}',
  'default_graph_version' => 'v2.10',
  //'default_access_token' => '{access-token}', // optional
]);

// Use one of the helper classes to get a Facebook\Authentication\AccessToken entity.
//   $helper = $fb->getRedirectLoginHelper();
//   $helper = $fb->getJavaScriptHelper();
//   $helper = $fb->getCanvasHelper();
//   $helper = $fb->getPageTabHelper();

try {
  // Get the \Facebook\GraphNode\GraphUser object for the current user.
  // If you provided a 'default_access_token', the '{access-token}' is optional.
  $response = $fb->get('/me', '{access-token}');
} catch(\Facebook\Exception\FacebookResponseException $e) {
  // When Graph returns an error
  echo 'Graph returned an error: ' . $e->getMessage();
  exit;
} catch(\Facebook\Exception\FacebookSDKException $e) {
  // When validation fails or other local issues
  echo 'Facebook SDK returned an error: ' . $e->getMessage();
  exit;
}

$me = $response->getGraphUser();
echo 'Logged in as ' . $me->getName();
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
