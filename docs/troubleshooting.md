# Troubleshooting

Run the built-in Laravel diagnostic command when available:

```bash
php artisan smart-response:doctor
```

If Laravel features are unavailable, confirm the application supplies the required Illuminate components. If XML or SOAP is unavailable, enable PHP's `xml`/`soap` extensions as appropriate. WebSocket support requires a compatible Ratchet runtime. For production distributed rate limits, configure an atomic shared cache store instead of the process-local array store.

When changing PHP source in a deployed application, clear the normal Composer/application caches according to that application's deployment process.
