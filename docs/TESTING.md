# Testing

## Commands

```bash
composer ci         # validate --strict -> pint --test -> phpstan -> phpunit tests/Unit
composer pint       # style check only, never fixes
composer stan       # PHPStan level 8 over src/
composer test       # full PHPUnit suite (Unit + Smoke)
composer test:unit  # tests/Unit only
composer test:smoke # tests/Smoke only, self-skips without credentials
vendor/bin/pint     # the only way to auto-fix style
```

`composer ci` expands to:

1. `composer validate --strict`
2. `composer pint` (`vendor/bin/pint --test`)
3. `composer stan` (`vendor/bin/phpstan --no-progress`)
4. `composer test:unit` (`vendor/bin/phpunit tests/Unit`)

Run a single file or filter:

```bash
composer test -- tests/Unit/Resources/AwbResourceTest.php
composer test -- --filter=AwbResourceTest
```

## Unit tests

The unit suite never touches the network and needs no Cargus credentials.
`tests/Support/TestClientFactory::create()` wires a `Client` to a fake transport
and the Guzzle PSR-17 factory:

```php
use Daika7ana\Cargus\Tests\Support\TestClientFactory;

[$client, $httpClient] = TestClientFactory::create($handler);
```

- `tests/Support/TestClientFactory.php` — `create(callable $handler, ?ClientConfig $config = null)`
  returns `[Client, FakeHttpClient]`. The default config is
  `username: 'test-user'`, `password: 'test-pass'`,
  `subscriptionKey: 'test-subscription-key'`.
- `tests/Support/FakeHttpClient.php` — implements the SDK transport contract,
  records `lastRequest` and `lastTimeout`, and returns the handler's response.
- `tests/Support/Fixture.php` — canned responses: `json(mixed $data, int $status = 200, array $headers = [])`
  and `raw(string $body, int $status = 200, array $headers = [])`.

A test that seeds a token asserts the exact `Authorization` header and keeps the
request to one call:

```php
use Daika7ana\Cargus\Auth\Token;

$client->setToken(new Token('abc'));
```

Without a token the first request is sent without `Authorization`; a `401`
handler in the test would then trigger a login request against the fake.

A test asserts both the outgoing request and the mapped result:

```php
use Daika7ana\Cargus\DTOs\Awb\Address;
use Daika7ana\Cargus\DTOs\Awb\AwbTurModel;
use Daika7ana\Cargus\DTOs\Awb\ParcelCode;
use Daika7ana\Cargus\Tests\Support\Fixture;
use Daika7ana\Cargus\Tests\Support\TestClientFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

final class AwbCreateTest extends TestCase
{
    public function testCreatePostsTheAwbAndReturnsTheBarcode(): void
    {
        [$client, $httpClient] = TestClientFactory::create(
            static fn (): ResponseInterface => Fixture::json('AWB123'),
        );
        $client->setToken(new \Daika7ana\Cargus\Auth\Token('abc'));

        $barCode = $client->awbs()->create(new AwbTurModel(
            sender: new Address(name: 'Sender', localityId: 1, countyId: 2),
            recipient: new Address(name: 'Receiver', localityId: 3, countyId: 4),
            parcels: 1,
            envelopes: 0,
            totalWeight: 2,
            shipmentPayer: 1,
            serviceId: 7,
            parcelCodes: [new ParcelCode(code: 'P1', type: 0, weight: 2.0)],
        ));

        self::assertSame('AWB123', $barCode);

        $request = $httpClient->lastRequest;
        self::assertNotNull($request);
        self::assertSame('POST', $request->getMethod());
        self::assertSame('/api/Awbs', $request->getUri()->getPath());
        self::assertSame('Bearer abc', $request->getHeaderLine('Authorization'));
        self::assertSame(
            'test-subscription-key',
            $request->getHeaderLine('Ocp-Apim-Subscription-Key'),
        );

        $body = json_decode((string) $request->getBody(), true);
        self::assertSame('Sender', $body['Sender']['Name']);
        self::assertSame([['Code' => 'P1', 'Type' => 0, 'Weight' => 2]], $body['ParcelCodes']);
    }
}
```

Every endpoint needs a test covering both the request (method, path, query, body,
headers) and the response mapping. `Fixture::raw('', 204)` covers the empty-body
branches; `Fixture::json($payload, 422)` covers `ValidationException`.

## Smoke tests

`tests/Smoke` talks to the live API. It skips itself unless all three credentials
are set:

| Variable | Purpose |
|---|---|
| `CARGUS_USERNAME` | Test account username |
| `CARGUS_PASSWORD` | Test account password |
| `CARGUS_SUBSCRIPTION_KEY` | Azure APIM subscription key |

Writes additionally require `CARGUS_SMOKE_ENABLE_WRITES`. The value is true for
`1`, `true`, `yes` or `on` (case-insensitive).

| Variable | Purpose |
|---|---|
| `CARGUS_SMOKE_ENABLE_WRITES` | Enables the AWB create/trace/documents/delete lifecycle |
| `CARGUS_SMOKE_BARCODE` | Existing AWB for the read-only trace and cash-account checks; `cashAccount` is skipped without it |
| `CARGUS_SMOKE_COUNTRY_ID` | Country used by the geography fixtures; defaults to the first country whose name contains `Rom` |
| `CARGUS_SMOKE_LOCALITY_ID` | Locality used as sender/recipient; otherwise derived from the country and county |
| `CARGUS_SMOKE_SERVICE_ID` | Service code for the AWB created by the write lifecycle |
| `CARGUS_SMOKE_RECIPIENT_NAME` | Recipient name; defaults to `Smoke Test Recipient` |
| `CARGUS_SMOKE_RECIPIENT_PHONE` | Recipient phone; defaults to `0722123456` |
| `CARGUS_SMOKE_STREET_NAME` | Recipient street; defaults to `Strada Testului` |

```bash
CARGUS_USERNAME=... CARGUS_PASSWORD=... CARGUS_SUBSCRIPTION_KEY=... \
CARGUS_SMOKE_ENABLE_WRITES=true \
composer test:smoke
```

Coverage:

- `ClientSmokeTest` — `tokenVerification()`. The smoke client authenticates
  eagerly because the live API answers `409` rather than `401` without a token.
- `GeographyAndPricingSmokeTest` — countries, counties, locality details,
  branches, PUDO points, streets, price tables, `calculateNoAuth()` and the
  authenticated `calculate()`.
- `AwbReadSmokeTest` — AWB list by date, plus trace and short trace when
  `CARGUS_SMOKE_BARCODE` is set.
- `FinancialAndAccountSmokeTest` — invoices (204 = empty; skipped on the shared
  account's 429), `cashAccount()` when a barcode is configured, pickup
  locations, recipients, customer details and orders by date (204 = empty).
- `AwbWriteLifecycleSmokeTest` — creates one AWB, updates its reference and
  parcel codes, calls scan, sync status, complaints and cash account, fetches
  its documents and deletes it in a `finally` block.

The shared test credentials are throttled intermittently. `ThrottledHttpClient`
decorates the bundled Guzzle transport for smoke tests only: it retries a `409`
or `429` response up to three attempts with 2s then 4s backoff and never changes
`src/`. Several operations are also rate-limited over longer windows
(`/api/Invoices`) or return `FORBIDDEN` for this account; those tests skip with
a reason instead of failing.

The smoke suite never creates pickups or pickup orders: those dispatch a
physical courier. Smoke tests never run in `composer ci`.

Cargus publishes no sandbox, so a green smoke run is the only live verification.
A green unit run proves the code matches its fixtures, not the API.
