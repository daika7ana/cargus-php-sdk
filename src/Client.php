<?php

declare(strict_types=1);

namespace Daika7ana\Cargus;

use Daika7ana\Cargus\Auth\CredentialsAuthenticator;
use Daika7ana\Cargus\Auth\InMemoryTokenStore;
use Daika7ana\Cargus\Auth\Token;
use Daika7ana\Cargus\Auth\TokenStoreInterface;
use Daika7ana\Cargus\Config\ClientConfig;
use Daika7ana\Cargus\Exceptions\AuthenticationException;
use Daika7ana\Cargus\Exceptions\TransportException;
use Daika7ana\Cargus\Exceptions\UnexpectedStatusException;
use Daika7ana\Cargus\Exceptions\ValidationException;
use Daika7ana\Cargus\Http\GuzzleHttpClient;
use Daika7ana\Cargus\Http\HttpClientInterface;
use Daika7ana\Cargus\Http\Psr18HttpClientAdapter;
use Daika7ana\Cargus\Resources\AwbResource;
use Daika7ana\Cargus\Resources\ComplaintResource;
use Daika7ana\Cargus\Resources\DocumentResource;
use Daika7ana\Cargus\Resources\FinancialResource;
use Daika7ana\Cargus\Resources\GeographyResource;
use Daika7ana\Cargus\Resources\MarketingResource;
use Daika7ana\Cargus\Resources\OnlineShopResource;
use Daika7ana\Cargus\Resources\OrderResource;
use Daika7ana\Cargus\Resources\PickupLocationResource;
use Daika7ana\Cargus\Resources\PickupResource;
use Daika7ana\Cargus\Resources\PricingResource;
use Daika7ana\Cargus\Resources\RecipientResource;
use Daika7ana\Cargus\Resources\TrackingResource;
use Daika7ana\Cargus\Support\ApiResponseMapper;
use Daika7ana\Cargus\Support\JsonHelper;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/** @phpstan-consistent-constructor */
class Client
{
    private HttpClientInterface $httpClient;

    private ClientConfig $config;

    private TokenStoreInterface $tokenStore;

    private string $username;

    private string $password;

    private string $loginPath = '/api/LoginUser';

    private ?AwbResource $awbResource = null;

    private ?DocumentResource $documentResource = null;

    private ?TrackingResource $trackingResource = null;

    private ?PickupResource $pickupResource = null;

    private ?PickupLocationResource $pickupLocationResource = null;

    private ?OrderResource $orderResource = null;

    private ?GeographyResource $geographyResource = null;

    private ?PricingResource $pricingResource = null;

    private ?FinancialResource $financialResource = null;

    private ?OnlineShopResource $onlineShopResource = null;

    private ?RecipientResource $recipientResource = null;

    private ?ComplaintResource $complaintResource = null;

    private ?MarketingResource $marketingResource = null;

    public function __construct(
        ClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
        ?ClientConfig $config = null,
        ?TokenStoreInterface $tokenStore = null,
    ) {
        if ($config !== null && $config->timeout !== null && !$httpClient instanceof HttpClientInterface) {
            throw new \InvalidArgumentException('ClientConfig::timeout cannot be applied to a foreign PSR-18 client; configure the timeout on that client or leave it null.');
        }

        $this->httpClient = $httpClient instanceof HttpClientInterface
            ? $httpClient
            : new Psr18HttpClientAdapter($httpClient);
        $this->config = $config ?? new ClientConfig();
        $this->tokenStore = $tokenStore ?? new InMemoryTokenStore();
        $this->username = $this->config->username;
        $this->password = $this->config->password;
    }

    /**
     * @throws AuthenticationException
     * @throws TransportException
     * @throws ValidationException
     */
    public function authenticate(string $username, string $password): void
    {
        $this->login($username, $password, '/api/LoginUser', 'Cargus username and password are required for login.');
    }

    /**
     * `POST /api/LoginUser/Juridic` mirrors {@see authenticate()} for juridic entities.
     *
     * @throws AuthenticationException
     * @throws TransportException
     * @throws ValidationException
     */
    public function authenticateJuridic(string $username, string $password): void
    {
        $this->login($username, $password, '/api/LoginUser/Juridic', 'Cargus username and password are required for juridic login.');
    }

    public function getConfig(): ClientConfig
    {
        return $this->config;
    }

    /**
     * A client sharing this client's transport, PSR-17 factories, token store
     * and credentials, but with a different request timeout budget. The token
     * is shared through the store, so the derived client starts with the same
     * token instead of logging in again.
     */
    public function withTimeout(?float $timeout): self
    {
        if ($timeout !== null && $this->httpClient instanceof Psr18HttpClientAdapter) {
            throw new \InvalidArgumentException('ClientConfig::timeout cannot be applied to a foreign PSR-18 client; configure the timeout on that client or leave it null.');
        }

        $client = new static(
            $this->httpClient,
            $this->requestFactory,
            $this->streamFactory,
            $this->config->withTimeout($timeout),
            $this->tokenStore,
        );
        $client->username = $this->username;
        $client->password = $this->password;
        $client->loginPath = $this->loginPath;

        return $client;
    }

