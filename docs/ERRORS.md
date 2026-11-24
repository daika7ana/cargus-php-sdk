# Errors

Every Cargus-specific exception extends
`Daika7ana\Cargus\Exceptions\CargusException`, which extends `Exception`.

```text
Exception
└── CargusException
    ├── AuthenticationException
    ├── TransportException          (implements ClientExceptionInterface)
    ├── UnexpectedStatusException
    └── ValidationException
```

| Exception | Raised when |
|---|---|
| `AuthenticationException` | Login failed, or an authenticated request answered `401` after the one automatic re-login |
| `ValidationException` | Local credentials are missing, or the API answered `400`/`422` |
| `UnexpectedStatusException` | Any other non-2xx status, a JSON endpoint returned a body that is not valid JSON, or an AWB boolean write returned a non-boolean payload |
| `TransportException` | DNS, TLS, timeout, connection reset or another transport failure |

## Status mapping

`Daika7ana\Cargus\Support\ApiResponseMapper::assertStatus()` maps the HTTP status:

| Status | Exception |
|---|---|
| `401` | `AuthenticationException` |
| `400`, `422` | `ValidationException` |
| Any other non-2xx | `UnexpectedStatusException` |

Each endpoint passes its expected success status: `200` by default, or
`[200, 204]` where it handles `204`. A status that is neither expected nor in
the table is `UnexpectedStatusException`.

`Client::send()` already retries once on `401`: it logs in again and resends the
request. A `401` that becomes an `AuthenticationException` means the retry also
failed.

Login has its own paths:

- `Client::authenticate()` raises `AuthenticationException` for any non-`200`
  answer from `POST /api/LoginUser`, and for an invalid JSON body or a body that
  is not a non-empty token string.
- `Client::authenticateJuridic()` uses the same login path as `authenticate()`:
  any non-`200` answer, an invalid JSON body, or a `200` that does not decode to a
  non-empty token string raises `AuthenticationException`.

## Local validation

These checks run before a request is sent:

| Condition | Exception |
|---|---|
| Empty `subscriptionKey` | `ValidationException` |
| Empty `username` or `password` on an authenticated call with no stored token | `ValidationException` |
| Empty `username` or `password` passed to `authenticate()` / `authenticateJuridic()` | `ValidationException` |
| `ClientConfig::timeout` set with a foreign PSR-18 client | `InvalidArgumentException` from `Client::__construct()` |
| Non-null per-call timeout reaching `Psr18HttpClientAdapter` | `InvalidArgumentException` from `sendRequest()` |
| Non-numeric `CARGUS_TIMEOUT` | `InvalidArgumentException` from `ClientConfig::fromEnvironment()` |
| `setToken('')` | `AuthenticationException` |

NoAuth calls check only the subscription key. The validation message names the
missing value, for example:

```text
Cargus subscription key is required. Pass it to ClientConfig or CARGUS_SUBSCRIPTION_KEY.
```

## Typed error payload

`AuthenticationException`, `ValidationException` and `UnexpectedStatusException`
expose the decoded failure body as `$error`, a
`Daika7ana\Cargus\DTOs\Common\Errors\CargusError` (or `null` when no response
body was read). It carries `message`, the status-derived `code`, the raw `raw`
payload and the `errors` field map, and offers `isValidationRelated()` /
`isAuthenticationRelated()` classifiers.

## `AuthenticationException`

| Member | Meaning |
|---|---|
| `$response` | The failing response, or `null` when login failed before one existed |
| `$retryAfter` | `Retry-After` in seconds, when present |
| `$error` | The decoded error body, when there was one |
| `getCode()` | The HTTP status, or `0` |

```php
use Daika7ana\Cargus\Exceptions\AuthenticationException;

try {
    $client->geography()->countries();
} catch (AuthenticationException $e) {
    if ($e->response !== null) {
        // The API rejected the token or the login request.
    }
}
```

## `ValidationException`

