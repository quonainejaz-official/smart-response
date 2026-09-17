# JSON responses

The modern JSON formatter uses the configured `success`, `message`, `data`, `meta`, and `errors` keys:

```json
{
  "success": true,
  "message": "User loaded",
  "data": {"id": 1},
  "meta": {},
  "errors": null
}
```

Use `smartSuccess`, `smartCreated`, `smartError`, `smartValidationError`, and the other shortcuts for common HTTP outcomes. Pagination and API-resource data are transformed by the Laravel integration. Configure key names in `config/smart-response.php` under `api`.
