<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit\DTOs\Complaint;

use Daika7ana\Cargus\DTOs\Complaint\ComplaintModel;
use Daika7ana\Cargus\DTOs\Complaint\ComplaintStatus;
use PHPUnit\Framework\TestCase;

final class ComplaintDtoTest extends TestCase
{
    public function testComplaintStatusReadsTheSchema(): void
    {
        $status = ComplaintStatus::fromArray([
            'ShipmentExists' => true,
            'ComplaintStatusRo' => 'Nou',
            'ComplaintStatusEn' => 'New',
        ]);

        self::assertTrue($status->shipmentExists);
        self::assertSame('Nou', $status->complaintStatusRo);
        self::assertSame('New', $status->complaintStatusEn);
    }

    public function testComplaintModelDropsNullOptionals(): void
    {
        $model = new ComplaintModel(
            personName: 'Jane',
            email: 'jane@example.com',
            complaintDetails: 'Broken',
            shipment: 'AWB1',
        );

        self::assertSame([
            'Shipment' => 'AWB1',
            'PersonName' => 'Jane',
            'Email' => 'jane@example.com',
            'ComplaintDetails' => 'Broken',
        ], $model->toArray());
    }
}
