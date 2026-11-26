# AGENTS.md

## Project

Typed PHP 8.3+ SDK for the Cargus (Urgent Cargus / UrgentOnline) courier API
(`daika7ana/cargus-php-sdk`, namespace `Daika7ana\Cargus`). Library only: no
server, no web entrypoint, no framework in `require`. PSR-18 core, one resource
class per API area, readonly DTOs.

Base URL `https://urgentcargus.azure-api.net` (`ClientConfig::BASE_URL`), so
paths are absolute from the host: `/api/LoginUser`, `/api/Counties`, and so on.
Every request, including login, carries the Azure API Management header
`Ocp-Apim-Subscription-Key`.

All 81 operations of the UrgentOnline API are covered across the 13 resource
accessors; the OpenAPI file in the repo root is the wire reference. Anything
not confirmed against the live API is marked unverified in the quirks below.

## Commands

```bash
composer install
composer ci         # validate --strict, pint --test, phpstan, phpunit tests/Unit
composer pint       # style check only, never fixes
composer stan       # PHPStan level 8 over src/
composer test       # full PHPUnit suite (Unit + Smoke)
composer test:unit  # tests/Unit only
composer test:smoke # tests/Smoke, self-skips without credentials
vendor/bin/pint     # the only way to auto-fix style
```

`composer ci` runs, in order:

```json
[
    "composer validate --strict",
    "@pint",
    "@stan",
    "@test:unit"
]
```

- `composer.lock` is gitignored, so CI resolves fresh; keep `guzzlehttp/psr7` at
  `>=2` because `Client::create()` instantiates `GuzzleHttp\Psr7\HttpFactory`.
- Quality gates: PHPStan level 8 over `src/` (vendor/tests excluded), Laravel
  Pint (`per` preset), PHPUnit strict (`.dist`), `composer validate --strict`.
  CI runs on branch `master` and skips every job for commits/PRs starting
  `docs` or `chore`, and for messages containing `[skip ci]` / `[skip-ci]`.

## Layout

`src/` holds `Client.php`, `Config/`, `Auth/`, `Http/`, `Support/`, `Exceptions/`,
`DTOs/<Area>/` and `Resources/`. `tests/Support/` holds the fakes, `tests/Unit/`
mirrors `src/`, `tests/Smoke/` is the live suite, and `docs/` holds the
user-facing guides listed in the README.

Resource accessors: `awbs()`, `documents()`, `tracking()`, `pickup()`,
`pickupLocations()`, `orders()`, `geography()`, `pricing()`, `financial()`,
`onlineShop()`, `recipients()`, `complaints()`, `marketing()`.

## Conventions

- Never import Symfony, Laravel, or another framework into `src/`, `tests/`, or
  docs, and never make one a runtime dependency; framework integration is out of
  scope. No new runtime dependencies without an explicit decision. Requests are
  built through the injected PSR-17 factories and absolute URIs assembled
  internally; callers never pass a URL.
- DTOs are `final readonly` with `fromArray()`; request DTOs add `toArray()`
  that drops null optionals.
- `docs/` is user-facing documentation: behavior changes go in the matching file,
  not just in code. `CHANGELOG.md` is Keep a Changelog, newest first, tags `vX.Y.Z`.
- Commits and PR titles use Conventional Commits (`<type>(<scope>): <subject>`,
  lowercase imperative, no trailing period).

## HTTP Transport And Timeouts

- `Client` builds requests with the injected PSR-17 `RequestFactoryInterface` /
  `StreamFactoryInterface`, consumes PSR-7 `ResponseInterface`, and depends only
  on PSR-18 `Psr\Http\Client\ClientInterface` — never a concrete client.
- `src/Http/HttpClientInterface.php` widens PSR-18 with
  `sendRequest(RequestInterface $request, ?float $timeout = null)`.
  `src/Http/GuzzleHttpClient.php` is the bundled transport (Guzzle `http_errors`
  and redirects disabled, so 4xx/5xx reach the mappers);
  `src/Http/Psr18HttpClientAdapter.php` wraps any other PSR-18 client, rejects a
  non-null per-call timeout with `InvalidArgumentException`, and rethrows a
  foreign `ClientExceptionInterface` as `TransportException` (original as
  `previous`). `TransportException` implements `ClientExceptionInterface`.