    public function getToken(): ?Token
    {
        return $this->tokenStore->getToken();
    }

    public function setToken(Token|string $token): self
    {
        $this->storeToken(is_string($token) ? Token::fromTokenString($token) : $token);

        return $this;
    }

    /**
     * `GET /api/TokenVerification` returns true while the bearer token is valid.
     *
     * @throws AuthenticationException
     * @throws TransportException
     * @throws UnexpectedStatusException
     * @throws ValidationException
     */
    public function tokenVerification(): bool
    {
        $payload = ApiResponseMapper::decodeJson(
            $this->send($this->createRequest('GET', '/api/TokenVerification')),
        );

        return $payload === true;
    }

    /**
     * @param array<string, mixed> $query Scalars and lists (`awbs` becomes `awbs[]`)
     */
    public function get(string $path, array $query = [], ?float $timeout = null): ResponseInterface
    {
        return $this->send($this->createRequest('GET', $path, $query), $timeout);
    }

    /**
     * @param array<string, mixed> $body
     * @param array<string, mixed> $query
     */
    public function post(string $path, array $body = [], array $query = [], ?float $timeout = null): ResponseInterface
    {
        return $this->send($this->withJsonBody($this->createRequest('POST', $path, $query), $body), $timeout);
    }

    /**
     * @param array<string, mixed> $body
     * @param array<string, mixed> $query
     */
    public function put(string $path, array $body = [], array $query = [], ?float $timeout = null): ResponseInterface
    {
        return $this->send($this->withJsonBody($this->createRequest('PUT', $path, $query), $body), $timeout);
    }

    /**
     * @param array<string, mixed> $query
     */
    public function delete(string $path, array $query = [], ?float $timeout = null): ResponseInterface
    {
        return $this->send($this->createRequest('DELETE', $path, $query), $timeout);
    }

    /**
     * Unauthenticated endpoint: subscription key only, no bearer token.
     *
     * @param array<string, mixed> $query
     */
    public function getNoAuth(string $path, array $query = [], ?float $timeout = null): ResponseInterface
    {
        return $this->sendNoAuth($this->createRequest('GET', $path, $query), $timeout);
    }

    /**
     * Unauthenticated endpoint: subscription key only, no bearer token.
     *
     * @param array<string, mixed> $body
     * @param array<string, mixed> $query
     */
    public function postNoAuth(string $path, array $body = [], array $query = [], ?float $timeout = null): ResponseInterface
    {
        return $this->sendNoAuth($this->withJsonBody($this->createRequest('POST', $path, $query), $body), $timeout);
    }

    /**
     * @internal Used by the bundled resources. Not part of the supported API.
     *
     * @param array<string, mixed> $query Scalars and lists (`awbs` becomes `awbs[]`)
     */
    public function createRequest(string $method, string $path, array $query = []): RequestInterface
    {
        $uri = rtrim($this->config->baseUrl, '/') . $path;
        $queryString = self::buildQuery($query);

        if ($queryString !== '') {
            $uri .= '?' . $queryString;
        }

        $request = $this->requestFactory->createRequest($method, $uri)
            ->withHeader('Accept', 'application/json')
            ->withHeader('Ocp-Apim-Subscription-Key', $this->config->subscriptionKey)
            ->withHeader('Ocp-Apim-Trace', 'true');

        return $this->applyToken($request, $this->currentToken());
    }

