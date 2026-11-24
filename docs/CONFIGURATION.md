# Configuration

`Daika7ana\Cargus\Config\ClientConfig` is a `final readonly` value object with
public properties. It has no `with*()` methods: build a new instance to change a
value.

| Option | Default | Purpose |
|---|---|---|
| `username` | `''` | WebExpress username, sent to `POST /api/LoginUser` |
| `password` | `''` | WebExpress password, sent to `POST /api/LoginUser` |
| `subscriptionKey` | `''` | Azure APIM key, sent in `Ocp-Apim-Subscription-Key` on every request |
| `baseUrl` | `https://urgentcargus.azure-api.net` | API base URL (`ClientConfig::BASE_URL`) |
| `timeout` | `null` | Request timeout in seconds; `null` uses the transport default |

## Construct a client

`Client::create()` takes named arguments. `config:` is the fourth parameter, so
always pass it by name:

```php
use Daika7ana\Cargus\Client;
use Daika7ana\Cargus\Config\ClientConfig;

$client = Client::create(config: new ClientConfig(
    username: 'your-webexpress-user',
    password: 'your-webexpress-password',
    subscriptionKey: 'your-azure-subscription-key',
));
```

With no `config` argument, `Client::create()` calls
`ClientConfig::fromEnvironment()`. Constructing `new ClientConfig()` yourself
does not read the environment.

`$client->getConfig()` returns the configuration the client uses. `Client`
exposes no setters for it; replace credentials with `authenticate()` or
`authenticateJuridic()` when they change at runtime. See
[Authentication](AUTHENTICATION.md).

## Environment variables

| Variable | Maps to | Behavior when unset or empty |
|---|---|---|
| `CARGUS_USERNAME` | `username` | Empty string; an authenticated call with no stored token throws `ValidationException` |
| `CARGUS_PASSWORD` | `password` | Empty string; an authenticated call with no stored token throws `ValidationException` |
| `CARGUS_SUBSCRIPTION_KEY` | `subscriptionKey` | Empty string; every request throws `ValidationException` |
| `CARGUS_BASE_URL` | `baseUrl` | Falls back to `ClientConfig::BASE_URL`; a trailing `/` is trimmed |
| `CARGUS_TIMEOUT` | `timeout` | `null`, so the transport default applies |

```bash
export CARGUS_USERNAME=your-webexpress-user
export CARGUS_PASSWORD=your-webexpress-password
export CARGUS_SUBSCRIPTION_KEY=your-azure-subscription-key
```

```php
$client = Client::create(); // reads the variables above
```

