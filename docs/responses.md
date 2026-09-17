# Response formats

The built-in formatters are JSON, XML, legacy JSON, GraphQL output, and SOAP XML. In Laravel, choose a format with the `format` option, `X-Smart-Response-Format`, `?format=...`, or a supported route suffix. Unsupported explicit formats are ignored.

```php
return $this->smartResponse(request: $request, data: $data, format: 'json');
return $this->smartResponse(request: $request, data: $data, format: 'xml');
return $this->smartResponse(request: $request, data: $data, format: 'legacy');
```

Register an application formatter through `ResponseFormatterRegistry` when a supported built-in envelope is not sufficient. See [customization](customization.md).
