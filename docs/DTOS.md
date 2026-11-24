# DTOs

All DTOs are `final readonly` classes with public camelCase properties.
Request DTOs add `toArray()`; response DTOs add `fromArray(array $data): self`.
Some DTOs serve both directions.

## Conventions

- **Wire keys are PascalCase.** `toArray()` emits keys such as `Sender`,
  `TotalWeight` and `CashRepayment`. `fromArray()` reads the same spelling.
  Many response DTOs also accept a camelCase fallback (`$data['Name'] ??
  $data['name']`); the AWB and tracking DTOs read PascalCase only.
- **Null optionals are dropped.** `toArray()` omits a key whose value is `null`
  rather than sending an empty value. Required constructor arguments have no
  default and are always emitted.
- **Missing response fields become defaults.** `fromArray()` casts missing keys
  to `''`, `0`, `false` or a zeroed nested DTO; it does not throw.
- **Booleans are JSON booleans.** A request body sends `true`/`false`. The shared
  query builder converts booleans to `1`/`0`.
- **Request lists are plain arrays; response lists are `Collection`.**
  `AwbTurModel::$parcelCodes` is `list<ParcelCode>`, while
  `AwbExtended::$parcelCodes` is `Collection<int, ParcelCode>`.
- **No enums.** Integer codes are spec-defined but unlabelled: order status
  (`Order::$ordStatus`, `0`–`5`), PUDO `PointType` (`1`–`5`) and `PaymentType`
  (`1`–`4`). They stay `int`. `PudoPaymentType` is the one nested boolean object
  (`Cash`, `Card`, `Online`).

## Request DTOs

Required arguments are those without a default in the constructor.

### `DTOs\Awb\AwbTurModel`

Domestic AWB request, sent by `awbs()->create()` and `awbs()->withGetAwb()`.

| Property | Wire key | Type | Required |
|---|---|---|---|
| `sender` | `Sender` | `Address` | Yes |
| `recipient` | `Recipient` | `Address` | Yes |
| `parcels` | `Parcels` | `int` | Yes |
| `envelopes` | `Envelopes` | `int` | Yes |
| `totalWeight` | `TotalWeight` | `int` | Yes |
| `shipmentPayer` | `ShipmentPayer` | `int` | Yes |
| `serviceId` | `ServiceId` | `?int` | No |
| `declaredValue` | `DeclaredValue` | `?float` | No |
| `cashRepayment` | `CashRepayment` | `?float` | No |
| `bankRepayment` | `BankRepayment` | `?float` | No |
| `otherRepayment` | `OtherRepayment` | `?string` | No |
| `barCodeRepayment` | `BarCodeRepayment` | `?string` | No |
| `paymentInstrumentId` | `PaymentInstrumentId` | `?int` | No |
| `paymentInstrumentValue` | `PaymentInstrumentValue` | `?float` | No |
| `openPackage` | `OpenPackage` | `?bool` | No |
| `saturdayDelivery` | `SaturdayDelivery` | `?bool` | No |
| `morningDelivery` | `MorningDelivery` | `?bool` | No |
| `deliveryTime` | `DeliveryTime` | `?int` | No |
| `observations` | `Observations` | `?string` | No |
| `packageContent` | `PackageContent` | `?string` | No |
| `senderReference1` | `SenderReference1` | `?string` | No |
| `recipientReference1` | `RecipientReference1` | `?string` | No |
| `recipientReference2` | `RecipientReference2` | `?string` | No |
| `invoiceReference` | `InvoiceReference` | `?string` | No |
| `length`, `width`, `height` | `Length`, `Width`, `Height` | `?int` | No |
| `parcelCodes` | `ParcelCodes` | `list<ParcelCode>` | No; omitted when empty |
| `shippingCost` | `ShippingCost` | `?ShippingCost` | No; normally server-computed |

Remaining fields (`deliveryPudoPoint`, `consumerReturnType`,
`returnCodeExpirationDays`, `priceTableId`, `shippingRepayment`, `status`,
`customString`, `barCode`, `hasTertReimbursement`, `hasNonStandardParcel` and the
`transit*`/`*Deposit*` fields) follow the same null-dropping rule and are listed
in the constructor. `toArray()` writes `ShippingCost` inline under
`ShippingCost`.

### `DTOs\Awb\Address`

| Property | Wire key |
|---|---|
| `locationId` | `LocationId` |
| `name` | `Name` |
| `countyId`, `countyName` | `CountyId`, `CountyName` |
| `localityId`, `localityName` | `LocalityId`, `LocalityName` |
| `streetId`, `streetName` | `StreetId`, `StreetName` |
| `buildingNumber` | `BuildingNumber` |
| `addressText` | `AddressText` |
| `contactPerson` | `ContactPerson` |
| `phoneNumber` | `PhoneNumber` |
| `email` | `Email` |
| `codPostal` | `CodPostal` |
| `postalCode` | `PostalCode` |
| `countryId` | `CountryId` |
| `cityCenterName`, `cityCenterId` | `CityCenterName`, `CityCenterId` |

