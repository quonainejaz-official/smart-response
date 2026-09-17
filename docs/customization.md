# Customization and extension

Publish `config/smart-response.php` to customize envelope keys, status codes, profiles, metadata, caching, logging, rate limiting, and middleware behavior.

Custom formatters implement `ResponseFormatterInterface` and can be registered with `ResponseFormatterRegistry`. Custom cache stores implement `Core\\CacheStore`; use an atomic shared store such as Redis for distributed limits.

Application responsibilities remain application-owned: authentication, authorization, validation, database transactions, queueing, retries for deliveries, and protocol server configuration.