A non-numeric, non-empty `CARGUS_TIMEOUT` throws `InvalidArgumentException` from
`fromEnvironment()`. A set value is cast to `float` and is a non-null config
timeout for the checks under [Timeouts](#timeouts).

## Base URL

The default is `https://urgentcargus.azure-api.net`. Paths are absolute from the
host, so `geography()->counties()` calls
`https://urgentcargus.azure-api.net/api/Counties`. Override it for a proxy or a
test gateway:

```php
$config = new ClientConfig(
    username: 'your-webexpress-user',
    password: 'your-webexpress-password',
    subscriptionKey: 'your-azure-subscription-key',
    baseUrl: 'https://cargus-proxy.internal',
);
```

The client joins `baseUrl` and the endpoint after trimming a trailing `/`, so
both `https://host` and `https://host/` produce `https://host/api/...`.
`fromEnvironment()` also trims `CARGUS_BASE_URL` when it reads it.

## Timeouts

`ClientConfig::timeout` is `?float` and defaults to `null`. The bundled transport
is `Daika7ana\Cargus\Http\GuzzleHttpClient`, whose constructor is:

```text
new GuzzleHttpClient(
    ?GuzzleHttp\Client $guzzleClient = null,
    ?float $timeout = null,
    ?float $connectTimeout = null,
)
```

When it creates its own Guzzle client, it applies `timeout: 30.0` and
`connect_timeout: 10.0` unless you pass values. Precedence, highest first:

1. Per-call `float $timeout` on `Client::get()` / `post()` / `put()` /
   `delete()` / `getNoAuth()` / `postNoAuth()` (and the internal `send()`).
2. `ClientConfig::timeout`.
3. `GuzzleHttpClient` constructor arguments.
4. `GuzzleHttpClient::DEFAULT_TIMEOUT` (`30.0`) and
   `DEFAULT_CONNECT_TIMEOUT` (`10.0`).

Resource methods such as `awbs()->create()` do not take a timeout. Set the
default on `ClientConfig`, or use a low-level method:

```php
$client = Client::create(config: new ClientConfig(
    username: 'your-webexpress-user',
    password: 'your-webexpress-password',
    subscriptionKey: 'your-azure-subscription-key',
    timeout: 10.0,
));

$response = $client->get('/api/Countries', timeout: 15.0);
```

`Client::withTimeout(?float $timeout)` returns a derived client sharing the
transport, PSR-17 factories, token store and credentials, with only the default
timeout replaced. `ClientConfig::withTimeout()` does the same for the config
alone. Neither builds a transport nor logs in again: the derived client starts
with the same token.

```php
$slow = $client->withTimeout(60.0);
$response = $slow->get('/api/Invoices');
```

There is no per-call connect timeout; the connect timeout comes from
`GuzzleHttpClient`'s `connectTimeout:` argument.

A foreign PSR-18 client cannot receive either SDK timeout. Two combinations
throw `InvalidArgumentException`:

- a non-null `ClientConfig::timeout` together with a foreign client, thrown by
  `Client::__construct()`;
- a non-null timeout passed to `Client::withTimeout()` on a foreign client,
  thrown by `withTimeout()` (`withTimeout(null)` is allowed);
- a non-null per-call timeout reaching `Psr18HttpClientAdapter`, thrown by its
  `sendRequest()`.

Because `Client::create()` falls back to `fromEnvironment()`, a set
`CARGUS_TIMEOUT` also counts as a non-null config timeout when you inject a
foreign client.

## Custom HTTP client

Pass any PSR-18 client as `httpClient:`. One that does not implement
`Daika7ana\Cargus\Http\HttpClientInterface` is wrapped in
`Psr18HttpClientAdapter`, which uses that client's own timeout:

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

The adapter rethrows a foreign `ClientExceptionInterface` as
`TransportException`, with the original exception as `getPrevious()`.

To get the SDK's per-call timeout, implement `HttpClientInterface`:

```php
use Daika7ana\Cargus\Http\HttpClientInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class MyTransport implements HttpClientInterface
{
    public function __construct(private ClientInterface $client) {}

    public function sendRequest(RequestInterface $request, ?float $timeout = null): ResponseInterface
    {
        return $this->client->sendRequest($request); // apply $timeout when supported
    }
}
```

`Client::create()` also accepts `requestFactory:` and `streamFactory:` PSR-17
factories. If either is omitted, both default to
`GuzzleHttp\Psr7\HttpFactory`.

## Token store

Pass `tokenStore:` to `Client::create()` to replace the default
`InMemoryTokenStore`. The interface is
`Daika7ana\Cargus\Auth\TokenStoreInterface` with `getToken()` and
`setToken(Token $token)`. See [Authentication](AUTHENTICATION.md#token-storage).

## Low-level requests

The typed `Client` methods bypass resource mapping and return the raw PSR-7
response:

| Method | Purpose |
|---|---|
| `get(string $path, array $query = [], ?float $timeout = null)` | Authenticated GET |
| `post(string $path, array $body = [], array $query = [], ?float $timeout = null)` | Authenticated POST with a JSON body |
| `put(string $path, array $body = [], array $query = [], ?float $timeout = null)` | Authenticated PUT with a JSON body |
| `delete(string $path, array $query = [], ?float $timeout = null)` | Authenticated DELETE |
| `getNoAuth(string $path, array $query = [], ?float $timeout = null)` | Subscription key only |
| `postNoAuth(string $path, array $body = [], array $query = [], ?float $timeout = null)` | Subscription key only |

They return the response without DTO mapping or status mapping. `createRequest()`,
`withJsonBody()`, `send()` and `sendNoAuth()` are `@internal` and not part of the
supported API.
