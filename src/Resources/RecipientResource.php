<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Resources;

use Daika7ana\Cargus\Client;
use Daika7ana\Cargus\DTOs\PickupLocation\AddressExtended;
use Daika7ana\Cargus\Exceptions\AuthenticationException;
use Daika7ana\Cargus\Exceptions\TransportException;
use Daika7ana\Cargus\Exceptions\UnexpectedStatusException;
use Daika7ana\Cargus\Exceptions\ValidationException;
use Daika7ana\Cargus\Support\ApiResponseMapper;
use Daika7ana\SimpleCollections\Collection;

/**
 * Planned operations: GET /api/Recipients.
 */
final class RecipientResource
{
    public function __construct(private Client $client) {}

    /**
     * @return Collection<int, AddressExtended>
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function list(): Collection
    {
        $response = $this->client->get('/api/Recipients');
        ApiResponseMapper::assertStatus($response, [200, 204]);

        if ($response->getStatusCode() === 204) {
            return new Collection([]);
        }

        $payload = ApiResponseMapper::decodeJson($response);

        return new Collection(array_map(
            static fn(array $row): AddressExtended => AddressExtended::fromArray($row),
            ApiResponseMapper::listOfArrays($payload),
        ));
    }
}
