<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Resources;

use Daika7ana\Cargus\Client;
use Daika7ana\Cargus\DTOs\Pickup\AwbPickupIndividualModel;
use Daika7ana\Cargus\DTOs\Pickup\AwbPickupModel;
use Daika7ana\Cargus\Exceptions\AuthenticationException;
use Daika7ana\Cargus\Exceptions\TransportException;
use Daika7ana\Cargus\Exceptions\UnexpectedStatusException;
use Daika7ana\Cargus\Exceptions\ValidationException;
use Daika7ana\Cargus\Support\ApiResponseMapper;

/**
 * Planned operations: POST /api/AwbPickup, /api/AwbPickup/WithGetAwb and
 * /api/NoAuth/IndividualWithGetAwb.
 */
final class PickupResource
{
    public function __construct(private Client $client) {}

    /**
     * `POST /api/AwbPickup` returns the generated AWB barcode.
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function request(AwbPickupModel $pickup): string
    {
        $response = $this->client->post('/api/AwbPickup', $pickup->toArray());

        return (string) ApiResponseMapper::decodeJson($response);
    }

    /**
     * The 200 schema is an untyped object, so the decoded payload is returned as-is.
     *
     * @return array<string, mixed>
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function requestWithGetAwb(AwbPickupModel $pickup): array
    {
        $response = $this->client->post('/api/AwbPickup/WithGetAwb', $pickup->toArray());
        ApiResponseMapper::assertStatus($response, [200, 204]);

        if ($response->getStatusCode() === 204) {
            return [];
        }

        $payload = ApiResponseMapper::decodeJson($response);

        return is_array($payload) ? $payload : [];
    }

    /**
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function requestIndividualNoAuth(AwbPickupIndividualModel $pickup): string
    {
        $response = $this->client->postNoAuth('/api/NoAuth/IndividualWithGetAwb', $pickup->toArray());
        ApiResponseMapper::assertStatus($response, [200, 204]);

        if ($response->getStatusCode() === 204) {
            return '';
        }

        return (string) ApiResponseMapper::decodeJson($response);
    }
}
