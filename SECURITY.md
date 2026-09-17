# Security policy

Please do not report suspected vulnerabilities in public issues. Email the maintainer at [quonainejaz-official@users.noreply.github.com](mailto:quonainejaz-official@users.noreply.github.com) with the package version, affected component, reproduction steps, and any suggested mitigation.

Supported security fixes are provided for the latest release line. Do not include secrets or personal data in reports.

## Security guidance

- Treat XML as untrusted data. Smart Response only formats XML; parsing inbound XML and disabling external entities is the host application's responsibility.
- Do not deserialize untrusted PHP serialization data. Prefer JSON/XML parsing and allow-listed DTO normalization.
- Header names and values must come from trusted application configuration; never concatenate user input into headers.
- Keep production error messages generic and log diagnostic details through the application's protected logging channel.
- Do not cache authenticated responses without an explicit, isolated cache key. Request-specific metadata is intentionally excluded from automatic caching.
- The SOAP adapter requires the host's `ext-soap`; configure WSDL caching, `send_errors`, and endpoint access according to the PHP SOAP deployment guidance.