    /**
     * @internal Used by the bundled resources. Not part of the supported API.
     *
     * @param array<string, mixed> $body
     *
     * @throws \JsonException
     */
    public function withJsonBody(RequestInterface $request, array $body): RequestInterface
    {
        return $request
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->streamFactory->createStream(JsonHelper::encode($body)));
    }

    /**
     * Send an authenticated request, re-authenticating on expiry or a 401 once.
     *
     * @internal Used by the bundled resources. Not part of the supported API.
     *
     * @throws AuthenticationException
     * @throws TransportException
     * @throws ValidationException
     */
    public function send(RequestInterface $request, ?float $timeout = null): ResponseInterface
    {
        $token = $this->currentToken();

        $this->assertSubscriptionKeyConfigured();

        if ($token === null) {
            $this->assertCredentialsConfigured();
        }

        if ($token?->isExpired() === true && $this->canReauthenticate()) {
            $token = $this->reauthenticate();
        }

        $request = $this->applyToken($request, $token);
        $response = $this->httpClient->sendRequest($request, $timeout ?? $this->config->timeout);

        if ($response->getStatusCode() === 401 && $this->canReauthenticate()) {
            $request = $this->applyToken($request, $this->reauthenticate());
            $response = $this->httpClient->sendRequest($request, $timeout ?? $this->config->timeout);
        }

        return $response;
    }

    /**
     * Send without a bearer token; only the subscription key is required.
     *
     * @internal Used by the bundled resources. Not part of the supported API.
     *
     * @throws TransportException
     * @throws ValidationException
     */
    public function sendNoAuth(RequestInterface $request, ?float $timeout = null): ResponseInterface
    {
        $this->assertSubscriptionKeyConfigured();

        $request = $request->withoutHeader('Authorization');

        return $this->httpClient->sendRequest($request, $timeout ?? $this->config->timeout);
    }

    public function awbs(): AwbResource
    {
        return $this->awbResource ??= new AwbResource($this);
    }

    public function documents(): DocumentResource
    {
        return $this->documentResource ??= new DocumentResource($this);
    }

    public function tracking(): TrackingResource
    {
        return $this->trackingResource ??= new TrackingResource($this);
    }

    public function pickup(): PickupResource
    {
        return $this->pickupResource ??= new PickupResource($this);
    }

    public function pickupLocations(): PickupLocationResource
    {
        return $this->pickupLocationResource ??= new PickupLocationResource($this);
    }

    public function orders(): OrderResource
    {
        return $this->orderResource ??= new OrderResource($this);
    }

    public function geography(): GeographyResource
    {
        return $this->geographyResource ??= new GeographyResource($this);
    }

    public function pricing(): PricingResource
    {
        return $this->pricingResource ??= new PricingResource($this);
    }

    public function financial(): FinancialResource
    {
        return $this->financialResource ??= new FinancialResource($this);
    }

    public function onlineShop(): OnlineShopResource
    {
        return $this->onlineShopResource ??= new OnlineShopResource($this);
    }

    public function recipients(): RecipientResource
    {
        return $this->recipientResource ??= new RecipientResource($this);
    }

    public function complaints(): ComplaintResource
    {
        return $this->complaintResource ??= new ComplaintResource($this);
    }

    public function marketing(): MarketingResource
    {
        return $this->marketingResource ??= new MarketingResource($this);
    }

    public static function create(
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
        ?ClientConfig $config = null,
        ?TokenStoreInterface $tokenStore = null,
    ): self {
        $httpClient ??= new GuzzleHttpClient();
        $config ??= ClientConfig::fromEnvironment();

        if ($requestFactory === null || $streamFactory === null) {
            $psr17Factory = new \GuzzleHttp\Psr7\HttpFactory();
            $requestFactory ??= $psr17Factory;
            $streamFactory ??= $psr17Factory;
        }

        return new self($httpClient, $requestFactory, $streamFactory, $config, $tokenStore);
    }

    private function makeAuthenticator(): CredentialsAuthenticator
    {
        return new CredentialsAuthenticator(
            $this->httpClient,
            $this->requestFactory,
            $this->streamFactory,
            $this->config,
        );
    }

    private function login(string $username, string $password, string $path, string $validationMessage): void
    {
        $this->assertSubscriptionKeyConfigured();

        if ($username === '' || $password === '') {
            throw new ValidationException(message: $validationMessage);
        }

        $token = $this->makeAuthenticator()->authenticate($username, $password, $path);

        // Assign only after a successful login so a failed attempt keeps the working credentials.
        $this->username = $username;
        $this->password = $password;
        $this->loginPath = $path;
        $this->storeToken($token);
    }

    /**
     * @throws AuthenticationException
     * @throws TransportException
     */
    private function reauthenticate(): Token
    {
        if (!$this->canReauthenticate()) {
            throw new AuthenticationException(message: 'Cannot re-authenticate: username and password are missing.');
        }

        $token = $this->makeAuthenticator()->authenticate($this->username, $this->password, $this->loginPath);
        $this->storeToken($token);

        return $token;
    }

    private function canReauthenticate(): bool
    {
        return $this->username !== '' && $this->password !== '';
    }

    private function assertCredentialsConfigured(): void
    {
        if ($this->username === '' || $this->password === '') {
            throw new ValidationException(
                message: 'Cargus username and password are required for authenticated calls.',
            );
        }
    }

    private function assertSubscriptionKeyConfigured(): void
    {
        if ($this->config->subscriptionKey === '') {
            throw new ValidationException(
                message: 'Cargus subscription key is required. Pass it to ClientConfig or CARGUS_SUBSCRIPTION_KEY.',
            );
        }
    }

    private function applyToken(RequestInterface $request, ?Token $token): RequestInterface
    {
        if ($token === null) {
            return $request->withoutHeader('Authorization');
        }

        return $request
            ->withoutHeader('Authorization')
            ->withHeader('Authorization', sprintf('%s %s', $token->tokenType, $token->accessToken));
    }

    private function currentToken(): ?Token
    {
        return $this->tokenStore->getToken();
    }

    private function storeToken(Token $token): void
    {
        $this->tokenStore->setToken($token);
    }

    /**
     * @param array<string, mixed> $query
     */
    private static function buildQuery(array $query): string
    {
        $parts = [];

        foreach ($query as $key => $value) {
            if ($value === null) {
                continue;
            }

            if (is_array($value)) {
                foreach ($value as $item) {
                    $parts[] = rawurlencode((string) $key . '[]') . '=' . rawurlencode((string) $item);
                }

                continue;
            }

            $parts[] = rawurlencode((string) $key) . '=' . rawurlencode(is_bool($value) ? ($value ? '1' : '0') : (string) $value);
        }

        return implode('&', $parts);
    }
}
