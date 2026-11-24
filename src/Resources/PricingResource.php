<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Resources;

use Daika7ana\Cargus\Client;
use Daika7ana\Cargus\DTOs\Pricing\PriceTable;
use Daika7ana\Cargus\DTOs\Pricing\ShippingCalculationModel;
use Daika7ana\Cargus\DTOs\Pricing\ShippingCost;
use Daika7ana\Cargus\Exceptions\AuthenticationException;
use Daika7ana\Cargus\Exceptions\TransportException;
use Daika7ana\Cargus\Exceptions\UnexpectedStatusException;
use Daika7ana\Cargus\Exceptions\ValidationException;
use Daika7ana\Cargus\Support\ApiResponseMapper;
use Daika7ana\SimpleCollections\Collection;
use Psr\Http\Message\ResponseInterface;

/**
 * Shipping calculation and price tables.
 */
final class PricingResource
{
    public function __construct(public readonly Client $client) {}

    /**
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function calculate(ShippingCalculationModel $model): ShippingCost
    {
        $response = $this->client->post('/api/ShippingCalculation', $model->toArray());

        return ShippingCost::fromArray($this->decodeObject($response));
    }

    /**
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function calculateNoAuth(ShippingCalculationModel $model): ShippingCost
    {
        $response = $this->client->postNoAuth('/api/NoAuth/PostShippingCalculation', $model->toArray());

        return ShippingCost::fromArray($this->decodeObject($response));
    }

    /**
     * @return Collection<int, PriceTable>
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function priceTables(): Collection
    {
        $response = $this->client->get('/api/PriceTables');

        if ($response->getStatusCode() === 204) {
            return new Collection([]);
        }

        return new Collection(array_map(
            static fn(array $row): PriceTable => PriceTable::fromArray($row),
            ApiResponseMapper::listOfArrays(ApiResponseMapper::decodeJson($response)),
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeObject(ResponseInterface $response): array
    {
        if ($response->getStatusCode() === 204) {
            return [];
        }

        $payload = ApiResponseMapper::decodeJson($response);

        return is_array($payload) ? $payload : [];
    }
}
