<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Smoke;

use Daika7ana\Cargus\DTOs\Awb\Address;
use Daika7ana\Cargus\DTOs\Awb\AwbParcelUpdateModel;
use Daika7ana\Cargus\DTOs\Awb\AwbTurModel;
use Daika7ana\Cargus\DTOs\Awb\AwbUpdate;
use Daika7ana\Cargus\DTOs\Awb\ParcelCode;
use Daika7ana\Cargus\DTOs\Complaint\ComplaintStatus;
use Daika7ana\Cargus\DTOs\Financial\AwbCash;
use Daika7ana\Cargus\DTOs\Geography\Street;
use Daika7ana\Cargus\Exceptions\AuthenticationException;
use Daika7ana\SimpleCollections\Collection;

/**
 * AWB create/update/trace/documents/delete lifecycle. Never smoke-tests
 * pickup() or orders() creation, nor AwbPickup: those dispatch a physical courier.
 */
final class AwbWriteLifecycleSmokeTest extends SmokeTestCase
{
    public function testCreateTraceDocumentsAndDeleteAwb(): void
    {
        $this->requireWriteAccess('AWB create/trace/documents/delete smoke test');

        $client = $this->client();
        $locality = $this->sampleLocality();
        $picker = $client->pickupLocations()->list()->first();

        if ($picker === null) {
            $this->markTestSkipped('Skipping smoke test because the account has no pickup location to use as AWB sender.');
        }

        $streetName = $this->env('CARGUS_SMOKE_STREET_NAME', 'Strada Testului');
        $postalCode = $locality->postalCode;
        $sampleStreet = $client->geography()->streets($locality->id)->first();

        if ($sampleStreet instanceof Street) {
            $streetName = $sampleStreet->name;
            $postalCode = $sampleStreet->postalNumbers->first()?->postalCode ?? $postalCode;
        }

        if ($postalCode === '' || $postalCode === '0') {
            $this->markTestSkipped(sprintf('Skipping smoke test because locality %d has no usable postal code for the recipient.', $locality->id));
        }

        $awb = new AwbTurModel(
            sender: new Address(
                name: $picker->name,
                countyId: $picker->countyId,
                countyName: $picker->countyName,
                localityId: $picker->localityId,
                localityName: $picker->localityName,
                streetId: $picker->streetId,
                streetName: $picker->streetName,
                buildingNumber: $picker->buildingNumber,
                addressText: $picker->addressText,
                contactPerson: $picker->contactPerson,
                phoneNumber: $picker->phoneNumber,
                email: $picker->email,
                postalCode: $picker->postalCode,
                countryId: $picker->countryId,
            ),
            recipient: new Address(
                name: $this->env('CARGUS_SMOKE_RECIPIENT_NAME', 'Smoke Test Recipient'),
                countyId: $locality->countyId,
                countyName: $locality->parentName !== '' ? $locality->parentName : null,
                localityId: $locality->id,
                localityName: $locality->name,
                streetId: $sampleStreet instanceof Street ? $sampleStreet->id : null,
                streetName: $streetName,
                buildingNumber: '1',
                addressText: $streetName . ' 1',
                contactPerson: 'Smoke Test',
                phoneNumber: $this->env('CARGUS_SMOKE_RECIPIENT_PHONE', '0722123456'),
                email: 'smoke@example.test',
                postalCode: $postalCode,
            ),
            parcels: 1,
            envelopes: 0,
            totalWeight: 1,
            shipmentPayer: 1,
            serviceId: $this->envInt('CARGUS_SMOKE_SERVICE_ID'),
            observations: 'SDK smoke test, deleted after run',
            senderReference1: $this->smokeReference('SMOKE'),
            parcelCodes: [new ParcelCode(code: '1', type: 1, weight: 1.0, length: 10, width: 10, height: 10)],
        );

        $barCode = $client->awbs()->create($awb);

        self::assertNotSame('', $barCode, 'AWB creation did not return a barcode.');

        try {
            self::assertInstanceOf(Collection::class, $client->tracking()->trace($barCode));

            self::assertTrue(
                $client->awbs()->updateReference(new AwbUpdate(
                    awbBarcode: $barCode,
                    reference: $this->smokeReference('REF'),
                    referenceType: '1',
                    referenceDate: gmdate('Y-m-d'),
                )),
                'updateReference() did not report true.',
            );

            self::assertSame(
                $barCode,
                $client->awbs()->updateParcelCodes(new AwbParcelUpdateModel(
                    barcode: $barCode,
                    parcelCodes: [new ParcelCode(code: $barCode, type: 2)],
                )),
                'updateParcelCodes() did not echo the AWB barcode.',
            );

            self::assertIsString($client->tracking()->scan($barCode));
            $syncStatus = $client->tracking()->syncStatusByBarCode($barCode);
            self::assertTrue($syncStatus === null || $syncStatus instanceof AwbCash);

            try {
                $complaint = $client->complaints()->checkAwb($barCode);
                self::assertTrue($complaint === null || $complaint instanceof ComplaintStatus);
            } catch (AuthenticationException) {
                // The shared test account is not authorized for /api/Complaints.
            }

            self::assertInstanceOf(Collection::class, $client->financial()->cashAccount($barCode));

            $documents = $client->documents()->awbDocuments($barCode, 'PDF');
            self::assertIsString($documents);

            if ($documents !== '') {
                self::assertStringStartsWith('%PDF', $documents);
            }

            $token = $client->getToken()?->accessToken ?? '';
            $pdf = $client->documents()->pdfAwbDocuments($token, $barCode);
            self::assertIsString($pdf->contents);

            if ($pdf->contents !== '') {
                self::assertTrue($pdf->isPdf(), 'PDF document contents do not start with %PDF.');
            }
        } finally {
            self::assertTrue($client->awbs()->delete($barCode), sprintf('Failed to delete AWB %s.', $barCode));
        }
    }
}
