# Changelog

All notable changes to **SmartResponse** will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Remove compromised font asset and publish clean package contents

## [2.0.0] - 2026-09-17

### Added

- Remove compromised font asset and publish clean package contents

## [1.2.1] - 2026-09-17

### Added

- Framework-agnostic core, safe caching, rate limiting, and automated releases

### Changed

- Improved Composer metadata and package positioning for PHP response and API searches
- Added capability-focused documentation, search-intent coverage, and open-source community files
- Expanded CI validation across PHP 8.2, 8.3, and 8.4 with Composer validation and security auditing

## [1.2.0] - 2026-09-17

### Added

- A complete feature and scope reference for the current response platform
- A prominent README explanation of the one-controller response model and runtime boundaries
- Documentation links for SOAP, WebSocket, gRPC, webhook, GraphQL, API, and web capabilities
- A pure-PHP `Core\\SmartResponse` API, response value object, cache contract, in-memory store, and fixed-window rate limiter for standalone PHP and framework adapters
- Opt-in, cache-backed fixed-window rate limiting, declared request-payload limits, and additive security headers for Laravel routes

### Changed

- Platform plan now reflects the implemented SOAP, WebSocket, and gRPC runtime slice
- Runtime documentation declares prerequisites and application-owned responsibilities
- Removed Laravel's Illuminate components from production requirements; Laravel integration remains available when Laravel provides them
- Hardened response caching with scalar snapshots, status filtering, authenticated and bearer-token protection, and dynamic-metadata bypass
- XML output now works without Laravel's response factory and normalizes unsafe element names

## [1.1.1] - 2026-05-21

### Changed

- Comprehensive README update: table of contents, detection guide, helper reference tables, v1.1 feature docs, Packagist sync note

## [1.1.0] - 2026-05-21

### Added

- HTTP shortcut helpers: `created()`, `noContent()`, `notFound()`, `unauthorized()`, `forbidden()`
- Trait helpers: `smartCreated()`, `smartNoContent()`, `smartNotFound()`, etc.
- Global helpers: `smart_created()`, `smart_not_found()`
- Response macros: `response()->smartCreated()`, `response()->smartNotFound()`
- API meta enrichment (timestamp, request ID, optional API version) via `MetaEnricher`
- Bearer token requests auto-detected as API (`detection.bearer_as_api`)
- Cursor pagination meta support
- `Retry-After` header on rate-limit responses
- Extra translation keys for common errors

### Fixed

- Custom response headers now apply correctly on JSON responses
- `204 No Content` returns an empty body

## [1.0.1] - 2026-05-21

### Added

- Laravel 13 support (`illuminate/*` ^13.0, `orchestra/testbench` ^11.0)

## [1.0.0] - 2026-05-21

### Added

- Initial release of SmartResponse Laravel package
- Unified API / Web response handling with automatic request detection
- `HasSmartResponse` trait and `smart_response()` helper
- Standardized JSON API structure with optional XML support
- Pagination, Resource, and Collection support
- Validation error formatting
- Exception handler integration for API requests
- Flash messages and optional toast notifications for web
- Inertia.js and Livewire optional adapters
- Response macros, events, logging hooks, and response caching
- Rate limit response helper and OpenAPI examples
- Pest PHP test suite
- Full configuration publishing and Laravel auto-discovery
