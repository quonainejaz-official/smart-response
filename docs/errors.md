# Errors

Use the convenience methods for common HTTP semantics:

```php
return SmartResponse::unauthorized();
return SmartResponse::forbidden();
return SmartResponse::notFound('Invoice not found');
return SmartResponse::error('Conflict', ['code' => 'invoice_locked'], 409);
```

Errors are formatted through the selected response formatter, so JSON, XML, and legacy consumers can receive different representations of the same application error. Do not place stack traces, SQL, credentials, tokens, or internal filesystem paths in `message` or `errors`.

RFC 7807 Problem Details is an interoperability target for a future opt-in formatter; the existing envelope remains the backwards-compatible default.
