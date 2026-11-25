# Resources

All paths are relative to `ClientConfig::BASE_URL`
(`https://urgentcargus.azure-api.net`). Every request carries
`Ocp-Apim-Subscription-Key`; authenticated requests also carry
`Authorization: Bearer <token>`.

Every method can throw `ValidationException` (missing local credentials or a
400/422 answer), `AuthenticationException` (401), `UnexpectedStatusException`
(any other non-2xx, or a non-JSON body) and `TransportException`. See
[Errors](ERRORS.md).

`Collection` is `Daika7ana\SimpleCollections\Collection`.

## Common behavior

- **204 No Content** — list endpoints return an empty `Collection`; single-item
  lookups map it as listed under [204 results](#204-results). Methods without a
  204 branch raise `UnexpectedStatusException` because the mapper expects `200`.
- **Query-only writes** — `awbs()->reschedule()`, `orders()->update()`,
  `orders()->updateAll()`, `orders()->cancel()`,
  `pickupLocations()->assignToUser()`, `complaints()->checkAwb()` and
  `tracking()->confirmDelivery()` take all input in the query string. The POST
  and PUT helpers always attach a JSON body, so these calls send `[]` as the
  body; whether the live API tolerates that body is unverified. `awbs()->delete()`
  sends the barcode in the query and no body.
- **Wire casing** — parameter names on the methods are lowerCamelCase, but the
  wire keys vary per endpoint (`FromDate`, `BarCode`, `InvoiceId`, `TariffVerion`).
  Use the method signature; the mapping is already done.
- **Order of arguments** — pagination order differs by resource. `invoices()`
  takes `itemsPerPage` before `pageNumber`; most others take `pageNumber` before
  `itemsPerPage`.

## AWB — `$client->awbs()`

| Method | Endpoint | Parameters | Returns |
|---|---|---|---|
| `get()` | `GET /api/Awbs` | `?int $orderId`, `?string $barCode` | `Collection<int, AwbExtended>` |
| `getByDate()` | `GET /api/Awbs/GetByDate` | `string $fromDate`, `string $toDate`, `?int $pageNumber`, `?int $itemsPerPage` | `Collection<int, AwbModel>` |
| `getIncomingByDate()` | `GET /api/Awbs/GetIncomingByDate` | `string $fromDate`, `string $toDate`, `int $destId` | `Collection<int, IncomingAwb>` |
| `getIncomingByDateClickCollect()` | `GET /api/Awbs/GetIncomingByDateClickCollect` | `string $fromDate`, `string $toDate`, `int $destId` | `Collection<int, IncomingAwb>` |
| `create()` | `POST /api/Awbs` | `AwbTurModel $awb` | `string` barcode |
| `withGetAwb()` | `POST /api/Awbs/WithGetAwb` | `AwbTurModel $awb` | `Collection<int, AwbExtended>` |
| `reschedule()` | `PUT /api/Awbs/PutReschedule` | `string $barCode`, `string $date` (query) | `bool` |
| `updateParcelCodes()` | `PUT /api/Awbs/Update` | `AwbParcelUpdateModel $update` | `string` barcode |
| `updateReference()` | `POST /api/Awbs/AwbRefUpdate` | `AwbUpdate $update` | `bool` |
| `delete()` | `DELETE /api/Awbs` | `string $barCode` (query) | `bool` |

The three `bool` writes parse the API's answer strictly and throw
`UnexpectedStatusException` on a non-boolean payload. `updateParcelCodes()`
returns the barcode the live API echoes instead.
| `routingAddress()` | `POST /api/Awbs/GetRoutingAddress` | `RoutingDetail $detail` | `?DeliveryRouteModel` |
| `returns()` | `GET /api/AwbRetur` | `string $date` (query key `data`) | `Collection<int, AwbReturModel>` |
| `createInternational()` | `POST /api/AwbGeneral/InternationalAwb` | `GeneralAwbModel $awb` | `string` barcode |
| `generateReturnNoAuth()` | `POST /api/NoAuth/GenerateRwb` | `ReturnAwbModel $awb` | `string` barcode |

`get()` accepts either filter or both; with neither it sends no filter.
`create()` and `createInternational()` return `''` on `204`. `create()` also sends the `Path: Crra` header the APIM gateway requires to route AWB creation.
`routingAddress()` returns `null` when the API has no route.
`generateReturnNoAuth()` needs only the subscription key.

## Documents — `$client->documents()`

| Method | Endpoint | Parameters | Returns |
|---|---|---|---|
| `awbDocuments()` | `GET /api/AwbDocuments` | `$barCodes`, `$type`, `?$format`, `?$printMainOnce`, `?$printOneAwbPerPage`, `?$senderName`, `?$printReturn` | `string` |
| `awbDocumentsDhl()` | `GET /api/AwbDocuments/GetDHL` | `$barCodes`, `$type`, `?$format`, `?$printMainOnce`, `?$printOneAwbPerPage`, `?$senderName` | `string` |
| `orderDocuments()` | `GET /api/OrderDocuments` | `int $orderId`, `int $docType = 0` | `string` |
| `orderDocumentsByDate()` | `GET /api/OrderDocuments/GetByDate` | `string $fromDate`, `string $toDate`, `int $docType = 0` | `string` |
| `invoiceDocuments()` | `GET /api/InvoiceDocuments` | `int $invoiceId` (query key `InvoiceId`) | `string` |
| `pdfAwbDocuments()` | `GET /api/PDF/AwbDocuments` | `string $token`, `string $barCodes`, `?$format`, `?$printMainOnce`, `?$printOneAwbPerPage` | `Document` |
| `pdfOrderDocuments()` | `GET /api/PDF/OrderDocuments` | `string $token`, `int $orderId`, `int $docType = 0` | `Document` |
| `pdfInvoiceDocuments()` | `GET /api/PDF/InvoiceDocuments` | `string $token`, `int $invoiceId` | `Document` |

The five JSON document methods return the decoded bytes and `''` on `204`. The
OpenAPI file declares them as `string` with `format: byte`; the SDK
base64-decodes the body, returning it unchanged if it is not valid base64. The
three `pdf*` methods use NoAuth, pass the token as the
`Token` query parameter, and return `Daika7ana\Cargus\Support\Document`; a `204`
yields an empty `Document`.

## Tracking — `$client->tracking()`

| Method | Endpoint | Parameters | Returns |
|---|---|---|---|
| `trace()` | `GET /api/AwbTrace` | `string $barCode` | `Collection<int, PackageEntity>` |
| `traceOneRequest()` | `GET /api/AwbTrace/GetAwbTraceOneRequest` | `string $barCode` | `Collection<int, PackageEntity>` |
| `traceWithRedirect()` | `GET /api/AwbTrace/WithRedirect` | `string $barCode` | `Collection<int, PackageEntity>` |
| `traceNoAuth()` | `GET /api/NoAuth/GetAwbTrace` | `string $barCode` | `Collection<int, PackageEntity>` |
| `shortTraceNoAuth()` | `GET /api/NoAuth/GetShortAwbTrace` | `string $barCode` | `Collection<int, ShortPackage>` |
| `deltaEvents()` | `GET /api/AwbTrace/GetDeltaEvents` | `string $fromDate`, `string $toDate` | `Collection<int, AwbTraceDelta>` |
| `confirmDelivery()` | `POST /api/AwbTrace/PostConfirmationDelivery` | `string $barCode`, `string $date`, `string $remarkComment` (query) | `Collection<int, PackageEntity>` |
| `scan()` | `GET /api/AwbScan` | `string $barCode` | `string` |
| `syncStatus()` | `GET /api/AwbStatus/GetAwbSyncStatus` | `string $fromDate`, `string $toDate` | `Collection<int, AwbCash>` |
| `syncStatusByTariff()` | `GET /api/AwbStatus/GetAwbSyncStatusByTariff` | `int $tariffId`, `string $fromDate`, `string $toDate` | `Collection<int, AwbCash>` |
| `syncStatusByBarCode()` | `GET /api/AwbStatus/GetAwbSyncStatusByBarCode` | `string $barCode` | `?AwbCash` |

`traceNoAuth()` and `shortTraceNoAuth()` need only the subscription key.
`scan()` returns the decoded `format: byte` bytes as a string, `''` on `204`.
`syncStatusByBarCode()` returns `null` on `204`.
`deltaEvents()` returns an empty `Collection` on `204`.

## Pickup — `$client->pickup()`

| Method | Endpoint | Parameters | Returns |
|---|---|---|---|
| `request()` | `POST /api/AwbPickup` | `AwbPickupModel $pickup` | `string` barcode |
| `requestWithGetAwb()` | `POST /api/AwbPickup/WithGetAwb` | `AwbPickupModel $pickup` | `array<string, mixed>` |
| `requestIndividualNoAuth()` | `POST /api/NoAuth/IndividualWithGetAwb` | `AwbPickupIndividualModel $pickup` | `string` |

`request()` dispatches a physical courier. `requestWithGetAwb()` returns the
untyped response object, `[]` on `204`. `requestIndividualNoAuth()` needs only
the subscription key and returns `''` on `204`.

## Pickup locations — `$client->pickupLocations()`

| Method | Endpoint | Parameters | Returns |
|---|---|---|---|
| `list()` | `GET /api/PickupLocations` | `?int $idPickupLocation` | `Collection<int, AddressExtended>` |
| `listForClient()` | `GET /api/PickupLocations/GetForClient` | — | `Collection<int, AddressExtended>` |
| `listClickCollect()` | `GET /api/PickupLocations/GetClickCollect` | — | `Collection<int, AddressExtendedClickCollect>` |
| `save()` | `POST /api/PickupLocations` | `AddressExtended $location` | `int` |
| `update()` | `PUT /api/PickupLocations` | `AddressExtended $location` | `int` |
| `delete()` | `DELETE /api/PickupLocations` | `int $idPickupLocation` | `int`, `0` on `204` |
| `saveClickCollect()` | `POST /api/PickupLocations/PostClickCollect` | `AddressExtendedClickCollect $location` | `int` |
| `updateClickCollect()` | `PUT /api/PickupLocations/PutClickCollect` | `AddressExtendedClickCollect $location` | `int` |
| `assignToUser()` | `POST /api/PickupLocations/AssignToUser` | `int $locationId` (query) | `int` |

## Orders — `$client->orders()`

| Method | Endpoint | Parameters | Returns |
|---|---|---|---|
| `get()` | `GET /api/Orders` | `int $locationId`, `int $status`, `?int $pageNumber`, `?int $itemsPerPage` | `Collection<int, Order>` |
| `getByDate()` | `GET /api/Orders/GetByDate` | `string $fromDate`, `string $toDate`, `?int $pageNumber`, `?int $itemsPerPage` | `Collection<int, Order>` |
| `getByOrderId()` | `GET /api/Orders/GetByOrderId` | `int $orderId` | `?Order` |
| `create()` | `POST /api/Orders` | `OrderPickupModel $order` | `int` |
| `update()` | `PUT /api/Orders` | `int $locationId`, `string $pickupStartDate`, `string $pickupEndDate`, `int $action` (query) | `int` |
| `updateAll()` | `PUT /api/Orders/PutAll` | `string $pickupStartDate`, `string $pickupEndDate`, `int $action` (query) | `string` |
| `createClickCollect()` | `POST /api/Orders/PostClickCollect` | `OrderPickupClickCollectModel $order` | `int` |
| `cancel()` | `PUT /api/Orders/Cancel` | `int $orderId` (query) | `int` |

`get()` requires `locationId` and `status`; the two pagination arguments are
optional and dropped when `null`. `getByOrderId()` returns `null` on `204`.

## Geography — `$client->geography()`

| Method | Endpoint | Parameters | Returns |
|---|---|---|---|
| `counties()` | `GET /api/Counties` | `int $countryId` | `Collection<int, County>` |
| `countiesNoAuth()` | `GET /api/NoAuth/GetCounties` | `int $countryId` | `Collection<int, County>` |
| `countries()` | `GET /api/Countries` | — | `Collection<int, Country>` |
| `localities()` | `GET /api/Localities` | `int $countryId`, `?int $countyId`, `?string $postalCode` | `Collection<int, Locality>` |
| `localitiesNoAuth()` | `GET /api/NoAuth/GetLocalities` | `int $countryId`, `int $countyId` | `Collection<int, Locality>` |
| `localityDetails()` | `GET /api/Localities/DetailsLocality` | `int $localityId` (query key `LocalityId`) | `Locality` |
| `streets()` | `GET /api/Streets` | `int $localityId` | `Collection<int, Street>` |
| `branches()` | `GET /api/Branches` | — | `Collection<int, Branch>` |
| `pudoPoints()` | `GET /api/PudoPoints` | — | `Collection<int, PudoPoint>` |

`/api/Counties` items are `{County: [...], message}` wrappers and
`/api/Localities` items are `{Locality: [...], message}` wrappers; the resource
flattens them. `countiesNoAuth()` and `localitiesNoAuth()` need only the
subscription key; NoAuth localities require both IDs. `localityDetails()` maps
`204` to `Locality::fromArray([])`, an empty DTO with `id === 0`, not `null`.
`Street::$postalNumbers` is a `Collection<int, PostalNumber>`.

## Pricing — `$client->pricing()`

| Method | Endpoint | Parameters | Returns |
|---|---|---|---|
| `calculate()` | `POST /api/ShippingCalculation` | `ShippingCalculationModel $model` | `ShippingCost` |
| `calculateNoAuth()` | `POST /api/NoAuth/PostShippingCalculation` | `ShippingCalculationModel $model` | `ShippingCost` |
| `priceTables()` | `GET /api/PriceTables` | — | `Collection<int, PriceTable>` |

`calculateNoAuth()` needs only the subscription key. On `204`, both calculate
methods return `ShippingCost::fromArray([])`, whose fields are all `0.0`.

## Financial — `$client->financial()`

| Method | Endpoint | Parameters | Returns |
|---|---|---|---|
| `invoices()` | `GET /api/Invoices` | `string $fromDate`, `string $toDate`, `?int $itemsPerPage`, `?int $pageNumber` | `Collection<int, Invoice>` |
| `cashAccount()` | `GET /api/CashAccount` | `?string $barCode` | `Collection<int, AwbCash>` |
| `cashAccountByDate()` | `GET /api/CashAccount/GetByDate` | `string $fromDate`, `string $toDate`, `?int $pageNumber`, `?int $itemsPerPage` | `Collection<int, AwbCash>` |
| `cashAccountByDeductionDate()` | `GET /api/CashAccount/GetByDeductionDate` | `string $deductionDate`, `?int $pageNumber`, `?int $itemsPerPage` | `Collection<int, AwbCash>` |
| `cashReceipts()` | `POST /api/CashReceipts` | `CashReceiptList $receipts` | `int` id |

`invoices()` takes `itemsPerPage` before `pageNumber`, unlike the other
paginated methods.

## Online shop — `$client->onlineShop()`

| Method | Endpoint | Parameters | Returns |
|---|---|---|---|
| `customerDetails()` | `GET /api/OnlineShop/GetCustomerDetails` | — | `CustomerDetails` |
| `userWorkstations()` | `GET /api/OnlineShop/GetUserWorkstations` | — | `Collection<int, UserWorkstation>` |
| `workstation()` | `GET /api/OnlineShop/GetWorkstationById` | `int $id` | `WorkstationDetails` |

On `204`, the two single-object methods return a DTO built from `[]`; the list
method returns an empty collection.

## Recipients — `$client->recipients()`

| Method | Endpoint | Parameters | Returns |
|---|---|---|---|
| `list()` | `GET /api/Recipients` | — | `Collection<int, AddressExtended>` |

## Complaints — `$client->complaints()`

| Method | Endpoint | Parameters | Returns |
|---|---|---|---|
| `checkAwb()` | `POST /api/Complaints/CheckAwb` | `string $barCode` (query) | `?ComplaintStatus` |
| `saveComplaint()` | `POST /api/Complaints/SaveComplaint` | `ComplaintModel $complaint` | `bool` |

`checkAwb()` returns `null` on `204`. `saveComplaint()` returns `true` only when
the response body is the JSON literal `true`.

## Marketing — `$client->marketing()`

| Method | Endpoint | Parameters | Returns |
|---|---|---|---|
| `updatedUsers()` | `GET /api/Marketing/GetUpdatedUsers` | `string $startDate`, `string $endDate`, `int $pageNumber`, `int $rowsPerPage` | `?UpdatedUsersExtended` |
| `unsubscribeEmail()` | `POST /api/Marketing/MarketingEmailUnsubscribe` | `string $email` (query) | `bool` |

All four `updatedUsers()` arguments are required. `updatedUsers()` returns
`null` on `204`.

## Client-level methods

| Method | Endpoint | Returns |
|---|---|---|
| `authenticate($username, $password)` | `POST /api/LoginUser` | `void` |
| `authenticateJuridic($username, $password)` | `POST /api/LoginUser/Juridic` | `void` |
| `tokenVerification()` | `GET /api/TokenVerification` | `bool` |
| `getToken()` | — | `?Token` |
| `setToken(Token\|string $token)` | — | `Client` |
| `getConfig()` | — | `ClientConfig` |
| `get()`, `post()`, `put()`, `delete()` | caller-supplied path | `ResponseInterface` |
| `getNoAuth()`, `postNoAuth()` | caller-supplied path | `ResponseInterface` |

`get()`/`post()`/`put()`/`delete()` are authenticated and do not map the body;
use the resources for typed results. `createRequest()`, `withJsonBody()`, `send()`
and `sendNoAuth()` are `@internal`. See
[Configuration](CONFIGURATION.md#low-level-requests).

## 204 results

| Method(s) | Result on `204` |
|---|---|
| `Collection`-returning methods: `awbs()` reads and `withGetAwb()`, `tracking()` traces and `syncStatus*`, `financial()` lists, `geography()` lists, `orders()` lists, `pickupLocations()` lists, `recipients()->list()`, `onlineShop()->userWorkstations()`, `pricing()->priceTables()` | Empty `Collection` |
| `awbs()->routingAddress()`, `orders()->getByOrderId()`, `tracking()->syncStatusByBarCode()` | `null` |
| `awbs()->create()`, `awbs()->createInternational()` | `''` |
| `documents()->awbDocuments*()`, `orderDocuments*()`, `invoiceDocuments()`, `tracking()->scan()` | `''` |
| `documents()->pdf*()` | Empty `Document` (`contents: ''`) |
| `pickup()->requestWithGetAwb()` | `[]` |
| `pickup()->requestIndividualNoAuth()` | `''` |
| `pickupLocations()->delete()` | `0` |
| `geography()->localityDetails()` | `Locality::fromArray([])`, `id === 0` |
| `pricing()->calculate()`, `calculateNoAuth()` | `ShippingCost::fromArray([])`, all `0.0` |
| `onlineShop()->customerDetails()`, `workstation()` | DTO built from `[]` |

Every other method treats `204` as an unexpected status.

## NoAuth variants

| Resource | Methods |
|---|---|
| `geography()` | `countiesNoAuth()`, `localitiesNoAuth()` |
| `pricing()` | `calculateNoAuth()` |
| `tracking()` | `shortTraceNoAuth()`, `traceNoAuth()` |
| `awbs()` | `generateReturnNoAuth()` |
| `pickup()` | `requestIndividualNoAuth()` |
| `documents()` | `pdfAwbDocuments()`, `pdfOrderDocuments()`, `pdfInvoiceDocuments()` (token as query parameter) |
