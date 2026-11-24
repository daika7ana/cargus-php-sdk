<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Auth;

use Daika7ana\Cargus\Config\ClientConfig;
use Daika7ana\Cargus\Exceptions\AuthenticationException;
use Daika7ana\Cargus\Exceptions\TransportException;
use Daika7ana\Cargus\Http\HttpClientInterface;
use Daika7ana\Cargus\Support\ApiResponseMapper;
use Daika7ana\Cargus\Support\JsonHelper;
use JsonException;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class CredentialsAuthenticator
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
        private ClientConfig $config,
    ) {}

    /**
     * Cargus returns a JSON-encoded string token valid for 24 hours.
     *
     * @throws AuthenticationException
     * @throws TransportException
     */
    public function authenticate(string $username, string $password, string $path = '/api/LoginUser'): Token
    {
        $request = $this->requestFactory
            ->createRequest('POST', rtrim($this->config->baseUrl, '/') . $path)
            ->withHeader('Accept', 'application/json')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Ocp-Apim-Subscription-Key', $this->config->subscriptionKey)
            ->withHeader('Ocp-Apim-Trace', 'true')
            ->withBody($this->streamFactory->createStream(JsonHelper::encode([
                'UserName' => $username,
                'Password' => $password,
            ])));

        $response = $this->httpClient->sendRequest($request, $this->config->timeout);

        if ($response->getStatusCode() !== 200) {
            throw new AuthenticationException(
                message: sprintf('Authentication failed with status %d.', $response->getStatusCode()),
                response: $response,
                retryAfter: ApiResponseMapper::retryAfter($response),
            );
        }

        try {
            $payload = JsonHelper::decode((string) $response->getBody());
        } catch (JsonException $e) {
            throw new AuthenticationException(
                message: 'Authentication response is not valid JSON.',
                previous: $e,
                response: $response,
                retryAfter: ApiResponseMapper::retryAfter($response),
            );
        }

        if (!is_string($payload) || $payload === '') {
            throw new AuthenticationException(
                message: 'Authentication response did not contain a token string.',
                response: $response,
                retryAfter: ApiResponseMapper::retryAfter($response),
            );
        }

        return Token::fromTokenString($payload);
    }
}
