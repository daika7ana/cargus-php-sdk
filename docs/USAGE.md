# Usage

Examples assume a configured client:

```php
use Daika7ana\Cargus\Client;
use Daika7ana\Cargus\Config\ClientConfig;

$client = Client::create(config: new ClientConfig(
    username: 'your-webexpress-user',
    password: 'your-webexpress-password',
    subscriptionKey: 'your-azure-subscription-key',
));
```

Every method below resolves its endpoint against `ClientConfig::BASE_URL`
(`https://urgentcargus.azure-api.net`). Dates are caller-formatted strings; the
SDK passes them through unchanged.

## Create an AWB

`Daika7ana\Cargus\DTOs\Awb\AwbTurModel` is the domestic AWB request. `Sender`
and `Recipient` are `Address` objects, and `parcels`, `envelopes`,
`totalWeight`, `shipmentPayer`, `sender` and `recipient` are required.

```php
use Daika7ana\Cargus\DTOs\Awb\Address;
use Daika7ana\Cargus\DTOs\Awb\AwbTurModel;
use Daika7ana\Cargus\DTOs\Awb\ParcelCode;

$awb = new AwbTurModel(
    sender: new Address(
        name: 'Sender SRL',
        countyId: 12,
        countyName: 'Cluj',
        localityId: 1234,
        localityName: 'Cluj-Napoca',
        streetId: 5678,
        streetName: 'Strada Exemplu',
        buildingNumber: '10',
        contactPerson: 'Ion Popescu',
        phoneNumber: '0722123456',
        email: 'sender@example.test',
        countryId: 1,
    ),
    recipient: new Address(
        name: 'Maria Ionescu',
        countyId: 12,
        countyName: 'Cluj',
        localityId: 1234,
        localityName: 'Cluj-Napoca',
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
    declaredValue: 500.0,
    cashRepayment: 149.99,
    openPackage: true,
    observations: 'Call before delivery',
    packageContent: 'Books',
    senderReference1: 'ORDER-1001',
    invoiceReference: 'INV-1001',
    parcelCodes: [
        new ParcelCode(code: 'P1', type: 0, weight: 2.0, length: 30, width: 20, height: 10),
    ],
);

$barCode = $client->awbs()->create($awb);

echo $barCode;
```

`Address` also carries `locationId`, `codPostal` (the `AwbGeneralAddress`
spelling), `postalCode` (the `AwbExtended` spelling), `addressText`,
`cityCenterName` and `cityCenterId`. `localityId` and `countyId` come from
`geography()`; `countyName` and `localityName` accompany them in the request.

`ParcelCode` takes `code` and `type`; `weight`, `length`, `width`, `height`,
`parcelContent` and `netAmount` are optional. The OpenAPI file does not label the
`type` values.

### Cash on delivery

Add the repayment fields to the same `AwbTurModel` constructor shown above:

| Field | Type | Purpose |
|---|---|---|
| `cashRepayment` | `?float` | Amount collected in cash on delivery |
| `bankRepayment` | `?float` | Bank-collected repayment |
| `otherRepayment` | `?string` | Other repayment form |
| `barCodeRepayment` | `?string` | Repayment barcode |
| `paymentInstrumentId` | `?int` | Payment instrument type at delivery |
| `paymentInstrumentValue` | `?float` | Payment instrument amount |

`declaredValue` is the declared shipment value. `openPackage`,
`saturdayDelivery` and `morningDelivery` are booleans; leave them `null` to omit
them from the request.

### Other AWB writes

| Method | Request DTO | Returns |
|---|---|---|
| `awbs()->create()` | `AwbTurModel` | `string` barcode |
| `awbs()->withGetAwb()` | `AwbTurModel` | `Collection<int, AwbExtended>` |
| `awbs()->createInternational()` | `GeneralAwbModel` | `string` barcode |
| `awbs()->generateReturnNoAuth()` | `ReturnAwbModel` | `string` barcode, subscription key only |
| `awbs()->updateParcelCodes()` | `AwbParcelUpdateModel` | `string` barcode echo |
| `awbs()->updateReference()` | `AwbUpdate` | `bool` |
| `awbs()->reschedule()` | query: bar code and date | `bool` |
| `awbs()->delete()` | query: bar code | `bool` |

`withGetAwb()` returns the created AWBs with the server-computed fields,
including `ShippingCost`. `generateReturnNoAuth()` posts to
`/api/NoAuth/GenerateRwb` and needs only the subscription key.

## Read AWB data

```php
$awbs = $client->awbs()->get(orderId: 1001);       // by order id
$awbs = $client->awbs()->get(barCode: '1234567890123');

foreach ($awbs as $awb) {
    echo $awb->barCode, ' ', $awb->status, ' ', $awb->shippingCost?->grandTotal, PHP_EOL;
}
```

