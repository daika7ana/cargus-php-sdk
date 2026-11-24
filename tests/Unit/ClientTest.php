<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Unit;

use Daika7ana\Cargus\Auth\Token;
use Daika7ana\Cargus\Client;
use Daika7ana\Cargus\Config\ClientConfig;
use Daika7ana\Cargus\Exceptions\AuthenticationException;
use Daika7ana\Cargus\Exceptions\ValidationException;
use Daika7ana\Cargus\Tests\Support\FakeHttpClient;
use Daika7ana\Cargus\Tests\Support\Fixture;
use Daika7ana\Cargus\Tests\Support\TestClientFactory;
use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class ClientTest extends TestCase
{
    public function testAuthenticatePostsJsonCredentialsWithTheSubscriptionKey(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json('fresh-token'),
        );

        $client->authenticate('login-user', 'login-pass');

        self::assertSame('POST', $http->lastRequest?->getMethod());
        self::assertSame('/api/LoginUser', $http->lastRequest?->getUri()->getPath());
        self::assertSame(
            '{"UserName":"login-user","Password":"login-pass"}',
            (string) $http->lastRequest?->getBody(),
        );
        self::assertSame(TestClientFactory::SUBSCRIPTION_KEY, $http->lastRequest?->getHeaderLine('Ocp-Apim-Subscription-Key'));
        self::assertSame('true', $http->lastRequest?->getHeaderLine('Ocp-Apim-Trace'));
        self::assertSame('fresh-token', $client->getToken()?->accessToken);
    }

    public function testTokenAndSubscriptionKeyAreSentOnResourceRequests(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([['County' => [], 'message' => 'ok']]),
        );
        $client->setToken(new Token('abc'));

        $client->get('/api/Counties', ['countryId' => 5]);

        self::assertSame('Bearer abc', $http->lastRequest?->getHeaderLine('Authorization'));
        self::assertSame(TestClientFactory::SUBSCRIPTION_KEY, $http->lastRequest?->getHeaderLine('Ocp-Apim-Subscription-Key'));
        self::assertSame('true', $http->lastRequest?->getHeaderLine('Ocp-Apim-Trace'));
    }

    public function testRejectedTokenIsRetriedOnceAfterReauthentication(): void
    {
        $calls = [];

        [$client, $http] = TestClientFactory::create(function (RequestInterface $request) use (&$calls): ResponseInterface {
            $calls[] = (string) $request->getUri();

            if (str_contains($request->getUri()->getPath(), '/api/LoginUser')) {
                return Fixture::json('fresh');
            }

            return $request->getHeaderLine('Authorization') === 'Bearer fresh'
                ? Fixture::json([])
                : Fixture::raw('', 401);
        });

        $client->setToken(new Token('revoked'));

        $response = $client->get('/api/Counties', ['countryId' => 1]);

        self::assertSame(200, $response->getStatusCode());
        self::assertCount(3, $calls);
        self::assertSame('Bearer fresh', $http->lastRequest?->getHeaderLine('Authorization'));
    }

    public function testConfiguredCredentialsLogInAfterA401(): void
    {
        $calls = [];

        [$client, $http] = TestClientFactory::create(function (RequestInterface $request) use (&$calls): ResponseInterface {
            $calls[] = (string) $request->getUri();

            if (str_contains($request->getUri()->getPath(), '/api/LoginUser')) {
                return Fixture::json('fresh');
            }

            return $request->getHeaderLine('Authorization') === 'Bearer fresh'
                ? Fixture::json([])
                : Fixture::raw('', 401);
        });

        $response = $client->get('/api/Counties', ['countryId' => 1]);

        self::assertSame(200, $response->getStatusCode());
        self::assertCount(3, $calls);
        self::assertSame('Bearer fresh', $http->lastRequest?->getHeaderLine('Authorization'));
    }

    public function testRejectedTokenIsRetriedOnlyOnce(): void
    {
        $calls = [];

        [$client] = TestClientFactory::create(function (RequestInterface $request) use (&$calls): ResponseInterface {
            $calls[] = (string) $request->getUri();

            return str_contains($request->getUri()->getPath(), '/api/LoginUser')
                ? Fixture::json('fresh')
                : Fixture::raw('', 401);
        });

        $client->setToken(new Token('revoked'));

        $response = $client->get('/api/Counties', ['countryId' => 1]);

        self::assertSame(401, $response->getStatusCode());
        self::assertCount(3, $calls);
    }

    public function testExpiredTokenIsRefreshedBeforeSending(): void
    {
        $calls = [];

        [$client, $http] = TestClientFactory::create(function (RequestInterface $request) use (&$calls): ResponseInterface {
            $calls[] = $request->getUri()->getPath();

            return str_contains($request->getUri()->getPath(), '/api/LoginUser')
                ? Fixture::json('fresh')
                : Fixture::json([]);
        });

        $client->setToken(new Token('stale', new \DateTimeImmutable('-1 hour')));

        $client->get('/api/Counties', ['countryId' => 1]);

        self::assertSame(['/api/LoginUser', '/api/Counties'], $calls);
        self::assertSame('Bearer fresh', $http->lastRequest?->getHeaderLine('Authorization'));
    }

    public function testNoEagerLoginWhenATokenIsAlreadyStored(): void
    {
        $calls = [];

        [$client] = TestClientFactory::create(function (RequestInterface $request) use (&$calls): ResponseInterface {
            $calls[] = $request->getUri()->getPath();

            return Fixture::json([]);
        });
        $client->setToken(new Token('abc'));

        $client->get('/api/Counties', ['countryId' => 1]);

        self::assertSame(['/api/Counties'], $calls);
    }

    public function testMissingCredentialsAreRejectedBeforeAnyRequest(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([]),
            new ClientConfig(subscriptionKey: TestClientFactory::SUBSCRIPTION_KEY),
        );

        try {
            $client->get('/api/Counties', ['countryId' => 1]);
            self::fail('Expected ValidationException.');
        } catch (ValidationException $e) {
            self::assertStringContainsString('username and password', $e->getMessage());
        }

        self::assertNull($http->lastRequest);
    }

    public function testMissingSubscriptionKeyIsRejectedBeforeAnyRequest(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([]),
            new ClientConfig(username: 'test-user', password: 'test-pass'),
        );

        $this->expectException(ValidationException::class);

        try {
            $client->get('/api/Counties', ['countryId' => 1]);
        } finally {
            self::assertNull($http->lastRequest);
        }
    }

    public function testWithTimeoutDerivesAClientSharingTheTransportAndToken(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([['County' => [], 'message' => 'ok']]),
            new ClientConfig(
                username: 'test-user',
                password: 'test-pass',
                subscriptionKey: TestClientFactory::SUBSCRIPTION_KEY,
                timeout: 10.0,
            ),
        );
        $client->setToken(new Token('shared-token'));

        $derived = $client->withTimeout(30.0);

        self::assertSame(30.0, $derived->getConfig()->timeout);
        self::assertSame(10.0, $client->getConfig()->timeout);
        self::assertSame('shared-token', $derived->getToken()?->accessToken);

        // A token stored later through the original is visible to the derived client.
        $client->setToken('later-token');
        self::assertSame('later-token', $derived->getToken()?->accessToken);

        $derived->get('/api/Counties', ['countryId' => 1]);

        self::assertSame(30.0, $http->lastTimeout);
    }

    public function testWithTimeoutRejectsAForeignPsr18Client(): void
    {
        $foreign = new class implements \Psr\Http\Client\ClientInterface {
            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                return Fixture::json([]);
            }
        };

        $client = Client::create(
            httpClient: $foreign,
            config: new ClientConfig(subscriptionKey: TestClientFactory::SUBSCRIPTION_KEY),
        );

        $client->withTimeout(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ClientConfig::timeout cannot be applied to a foreign PSR-18 client; configure the timeout on that client or leave it null.');

        $client->withTimeout(30.0);
    }

    public function testApiErrorBodyIsExposedOnTheException(): void
    {
        [$client] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json(
                ['Error' => 'Invalid AWB', 'errors' => ['weight' => ['required']]],
                400,
            ),
        );
        $client->setToken(new Token('abc'));

        try {
            $client->tokenVerification();
            self::fail('Expected ValidationException.');
        } catch (ValidationException $e) {
            self::assertSame('Invalid AWB', $e->getMessage());
            self::assertNotNull($e->error);
            self::assertSame('Invalid AWB', $e->error->message);
            self::assertSame(['weight' => ['required']], $e->error->errors);
            self::assertTrue($e->error->isValidationRelated());
        }
    }

    public function testNoAuthEndpointNeedsOnlyTheSubscriptionKey(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([]),
            new ClientConfig(subscriptionKey: TestClientFactory::SUBSCRIPTION_KEY),
        );

        $response = $client->getNoAuth('/api/NoAuth/GetCounties', ['countryId' => 1]);

        self::assertSame(200, $response->getStatusCode());
        self::assertFalse($http->lastRequest?->hasHeader('Authorization'));
        self::assertSame(TestClientFactory::SUBSCRIPTION_KEY, $http->lastRequest?->getHeaderLine('Ocp-Apim-Subscription-Key'));
    }

    public function testTokenVerificationReturnsTheBooleanBody(): void
    {
        [$client] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json(true),
        );
        $client->setToken(new Token('abc'));

        self::assertTrue($client->tokenVerification());
    }

    public function testPostAndPutSerializeTheJsonBody(): void
    {
        $bodies = [];

        [$client] = TestClientFactory::create(function (RequestInterface $request) use (&$bodies): ResponseInterface {
            $bodies[$request->getMethod()] = (string) $request->getBody();

            return Fixture::json([]);
        });
        $client->setToken(new Token('abc'));

        $client->post('/api/AwbPickup', ['BarCode' => '123']);
        $client->put('/api/Pickups', ['Id' => 1], ['flag' => true]);
        $client->delete('/api/Awbs', ['barCode' => '123']);

        self::assertSame('{"BarCode":"123"}', $bodies['POST']);
        self::assertSame('{"Id":1}', $bodies['PUT']);
    }

    public function testConfiguredTimeoutReachesTheTransport(): void
    {
        $factory = new HttpFactory();
        $httpClient = new FakeHttpClient(static fn(): ResponseInterface => Fixture::json([]));
        $client = Client::create(
            httpClient: $httpClient,
            requestFactory: $factory,
            streamFactory: $factory,
            config: new ClientConfig(
                username: 'test-user',
                password: 'test-pass',
                subscriptionKey: TestClientFactory::SUBSCRIPTION_KEY,
                timeout: 5.0,
            ),
        );
        $client->setToken(new Token('abc'));

        $client->send($client->createRequest('GET', '/api/Counties'));

        self::assertSame(5.0, $httpClient->lastTimeout);
    }

    public function testTimeoutWithAForeignPsr18ClientIsRejected(): void
    {
        $foreignClient = new class implements \Psr\Http\Client\ClientInterface {
            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                return new \GuzzleHttp\Psr7\Response(200);
            }
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ClientConfig::timeout cannot be applied to a foreign PSR-18 client; configure the timeout on that client or leave it null.');

        Client::create(
            httpClient: $foreignClient,
            config: new ClientConfig(
                username: 'test-user',
                password: 'test-pass',
                subscriptionKey: TestClientFactory::SUBSCRIPTION_KEY,
                timeout: 5.0,
            ),
        );
    }

    public function testAuthenticateJuridicPostsToTheJuridicEndpointAndStoresTheToken(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json('juridic-token'),
        );
        $client->setToken(new Token('old'));

        $client->authenticateJuridic('juridic-user', 'juridic-pass');

        self::assertSame('POST', $http->lastRequest?->getMethod());
        self::assertSame('/api/LoginUser/Juridic', $http->lastRequest?->getUri()->getPath());
        self::assertSame(
            '{"UserName":"juridic-user","Password":"juridic-pass"}',
            (string) $http->lastRequest?->getBody(),
        );
        self::assertFalse($http->lastRequest?->hasHeader('Authorization'));
        self::assertSame(TestClientFactory::SUBSCRIPTION_KEY, $http->lastRequest?->getHeaderLine('Ocp-Apim-Subscription-Key'));
        self::assertSame('juridic-token', $client->getToken()?->accessToken);
    }

    public function testJuridicReauthenticationReplaysTheJuridicEndpoint(): void
    {
        $calls = [];

        [$client] = TestClientFactory::create(function (RequestInterface $request) use (&$calls): ResponseInterface {
            $calls[] = $request->getUri()->getPath();

            if ($request->getUri()->getPath() === '/api/LoginUser/Juridic') {
                return Fixture::json('fresh');
            }

            return $request->getHeaderLine('Authorization') === 'Bearer fresh'
                ? Fixture::json([])
                : Fixture::raw('', 401);
        });

        $client->authenticateJuridic('juridic-user', 'juridic-pass');
        $client->setToken(new Token('revoked'));

        $response = $client->get('/api/Counties', ['countryId' => 1]);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['/api/LoginUser/Juridic', '/api/Counties', '/api/LoginUser/Juridic', '/api/Counties'], $calls);
    }

    public function testReauthenticationAfterAuthenticateUsesThePersonalEndpoint(): void
    {
        $calls = [];

        [$client] = TestClientFactory::create(function (RequestInterface $request) use (&$calls): ResponseInterface {
            $calls[] = $request->getUri()->getPath();

            if ($request->getUri()->getPath() === '/api/LoginUser') {
                return Fixture::json('fresh');
            }

            return $request->getHeaderLine('Authorization') === 'Bearer fresh'
                ? Fixture::json([])
                : Fixture::raw('', 401);
        });

        $client->authenticate('login-user', 'login-pass');
        $client->setToken(new Token('revoked'));

        $response = $client->get('/api/Counties', ['countryId' => 1]);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['/api/LoginUser', '/api/Counties', '/api/LoginUser', '/api/Counties'], $calls);
    }

    public function testTokenOnlyClientWorksWithoutConfiguredCredentials(): void
    {
        [$client, $http] = TestClientFactory::create(
            static fn(): ResponseInterface => Fixture::json([]),
            new ClientConfig(subscriptionKey: TestClientFactory::SUBSCRIPTION_KEY),
        );
        $client->setToken(new Token('abc'));

        $response = $client->get('/api/Counties', ['countryId' => 1]);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('Bearer abc', $http->lastRequest?->getHeaderLine('Authorization'));
    }

    public function testTokenOnlyClientSurfacesA401WithoutLoggingIn(): void
    {
        $calls = [];

        [$client] = TestClientFactory::create(
            function (RequestInterface $request) use (&$calls): ResponseInterface {
                $calls[] = $request->getUri()->getPath();

                return Fixture::raw('', 401);
            },
            new ClientConfig(subscriptionKey: TestClientFactory::SUBSCRIPTION_KEY),
        );
        $client->setToken(new Token('revoked'));

        $this->expectException(AuthenticationException::class);

        try {
            $client->geography()->counties(1);
        } finally {
            self::assertSame(['/api/Counties'], $calls);
        }
    }

    public function testTokenOnlyClientRejectsAnEmptySubscriptionKeyBeforeSending(): void
    {
        [$client, $http] = TestClientFactory::create(
            fn(RequestInterface $request): ResponseInterface => Fixture::raw('', 200),
            new ClientConfig(subscriptionKey: ''),
        );
        $client->setToken(new Token('stored-token'));

        $this->expectException(ValidationException::class);

        try {
            $client->geography()->counties(1);
        } finally {
            self::assertNull($http->lastRequest);
        }
    }

    public function testFailedAuthenticationKeepsThePreviousCredentialsAndToken(): void
    {
        $loginBodies = [];

        [$client] = TestClientFactory::create(function (RequestInterface $request) use (&$loginBodies): ResponseInterface {
            if ($request->getUri()->getPath() === '/api/LoginUser') {
                $loginBodies[] = (string) $request->getBody();

                return str_contains($loginBodies[array_key_last($loginBodies)], '"wrong"')
                    ? Fixture::raw('', 401)
                    : Fixture::json('fresh');
            }

            return $request->getHeaderLine('Authorization') === 'Bearer fresh'
                ? Fixture::json([])
                : Fixture::raw('', 401);
        });

        $client->setToken(new Token('old'));

        try {
            $client->authenticate('wrong', 'wrong');
            self::fail('Expected AuthenticationException.');
        } catch (AuthenticationException) {
        }

        self::assertSame('old', $client->getToken()?->accessToken);

        $response = $client->get('/api/Counties', ['countryId' => 1]);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('{"UserName":"test-user","Password":"test-pass"}', $loginBodies[1] ?? null);
    }
}