- `Client::__construct()` throws `InvalidArgumentException` when
  `ClientConfig::timeout` is non-null and the client is not this package's
  `HttpClientInterface`. `ClientConfig::timeout` is `?float`, default `null`;
  `connectTimeout` exists only on
  `GuzzleHttpClient::__construct(?GuzzleClient $guzzleClient = null, ?float $timeout = null, ?float $connectTimeout = null)`,
  which applies `DEFAULT_TIMEOUT = 30.0` / `DEFAULT_CONNECT_TIMEOUT = 10.0` only
  when it creates its own Guzzle client. Precedence: per-call
  `Client::send($request, $t)` → `ClientConfig::timeout` → transport constructor
  arguments → 30.0/10.0.
- `guzzlehttp/guzzle` and `guzzlehttp/psr7` are runtime dependencies (default
  transport and `GuzzleHttp\Psr7\HttpFactory`), so `Client::create()` needs no
  arguments.

## API Quirks

- Auth: `POST /api/LoginUser` with a JSON body `{"UserName": ..., "Password": ...}`
  using the WebExpress user/password (not the subscription key). The 200 body is
  a JSON-encoded string token valid for 24 hours; every later request sends
  `Authorization: Bearer <token>`. There is no eager login: `Client::send()`
  re-authenticates an expired token and, on a 401, re-logs in and retries exactly
  once — both only when username and password are configured.
  `GET /api/TokenVerification` returns a JSON boolean and is exposed as
  `Client::tokenVerification()`.
- Credentials: `ClientConfig` takes `username`, `password`, `subscriptionKey`,
  `baseUrl`, `timeout`. `fromEnvironment()` reads `CARGUS_USERNAME`,
  `CARGUS_PASSWORD`, `CARGUS_SUBSCRIPTION_KEY`, `CARGUS_BASE_URL`,
  `CARGUS_TIMEOUT`; a non-numeric timeout throws `InvalidArgumentException`.
  A request with no stored token and an empty username or password throws
  `ValidationException` locally, before any request; an empty subscription key
  always does. `getNoAuth()` / `postNoAuth()` require only the subscription key.
- `POST /api/LoginUser/Juridic` (login for juridic entities) takes the same
  `AccountModel` body and returns a JSON string token like `/api/LoginUser`;
  exposed as `Client::authenticateJuridic()`. Re-authentication replays the
  endpoint of the last successful login, so a juridic login keeps using
  `/api/LoginUser/Juridic`.
- Status mapping: 401 → `AuthenticationException`, 400/422 → `ValidationException`,
  any other non-2xx → `UnexpectedStatusException`. Requests carrying a stored
  token are not pre-validated against configured credentials; a 401 without
  credentials surfaces as `AuthenticationException` with no login attempt. Binary
  PDF endpoints return `Support\Document` (`isPdf()` sniffs `%PDF`).
- `GET /api/Counties` and `GET /api/NoAuth/GetCounties` require the `countryId`
  query parameter and return an array of `{County: [{CountyId, Name, Abbreviation}], message}`
  wrappers, which the resource flattens into `County` DTOs. The OpenAPI file lists
  no `name` filter.
- `createRequest()`, `withJsonBody()` and `send()` are `@internal`. Query arrays
  serialize as `key[]=` for lists, `1`/`0` for bools, nulls dropped.

- Wrapper responses: `/api/Counties` and `/api/Localities` items are
  `{County|Locality: [...], message}` wrappers, flattened by the resources.
  `/api/Localities/DetailsLocality` uses capital `LocalityId`; NoAuth
  `GetLocalities` requires both `countryId` and `countyId`; `/api/Streets`
  items carry `PostalNumbers: [{FromNo, ToNo, PostalCode}]`.
- `PriceTableEntity` is misspelled `TariffVerion` on the wire.
- Date pagination casing varies per endpoint: Invoices `FromDate`/`ToDate`,
  Marketing `startDate`/`endDate`/`rowsPerPage`, most others
  `pageNumber`/`itemsPerPage`. Dates are caller-formatted strings.
- Query-only writes: `PUT /api/Orders`, `/api/Orders/PutAll`,
  `/api/Orders/Cancel`, `POST /api/PickupLocations/AssignToUser` and
  `POST /api/AwbTrace/PostConfirmationDelivery` take no body; the SDK sends an
  empty JSON array body — unverified whether the live API tolerates it.
