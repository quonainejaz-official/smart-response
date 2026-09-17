# Migration guide

SmartResponse is designed for additive adoption:

1. Install the package and run the test suite.
2. Add the trait to one controller.
3. Compare the modern envelope with the existing endpoint contract.
4. Use `format: 'legacy'` or a named profile for clients that cannot migrate yet.
5. Move endpoints gradually and keep explicit format selection at compatibility boundaries.

The `1.x` line follows Semantic Versioning. Review [CHANGELOG.md](../CHANGELOG.md) before upgrading and pin a compatible minor line when rolling out a large migration.
