# Poloniex PHP Client
An efficient poloniex rest api client, built on top of [guzzle](https://github.com/guzzle/guzzle)

## Requirements
* PHP 7.x
* [Composer Dependency Manager](https://getcomposer.org/)
* [Poloniex](https://poloniex.com/) account (optional, but required for trading)

## Installation
```shell
composer require andreas-glaser/poloniex-php-client dev-master
```

## Usage
```php
<?php

use AndreasGlaser\PPC\PPC;

$apiKey = 'YOUR_PRIVATE_API_KEY';
$apiSecret = 'YOUR_PRIVATE_API_SECRET';

/** @var PPC $pcc */
$pcc = new PPC($apiKey, $apiSecret);

$result = $pcc->buy('BTC_ETH', 0.034, 100, 1);

var_dump($result->decoded);