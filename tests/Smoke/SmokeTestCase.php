<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\Tests\Smoke;

use Daika7ana\Cargus\Client;
use Daika7ana\Cargus\Config\ClientConfig;
use Daika7ana\Cargus\DTOs\Geography\Locality;
use PHPUnit\Framework\TestCase;

abstract class SmokeTestCase extends TestCase
{
    private ?Client $client = null;

    private ?Locality $sampleLocality = null;

    protected function client(): Client
    {
        if ($this->client instanceof Client) {
            return $this->client;
        }

        foreach (['CARGUS_USERNAME', 'CARGUS_PASSWORD', 'CARGUS_SUBSCRIPTION_KEY'] as $key) {
            $value = getenv($key);

            if ($value === false || $value === '') {
                $this->markTestSkipped(sprintf('Set %s (and the other CARGUS_* credentials) to run smoke tests.', $key));
            }
        }

        $config = ClientConfig::fromEnvironment();
        $client = Client::create(httpClient: new ThrottledHttpClient(), config: $config);
        // The API answers 409 (not 401) without a bearer token, so the smoke client logs in eagerly.
        $client->authenticate($config->username, $config->password);

        return $this->client = $client;
    }

    protected function requireWriteAccess(string $workflow): void
    {
        if (!$this->envBool('CARGUS_SMOKE_ENABLE_WRITES')) {
            $this->markTestSkipped(sprintf('Skipping %s because CARGUS_SMOKE_ENABLE_WRITES is not true.', $workflow));
        }
    }

    protected function sampleLocality(): Locality
    {
        if ($this->sampleLocality instanceof Locality) {
            return $this->sampleLocality;
        }

        $configured = $this->envInt('CARGUS_SMOKE_LOCALITY_ID');

        if ($configured !== null) {
            $locality = $this->client()->geography()->localityDetails($configured);

            if ($locality->id === 0) {
                $this->markTestSkipped(sprintf('Skipping smoke test because locality %d was not found.', $configured));
            }

            return $this->sampleLocality = $locality;
        }

        $countryId = $this->sampleCountryId();
        $county = $this->client()->geography()->counties($countryId)->first();

        if ($county === null) {
            $this->markTestSkipped(sprintf('Skipping smoke test because country %d has no counties.', $countryId));
        }

        $locality = $this->client()->geography()->localities($countryId, $county->id)->first();

        if ($locality === null) {
            $this->markTestSkipped(sprintf('Skipping smoke test because county %d has no localities.', $county->id));
        }

        return $this->sampleLocality = $locality;
    }

    protected function sampleCountryId(): int
    {
        $configured = $this->envInt('CARGUS_SMOKE_COUNTRY_ID');

        if ($configured !== null) {
            return $configured;
        }

        $countries = $this->client()->geography()->countries();

        foreach ($countries as $country) {
            if (stripos($country->name, 'rom') !== false) {
                return $country->id;
            }
        }

        $first = $countries->first();

        if ($first === null) {
            $this->markTestSkipped('Skipping smoke test because the account has no countries.');
        }

        return $first->id;
    }

    protected function smokeReference(string $prefix): string
    {
        return sprintf('%s-%s', $prefix, gmdate('YmdHis'));
    }

    protected function env(string $key, ?string $default = null): ?string
    {
        $value = getenv($key);

        if ($value === false || $value === '') {
            return $default;
        }

        return $value;
    }

    protected function envInt(string $key, ?int $default = null): ?int
    {
        $value = $this->env($key);

        return $value === null ? $default : (int) $value;
    }

    protected function envBool(string $key): bool
    {
        $value = $this->env($key);

        return $value !== null && in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
    }
}
