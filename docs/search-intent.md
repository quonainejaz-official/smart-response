# Search intent coverage

This map records genuine capability coverage. It is intended to guide useful documentation and repository metadata, not to add repeated keywords to prose.

| Search query | User intent | Relevant SmartResponse feature | Documentation URL/section | Current coverage | Missing coverage |
| --- | --- | --- | --- | --- | --- |
| PHP response library | Find a reusable PHP response package | Framework-agnostic core | [Getting started](getting-started.md) | Covered | None |
| PHP API response library | Standardize API envelopes | Modern JSON formatter and shortcuts | [JSON responses](json.md) | Covered | None |
| REST API response PHP | Return consistent REST output | Laravel API builder and core value object | [Basic usage](basic-usage.md) | Covered | None |
| API response formatter PHP | Serialize response contracts | Formatter registry | [Response formats](responses.md) | Covered | None |
| JSON API response PHP | Produce JSON envelope | JSON formatter | [JSON responses](json.md) | Covered | None |
| XML API response PHP | Produce XML output | XML formatter | [XML responses](xml.md) | Covered | None |
| legacy API response PHP | Preserve an existing envelope | Legacy formatter and profiles | [Legacy compatibility](legacy.md) | Covered | None |
| multiple API response formats PHP | Select formats per client | Explicit format selectors | [Response formats](responses.md) | Covered | None |
| content negotiation PHP | Select output from request headers | Configurable request detection | [Request detection](content-negotiation.md) | Partial | Not a full RFC negotiation engine |
| Laravel API response | Laravel controller integration | Trait, facade, helpers, middleware | [Framework integration](framework-integration.md) | Covered | None |
| Symfony API response | Use with Symfony | Core response mapping | [Framework integration](framework-integration.md) | Partial | No dedicated Symfony adapter |
| response factory PHP | Centralize response creation | Core service and Laravel builders | [Architecture](architecture.md) | Covered | None |
| API response abstraction PHP | Decouple response creation | Immutable core response value | [Architecture](architecture.md) | Covered | None |
| PHP response builder | Fluent response construction | `SmartResponseBuilder` | [Basic usage](basic-usage.md) | Covered | None |
