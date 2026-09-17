# Architecture

The core produces an immutable response value without depending on a framework. Laravel-specific builders adapt requests, views, sessions, events, translations, and framework response objects around that core.

Formatters own envelope serialization. Detectors select API/web and format behavior. Support objects handle pagination, metadata, rate limits, and transport-neutral webhook/WebSocket/gRPC payloads. Runtime adapters deliberately do not replace the host protocol server.

This separation keeps the default API contract stable and lets applications integrate incrementally.
