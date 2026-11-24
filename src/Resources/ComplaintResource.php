<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Resources;

use Daika7ana\Cargus\Client;
use Daika7ana\Cargus\DTOs\Complaint\ComplaintModel;
use Daika7ana\Cargus\DTOs\Complaint\ComplaintStatus;
use Daika7ana\Cargus\Exceptions\AuthenticationException;
use Daika7ana\Cargus\Exceptions\TransportException;
use Daika7ana\Cargus\Exceptions\UnexpectedStatusException;
use Daika7ana\Cargus\Exceptions\ValidationException;
use Daika7ana\Cargus\Support\ApiResponseMapper;

/**
 * Complaint status check and submission.
 */
final class ComplaintResource
{
    public function __construct(public readonly Client $client) {}

    /**
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function checkAwb(string $barCode): ?ComplaintStatus
    {
        $response = $this->client->post('/api/Complaints/CheckAwb', [], ['barCode' => $barCode]);
        ApiResponseMapper::assertStatus($response, [200, 204]);

        if ($response->getStatusCode() === 204) {
            return null;
        }

        $payload = ApiResponseMapper::decodeJson($response);

        return ComplaintStatus::fromArray(is_array($payload) ? $payload : []);
    }

    /**
     * @throws AuthenticationException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     * @throws TransportException
     */
    public function saveComplaint(ComplaintModel $complaint): bool
    {
        $payload = ApiResponseMapper::decodeJson(
            $this->client->post('/api/Complaints/SaveComplaint', $complaint->toArray()),
        );

        return $payload === true;
    }
}
