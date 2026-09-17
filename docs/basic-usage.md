# Basic usage

## Framework-agnostic core

```php
use Quonain\\SmartResponse\\Core\\SmartResponse;

$result = (new SmartResponse())->success(['id' => 1], 'User loaded');

http_response_code($result->status());
foreach ($result->headers() as $name => $value) {
    header("{$name}: {$value}");
}
echo $result->content();
```

The response value exposes `status()`, `headers()`, `body()`, and `content()`. The expected JSON envelope is documented in [JSON responses](json.md).

## Laravel

```php
use Illuminate\\Http\\Request;
use Quonain\\SmartResponse\\Traits\\HasSmartResponse;

final class UserController
{
    use HasSmartResponse;

    public function show(Request $request, User $user)
    {
        return $this->smartResponse(
            request: $request,
            data: $user,
            message: 'User loaded',
        );
    }
}
```

API requests receive the configured JSON response; normal browser requests can render the supplied view. Keep business logic in the controller or service layer.