`SenderAddress` and `RecipientAddress` are the smaller shapes used by
`RoutingDetail`: `SenderAddress` has `countyName`, `localityId`, `localityName`
and `zipCode`; `RecipientAddress` adds `streetId`, `streetName`,
`buildingNumber` and `addressText`.

### `DTOs\Awb\ParcelCode`

| Property | Wire key | Type | Required |
|---|---|---|---|
| `code` | `Code` | `string` | Yes |
| `type` | `Type` | `int` | Yes |
| `weight` | `Weight` | `?float` | No |
| `length`, `width`, `height` | `Length`, `Width`, `Height` | `?int` | No |
| `parcelContent` | `ParcelContent` | `?string` | No |
| `netAmount` | `NetAmount` | `?float` | No |

### Other request DTOs

| DTO | Used by | Notes |
|---|---|---|
| `DTOs\Awb\GeneralAwbModel` | `awbs()->createInternational()` | Similar field set plus `isReturn`; omits the dimension, validation, shipping-cost, status, transit and payment-instrument fields |
| `DTOs\Awb\AwbUpdate` | `awbs()->updateReference()` | `AwbBarcode`, `PieceBarcode`, `Reference`, `ReferenceDate`, `ReferenceType`, `Attach` |
| `DTOs\Awb\AwbParcelUpdateModel` | `awbs()->updateParcelCodes()` | `Barcode` and `ParcelCodes`; `ParcelCodes` is always emitted |
| `DTOs\Awb\RoutingDetail` | `awbs()->routingAddress()` | `TotalWeight`, `Sender`, `Recipient` |
| `DTOs\Awb\ReturnAwbModel` | `awbs()->generateReturnNoAuth()` | `Barcode`, `ClientId`, `UserId`, `HasContract`, `IsMarketplace`, `ReturnCodeExpirationDays`, `SenderLocationId` |
| `DTOs\Pricing\ShippingCalculationModel` | `pricing()->calculate*()` | Required: `parcels`, `envelopes`, `totalWeight`, `shipmentPayer`, `declaredValue`, `cashRepayment`, `bankRepayment`, `openPackage`, `saturdayDelivery`, `morningDelivery`; optionals include the `from*`/`to*` locality fields, `priceTableId`, `serviceId` and `parcelCodes` |
| `DTOs\Pricing\ShippingCalculationParcelCode` | `ShippingCalculationModel::$parcelCodes` | `Type` required; `Weight`, `Length`, `Width`, `Height` optional |
| `DTOs\Order\OrderPickupModel` | `orders()->create()` | All optional: pickup window, location, address and parcel fields |
| `DTOs\Order\OrderPickupClickCollectModel` | `orders()->createClickCollect()` | Same as `OrderPickupModel` plus `Observations` |
| `DTOs\Pickup\AwbPickupModel` | `pickup()->request()`, `requestWithGetAwb()` | All optional; `Sender`/`Recipient` are `AddressExtended` |
| `DTOs\Pickup\AwbPickupIndividualModel` | `pickup()->requestIndividualNoAuth()` | Adds `PickUpPudoPoint` and `SenderReturnAccountDetails` |
| `DTOs\PickupLocation\AddressExtended` | pickup locations and pickup requests | `AutomaticEOD` plus the address fields |
| `DTOs\PickupLocation\AddressExtendedClickCollect` | click-and-collect locations | Adds `MinihubId` and `Active` |
| `DTOs\Complaint\ComplaintModel` | `complaints()->saveComplaint()` | Required: `personName`, `email`, `complaintDetails`; optional: `shipment`, `company`, `telephone`, `shipmentContent`, `shipmentPack` |
| `DTOs\Financial\CashReceiptList` | `financial()->cashReceipts()` | Required: `date`, `operator`, `location`; nests `list<CashReceipt>` |
| `DTOs\Financial\CashReceipt` | `CashReceiptList::$cashReceipts` | `ReceiptDate`, `ReceiptNumber`, `ReceiptOperator`, `Amount`, `Awb`, `IdAwb` |

## Response DTOs

### `DTOs\Awb`

