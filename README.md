<div align="center">

<h1>Cargus PHP SDK</h1>

[![CI](https://github.com/daika7ana/cargus-php-sdk/actions/workflows/ci.yml/badge.svg)](https://github.com/daika7ana/cargus-php-sdk/actions/workflows/ci.yml)
[![PHP 8.3+](https://img.shields.io/badge/PHP-8.3%2B-777BB4?style=flat-square&logo=php)](https://www.php.net/)
[![PHPUnit](https://img.shields.io/badge/PHPUnit-12%E2%80%9313-0A7BBB?style=flat-square)](docs/TESTING.md)
[![Smoke Tests](https://img.shields.io/badge/Smoke%20Tests-Live%20API-orange?style=flat-square)](docs/TESTING.md)
[![PSR Standards](https://img.shields.io/badge/PSR-7%2F17%2F18-blue?style=flat-square)](https://www.php-fig.org/)
[![License](https://img.shields.io/badge/license-GPL--3.0--or--later-green?style=flat-square)](LICENSE)

A modern, type-safe PHP SDK for the **Cargus (Urgent Cargus) API**

</div>

> 🚀 Framework-agnostic, subscription-key and WebExpress-credential based, fully typed with DTOs, and built for production integrations.

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Configuration](#configuration)
- [Authentication](#authentication)
- [Supported Resources](#supported-resources)
- [Framework Support](#framework-support)
- [Testing](#testing)
- [Documentation](#documentation)

## Features

### Core Strengths

- ✅ **All 81 API Operations** — AWBs, documents, tracking, pickups, pickup locations, orders, geography, pricing, financial data, online shop, recipients, complaints and marketing across 13 accessors, plus client-level `authenticate()`, `authenticateJuridic()` and `tokenVerification()`
- ✅ **Bearer Authentication** — WebExpress login with a 24-hour token, re-authenticated on demand, with one retry after a `401`
- ✅ **PSR-18 HTTP Client** — Guzzle adapter by default, bring your own client if you prefer
- ✅ **Fully Typed DTOs** — readonly request and response objects with PascalCase wire serialization; list endpoints return `Collection` objects
- ✅ **Binary Response Helpers** — AWB labels as base64-decoded bytes or `Support\Document` with an `isPdf()` check
- ✅ **Subscription-Key-Only Endpoints** — explicit `*NoAuth()` methods that skip the login
- ✅ **Static Analysis with PHPStan** — Level 8 across `src/`
- ✅ **No Framework Coupling** — No framework in `require` and no framework imports in `src/`

### Perfect For

- 🎯 Domestic AWB, tracking and label workflows
- 🎯 Shipping price calculation and pickup order management
- 🎯 PHP applications of any framework, microservices and standalone projects

## Requirements

- **PHP 8.3+** with `ext-json`
- PSR-7 / PSR-17 / PSR-18 compatible environment
- An Azure APIM subscription key and WebExpress credentials

## Installation

### Via Composer

```bash
composer require daika7ana/cargus-php-sdk
```

## Quick Start

Working with the Cargus API is straightforward:

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

That's it! You have an authenticated client, resolved geography, and a typed
AWB trace. Resolve `countyId`, `localityId` and `countryId` from
`geography()->countries()`, `counties()` and `localities()`; `serviceId` is the
Cargus service code, unlabelled in the OpenAPI file.

## Configuration

### Base URL

The client targets production at `https://urgentcargus.azure-api.net` by
default. Only production is documented by Cargus; for a proxy or a local stub,
pass a different `baseUrl`:

```php
$config = new ClientConfig(baseUrl: 'http://localhost:8080', /* ... */);
```

### Credentials & Environment Variables

`ClientConfig::fromEnvironment()` is used by `Client::create()` and reads:

| Variable | Maps to |
|----------|---------|
| `CARGUS_USERNAME` | WebExpress username |
| `CARGUS_PASSWORD` | WebExpress password |
| `CARGUS_SUBSCRIPTION_KEY` | Azure APIM subscription key |
| `CARGUS_BASE_URL` | `baseUrl` |
| `CARGUS_TIMEOUT` | `timeout` |

A non-numeric `CARGUS_TIMEOUT` throws `InvalidArgumentException`. An
authenticated call with no stored token and empty credentials, or any call with
an empty subscription key, throws `ValidationException` locally before a
request is sent.

### Timeouts

`ClientConfig::timeout` is `null` by default. Precedence: the per-call argument
→ `ClientConfig::timeout` → the transport's own default (the bundled
`GuzzleHttpClient` uses 30 s to respond and 10 s to connect unless you pass
`timeout:`/`connectTimeout:`; an injected Guzzle client keeps its own
configuration). A bare PSR-18 client keeps its own timeout and cannot receive
the per-call argument — combining one with a non-null `ClientConfig::timeout`
throws `InvalidArgumentException`. `Client::send()` is `@internal`, so
resource methods expose no timeout parameter.

For a different budget, `$slow = $client->withTimeout(30.0);` returns a new
client sharing the transport, token store and credentials; the original keeps
its timeout.

## Authentication

Every request carries the Azure APIM header `Ocp-Apim-Subscription-Key`. The
WebExpress username and password are posted to `/api/LoginUser`, and the
response is a JSON string token valid for 24 hours, sent as
`Authorization: Bearer <token>` on every later call.

Nothing logs in at construction: the client re-authenticates an expired token
before sending, and answers a first `401` by logging in and retrying once.
`authenticateJuridic()` uses `POST /api/LoginUser/Juridic` for juridic entities,
and re-authentication replays the endpoint of the last successful login.
`tokenVerification()` checks the current token. Subscription-key-only endpoints
are exposed as explicit `*NoAuth()` methods.

### Token Persistence

Persist and restore a token yourself:

```php
use Daika7ana\Cargus\Auth\Token;

$token = $client->getToken();          // Token|null

if ($token !== null) {
    $client->setToken(new Token(       // restore
        $token->accessToken,
        $token->expiresAt,
    ));
}
```

## Supported Resources

| Accessor | Covers |
| --- | --- |
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

See [docs/RESOURCES.md](docs/RESOURCES.md) for endpoint-by-endpoint details.

### Error Handling

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
    print_r($e->errors);
} catch (TransportException $e) {
    // Network failure; getPrevious() holds the cause.
} catch (UnexpectedStatusException $e) {
    // Other non-2xx status or an undecodable body.
} catch (CargusException $e) {
    // Any other SDK exception.
}
```

All exceptions extend `Daika7ana\Cargus\Exceptions\CargusException`, and the
HTTP ones carry `$response`, `$retryAfter` and a typed `CargusError` under
`$error`.

## Framework Support

### Zero Framework Dependencies

The package keeps no framework in `require` and no framework imports in `src/`, so it works equally well in:

- 🌐 **Laravel** applications
- 🌐 **Symfony** projects
- 🌐 **Standalone PHP** applications
- 🌐 **Microservices**
- 🌐 Any **PHP 8.3+** environment

### Custom HTTP Client

Pass any PSR-18 client (auto-wrapped), or the bundled `GuzzleHttpClient` with
your own Guzzle instance:

```php
use Daika7ana\Cargus\Client;
use Daika7ana\Cargus\Config\ClientConfig;
use GuzzleHttp\Client as GuzzleClient;

$client = Client::create(
    httpClient: new GuzzleClient(['timeout' => 10.0]),
    config: new ClientConfig(
        username: 'your-webexpress-user',
        password: 'your-webexpress-password',
        subscriptionKey: 'your-azure-subscription-key',
        // timeout stays null: the adapter cannot forward one.
    ),
);
```

A bare PSR-18 client keeps its own timeout, so a non-null
`ClientConfig::timeout` alongside one throws `InvalidArgumentException`.

## Testing

### Run Static Analysis

```bash
composer stan
# or
vendor/bin/phpstan --no-progress
```

PHPStan is configured via `phpstan.neon` and analyzes the `src/` directory at level 8.

### Run Full Test Suite

```bash
composer test
# or
php vendor/bin/phpunit
```

Pass PHPUnit arguments through Composer with `--`:

```bash
composer test -- --filter=AwbResourceTest
composer test -- tests/Unit/Resources
```

### Run The Same Checks As CI

```bash
composer ci
```

Available Composer scripts:

- `composer pint` runs Pint in test mode
- `composer stan` runs PHPStan against `src/`
- `composer test` runs the full PHPUnit suite (unit plus the self-skipping smoke suite)
- `composer test:unit` runs the unit test suite
- `composer test:smoke` runs `tests/Smoke` against the live API (needs credentials)
- `composer ci` runs the same local checks as CI

### Test Environment Variables

None for the unit suite, which is fully offline — no credentials, no network.
`tests/Smoke` calls the live API but self-skips unless `CARGUS_USERNAME`,
`CARGUS_PASSWORD` and `CARGUS_SUBSCRIPTION_KEY` are set; creating an AWB also
needs `CARGUS_SMOKE_ENABLE_WRITES=true` and is deleted in `finally`. There is
no documented Cargus sandbox. See [docs/TESTING.md](docs/TESTING.md) for the
fake HTTP client and fixtures.

## Documentation

Complete guides and references:

| Guide | Purpose |
|-------|---------|
| 📖 [Quickstart](docs/QUICKSTART.md) | Install, configure, geography, pricing, AWB create and trace |
| 🔧 [Installation](docs/INSTALLATION.md) | Requirements and dependencies |
| ⚙️ [Configuration](docs/CONFIGURATION.md) | `ClientConfig`, environment variables, timeouts, custom clients |
| 🔐 [Authentication](docs/AUTHENTICATION.md) | Subscription key, login, tokens, NoAuth endpoints |
| 📚 [All Docs](docs/USAGE.md) | Workflow guidance for every resource area |
| 🔗 [Resources](docs/RESOURCES.md) | Method-level public API reference |
| 📦 [DTOs](docs/DTOS.md) | DTO conventions, field maps and namespaces |
| ❌ [Errors](docs/ERRORS.md) | Exception hierarchy and handling |
| ✅ [Testing](docs/TESTING.md) | Unit, smoke and release verification |

---

<div align="center">

Made with ❤️

**Questions?** [Check the docs](docs/USAGE.md) or [open an issue](../../issues)

</div>
