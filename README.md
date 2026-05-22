# SmartResponse

[![Latest Version on Packagist](https://img.shields.io/packagist/v/quonain/smart-response.svg)](https://packagist.org/packages/quonain/smart-response)
[![License](https://img.shields.io/packagist/l/quonain/smart-response.svg)](https://packagist.org/packages/quonain/smart-response)
[![PHP Version](https://img.shields.io/packagist/php-v/quonain/smart-response.svg)](https://packagist.org/packages/quonain/smart-response)

**SmartResponse** is a production-ready Laravel package that returns **API JSON** or **Blade / Inertia web views** from the **same controller method** — with automatic request-type detection.

---

## Table of contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick start](#quick-start)
- [How API vs Web is detected](#how-api-vs-web-is-detected)
- [Usage](#usage)
  - [Unified response](#unified-smartresponse)
  - [HTTP shortcuts](#http-shortcuts)
  - [Facade & helpers](#facade--global-helpers)
  - [Response macros](#response-macros)
  - [Pagination](#pagination)
  - [API meta enrichment](#api-meta-enrichment)
  - [Rate limiting](#rate-limiting)
  - [Caching](#caching-api)
- [Exception handling](#exception-handling-api)
- [Configuration](#configuration)
- [Middleware & events](#middleware)
- [Testing](#testing)
- [Changelog & license](#changelog)

---

## Features

| Category | Capabilities |
|----------|--------------|
| **Detection** | `Accept` header, `expectsJson()`, `/api/*` routes, **Bearer tokens** (Sanctum / Passport) |
| **API** | Standard JSON envelope, optional XML, validation errors, exception handler |
| **Web** | Blade views, redirects, session flash, optional toast |
| **Pagination** | Length-aware, simple, **cursor** paginators + API Resources |
| **DX** | Trait, Facade, global helpers, `response()->smart*` macros |
| **HTTP shortcuts** | `created`, `noContent`, `notFound`, `unauthorized`, `forbidden` |
| **Meta** | Auto `timestamp`, `request_id`, optional `api_version` on API responses |
| **Extras** | Inertia.js, Livewire, i18n, caching, logging, events, OpenAPI examples |
| **Framework** | Laravel **10 · 11 · 12 · 13** · PHP **8.2+** |

---

## Requirements

- PHP `^8.2`
- Laravel `^10.0` · `^11.0` · `^12.0` · `^13.0`

---

## Installation

```bash
composer require quonain/smart-response
```

Or pin the latest 1.x release:

```bash
composer require quonain/smart-response:^1.1
```

Laravel **auto-discovers** the service provider — no manual registration.

### Publish assets

```bash
# Configuration
php artisan vendor:publish --tag=smart-response-config

# Translations
php artisan vendor:publish --tag=smart-response-lang
```

> **Packagist:** After a new release, open your [package page](https://packagist.org/packages/quonain/smart-response) and click **Update** if Composer does not see the latest tag yet. Enable the GitHub hook under package settings for automatic sync.

---

## Quick start

### 1. Add the trait

```php
use Illuminate\Http\Request;
use Quonain\SmartResponse\Traits\HasSmartResponse;

class UserController extends Controller
{
    use HasSmartResponse;

    public function index(Request $request)
    {
        $users = User::paginate(15);

        return $this->smartResponse(
            request: $request,
            data: UserResource::collection($users),
            view: 'users.index',
            message: 'users.fetched', // translation key (optional)
        );
    }

    public function store(Request $request)
    {
        $user = User::create($request->validated());

        return $this->smartCreated($user, 'users.created');
    }
}
```

| Request type | Result |
|--------------|--------|
| API (`Accept: application/json`, `/api/*`, Bearer token, …) | Standard JSON |
| Web (`text/html`, normal browser) | Blade view `users.index` with `$data`, `$message`, … |

### 2. Default JSON shape

```json
{
  "success": true,
  "message": "Users fetched successfully",
  "data": [],
  "meta": {
    "timestamp": "2026-05-21T12:00:00+00:00",
    "request_id": "550e8400-e29b-41d4-a716-446655440000",
    "current_page": 1,
    "per_page": 15,
    "total": 100
  },
  "errors": null
}
```

---

## How API vs Web is detected

SmartResponse treats a request as **API** when any of these match (configurable in `config/smart-response.php`):

1. `Accept` contains `application/json` or `application/vnd.api+json`
2. `Accept` contains `application/xml` or `text/xml`
3. Route matches `api/*` or configured prefixes (`api` by default)
4. Laravel `expectsJson()` is true (AJAX, etc.)
5. **`Authorization: Bearer …` is present** (`detection.bearer_as_api` — ideal for Sanctum / Passport SPA or mobile apps)

Otherwise the request is handled as **Web** (view or redirect).

---

## Usage

### Unified `smartResponse()`

```php
return $this->smartResponse(
    request: $request,
    data: $data,
    view: 'users.index',
    viewData: ['title' => 'Users'],
    message: 'Success',
    success: true,
    errors: null,
    meta: ['custom' => 'value'],
    status: 200,
    route: 'users.index',       // web redirect
    routeParameters: [],
    format: null,               // auto: json | xml
    flash: true,
    toast: false,
    cacheKey: null,
    cacheTtl: null,
    headers: ['X-Custom' => '1'],
    inertiaComponent: 'Users/Index',
    useInertia: false,
    useLivewire: false,
);
```

### HTTP shortcuts

| Trait method | Facade / Manager | Status | Use case |
|--------------|------------------|--------|----------|
| `smartSuccess()` | `SmartResponse::success()` | 200 | OK with data |
| `smartCreated()` | `SmartResponse::created()` | 201 | Resource created |
| `smartNoContent()` | `SmartResponse::noContent()` | 204 | Delete / empty OK |
| `smartError()` | `SmartResponse::error()` | 4xx/5xx | Generic error |
| `smartNotFound()` | `SmartResponse::notFound()` | 404 | Missing resource |
| `smartUnauthorized()` | `SmartResponse::unauthorized()` | 401 | Not logged in |
| `smartForbidden()` | `SmartResponse::forbidden()` | 403 | No permission |
| `smartValidationError()` | `SmartResponse::validationError()` | 422 | Form / API validation |

```php
return $this->smartSuccess($users, 'Users loaded');
return $this->smartCreated($user, 'users.created');
return $this->smartNoContent();
return $this->smartNotFound('error.not_found');
return $this->smartUnauthorized();
return $this->smartForbidden('error.forbidden');
return $this->smartValidationError($validator->errors());
```

### Facade & global helpers

```php
use Quonain\SmartResponse\Facades\SmartResponse;

SmartResponse::success($data, 'Done');
SmartResponse::created($data, 'Created');
SmartResponse::notFound('Not found');

// Global helpers (no trait required)
smart_response(request: $request, data: $users, view: 'users.index');
smart_created($user, 'users.created');
smart_not_found('error.not_found');
smart_rate_limit_response(retryAfter: 60);
```

### Response macros

```php
response()->smart($data, 'OK');
response()->smartSuccess($data, 'Saved');
response()->smartError('Failed', ['code' => 'X'], 400);
response()->smartCreated($data, 'Created');
response()->smartNotFound('Not found');
```

### Pagination

**Length-aware** — pass a paginator; meta keys are merged automatically:

```php
return $this->smartResponse(
    request: $request,
    data: User::paginate(20),
    view: 'users.index',
);
```

**Cursor** — works with `cursorPaginate()`:

```php
return $this->smartResponse(
    request: $request,
    data: User::orderBy('id')->cursorPaginate(15),
);
```

Meta includes: `per_page`, `path`, `next_cursor`, `prev_cursor`, `has_more`.

### API Resources & XML

```php
// API Resource collection
return $this->smartResponse(
    request: $request,
    data: UserResource::collection($users),
    view: 'users.index',
);

// Force or auto-detect XML
return $this->smartResponse(request: $request, data: $users, format: 'xml');
```

### API meta enrichment

Enabled by default (`meta.enabled` in config). Every API response can include:

| Meta key | Source |
|----------|--------|
| `timestamp` | Current time (ISO 8601) |
| `request_id` | `X-Request-Id` header or auto UUID |
| `api_version` | `X-API-Version` header or `meta.api_version` config |

```php
// config/smart-response.php
'meta' => [
    'enabled' => true,
    'include_timestamp' => true,
    'include_request_id' => true,
    'request_id_header' => 'X-Request-Id',
    'include_api_version' => true,
    'api_version' => '1.0',
],
```

### Rate limiting

Returns a standard error JSON with **`Retry-After`** header:

```php
// Uses config defaults (429 + retry_after_seconds)
return smart_rate_limit_response();

// Custom message and seconds
return smart_rate_limit_response('Slow down', 120);
```

### Caching (API)

Enable in config, then cache GET API responses:

```php
return $this->smartResponse(
    request: $request,
    data: $expensiveData,
    cacheKey: 'users.index',
    cacheTtl: 120,
);
```

Without `cacheKey`, a hash of the full URL + `Accept` header is used.

### Web redirect with flash & toast

```php
return $this->smartResponse(
    request: $request,
    message: 'User created',
    route: 'users.index',
    toast: true,
    status: 201,
);
```

---

## Exception handling (API)

Register in `bootstrap/app.php` (Laravel 11+):

```php
use Quonain\SmartResponse\Exceptions\Handler\SmartResponseExceptionHandler;

->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (Throwable $e, $request) {
        return app(SmartResponseExceptionHandler::class)->render($request, $e);
    });
})
```

API requests receive the same JSON envelope; web requests fall through to Laravel’s default handling.

---

## Configuration

Publish `config/smart-response.php` and adjust:

| Key | Description |
|-----|-------------|
| `api.*` | JSON keys: `success`, `message`, `data`, `meta`, `errors` |
| `detection.*` | JSON/XML accepts, route prefixes, **`bearer_as_api`** |
| `meta.*` | Timestamp, request ID, API version injection |
| `default_format` | `json` or `xml` |
| `status_codes.*` | Defaults for 200, 201, 204, 401, 403, 404, 422, 429, 500 |
| `web.*` | Flash / toast session keys, default redirect route |
| `inertia.enabled` | Inertia.js adapter |
| `livewire.enabled` | Livewire hooks |
| `locale.enabled` | Translate message keys via lang files |
| `cache.enabled` | Cache GET API responses |
| `logging.enabled` | Log each response |
| `events.enabled` | `SmartResponsePreparing` / `SmartResponsePrepared` |
| `rate_limit.*` | 429 message and `retry_after_seconds` |
| `graphql.enabled` | GraphQL response `Accept` detection |

### Multi-language messages

```php
return $this->smartResponse(message: 'users.fetched');
// → lang/vendor/smart-response/en/messages.php
```

Built-in keys include: `users.fetched`, `users.created`, `error.not_found`, `error.unauthorized`, `error.forbidden`, `error.rate_limit`, and more.

### Inertia.js

```php
// config/smart-response.php
'inertia' => ['enabled' => true],

return $this->smartResponse(
    request: $request,
    data: $users,
    inertiaComponent: 'Users/Index',
    useInertia: true,
);
```

---

## Middleware

Alias: `smart.response` (enabled by default)

```php
Route::middleware('smart.response')->group(function () {
    // ...
});
```

### Events

```php
use Quonain\SmartResponse\Events\SmartResponsePreparing;
use Quonain\SmartResponse\Events\SmartResponsePrepared;

Event::listen(SmartResponsePreparing::class, fn ($e) => /* mutate payload */);
Event::listen(SmartResponsePrepared::class, fn ($e) => /* inspect response */);
```

### OpenAPI / Swagger

```php
use Quonain\SmartResponse\Support\OpenApiExample;

OpenApiExample::successExample();
OpenApiExample::errorExample();
```

---

## Testing

```bash
composer install
composer test
```

---

## Package structure

```text
smart-response/
├── config/smart-response.php
├── lang/en/messages.php
├── src/
│   ├── Contracts/
│   ├── Detectors/          # API vs Web detection
│   ├── DTO/
│   ├── Formatters/         # JSON, XML
│   ├── Builders/
│   ├── Services/
│   ├── Traits/             # HasSmartResponse
│   ├── Facades/
│   ├── Events/
│   ├── Exceptions/
│   ├── Http/Middleware/
│   ├── Macros/
│   └── Support/            # MetaEnricher, Pagination, i18n, …
├── tests/
├── examples/
└── README.md
```

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history (`1.1.0` — HTTP shortcuts, meta enrichment, cursor pagination, Bearer detection).

---

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

---

## License

MIT © [Quonain Ejaz](https://github.com/quonainejaz-official). See [LICENSE](LICENSE).
