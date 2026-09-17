# REST responses

Use `SmartResponseManager` in Laravel when the request may be an API or a web request:

```php
return SmartResponse::success($user, 'User loaded', ['version' => 1]);
return SmartResponse::created($user);
return SmartResponse::notFound('User not found');
return SmartResponse::validationError($validator->errors());
```

Status codes and headers belong to the HTTP response. The envelope is application data and can be changed only through a configured formatter or legacy profile. `204 No Content` has no response body.

For pagination, pass a Laravel paginator or use the existing pagination transformer. Links and cursor metadata are placed in `meta`; business logic stays in the service layer.
