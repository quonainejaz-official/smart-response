# Performance

The core performs one normalization pass per formatter and does not load a framework or serializer package. No benchmark numbers are published because performance depends on payload shape, PHP version, and the selected serializer.

For production workloads:

- normalize once when the same payload feeds multiple outputs;
- use a mature serializer for complex DTO graphs;
- avoid caching responses containing request-specific metadata;
- do not cache authenticated responses without an isolated key;
- benchmark representative payloads before changing formatters.

The Laravel integration already avoids caching dynamic metadata and private responses by default.
