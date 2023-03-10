# polaris-bank-api-client

[![Latest Stable Version](https://img.shields.io/github/v/release/glocurrency/polaris-bank-api-client-php)](https://github.com/glocurrency/polaris-bank-api-client-php/releases)
[![Total Downloads](https://poser.pugx.org/glocurrency/polaris-bank-api-client/downloads)](https://packagist.org/packages/glocurrency/polaris-bank-api-client)
[![License: MPL-2.0](https://img.shields.io/badge/license-MPL--2.0-purple.svg)](https://github.com/glocurrency/polaris-bank-api-client-php/blob/main/LICENSE)
[![tests](https://github.com/glocurrency/polaris-bank-api-client-php/actions/workflows/tests.yml/badge.svg)](https://github.com/glocurrency/polaris-bank-api-client-php/actions/workflows/tests.yml)
[![Maintainability](https://api.codeclimate.com/v1/badges/8b662f4424ebd797d4a8/maintainability)](https://codeclimate.com/github/glocurrency/polaris-bank-api-client-php/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/8b662f4424ebd797d4a8/test_coverage)](https://codeclimate.com/github/glocurrency/polaris-bank-api-client-php/test_coverage)

Polaris Bank API Client for PHP

## Installation

```bash
composer require glocurrency/polaris-bank-api-client
```

## Usage

```php
use GloCurrency\PolarisBank\Client;
use GloCurrency\PolarisBank\Interfaces\ConfigInterface;

assert($config instanceof ConfigInterface);
assert($httpClient instanceof \GuzzleHttp\ClientInterface);

$apiClient = new Client($config, $httpClient);
$apiClient->disburseUSD();
```

## Authors
- [Ivan Stasiuk](https://github.com/brokeyourbike) | [Twitter](https://twitter.com/brokeyourbike) | [LinkedIn](https://www.linkedin.com/in/brokeyourbike) | [stasi.uk](https://stasi.uk)

## License
[Mozilla Public License v2.0](https://github.com/glocurrency/polaris-bank-api-client-php/blob/main/LICENSE)
