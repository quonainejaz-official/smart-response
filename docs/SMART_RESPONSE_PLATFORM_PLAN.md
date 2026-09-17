# SmartResponse Platform Plan

## Product goal

SmartResponse will be the unified response layer for Laravel and PHP applications. A developer should be able to keep one controller and one business logic path while SmartResponse produces the response required by the client: web, REST JSON, legacy JSON, XML, SOAP, GraphQL, gRPC, WebSocket, or webhook.

The package must reduce duplicate controllers, response workarounds, protocol-specific envelopes, and migration risk. Existing Laravel behavior should remain usable, with an explicit format or profile available whenever automatic negotiation is not enough.

## Product principles

- One payload model and one response pipeline for every protocol.
- Automatic negotiation with explicit overrides.
- Backward-compatible defaults and opt-in advanced behavior.
- Built-in core features with first-class adapters for protocol runtimes.
- Secure, observable, testable output.
- Documentation and examples are part of every feature.

## Delivery phases

### Phase 1: Core response platform

- [x] Central response manager and immutable payload.
- [x] JSON, XML, legacy, GraphQL, SOAP, Blade, and Inertia foundations.
- [ ] Define a stable adapter contract for all protocols.
- [ ] Add deterministic format negotiation precedence.
- [ ] Add explicit format header, query, route suffix, and profile selection.
- [ ] Add named response profiles with validation.
- [ ] Add a fluent response builder without breaking existing helpers.
- [ ] Add unsupported-format and fallback policies.

Acceptance: one controller response can select a configured format from an explicit option, request metadata, or default policy, with tests covering precedence and fallback.

### Phase 2: Web and REST excellence

- [ ] Complete Blade, Inertia, redirect, flash, toast, HTMX, and Livewire paths.
- [ ] Add API resources, pagination, field selection, links, ETags, and version headers.
- [ ] Add RFC 7807 and JSON:API response profiles.
- [ ] Add request, correlation, and trace IDs.
- [ ] Add consistent validation and exception mapping.

### Phase 3: Compatibility and migration

- [ ] Add configurable legacy profiles and response snapshots.
- [ ] Add per-route, per-client, and per-version profiles.
- [ ] Add migration helpers and backward-compatibility tests.
- [ ] Document replacing `response()->json()` incrementally.

### Phase 4: Protocol adapters

- [ ] SOAP 1.1/1.2, WSDL, faults, headers, and service routing.
- [ ] GraphQL execution, errors, context, schema, and pagination integration.
- [ ] gRPC metadata, status mapping, protobuf, streaming, and health checks.
- [ ] WebSocket channels, authentication, events, acknowledgements, and adapters.
- [ ] Webhook signing, verification, retries, idempotency, queueing, and delivery logs.

### Phase 5: Platform operations

- [ ] Artisan install, doctor, adapter, profile, and protocol generation commands.
- [ ] Structured logs, metrics, OpenTelemetry hooks, and Laravel observability support.
- [ ] Security hardening, rate limits, replay protection, and payload limits.
- [ ] Streaming, caching, benchmarks, and large-payload safeguards.

### Phase 6: Documentation and release quality

- [ ] Protocol-specific documentation and runnable examples.
- [ ] Compatibility matrix for Laravel, PHP, extensions, and optional runtimes.
- [ ] CI for unit, feature, static-analysis, security, and compatibility suites.
- [ ] Upgrade guides, changelog discipline, and stable release checklist.

## Current implementation slice

The first implementation on `feat/improvements` adds deterministic request format negotiation. The precedence is:

1. Payload format explicitly supplied by the developer.
2. `X-Smart-Response-Format` header.
3. `format` query parameter.
4. A `.json`, `.xml`, `.graphql`, `.soap`, or `.legacy` route suffix.
5. `Accept` header.
6. Configured default format.

Only configured/supported formats are accepted. Invalid explicit values fall back to the configured default so existing applications do not fail unexpectedly.

## Definition of done for the platform

- A documented controller example works for each supported protocol.
- Every adapter has contract, success, error, and edge-case tests.
- Existing JSON and web behavior remains backward compatible.
- Optional protocol runtimes are detected and reported by `smart-response:doctor`.
- Security, performance, and failure behavior are documented.
- README and protocol guides are updated with every public API change.
