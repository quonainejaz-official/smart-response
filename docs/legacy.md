# Legacy API compatibility

Existing clients can keep a legacy envelope while new endpoints use the modern JSON contract:

```php
return $this->smartResponse(
    request: $request,
    data: $user,
    message: 'User loaded',
    format: 'legacy',
);
```

The default legacy keys are `status`, `message`, `data`, and `errors`. Publish the config and change `legacy.keys` or define a named profile such as `legacy-v1`. This is additive: the default JSON format remains unchanged.
