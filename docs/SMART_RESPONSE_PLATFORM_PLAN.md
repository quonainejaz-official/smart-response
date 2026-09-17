# SmartResponse Platform Plan

**Content type:** Conceptual plan<br>
**Goal:** Define the response platform, its implementation boundaries, and the acceptance criteria for each phase.

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
- [x] Define a stable adapter contract for all protocols.
- [x] Add deterministic format negotiation precedence.
- [x] Add explicit format header, query, route suffix, and profile selection.
- [x] Add named response profiles with validation.
- [x] Add a fluent response builder without breaking existing helpers.
- [x] Add a registry for application-defined response formatters.
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

- [x] SOAP 1.1/1.2 runtime entrypoint with WSDL/service dispatch and fault output.
- [ ] GraphQL execution, errors, context, schema, and pagination integration.
- [x] gRPC metadata/status application handler for a real HTTP/2/protobuf host.
- [x] WebSocket Ratchet component and server entrypoint with normalized messages.
- [ ] Webhook signing, verification, retries, idempotency, queueing, and delivery logs.

Runtime note: PHP's official gRPC package is a client library. SmartResponse's
gRPC runtime integration therefore exposes a normalized handler for a real
HTTP/2 host such as RoadRunner or FrankenPHP, while SOAP and WebSocket can run
directly through PHP's `SoapServer` and a WebSocket server runtime.

### Phase 5: Platform operations

- [x] Artisan doctor command for PHP extensions, configured adapters, and protocol readiness.
- [ ] Artisan install, adapter, profile, and protocol generation commands.
- [ ] Structured logs, metrics, OpenTelemetry hooks, and Laravel observability support.
- [~] Security hardening, rate limits, and declared payload limits. Replay protection remains protocol-specific.
- [~] Safe GET response caching and declared large-payload safeguards. Streaming and benchmarks remain open.

### Phase 6: Documentation and release quality

- [x] Protocol-specific runtime documentation and runnable examples.
- [ ] Compatibility matrix for Laravel, PHP, extensions, and optional runtimes.
- [ ] CI for unit, feature, static-analysis, security, and compatibility suites.
- [ ] Upgrade guides, changelog discipline, and stable release checklist.

## Current implementation status

The current `feat/improvements` implementation includes the core response pipeline, deterministic format negotiation, named profiles, custom formatters, protocol value objects, the SOAP runtime adapter, the Ratchet WebSocket server entrypoint, and the gRPC application handler for an external HTTP/2/protobuf host.

The format negotiation precedence is:

1. Payload format explicitly supplied by the developer.
2. `X-Smart-Response-Format` header.
3. `format` query parameter.
4. A `.json`, `.xml`, `.graphql`, `.soap`, or `.legacy` route suffix.
5. `Accept` header.
6. Configured default format.

Only configured/supported formats are accepted. Invalid explicit values fall back to the configured default so existing applications do not fail unexpectedly.

Read [features and scope](FEATURES_AND_SCOPE.md) for the complete public capability inventory. Read [runtime servers](runtime-servers.md) for SOAP, WebSocket, and gRPC integration examples.

## Definition of done for the platform

- A documented controller example works for each supported protocol.
- Every adapter has contract, success, error, and edge-case tests.
- Existing JSON and web behavior remains backward compatible.
- Optional protocol runtimes are detected and reported by `smart-response:doctor`.
- Security, performance, and failure behavior are documented.
- README and protocol guides are updated with every public API change.
