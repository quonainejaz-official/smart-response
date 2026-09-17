# Content negotiation

The standalone `ResponseFactory` can select a registered formatter from `Accept`, including quality values and wildcard media types:

```php
$response = $factory->negotiate($payload, 'application/xml;q=0.9, application/json;q=1');
```

No match raises an exception so an adapter can translate it to `406 Not Acceptable`. For complex language/profile rules or strict RFC policies, inject an adapter around [willdurand/negotiation](https://github.com/willdurand/Negotiation) rather than expanding the core parser.

The Laravel detector also supports explicit format headers, query parameters, route suffixes, and configured API prefixes. Explicit application choices take precedence over inference.
