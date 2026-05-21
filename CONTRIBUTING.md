# Contributing to SmartResponse

Thank you for considering contributing to SmartResponse!

## Development setup

```bash
git clone https://github.com/your-org/smart-response.git
cd smart-response
composer install
composer test
```

## Coding standards

- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/)
- Use `declare(strict_types=1);` in all PHP files
- Add PHPDoc blocks for public APIs
- Write Pest tests for new behavior

## Pull requests

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/my-feature`)
3. Commit using [Conventional Commits](https://www.conventionalcommits.org/)
4. Ensure `composer test` passes
5. Open a pull request with a clear description

## Versioning

This package follows [Semantic Versioning](https://semver.org/).

## License

By contributing, you agree that your contributions will be licensed under the MIT License.
