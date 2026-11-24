# Installation

## Requirements

- PHP 8.3 or newer
- Composer 2
- A PSR-7/17/18 compatible environment

The package declares no `ext-*` requirement.

## Dependencies

All of these are runtime dependencies of the package, so `composer require`
installs them:

| Package | Used for |
|---|---|
| `psr/http-message` `^1.0 \|\| ^2.0` | PSR-7 requests and responses |
| `psr/http-factory` `^1.0` | PSR-17 request and stream factories |
| `psr/http-client` `^1.0` | PSR-18 transport contract |
| `guzzlehttp/guzzle` `^7.0 \|\| ^8.0` | `GuzzleHttpClient`, the bundled transport |
| `guzzlehttp/psr7` `^2.0 \|\| ^3.0` | `GuzzleHttp\Psr7\HttpFactory` used by `Client::create()` |
| `daika7ana/simple-collections` `^1.0` | `Collection` returned by list endpoints |

The package is a library: no server, no web entrypoint and no framework service
provider. Laravel, Symfony and other frameworks are not dependencies.

## Install

```bash
composer require daika7ana/cargus-php-sdk
```

## Verify

```php
require 'vendor/autoload.php';

use Daika7ana\Cargus\Config\ClientConfig;

echo ClientConfig::BASE_URL; // https://urgentcargus.azure-api.net
```

Constructing `ClientConfig` does not make a network request. The client contacts
Cargus only when you call a resource method or `Client::authenticate()`.

## Development Install

```bash
git clone https://github.com/daika7ana/cargus-php-sdk.git
cd cargus-php-sdk
composer install
composer ci
```

`composer ci` runs `composer validate --strict`, Pint (`--test`), PHPStan level 8
over `src/` and the unit suite. See [TESTING.md](TESTING.md) for the individual
commands.