`get()` accepts `orderId`, `barCode` or both; both are optional, so `get()` with
no arguments sends no filter. It returns `AwbExtended` objects.

| Method | Filters | Returns |
|---|---|---|
| `awbs()->get()` | `?int $orderId`, `?string $barCode` | `Collection<int, AwbExtended>` |
| `awbs()->getByDate()` | `$fromDate`, `$toDate`, `?pageNumber`, `?itemsPerPage` | `Collection<int, AwbModel>` |
| `awbs()->getIncomingByDate()` | `$fromDate`, `$toDate`, required `$destId` | `Collection<int, IncomingAwb>` |
| `awbs()->getIncomingByDateClickCollect()` | same as above | `Collection<int, IncomingAwb>` |
| `awbs()->returns()` | single `$date` | `Collection<int, AwbReturModel>` |
| `awbs()->routingAddress()` | `RoutingDetail` | `?DeliveryRouteModel` |

```php
use Daika7ana\Cargus\DTOs\Awb\RoutingDetail;
use Daika7ana\Cargus\DTOs\Awb\SenderAddress;

$route = $client->awbs()->routingAddress(new RoutingDetail(
    totalWeight: 2,
    sender: new SenderAddress(countyName: 'Cluj', localityId: 1234),
));

if ($route !== null) {
    echo $route->deliveryRoute, PHP_EOL;
}
```

`routingAddress()` returns `null` on `204 No Content`. `AwbReturModel` nests the
original and return AWBs as `AwbModel` objects in `$awb` and `$awbReturn`.

## Tracking

```php
$packages = $client->tracking()->trace('1234567890123');

foreach ($packages as $package) {
    echo $package->code, ' ', $package->orderStatus, PHP_EOL;

    foreach ($package->events as $event) {
        echo $event->date, ' ', $event->description, PHP_EOL;
    }
}
```

| Method | Endpoint | Auth | Returns |
|---|---|---|---|
| `trace($barCode)` | `GET /api/AwbTrace` | Bearer + key | `Collection<int, PackageEntity>` |
| `traceOneRequest($barCode)` | `GET /api/AwbTrace/GetAwbTraceOneRequest` | Bearer + key | `Collection<int, PackageEntity>` |
| `traceWithRedirect($barCode)` | `GET /api/AwbTrace/WithRedirect` | Bearer + key | `Collection<int, PackageEntity>` |
| `traceNoAuth($barCode)` | `GET /api/NoAuth/GetAwbTrace` | Key only | `Collection<int, PackageEntity>` |
| `shortTraceNoAuth($barCode)` | `GET /api/NoAuth/GetShortAwbTrace` | Key only | `Collection<int, ShortPackage>` |
| `deltaEvents($fromDate, $toDate)` | `GET /api/AwbTrace/GetDeltaEvents` | Bearer + key | `Collection<int, AwbTraceDelta>` |
| `confirmDelivery($barCode, $date, $remarkComment)` | `POST /api/AwbTrace/PostConfirmationDelivery` | Bearer + key | `Collection<int, PackageEntity>` |
| `scan($barCode)` | `GET /api/AwbScan` | Bearer + key | `string` |
| `syncStatus($fromDate, $toDate)` | `GET /api/AwbStatus/GetAwbSyncStatus` | Bearer + key | `Collection<int, AwbCash>` |
| `syncStatusByTariff($tariffId, $fromDate, $toDate)` | `GET /api/AwbStatus/GetAwbSyncStatusByTariff` | Bearer + key | `Collection<int, AwbCash>` |
| `syncStatusByBarCode($barCode)` | `GET /api/AwbStatus/GetAwbSyncStatusByBarCode` | Bearer + key | `?AwbCash` |

`PackageEntity::$events` and `ShortPackage::$events` are
`Collection<int, TraceEvent>`; `AwbTraceDelta::$packages` is
`Collection<int, PackageDelta>`, whose `$events` is also a `TraceEvent`
collection. `syncStatusByBarCode()` returns `null` on `204`.

`confirmDelivery()` sends everything in the query string. `scan()` returns the
decoded `format: byte` bytes as a string.

## Labels and documents

```php
// JSON document endpoints return the decoded bytes.
$document = $client->documents()->awbDocuments(
    barCodes: '1234567890123',
    type: 'PDF',
);

file_put_contents('awb-label.pdf', $document);
```

