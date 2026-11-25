# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.0.0/) and this project
adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1] - 2026-11-25

### Added

- `AuthToken::toArray()` and `Token::fromArray()` serialize and restore a stored token, so custom `TokenStoreInterface` stores can persist it between processes.

## [1.0.0] - 2026-11-25

### Added

- `Client::withTimeout(?float $timeout)` and `ClientConfig::withTimeout()` derive a client or config with a different request timeout while sharing the transport, PSR-17 factories, token store and credentials.
- `DTOs\Common\Errors\CargusError` decodes the API failure body, with `isValidationRelated()` and `isAuthenticationRelated()` helpers, exposed as `$error` on the HTTP exceptions.
- Initial skeleton: PSR-18 client, bearer login, subscription-key and trace headers, config and environment loading, exceptions, token store.
- Full UrgentOnline API coverage: all 81 operations across 13 resources — `awbs()`, `documents()`, `tracking()`, `pickup()`, `pickupLocations()`, `orders()`, `geography()`, `pricing()`, `financial()`, `onlineShop()`, `recipients()`, `complaints()`, `marketing()`.
- Typed readonly DTOs with PascalCase wire serialization for every request/response model, plus `authenticateJuridic()` and `tokenVerification()` on the client.
- Smoke suite (`composer test:smoke`) for live reads and a write-gated AWB lifecycle, with throttle retries and coverage for geography, pricing, financial, account and AWB update/document reads.

### Changed

- Token-expiry and 401 re-authentication replay the endpoint of the last successful login, so juridic logins keep using `/api/LoginUser/Juridic`.
- `complaints()->checkAwb()` and `marketing()->updatedUsers()` return `null` on the spec's 204 instead of throwing.
- AWB write results parse the API's boolean strictly; unexpected payloads throw `UnexpectedStatusException` instead of casting.
- `awbs()->updateParcelCodes()` returns the AWB barcode the live API echoes, not the spec's boolean.
- `format: byte` document endpoints and `tracking()->scan()` base64-decode their bodies to raw bytes; a non-base64 payload passes through unchanged.
- Clients configured with a stored token but no username/password now work; a 401 surfaces `AuthenticationException` without a login attempt.
- An empty subscription key raises `ValidationException` before any request, even when a stored token is present.
- The duplicate `AwbCash` DTO is gone: `tracking()` and `financial()` share `DTOs\Financial\AwbCash`.
- `AuthenticationException`, `ValidationException` and `UnexpectedStatusException` expose the decoded error body on `$error`; `ext-json` is declared as a platform requirement.
- Added a CI workflow and README badges.
