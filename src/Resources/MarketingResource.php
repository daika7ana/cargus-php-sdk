<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Resources;

use Daika7ana\Cargus\Client;
use Daika7ana\Cargus\DTOs\Marketing\UpdatedUsersExtended;
use Daika7ana\Cargus\Exceptions\AuthenticationException;
use Daika7ana\Cargus\Exceptions\TransportException;
use Daika7ana\Cargus\Exceptions\UnexpectedStatusException;
use Daika7ana\Cargus\Exceptions\ValidationException;
use Daika7ana\Cargus\Support\ApiResponseMapper;

/**
 * Marketing user updates and email unsubscribe.
 */
final class MarketingResource
{
    public function __construct(public readonly Client $client) {}

    /**
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function updatedUsers(
        string $startDate,
        string $endDate,
        int $pageNumber,
        int $rowsPerPage,
    ): ?UpdatedUsersExtended {
        $response = $this->client->get('/api/Marketing/GetUpdatedUsers', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'pageNumber' => $pageNumber,
            'rowsPerPage' => $rowsPerPage,
        ]);
        ApiResponseMapper::assertStatus($response, [200, 204]);

        if ($response->getStatusCode() === 204) {
            return null;
        }

        $payload = ApiResponseMapper::decodeJson($response);

        return UpdatedUsersExtended::fromArray(is_array($payload) ? $payload : []);
    }

    /**
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function unsubscribeEmail(string $email): bool
    {
        $payload = ApiResponseMapper::decodeJson(
            $this->client->post('/api/Marketing/MarketingEmailUnsubscribe', [], ['email' => $email]),
        );

        return $payload === true;
    }
}
