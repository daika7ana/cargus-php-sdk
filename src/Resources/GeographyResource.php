<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Resources;

use Daika7ana\Cargus\Client;
use Daika7ana\Cargus\DTOs\Geography\Branch;
use Daika7ana\Cargus\DTOs\Geography\Country;
use Daika7ana\Cargus\DTOs\Geography\County;
use Daika7ana\Cargus\DTOs\Geography\Locality;
use Daika7ana\Cargus\DTOs\Geography\PudoPoint;
use Daika7ana\Cargus\DTOs\Geography\Street;
use Daika7ana\Cargus\Exceptions\AuthenticationException;
use Daika7ana\Cargus\Exceptions\TransportException;
use Daika7ana\Cargus\Exceptions\UnexpectedStatusException;
use Daika7ana\Cargus\Exceptions\ValidationException;
use Daika7ana\Cargus\Support\ApiResponseMapper;
use Daika7ana\SimpleCollections\Collection;
use Psr\Http\Message\ResponseInterface;

/**
 * Geography lookups: counties, countries, localities, streets, branches and PUDO points.
 */
final class GeographyResource
{
    public function __construct(private Client $client) {}

    /**
     * `GET /api/Counties` wraps counties in a `County` array per response item.
     *
     * @return Collection<int, County>
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function counties(int $countryId): Collection
    {
        $response = $this->client->get('/api/Counties', ['countryId' => $countryId]);

        return $this->mapCounties($response->getStatusCode(), $response);
    }

    /**
     * @return Collection<int, County>
     *
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function countiesNoAuth(int $countryId): Collection
    {
        $response = $this->client->getNoAuth('/api/NoAuth/GetCounties', ['countryId' => $countryId]);

        return $this->mapCounties($response->getStatusCode(), $response);
    }

    /**
     * @return Collection<int, Country>
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function countries(): Collection
    {
        $response = $this->client->get('/api/Countries');

        return $this->mapList($response, Country::fromArray(...));
    }

    /**
     * `GET /api/Localities` returns `[{Locality: [...], message}]` items.
     *
     * @return Collection<int, Locality>
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function localities(int $countryId, ?int $countyId = null, ?string $postalCode = null): Collection
    {
        $response = $this->client->get('/api/Localities', [
            'countryId' => $countryId,
            'countyId' => $countyId,
            'postalCode' => $postalCode,
        ]);

        return $this->mapLocalities($response);
    }

    /**
     * @return Collection<int, Locality>
     *
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function localitiesNoAuth(int $countryId, int $countyId): Collection
    {
        $response = $this->client->getNoAuth('/api/NoAuth/GetLocalities', [
            'countryId' => $countryId,
            'countyId' => $countyId,
        ]);

        return $this->mapLocalities($response);
    }

    /**
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function localityDetails(int $localityId): Locality
    {
        $response = $this->client->get('/api/Localities/DetailsLocality', ['LocalityId' => $localityId]);

        if ($response->getStatusCode() === 204) {
            return Locality::fromArray([]);
        }

        $payload = ApiResponseMapper::decodeJson($response);

        return Locality::fromArray(is_array($payload) ? $payload : []);
    }

    /**
     * @return Collection<int, Street>
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function streets(int $localityId): Collection
    {
        $response = $this->client->get('/api/Streets', ['localityId' => $localityId]);

        return $this->mapList($response, Street::fromArray(...));
    }

    /**
     * @return Collection<int, Branch>
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function branches(): Collection
    {
        $response = $this->client->get('/api/Branches');

        return $this->mapList($response, Branch::fromArray(...));
    }

    /**
     * @return Collection<int, PudoPoint>
     *
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function pudoPoints(): Collection
    {
        $response = $this->client->get('/api/PudoPoints');

        return $this->mapList($response, PudoPoint::fromArray(...));
    }

    /**
     * @return Collection<int, County>
     */
    private function mapCounties(int $statusCode, ResponseInterface $response): Collection
    {
        if ($statusCode === 204) {
            return new Collection([]);
        }

        $payload = ApiResponseMapper::decodeJson($response);
        $rows = [];

        foreach (ApiResponseMapper::listOfArrays($payload) as $item) {
            if (isset($item['CountyId']) || isset($item['Name'])) {
                $rows[] = $item;

                continue;
            }

            foreach (ApiResponseMapper::listOfArrays($item['County'] ?? null) as $county) {
                $rows[] = $county;
            }
        }

        return new Collection(array_map(
            static fn(array $row): County => County::fromArray($row),
            $rows,
        ));
    }

    /**
     * @return Collection<int, Locality>
     */
    private function mapLocalities(ResponseInterface $response): Collection
    {
        if ($response->getStatusCode() === 204) {
            return new Collection([]);
        }

        $payload = ApiResponseMapper::decodeJson($response);
        $rows = [];

        foreach (ApiResponseMapper::listOfArrays($payload) as $item) {
            if (isset($item['LocalityId'])) {
                $rows[] = $item;

                continue;
            }

            foreach (ApiResponseMapper::listOfArrays($item['Locality'] ?? null) as $locality) {
                $rows[] = $locality;
            }
        }

        return new Collection(array_map(
            static fn(array $row): Locality => Locality::fromArray($row),
            $rows,
        ));
    }

    /**
     * @template T
     *
     * @param callable(array<string, mixed>): T $factory
     *
     * @return Collection<int, T>
     */
    private function mapList(ResponseInterface $response, callable $factory): Collection
    {
        if ($response->getStatusCode() === 204) {
            return new Collection([]);
        }

        $payload = ApiResponseMapper::decodeJson($response);

        return new Collection(array_map(
            $factory,
            ApiResponseMapper::listOfArrays($payload),
        ));
    }
}