| DTO | Role | Nested values |
|---|---|---|
| `AwbExtended` | Full AWB from `get()`, `withGetAwb()` | `sender`/`recipient` are `Address`; `parcelCodes` is `Collection<int, ParcelCode>`; `shippingCost` is `?ShippingCost` |
| `AwbModel` | AWB from `getByDate()` and inside `AwbReturModel` | Same nested shapes as `AwbExtended`, without the return fields |
| `AwbReturModel` | Row from `returns()` | `awb` and `awbReturn` are `?AwbModel` |
| `IncomingAwb` | Incoming AWB list | `returnAddress` is `?Address`; `packages` is `Collection<int, IncomingPackage>` |
| `DeliveryRouteModel` | `routingAddress()` | `transitPriority`, `transitRoute`, `transitCodes`, `deliveryRoute` and deposit fields |

`AwbExtended::$shippingCost` is the server-computed cost, present when the API
returns it.

### `DTOs\Pricing`

`ShippingCost` has `baseCost`, `extraKmCost`, `weightCost`, `insuranceCost`,
`specialCost`, `repaymentCost`, `subtotal`, `tax` and `grandTotal`, all `float`,
read from `BaseCost`, `ExtraKmCost`, `WeightCost`, `InsuranceCost`,
`SpecialCost`, `RepaymentCost`, `Subtotal`, `Tax` and `GrandTotal`.

`PriceTable` has `id` (`PriceTableId`), `name` (`Name`) and `tariffVersion`.
The API misspells the last wire key as `TariffVerion`; the DTO accepts
`TariffVerion`, `TariffVersion` and `tariffVersion`.

### `DTOs\Tracking`

| DTO | Role | Nested values |
|---|---|---|
| `PackageEntity` | `trace()`, `traceOneRequest()`, `traceWithRedirect()`, `traceNoAuth()`, `confirmDelivery()` | `events` is `Collection<int, TraceEvent>` from the wire key `Event` |
| `ShortPackage` | `shortTraceNoAuth()` | `events` from `Event` |
| `AwbTraceDelta` | `deltaEvents()` | `packages` is `Collection<int, PackageDelta>` |
| `PackageDelta` | Inside `AwbTraceDelta` | `events` from `Events` |
| `TraceEvent` | Trace rows | `date`, `eventId`, `description`, `localityName`, `countyName` |

`tracking()` sync-status reads deserialize the wire's `AwbCashModel` through the
shared `DTOs\Financial\AwbCash`, which `financial()` cash-account reads also use.

### Remaining response DTOs

| Namespace | DTOs |
|---|---|
| `DTOs\Complaint` | `ComplaintStatus` (`shipmentExists`, `complaintStatusRo`, `complaintStatusEn`) |
| `DTOs\Financial` | `AwbCash` (also used by `tracking()`), `Invoice` (`id`, `date`, `dueDate`, `series`, `number`, `value`, `total`, `closed`, `balance`, `currencyId`) |
| `DTOs\Geography` | `Country`, `County`, `Locality`, `Street`, `PostalNumber`, `Branch`, `PudoPoint`, `PudoPaymentType` |
| `DTOs\Marketing` | `UpdatedUsersExtended` (`updatedUsers` collection, `totalRecords`), `UpdatedUser` |
| `DTOs\OnlineShop` | `CustomerDetails`, `UserWorkstation`, `WorkstationDetails` |
| `DTOs\Order` | `Order` |
| `DTOs\PickupLocation` | `AddressExtended`, `AddressExtendedClickCollect` |

`Street::$postalNumbers` is a `Collection<int, PostalNumber>`;
`PudoPoint::$acceptedPaymentType` is a `?PudoPaymentType`.

## `Support\Document`

`Daika7ana\Cargus\Support\Document` is a `final readonly` value object, not a
`DTOs\*` class.

| Member | Type | Meaning |
|---|---|---|
| `$contents` | `string` | Raw response body |
| `$mimeType` | `?string` | `Content-Type`, or `null` when the header is absent |
| `isPdf()` | `bool` | `true` when `$contents` starts with `%PDF` |

## Namespace summary

| Namespace | Contents |
|---|---|
| `DTOs\Awb` | AWB requests, addresses, parcel codes, routing and AWB responses |
| `DTOs\Complaint` | `ComplaintModel`, `ComplaintStatus` |
| `DTOs\Financial` | `Invoice`, `AwbCash`, `CashReceipt`, `CashReceiptList` |
| `DTOs\Geography` | Countries, counties, localities, streets, branches, PUDO points |
| `DTOs\Marketing` | Updated users |
| `DTOs\OnlineShop` | Customer and workstation details |
| `DTOs\Order` | Pickup order request and response |
| `DTOs\Pickup` | Pickup request models |
| `DTOs\PickupLocation` | `AddressExtended`, `AddressExtendedClickCollect` |
| `DTOs\Pricing` | Shipping calculation request, cost and price tables |
| `DTOs\Tracking` | Trace, package, delta and sync-status responses |
