# Custom formatters

Standalone formatters implement `CoreResponseFormatterInterface`:

```php
final class CsvFormatter implements CoreResponseFormatterInterface
{
    public function mediaType(): string { return 'text/csv'; }

    public function format(mixed $data, array $context = []): Response
    {
        // Normalize and encode according to the application's CSV contract.
    }
}
```

Register it by name with `ResponseFactory::register('csv', $formatter)`. Laravel formatters implement the existing `ResponseFormatterInterface` and return a Symfony response. Keep media types, status handling, and escaping inside the formatter; do not modify the core for application-specific envelopes.
