# Authentication

Cargus uses two credentials. Both are required to log in; resource calls can rely
on a stored token alone:

| Credential | Sent as | Source |
|---|---|---|
| Azure API Management subscription key | `Ocp-Apim-Subscription-Key` header, on every request | urgentcargus portal → PRODUCTS → Primary Key |
| WebExpress username and password | JSON body of `POST /api/LoginUser` | The Cargus WebExpress account, not the portal login |

The subscription key is not a bearer token and does not identify a user. The
username and password are not sent on resource requests; only the token derived
from them is.

## Subscription key

Pass the key to `ClientConfig::$subscriptionKey` or set
`CARGUS_SUBSCRIPTION_KEY`:

```php
use Daika7ana\Cargus\Client;
use Daika7ana\Cargus\Config\ClientConfig;

$client = Client::create(config: new ClientConfig(
    username: 'your-webexpress-user',
    password: 'your-webexpress-password',
    subscriptionKey: 'your-azure-subscription-key',
));
```

`Client::createRequest()` adds `Ocp-Apim-Subscription-Key` and
`Ocp-Apim-Trace: true` to every request. The login request carries the same two
headers, and so do the NoAuth endpoints. With an empty key, every request throws
`ValidationException` before anything is sent.

## Login

`POST /api/LoginUser` takes:

```json
{"UserName": "your-webexpress-user", "Password": "your-webexpress-password"}
```

A `200` response carries a JSON-encoded string. The SDK stores it as a
`Daika7ana\Cargus\Auth\Token` valid for 24 hours
(`Token::LIFETIME_HOURS`) and sends it as:

```text
Authorization: Bearer <token>
```

You can log in explicitly. `authenticate()` replaces the credentials used by
later re-authentication:

```php
$client->authenticate('your-webexpress-user', 'your-webexpress-password');
```

An empty username or password throws `ValidationException` before anything is
sent. Any non-`200` answer from `/api/LoginUser` raises
`AuthenticationException`.

## Login on demand and the 401 retry

You do not have to call `authenticate()`, and nothing logs in at construction.
`Client::send()` does the following:

1. Checks the stored token. With no token, username and password must be
   configured (`ValidationException` otherwise) and the request goes out without
   an `Authorization` header.
2. Replaces an expired token with a fresh login before sending when credentials
   are configured. A token within 60 seconds of its expiry is treated as
   expired.
3. On a `401`, logs in with the configured credentials and retries the request
   exactly once; without credentials the `401` reaches the exception mapper.

So a new client's first call goes out without an `Authorization` header and
relies on the `401` path to log in. Call `authenticate()` up front if you do not
want that extra request.

Because of step 3, a `401` that reaches the exception mapper means the retry also
failed or no credentials are configured. Re-login replays the endpoint of the
most recent successful login: `authenticate()` uses `/api/LoginUser` and
`authenticateJuridic()` uses `/api/LoginUser/Juridic`. The credentials are the
ones most recently set by `ClientConfig`, `authenticate()` or
`authenticateJuridic()`; a failed login leaves the previous credentials and
token untouched.

## Juridic login

`POST /api/LoginUser/Juridic` takes the same `UserName`/`Password` body and
returns a token of the same shape. Use it when the account is a juridic entity:

```php
$client->authenticateJuridic('your-webexpress-user', 'your-webexpress-password');
```

`authenticateJuridic()` shares its request path with `authenticate()`. Any
non-`200` answer raises `AuthenticationException`, and so does a `200` whose body
decodes to anything other than a non-empty string. Later re-authentication
replays `/api/LoginUser/Juridic`, so a juridic-only account keeps working.

## Token verification

`GET /api/TokenVerification` returns a JSON boolean. The SDK exposes it as:

```php
if (! $client->tokenVerification()) {
    // The token is no longer accepted; log in again.
}
```

The call goes through `Client::send()`, so it requires the username and
password and can trigger the `401` re-login path. It returns `true` only when
the body is the JSON literal `true`.

## Token storage

| Member | Purpose |
|---|---|
| `Client::getToken(): ?Token` | Current token, or `null` before login |
| `Client::setToken(Token\|string $token): self` | Replace the token; a raw string is wrapped by `Token::fromTokenString()` |
| `Token::$accessToken` | The token string |
| `Token::$tokenType` | `Bearer` |
| `Token::$expiresAt` | `DateTimeImmutable`, 24 hours after login, or `null` |
| `Token::isExpired()` | `true` when `expiresAt` is within 60 seconds |

`setToken('')` throws `AuthenticationException`. Tokens are kept in memory by
default through `InMemoryTokenStore`. Pass a custom
`Daika7ana\Cargus\Auth\TokenStoreInterface` as `tokenStore:` to
`Client::create()` to persist the token across processes; a `Token` with
`expiresAt: null` never expires client-side.

A client configured with only the subscription key can `setToken()` a persisted
token and call resources normally: requests with a stored token are not
pre-validated against configured credentials. If the API rejects that token, no
login is attempted and the `401` becomes an `AuthenticationException`.

## Unauthenticated endpoints

`Client::getNoAuth()` and `Client::postNoAuth()` send only the subscription key
and strip the `Authorization` header. The resources expose NoAuth variants:

| Accessor | Method | Endpoint |
|---|---|---|
| `geography()` | `countiesNoAuth()` | `GET /api/NoAuth/GetCounties` |
| `geography()` | `localitiesNoAuth()` | `GET /api/NoAuth/GetLocalities` |
| `pricing()` | `calculateNoAuth()` | `POST /api/NoAuth/PostShippingCalculation` |
| `tracking()` | `shortTraceNoAuth()` | `GET /api/NoAuth/GetShortAwbTrace` |
| `tracking()` | `traceNoAuth()` | `GET /api/NoAuth/GetAwbTrace` |
| `awbs()` | `generateReturnNoAuth()` | `POST /api/NoAuth/GenerateRwb` |
| `pickup()` | `requestIndividualNoAuth()` | `POST /api/NoAuth/IndividualWithGetAwb` |

The three `documents()->pdf*()` methods also use NoAuth, but take the bearer
token as the `Token` query parameter instead of the `Authorization` header. See
[Usage](USAGE.md#labels-and-documents).

## Environment variables

| Variable | Maps to |
|---|---|
| `CARGUS_USERNAME` | `ClientConfig::$username` |
| `CARGUS_PASSWORD` | `ClientConfig::$password` |
| `CARGUS_SUBSCRIPTION_KEY` | `ClientConfig::$subscriptionKey` |

`CARGUS_BASE_URL` and `CARGUS_TIMEOUT` are covered in
[Configuration](CONFIGURATION.md#environment-variables). With all variables set,
`Client::create()` needs no arguments.

## Missing credentials

| Condition | Exception | When |
|---|---|---|
| Empty subscription key | `ValidationException` | Before any request, including NoAuth and login |
| Empty username or password | `ValidationException` | Before an authenticated request that has no stored token; NoAuth calls do not need them |

The messages name the missing value, for example:

```text
Cargus username and password are required for authenticated calls.
```

## Redaction

The subscription key, username, password and bearer token travel over HTTPS
only. The SDK does not log them, but a PSR-18 middleware, Guzzle handler or
application logger you add can. Redact `Ocp-Apim-Subscription-Key`,
`Authorization`, `UserName` and `Password` from logs and request dumps.