| Method | Endpoint | Arguments |
|---|---|---|
| `awbDocuments()` | `GET /api/AwbDocuments` | `$barCodes`, `$type`, `?format`, `?printMainOnce`, `?printOneAwbPerPage`, `?senderName`, `?printReturn` |
| `awbDocumentsDhl()` | `GET /api/AwbDocuments/GetDHL` | same minus `printReturn` |
| `orderDocuments()` | `GET /api/OrderDocuments` | `$orderId`, `$docType` (default `0`) |
| `orderDocumentsByDate()` | `GET /api/OrderDocuments/GetByDate` | `$fromDate`, `$toDate`, `$docType` |
| `invoiceDocuments()` | `GET /api/InvoiceDocuments` | `$invoiceId` |

`type` is the document type understood by the API (`'PDF'` in the smoke suite).
The OpenAPI file declares the response as a `string` with `format: byte`; the SDK
base64-decodes the body and returns the raw bytes (a value that is not valid
base64 is returned unchanged).

### PDF documents

The PDF endpoints take the bearer token as a `Token` query parameter and send no
`Authorization` header. Pass `Token::$accessToken` explicitly:

```php
$token = $client->getToken();

if ($token === null) {
    throw new RuntimeException('Log in before requesting a PDF document.');
}

$pdf = $client->documents()->pdfAwbDocuments(
    token: $token->accessToken,
    barCodes: '1234567890123',
);

if (! $pdf->isPdf()) {
    throw new RuntimeException('Expected a PDF, got ' . ($pdf->mimeType ?? 'no content type'));
}

file_put_contents('awb-label.pdf', $pdf->contents);
```

| Method | Endpoint | Token plus |
|---|---|---|
| `pdfAwbDocuments()` | `GET /api/PDF/AwbDocuments` | `$barCodes`, `?format`, `?printMainOnce`, `?printOneAwbPerPage` |
| `pdfOrderDocuments()` | `GET /api/PDF/OrderDocuments` | `$orderId`, `$docType` |
| `pdfInvoiceDocuments()` | `GET /api/PDF/InvoiceDocuments` | `$invoiceId` |

These three return `Daika7ana\Cargus\Support\Document`, not a string.
`Document::$contents` is the raw body, `Document::$mimeType` is the
`Content-Type` header or `null`, and `isPdf()` checks the body for a `%PDF`
prefix. A `204` yields an empty `Document`.

## Pickup locations

```php
use Daika7ana\Cargus\DTOs\PickupLocation\AddressExtended;

foreach ($client->pickupLocations()->list() as $location) {
    echo $location->locationId, ' ', $location->name, PHP_EOL;
}

$locationId = $client->pickupLocations()->save(new AddressExtended(
    name: 'Depot',
    countyId: 12,
    countyName: 'Cluj',
    localityId: 1234,
    localityName: 'Cluj-Napoca',
    streetName: 'Strada Depozitului',
    buildingNumber: '5',
    contactPerson: 'Ion Popescu',
    phoneNumber: '0722123456',
    countryId: 1,
));

$client->pickupLocations()->assignToUser($locationId);
```

| Method | Endpoint | Returns |
|---|---|---|
| `list(?int $idPickupLocation = null)` | `GET /api/PickupLocations` | `Collection<int, AddressExtended>` |
| `listForClient()` | `GET /api/PickupLocations/GetForClient` | `Collection<int, AddressExtended>` |
| `listClickCollect()` | `GET /api/PickupLocations/GetClickCollect` | `Collection<int, AddressExtendedClickCollect>` |
| `save(AddressExtended)` | `POST /api/PickupLocations` | `int` id |
| `update(AddressExtended)` | `PUT /api/PickupLocations` | `int` |
| `delete(int $idPickupLocation)` | `DELETE /api/PickupLocations` | `int`, `0` on `204` |
| `saveClickCollect(AddressExtendedClickCollect)` | `POST /api/PickupLocations/PostClickCollect` | `int` |
| `updateClickCollect(AddressExtendedClickCollect)` | `PUT /api/PickupLocations/PutClickCollect` | `int` |
| `assignToUser(int $locationId)` | `POST /api/PickupLocations/AssignToUser` | `int` |

`AddressExtended` is also the address DTO for `AwbPickupModel` and
`AwbPickupIndividualModel`, so a saved pickup location can seed a pickup request.
PUDO points are read through `geography()->pudoPoints()`.

### Request a pickup

```php
use Daika7ana\Cargus\DTOs\Pickup\AwbPickupModel;

$pickupBarCode = $client->pickup()->request(new AwbPickupModel(
    pickupStartDate: '2026-12-01T10:00:00',
    pickupEndDate: '2026-12-01T16:00:00',
    parcels: 1,
    envelopes: 0,
    totalWeight: 2,
    shipmentPayer: 1,
    serviceId: 7,
    observations: 'Call on arrival',
));
```