| Member | Meaning |
|---|---|
| `$errors` | Field errors from a body shaped `{"errors": {"field": ["message"]}}`, empty otherwise |
| `$response` | The failing response, or `null` for a local check |
| `$retryAfter` | `Retry-After` in seconds, when present |
| `$error` | The decoded error body, when there was one |

`ApiResponseMapper` reads the exception message from the first non-empty string
under `message`, `Message`, `error` or `Error`; it falls back to
`Validation failed`.

```php
use Daika7ana\Cargus\Exceptions\ValidationException;

try {
    $client->awbs()->create($awb);
} catch (ValidationException $e) {
    foreach ($e->errors as $field => $messages) {
        echo $field, ': ', implode(', ', $messages), PHP_EOL;
    }
}
```

The Cargus API commonly returns no field map, so `$errors` is often empty even
when the request was rejected.

## `UnexpectedStatusException`

Besides non-2xx statuses and invalid JSON, `awbs()->reschedule()`,
`updateReference()` and `delete()` raise it when a write returns a payload that
is not a boolean (`true`, `"True"`, `"true"`, `1`, `"1"` or their false
counterparts). `updateParcelCodes()` returns the barcode echoed by the live API
instead of a boolean.

| Member | Meaning |
|---|---|
| `$response` | The offending response, when one exists |
| `$retryAfter` | `Retry-After` in seconds, when present |
| `$error` | The decoded error body, when there was one |
| `getCode()` | The HTTP status |

It is also raised when `ApiResponseMapper::decodeJson()` cannot decode the body.
Its message then begins `Response body is not valid JSON:` and the previous
`JsonException` is not attached.

## `TransportException`

`Daika7ana\Cargus\Exceptions\TransportException` implements
`Psr\Http\Client\ClientExceptionInterface`, so code that catches PSR-18 client
failures catches it too. The original exception, when there is one, is available
through `getPrevious()`.

```php
use Daika7ana\Cargus\Exceptions\TransportException;

try {
    $client->geography()->countries();
} catch (TransportException $e) {
    $e->getMessage();
    $e->getPrevious(); // Guzzle or PSR-18 exception, when available
}
```

With the bundled `GuzzleHttpClient`, HTTP error statuses are not transport
failures: `http_errors` is disabled, so 4xx/5xx responses reach the status
mapper. A foreign PSR-18 client that throws on 4xx/5xx is wrapped as
`TransportException` by `Psr18HttpClientAdapter`.

## Retrying

| Operation | Guidance |
|---|---|
| Read endpoints: geography, pricing reads, AWB reads, traces, labels, invoices, cash account, recipients, online shop | Safe to retry |
| `authenticate()`, `authenticateJuridic()`, `tokenVerification()` | Safe to retry |
| `awbs()->create()`, `createInternational()`, `withGetAwb()`, `pickup()->request*()`, `orders()->create*()` | Reconcile first: a transport failure does not prove the request did not reach Cargus |
| `delete()`, `cancel()`, `reschedule()`, `updateParcelCodes()`, `updateReference()`, `pickupLocations()->save()`/`update()`/`delete()` | Retry only after confirming the current state |
| `documents()->pdf*()` | Safe to retry; a `204` yields an empty `Document` |

`pickup()->request()` dispatches a physical courier: never retry it
automatically.

## Catch all SDK failures

```php
use Daika7ana\Cargus\Exceptions\AuthenticationException;
use Daika7ana\Cargus\Exceptions\CargusException;
use Daika7ana\Cargus\Exceptions\TransportException;
use Daika7ana\Cargus\Exceptions\UnexpectedStatusException;
use Daika7ana\Cargus\Exceptions\ValidationException;

try {
    $barCode = $client->awbs()->create($awb);
} catch (AuthenticationException $e) {
    // Login or token failure.
} catch (ValidationException $e) {
    // Missing local credentials or a 400/422 answer.
} catch (TransportException $e) {
    // Network failure; the request may or may not have reached the API.
} catch (UnexpectedStatusException $e) {
    // Other non-2xx status or an undecodable body.
} catch (CargusException $e) {
    // Any SDK exception not matched above.
}
```
