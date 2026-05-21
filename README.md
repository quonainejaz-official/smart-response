# SmartResponse

[![Latest Version on Packagist](https://img.shields.io/packagist/v/quonain/smart-response.svg)](https://packagist.org/packages/quonain/smart-response)
[![License](https://img.shields.io/packagist/l/quonain/smart-response.svg)](https://packagist.org/packages/quonain/smart-response)
[![PHP Version](https://img.shields.io/packagist/php-v/quonain/smart-response.svg)](https://packagist.org/packages/quonain/smart-response)

**SmartResponse** is a production-ready Laravel package that lets you return **API JSON** or **Blade web views** from the **same controller method** — automatically detecting the request type.

---

## Features

- Automatic API vs Web detection (`Accept`, `expectsJson()`, route prefixes)
- Standardized JSON API envelope
- Blade views with compact data injection
- Validation error formatting
- Exception handler integration for APIs
- Pagination, Eloquent collections, and API Resources
- Redirects with session flash and optional toast
- Optional **Inertia.js** and **Livewire** support
- Optional **XML** responses
- Multi-language messages
- Response macros, events, logging, and caching hooks
- Rate-limit response helper
- OpenAPI / Swagger example payloads
- Laravel 10, 11, and 12 compatible
- PHP 8.2+

---

## Requirements

- PHP ^8.2
- Laravel ^10.0 | ^11.0 | ^12.0

---

## Installation

```bash
composer require quonain/smart-response
```

Laravel **auto-discovers** the service provider. No manual registration required.

### Publish configuration

```bash
php artisan vendor:publish --tag=smart-response-config
```

### Publish translations

```bash
php artisan vendor:publish --tag=smart-response-lang
```

---

## Quick start

### 1. Use the trait in your controller

```php
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
            message: 'Users fetched successfully',
        );
    }
}
```

- **API request** (`Accept: application/json` or `/api/*` routes) → JSON
- **Web request** → `users.index` Blade view with `$data`, `$message`, etc.

### 2. API response format

```json
{
  "success": true,
  "message": "Users fetched successfully",
  "data": [],
  "meta": {},
  "errors": null
}
```

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
    meta: ['version' => '1.0'],
    status: 200,
    redirect: null,
    route: 'users.index',
    routeParameters: [],
    format: null,
    flash: true,
    toast: false,
);
```

### Success / error helpers

```php
return $this->smartSuccess($users, 'Users loaded');
return $this->smartError('Not allowed', null, 403);
return $this->smartValidationError($validator->errors());
```

### Facade

```php
use Quonain\SmartResponse\Facades\SmartResponse;

return SmartResponse::success($data, 'Done');
return SmartResponse::error('Failed', ['code' => 'X'], 400);
```

### Global helper

```php
return smart_response(
    request: $request,
    data: $users,
    view: 'users.index',
    message: 'OK',
);
```

### Redirect (web) with flash + toast

```php
return $this->smartResponse(
    request: $request,
    message: 'User created',
    route: 'users.index',
    toast: true,
    status: 201,
);
```

### Pagination

Pass a paginator directly — meta is merged automatically:

```php
return $this->smartResponse(
    request: $request,
    data: User::paginate(20),
    view: 'users.index',
);
```

### API Resources & collections

```php
return $this->smartResponse(
    request: $request,
    data: UserResource::collection($users),
    view: 'users.index',
);
```

### XML responses

Clients sending `Accept: application/xml` receive XML automatically, or force format:

```php
return $this->smartResponse(
    request: $request,
    data: $users,
    format: 'xml',
);
```

### Rate limiting

```php
return smart_rate_limit_response();
```

### Response macro

```php
return response()->smart($data, 'OK');
```

### Caching (API)

Enable in config, then:

```php
return $this->smartResponse(
    request: $request,
    data: $expensiveData,
    cacheKey: 'users.index',
    cacheTtl: 120,
);
```

---

## Exception handling (API)

Register the handler for API requests in `bootstrap/app.php` (Laravel 11+) or your exception handler:

```php
use Quonain\SmartResponse\Exceptions\Handler\SmartResponseExceptionHandler;

->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (Throwable $e, $request) {
        return app(SmartResponseExceptionHandler::class)->render($request, $e);
    });
})
```

---

## Configuration

Key options in `config/smart-response.php`:

| Option | Description |
|--------|-------------|
| `api.*` | JSON response keys |
| `detection.*` | How API vs web is detected |
| `default_format` | `json` or `xml` |
| `status_codes.*` | Default HTTP codes |
| `web.*` | Flash / toast session keys |
| `inertia.enabled` | Enable Inertia adapter |
| `locale.enabled` | Translate messages via lang files |
| `logging.enabled` | Log each response |
| `cache.enabled` | Cache API responses |
| `events.enabled` | Dispatch lifecycle events |

### Multi-language messages

Use translation keys as messages:

```php
return $this->smartResponse(
    message: 'users.fetched', // resolves smart-response::messages.users.fetched
);
```

Publish lang files and edit `lang/vendor/smart-response/en/messages.php`.

### Inertia.js

```php
// config/smart-response.php
'inertia' => ['enabled' => true],

// controller
return $this->smartResponse(
    request: $request,
    data: $users,
    inertiaComponent: 'Users/Index',
    useInertia: true,
);
```

---

## Middleware

Optional middleware alias: `smart.response`

```php
Route::middleware('smart.response')->group(function () {
    // ...
});
```

---

## Events

Listen for lifecycle hooks:

```php
use Quonain\SmartResponse\Events\SmartResponsePreparing;
use Quonain\SmartResponse\Events\SmartResponsePrepared;

Event::listen(SmartResponsePreparing::class, fn ($e) => /* ... */);
Event::listen(SmartResponsePrepared::class, fn ($e) => /* ... */);
```

---

## OpenAPI / Swagger

```php
use Quonain\SmartResponse\Support\OpenApiExample;

OpenApiExample::successExample();
OpenApiExample::errorExample();
```

---

## Publishing to Packagist

1. Push to GitHub: `quonainejaz-official/smart-response`
2. Submit repository URL at [packagist.org](https://packagist.org)
3. Tag releases with semantic versioning: `v1.0.0`

```bash
git tag -a v1.0.0 -m "Initial release"
git push origin v1.0.0
```

---

## Testing

```bash
composer install
composer test
```

---

## Package structure

```
smart-response/
├── config/smart-response.php
├── lang/en/messages.php
├── src/
│   ├── Contracts/
│   ├── Detectors/
│   ├── DTO/
│   ├── Formatters/
│   ├── Builders/
│   ├── Services/
│   ├── Traits/
│   ├── Facades/
│   ├── Events/
│   ├── Exceptions/
│   ├── Http/Middleware/
│   ├── Macros/
│   └── Support/
├── tests/
├── examples/
├── composer.json
└── README.md
```

---

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

---

## License

MIT © SmartResponse Contributors. See [LICENSE](LICENSE).
