<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\DTOs\Pickup;

use Daika7ana\Cargus\DTOs\Pickup\AwbPickupIndividualModel;
use Daika7ana\Cargus\DTOs\PickupLocation\AddressExtended;
use PHPUnit\Framework\TestCase;

final class AwbPickupIndividualModelTest extends TestCase
{
    public function testToArraySerializesIndividualFieldsAndDropsNullOptionals(): void
    {
        $model = new AwbPickupIndividualModel(
            pickUpPudoPoint: 7,
            sender: new AddressExtended(name: 'Sender'),
            parcels: 1,
            senderReturnAccountDetails: ['Name' => 'Sender', 'Iban' => 'RO49AAAA1B31007593840000'],
        );

        self::assertSame([
            'PickUpPudoPoint' => 7,
            'SenderReturnAccountDetails' => ['Name' => 'Sender', 'Iban' => 'RO49AAAA1B31007593840000'],
            'Sender' => ['Name' => 'Sender'],
            'Parcels' => 1,
        ], $model->toArray());
    }
}
