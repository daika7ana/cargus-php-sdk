<div align="center">

<h1>Cargus PHP SDK</h1>

[![CI](https://github.com/daika7ana/cargus-php-sdk/actions/workflows/ci.yml/badge.svg)](https://github.com/daika7ana/cargus-php-sdk/actions/workflows/ci.yml)
[![PHP 8.3+](https://img.shields.io/badge/PHP-8.3%2B-777BB4?style=flat-square&logo=php)](https://www.php.net/)
[![PHPUnit](https://img.shields.io/badge/PHPUnit-12%E2%80%9313-0A7BBB?style=flat-square)](docs/TESTING.md)
[![Smoke Tests](https://img.shields.io/badge/Smoke%20Tests-Live%20API-orange?style=flat-square)](docs/TESTING.md)
[![PSR Standards](https://img.shields.io/badge/PSR-7%2F17%2F18-blue?style=flat-square)](https://www.php-fig.org/)
[![License](https://img.shields.io/badge/license-GPL--3.0--or--later-green?style=flat-square)](LICENSE)

Typed PHP SDK for the Cargus (Urgent Cargus / UrgentOnline) courier API

</div>

The SDK wraps the UrgentOnline API behind a PSR-18 client, builds and parses the
JSON payloads internally, and returns readonly DTOs. Authentication uses an Azure
API Management subscription key plus a WebExpress login that yields a 24-hour
bearer token; the client logs in on demand and retries a `401` once.

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Configuration](#configuration)
- [Authentication](#authentication)
- [Supported Resources](#supported-resources)
- [Error Handling](#error-handling)
- [Framework Support](#framework-support)
- [Testing](#testing)
- [Documentation](#documentation)
- [License](#license)

## Features

- **All 81 API operations** — 78 resource methods across 13 accessors, plus
  `authenticate()`, `authenticateJuridic()` and `tokenVerification()` on the
  client. Covers AWBs, documents, tracking, pickups, pickup locations, orders,
  geography, pricing, financial data, online shop, recipients, complaints and
  marketing.
- **PSR-18 transport** — bundled Guzzle client by default, any PSR-18 client by
  injection, PSR-7 responses and PSR-17 factories throughout.
- **Readonly DTOs** — typed request and response objects with PascalCase wire
  serialization; list endpoints return `Collection` objects.
- **On-demand bearer authentication** — no login at construction, re-login on
  token expiry, and one retry after a `401`.
- **Subscription-key-only endpoints** exposed as explicit `*NoAuth()` methods.
- **Binary PDF documents** returned as `Support\Document` with an `isPdf()`
  check.
- **Framework-agnostic** — no Laravel or Symfony dependency, no service provider.
- **Quality gates** — PHPStan level 8 over `src/`, Laravel Pint and PHPUnit.

## Requirements

- PHP 8.3+
- `ext-json`
- Composer 2
- A PSR-7/17/18 compatible environment

## Installation

```bash
composer require daika7ana/cargus-php-sdk
```

## Quick Start

Pass the credentials explicitly:

```php
use Daika7ana\Cargus\Client;
use Daika7ana\Cargus\Config\ClientConfig;

$client = Client::create(config: new ClientConfig(
    username: 'your-webexpress-user',
    password: 'your-webexpress-password',
    subscriptionKey: 'your-azure-subscription-key',
));

$counties = $client->geography()->counties(countryId: 1);
```

Or set `CARGUS_USERNAME`, `CARGUS_PASSWORD` and `CARGUS_SUBSCRIPTION_KEY` and
call `Client::create()` with no arguments.

Create an AWB and read its trace:

```php
use Daika7ana\Cargus\DTOs\Awb\Address;
use Daika7ana\Cargus\DTOs\Awb\AwbTurModel;

$barCode = $client->awbs()->create(new AwbTurModel(
    sender: new Address(
        name: 'Sender SRL',
        countyId: 12,
        localityId: 1234,
    ),
    recipient: new Address(
        name: 'Maria Ionescu',
        countyId: 12,
        localityId: 1234,
        streetName: 'Strada Testului',
        buildingNumber: '1',
        phoneNumber: '0723000000',
    ),
    parcels: 1,
    envelopes: 0,
    totalWeight: 2,
    shipmentPayer: 1,
    serviceId: 7,
    cashRepayment: 149.99,
));

foreach ($client->tracking()->trace($barCode) as $package) {
    foreach ($package->events as $event) {
        echo $event->date, ' ', $event->description, PHP_EOL;
    }
}
```

Resolve `countyId`, `localityId` and `countryId` from `geography()->countries()`,
`counties()` and `localities()`. `serviceId` is the Cargus service code; the
OpenAPI file does not label the values. Full flows are in
[docs/QUICKSTART.md](docs/QUICKSTART.md).

## Configuration

`Client::create()` with no config calls `ClientConfig::fromEnvironment()`:

| Variable | Maps to |
|---|---|
| `CARGUS_USERNAME` | WebExpress username |
| `CARGUS_PASSWORD` | WebExpress password |
| `CARGUS_SUBSCRIPTION_KEY` | Azure APIM subscription key |
| `CARGUS_BASE_URL` | API base URL; default `https://urgentcargus.azure-api.net` |
| `CARGUS_TIMEOUT` | Request timeout in seconds |

A non-numeric `CARGUS_TIMEOUT` throws `InvalidArgumentException`. Timeout
precedence is per-call argument → `ClientConfig::timeout` → transport
constructor arguments → `30.0`/`10.0`. Resource methods do not take a timeout;
use `ClientConfig`, `Client::withTimeout()`, or the low-level `Client::get()` /
`post()` methods.

A bare PSR-18 client is wrapped automatically and keeps its own timeout:

```php
use Daika7ana\Cargus\Client;
use Daika7ana\Cargus\Config\ClientConfig;
use GuzzleHttp\Client as GuzzleClient;

$config = new ClientConfig(
    username: 'your-webexpress-user',
    password: 'your-webexpress-password',
    subscriptionKey: 'your-azure-subscription-key',
    // timeout stays null: the adapter cannot forward one.
);

$client = Client::create(
    httpClient: new GuzzleClient(['timeout' => 10.0]),
    config: $config,
);
```

See [Configuration](docs/CONFIGURATION.md).

## Authentication

Two credentials are required:

| Credential | Sent as |
|---|---|
| Azure APIM subscription key | `Ocp-Apim-Subscription-Key` header on every request |
| WebExpress username and password | JSON body of `POST /api/LoginUser` |

The login response is a JSON string token valid for 24 hours, sent as
`Authorization: Bearer <token>`. Nothing logs in at construction: the client
re-authenticates an expired token before sending, and answers a first `401` by
logging in and retrying once, so a new client may spend one unauthenticated round
trip before the token is set. `authenticateJuridic()` uses
`POST /api/LoginUser/Juridic`, and `tokenVerification()` checks the current
token. See [Authentication](docs/AUTHENTICATION.md).

## Supported Resources

| Accessor | Covers |
|---|---|
| `awbs()` | Create, read, update, delete, reschedule, routing, returns, international AWBs |
| `documents()` | AWB, order and invoice documents; PDF variants |
| `tracking()` | Trace, short trace, delta events, delivery confirmation, scan, sync status |
| `pickup()` | Pickup requests and generated AWB barcodes |
| `pickupLocations()` | Pickup and click-and-collect locations |
| `orders()` | Pickup orders and click-and-collect orders |
| `geography()` | Countries, counties, localities, streets, branches, PUDO points |
| `pricing()` | Shipping calculation and price tables |
| `financial()` | Invoices, cash account and cash receipts |
| `onlineShop()` | Customer and workstation lookups |
| `recipients()` | Recipient list |
| `complaints()` | Complaint status and submission |
| `marketing()` | Updated users and email unsubscribe |

Method-level endpoints, parameters and return types are in
[Resources](docs/RESOURCES.md).

## Error Handling

```php
use Daika7ana\Cargus\Exceptions\AuthenticationException;
use Daika7ana\Cargus\Exceptions\CargusException;
use Daika7ana\Cargus\Exceptions\TransportException;
use Daika7ana\Cargus\Exceptions\UnexpectedStatusException;
use Daika7ana\Cargus\Exceptions\ValidationException;

try {
    $barCode = $client->awbs()->create($awb);
} catch (AuthenticationException $e) {
    // Login failed, or a 401 survived the automatic retry.
} catch (ValidationException $e) {
    // Missing local credentials, or the API answered 400/422.
} catch (TransportException $e) {
    // Network failure; getPrevious() holds the cause.
} catch (UnexpectedStatusException $e) {
    // Other non-2xx status or an undecodable body.
} catch (CargusException $e) {
    // Any other SDK exception.
}
```

`400`/`422` become `ValidationException`, `401` becomes
`AuthenticationException`, and every other non-2xx status becomes
`UnexpectedStatusException`. See [Errors](docs/ERRORS.md).

## Framework Support

No Laravel, Symfony or other framework is in `require`, and no service provider
is needed. The SDK works in framework applications and standalone PHP alike. A
custom transport only has to implement PSR-18; the wrapped adapter cannot forward
the SDK timeout, so leave `ClientConfig::timeout` at `null` in that case.

## Testing

```bash
composer ci         # validate --strict -> pint --test -> phpstan -> phpunit tests/Unit
composer test:unit  # unit suite, no network
composer test:smoke # live suite, self-skips without credentials
```

Unit tests use a fake transport and need no credentials. The smoke suite reads
`CARGUS_USERNAME`, `CARGUS_PASSWORD` and `CARGUS_SUBSCRIPTION_KEY`; creating an
AWB also needs `CARGUS_SMOKE_ENABLE_WRITES=true`. There is no documented Cargus
sandbox. See [Testing](docs/TESTING.md).

## Documentation

| Guide | Purpose |
|---|---|
| [Quickstart](docs/QUICKSTART.md) | Install, configure, geography, pricing, AWB create and trace |
| [Installation](docs/INSTALLATION.md) | Requirements and dependencies |
| [Configuration](docs/CONFIGURATION.md) | `ClientConfig`, environment variables, timeouts, custom clients |
| [Authentication](docs/AUTHENTICATION.md) | Subscription key, login, tokens, NoAuth endpoints |
| [Usage](docs/USAGE.md) | AWB, tracking, documents, pickup locations, pricing, orders |
| [Resources](docs/RESOURCES.md) | Every method with its endpoint and return type |
| [DTOs](docs/DTOS.md) | DTO conventions, field maps and namespaces |
| [Errors](docs/ERRORS.md) | Exception hierarchy, status mapping, retry guidance |
| [Testing](docs/TESTING.md) | Unit tests, smoke tests and the helper classes |

## License

GPL-3.0-or-later. See [LICENSE](LICENSE).

Author: Dan-Radu Pana.
