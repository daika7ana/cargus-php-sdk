# Quickstart

A complete flow: install, configure, look up geography, calculate a shipping
cost, create one AWB and read its trace.

## 1. Install

```bash
composer require daika7ana/cargus-php-sdk
```

PHP 8.3+ and Composer 2 are required. See [Installation](INSTALLATION.md).

## 2. Configure credentials

Cargus needs an Azure APIM subscription key and the WebExpress username and
password. Set the environment variables and let `Client::create()` read them:

```bash
export CARGUS_USERNAME=your-webexpress-user
export CARGUS_PASSWORD=your-webexpress-password
export CARGUS_SUBSCRIPTION_KEY=your-azure-subscription-key
```

```php
use Daika7ana\Cargus\Client;

$client = Client::create();
```

Or pass them explicitly:

```php
use Daika7ana\Cargus\Client;
use Daika7ana\Cargus\Config\ClientConfig;

$client = Client::create(config: new ClientConfig(
    username: 'your-webexpress-user',
    password: 'your-webexpress-password',
    subscriptionKey: 'your-azure-subscription-key',
));
```

The client does not log in at construction. It re-authenticates an expired token
before sending, and answers a `401` by logging in and retrying once. Call
`authenticate()` first if you want to avoid that first unauthenticated round
trip. See [Authentication](AUTHENTICATION.md).

## 3. Look up geography

```php
$romania = null;
foreach ($client->geography()->countries() as $country) {
    if (stripos($country->name, 'Rom') !== false) {
        $romania = $country;
        break;
    }
}

if ($romania === null) {
    throw new RuntimeException('Romania was not found in /api/Countries.');
}

$county = $client->geography()->counties($romania->id)->first();

if ($county === null) {
    throw new RuntimeException('No counties for the selected country.');
}

$locality = $client->geography()->localities($romania->id, $county->id)->first();

if ($locality === null) {
    throw new RuntimeException('No localities for the selected county.');
}

printf("%s, %s (%d)\n", $locality->name, $county->name, $locality->id);
```

AWB creation uses `localityId`, `countyId` and `countryId`; keep the three
objects.

## 4. Calculate a shipping cost

```php
use Daika7ana\Cargus\DTOs\Pricing\ShippingCalculationModel;

$cost = $client->pricing()->calculateNoAuth(new ShippingCalculationModel(
    parcels: 1,
    envelopes: 0,
    totalWeight: 2,
    shipmentPayer: 1,
    declaredValue: 0.0,
    cashRepayment: 0.0,
    bankRepayment: 0.0,
    openPackage: false,
    saturdayDelivery: false,
    morningDelivery: false,
    fromLocalityId: $locality->id,
    toLocalityId: $locality->id,
    serviceId: 7,
));

echo $cost->subtotal, ' + ', $cost->tax, ' = ', $cost->grandTotal, PHP_EOL;
```

`calculateNoAuth()` needs only the subscription key. `calculate()` uses the same
body with a bearer token.

`serviceId` is the Cargus service code; the OpenAPI file does not label the
values. `shipmentPayer` is the API's payer code (`1` in the bundled tests and
smoke suite).

## 5. Create an AWB

```php
use Daika7ana\Cargus\DTOs\Awb\Address;
use Daika7ana\Cargus\DTOs\Awb\AwbTurModel;
use Daika7ana\Cargus\DTOs\Awb\ParcelCode;

$awb = new AwbTurModel(
    sender: new Address(
        name: 'Sender SRL',
        countyId: $county->id,
        countyName: $county->name,
        localityId: $locality->id,
        localityName: $locality->name,
        streetName: 'Strada Exemplu',
        buildingNumber: '10',
        contactPerson: 'Ion Popescu',
        phoneNumber: '0722123456',
        email: 'sender@example.test',
        countryId: $romania->id,
    ),
    recipient: new Address(
        name: 'Maria Ionescu',
        countyId: $county->id,
        countyName: $county->name,
        localityId: $locality->id,
        localityName: $locality->name,
        streetName: 'Strada Testului',
        buildingNumber: '1',
        contactPerson: 'Maria Ionescu',
        phoneNumber: '0723000000',
    ),
    parcels: 1,
    envelopes: 0,
    totalWeight: 2,
    shipmentPayer: 1,
    serviceId: 7,
    cashRepayment: 149.99,
    packageContent: 'Books',
    parcelCodes: [new ParcelCode(code: 'P1', type: 0, weight: 2.0)],
);

$barCode = $client->awbs()->create($awb);

echo $barCode, PHP_EOL;
```

`create()` returns the barcode as the API sent it; the SDK does not validate or
reformat it. `cashRepayment` collects the amount on delivery; omit it for a
shipment without cash-on-delivery.

## 6. Read the trace

```php
foreach ($client->tracking()->trace($barCode) as $package) {
    foreach ($package->events as $event) {
        printf(
            "%s  %s  %s\n",
            $event->date,
            $event->description,
            $event->localityName,
        );
    }
}
```

`trace()` requires a bearer token. `shortTraceNoAuth()` returns the condensed
trace with the subscription key only.

## 7. Handle errors

```php
use Daika7ana\Cargus\Exceptions\AuthenticationException;
use Daika7ana\Cargus\Exceptions\TransportException;
use Daika7ana\Cargus\Exceptions\UnexpectedStatusException;
use Daika7ana\Cargus\Exceptions\ValidationException;

try {
    $barCode = $client->awbs()->create($awb);
} catch (AuthenticationException $e) {
    // Login failed, or a 401 survived the one automatic re-login and retry.
} catch (ValidationException $e) {
    // Missing local credentials, or the API answered 400/422.
} catch (TransportException $e) {
    // DNS, TLS, timeout or connection failure; getPrevious() holds the cause.
} catch (UnexpectedStatusException $e) {
    // Any other non-2xx status, or a body that is not valid JSON.
}
```

See [Errors](ERRORS.md) before retrying a create: a transport failure does not
prove the AWB was not created. Reconcile with `awbs()->get()` first.