`pickup()->request()` posts to `/api/AwbPickup` and returns the generated
barcode. It dispatches a physical courier. The resource also exposes
`requestWithGetAwb()` (`array` of server fields) and
`requestIndividualNoAuth()` (key only). Do not retry a pickup automatically.

## Pricing

```php
use Daika7ana\Cargus\DTOs\Pricing\ShippingCalculationModel;

$model = new ShippingCalculationModel(
    parcels: 1,
    envelopes: 0,
    totalWeight: 2,
    shipmentPayer: 1,
    declaredValue: 500.0,
    cashRepayment: 149.99,
    bankRepayment: 0.0,
    openPackage: false,
    saturdayDelivery: false,
    morningDelivery: false,
    fromLocalityId: 1234,
    toLocalityId: 5678,
    priceTableId: 10,
    serviceId: 7,
    parcelCodes: [
        new \Daika7ana\Cargus\DTOs\Pricing\ShippingCalculationParcelCode(type: 0, weight: 2.0),
    ],
);

$cost = $client->pricing()->calculate($model);
echo $cost->grandTotal, PHP_EOL;

$noAuthCost = $client->pricing()->calculateNoAuth($model);

foreach ($client->pricing()->priceTables() as $table) {
    echo $table->id, ' ', $table->name, PHP_EOL;
}
```

`ShippingCost` exposes `baseCost`, `extraKmCost`, `weightCost`, `insuranceCost`,
`specialCost`, `repaymentCost`, `subtotal`, `tax` and `grandTotal`, all `float`.
`PriceTable::$tariffVersion` reads the wire key `TariffVerion` (misspelled by the
API) and also accepts `TariffVersion`.

## Orders

`orders()` manages pickup orders, not AWBs.

```php
use Daika7ana\Cargus\DTOs\Order\OrderPickupModel;

$orderId = $client->orders()->create(new OrderPickupModel(
    parcels: 2,
    envelopes: 0,
    totalWeight: 5,
    pickupStartDate: '2026-12-01T10:00:00',
    pickupEndDate: '2026-12-01T16:00:00',
    name: 'Sender SRL',
    countyId: 12,
    countyName: 'Cluj',
    localityId: 1234,
    localityName: 'Cluj-Napoca',
    streetName: 'Strada Exemplu',
    buildingNumber: '10',
    contactPerson: 'Ion Popescu',
    phoneNumber: '0722123456',
    countryId: 1,
));

$orders = $client->orders()->get(locationId: 1, status: 0);

foreach ($orders as $order) {
    echo $order->orderId, ' ', $order->ordStatus, ' ', $order->pickupStartDate, PHP_EOL;
}

$client->orders()->cancel($orderId);
```

| Method | Input | Returns |
|---|---|---|
| `get($locationId, $status, ?$pageNumber, ?$itemsPerPage)` | query | `Collection<int, Order>` |
| `getByDate($fromDate, $toDate, ?$pageNumber, ?$itemsPerPage)` | query | `Collection<int, Order>` |
| `getByOrderId($orderId)` | query | `?Order`, `null` on `204` |
| `create(OrderPickupModel)` | JSON body | `int` order id |
| `createClickCollect(OrderPickupClickCollectModel)` | JSON body | `int` order id |
| `update($locationId, $pickupStartDate, $pickupEndDate, $action)` | query | `int` |
| `updateAll($pickupStartDate, $pickupEndDate, $action)` | query | `string` |
| `cancel($orderId)` | query | `int` |

`OrderPickupClickCollectModel` adds `observations` to the pickup fields.
`$action` and `$status` are integer codes the OpenAPI file does not label.
`totalWeight` is `int`: pass whole units (the spec declares it as an integer).

## Other resources

```php
// Geography
$pudo = $client->geography()->pudoPoints();
$streets = $client->geography()->streets(1234);
$branches = $client->geography()->branches();

// Financial
$invoices = $client->financial()->invoices('2026-01-01', '2026-12-31', 50, 1);
$cash = $client->financial()->cashAccount('1234567890123');

// Online shop
$customer = $client->onlineShop()->customerDetails();
$workstations = $client->onlineShop()->userWorkstations();

// Recipients and complaints
$recipients = $client->recipients()->list();
$complaint = $client->complaints()->checkAwb('1234567890123');

// Marketing
$updates = $client->marketing()->updatedUsers('2026-01-01', '2026-12-31', 1, 100);
```

`invoices()` takes `itemsPerPage` before `pageNumber`. `cashAccount()` filters by
an optional barcode; `cashAccountByDate()` and `cashAccountByDeductionDate()`
paginate. `cashReceipts()` posts a `CashReceiptList`. `updatedUsers()` requires
all four arguments (`startDate`, `endDate`, `pageNumber`, `rowsPerPage`).
