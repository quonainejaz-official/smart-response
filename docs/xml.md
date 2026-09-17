# XML responses

Request XML with an accepted XML media type or select `format: 'xml'` explicitly:

```php
return $this->smartResponse(request: $request, data: $users, format: 'xml');
```

The formatter emits the configured response envelope as XML and normalizes unsafe array keys into valid element names. XML output is available through the response pipeline; it does not provide XML schema generation or an XML transport server.
