# FAQ

**Does SmartResponse replace Laravel?** No. It standardizes response construction around your existing controllers and services.

**Does the core require Laravel?** No. `Core\\SmartResponse` has no production framework dependency.

**Does it provide a REST server or gRPC server?** No. It formats application responses and provides selected adapters; routing and server runtimes remain application-owned.

**Can I keep an old API contract?** Yes. Select the legacy formatter or configure a named profile.