- 204 handling: list endpoints map 204 to an empty collection, single-item
  lookups (`GetByOrderId`, `GetAwbSyncStatusByBarCode`, `GetRoutingAddress`) to
  `null`, writes to `''`/`0`/`[]` per their declared type. The JSON document
  methods throw `UnexpectedStatusException` on `204` and on a non-string or
  empty `200` body instead of returning `''`; so do `tracking()->scan()` and the
  `documents()->pdf*()` methods, whose `204` used to yield an empty `Document`.
- `/api/AwbDocuments`, `/api/AwbDocuments/GetDHL`, `/api/OrderDocuments`,
  `/api/InvoiceDocuments` and `/api/AwbScan` declare `string` + `format: byte`
  bodies; verified live as base64, which the SDK decodes to raw bytes (a
  non-base64 payload passes through unchanged).
- `/api/PDF/{AwbDocuments,OrderDocuments,InvoiceDocuments}` take the token as
  a `Token` query parameter (no bearer header); the SDK routes them through
  `getNoAuth()` with the caller-supplied token and throws on `204`.
- `POST /api/Awbs` and `POST /api/AwbPickup` return a bare JSON string
  barcode; `/api/AwbPickup/WithGetAwb` returns an untyped object (204 → `[]`);
  `POST /api/CashReceipts` returns a bare integer id.
- AWB reads: `GET /api/Awbs/GetByDate` mixes `FromDate`/`ToDate` with
  `pageNumber`/`itemsPerPage`; incoming variants add a required `DestId`;
  `GET /api/AwbRetur` takes a single `data` date-time. `PUT /api/Awbs/PutReschedule`
  queries `BarCode`/`data`; `DELETE /api/Awbs` queries `barCode`.
- `AwbExtended` Sender/Recipient use `FullAddress`; `DTOs\Awb\Address` covers
  the `Address`, `FullAddress` and `AwbGeneralAddress` (PostalCode, no
  CodPostal) shapes. `AwbTurModel` embeds the server-computed `ShippingCost`
  inline in `toArray()`.
- Pickup requests reuse `DTOs\PickupLocation\AddressExtended` for
  Sender/Recipient; `Order::totalWeight` is `int` per spec, so fractional
  weights truncate.
- Enums: `OrdStatus` (0–5), PUDO `PointType` (1–5) and `PaymentType` (1–4) are
  spec-defined but unlabelled, so they stay `int`; `AcceptedPaymentType` is a
  nested `{Cash, Card, Online}` object under `ServiceCOD`.

## Tests

- Unit tests never touch the network. `TestClientFactory::create($handler)`
  returns `[Client, FakeHttpClient]` with fake username/password and the
  `test-subscription-key`; `Fixture::json()/raw()` build responses.
- Assert method, path, query, body and headers on `$httpClient->lastRequest`;
  every new endpoint needs such a test covering both request and response
  mapping. `tests/Smoke/` self-skips unless `CARGUS_USERNAME`,
  `CARGUS_PASSWORD` and `CARGUS_SUBSCRIPTION_KEY` are set; there is no documented
  sandbox, so a green unit test proves the code matches its fixture, not the API.
  `composer ci` must pass before done.
- `tests/Smoke/` reads run freely with credentials; writes require
  `CARGUS_SMOKE_ENABLE_WRITES=true` and are deleted in `finally`. The smoke
  client wraps the bundled transport in `ThrottledHttpClient`, which retries a
  `409`/`429` up to three attempts with 2s then 4s backoff (test support only;
  `src/` stays retry-free) and authenticates eagerly because the live API answers
  `409` rather than `401` without a bearer token. `cashAccount` needs a `barCode`
  live: without one the API answers `409` even though the OpenAPI marks it
  optional. `/api/Invoices` is rate-limited on the shared account and the test
  skips on `429`. Optional fixtures: `CARGUS_SMOKE_BARCODE`,
  `CARGUS_SMOKE_LOCALITY_ID`, `CARGUS_SMOKE_COUNTRY_ID`,
  `CARGUS_SMOKE_SERVICE_ID`, `CARGUS_SMOKE_RECIPIENT_NAME`,
  `CARGUS_SMOKE_RECIPIENT_PHONE`, `CARGUS_SMOKE_STREET_NAME`. Never smoke-test
  `pickup()`/`orders()` creation or AwbPickup: they dispatch a physical courier.
