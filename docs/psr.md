# PSR interoperability

PSR-7 describes immutable HTTP messages, PSR-17 describes factories for creating them, and PSR-18 describes sending PSR-7 requests through an implementation-neutral client. Smart Response does not require any of these packages in the core because applications may already use different implementations.

The supported direction is an adapter at the edge:

```text
domain data -> Smart Response formatter -> framework or PSR-7 response
```

Adding a PSR-7 bridge is appropriate when a concrete PSR-17 response factory is available. It should be a separate optional package or integration so a minimal installation remains dependency-free.
