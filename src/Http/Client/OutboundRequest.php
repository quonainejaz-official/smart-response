<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Http\Client;

use Quonain\SmartResponse\Exceptions\OutboundRequestException;

final class OutboundRequest
{
    private string $method = 'GET';
    private string $path = '/';
    /** @var array<string, mixed> */
    private array $query = [];
    /** @var array<string, string> */
    private array $headers = [];
    /** @var array<string, string> */
    private array $cookies = [];
    private mixed $body = null;
    /** @var array<string, mixed> */
    private array $options = [];
    private int $retries = 0;
    private int $backoffMs = 100;

    /** @param array<string, mixed> $config */
    public function __construct(private readonly string $provider, private readonly array $config, private readonly ?\Closure $logger = null) {}
    public function get(string $path): self { return $this->method('GET', $path); }
    public function post(string $path): self { return $this->method('POST', $path); }
    public function put(string $path): self { return $this->method('PUT', $path); }
    public function patch(string $path): self { return $this->method('PATCH', $path); }
    public function delete(string $path): self { return $this->method('DELETE', $path); }
    public function options(string $path): self { return $this->method('OPTIONS', $path); }
    public function head(string $path): self { return $this->method('HEAD', $path); }
    public function method(string $method, string $path): self { $this->method = strtoupper($method); $this->path = $path; return $this; }
    /** @param array<string, mixed> $query */
    public function query(array $query): self { $this->query = array_merge($this->query, $query); return $this; }
    public function path(string $key, string|int $value): self { $this->path = str_replace('{'.$key.'}', rawurlencode((string) $value), $this->path); return $this; }
    /** @param array<string, string> $headers */
    public function headers(array $headers): self { $this->headers = array_merge($this->headers, $headers); return $this; }
    /** @param array<string, string> $cookies */
    public function cookies(array $cookies): self { $this->cookies = array_merge($this->cookies, $cookies); return $this; }
    public function body(mixed $body): self { $this->body = $body; return $this; }
    public function json(mixed $body): self { return $this->headers(['Content-Type' => 'application/json'])->body($body); }
    /** @param array<string, mixed> $body */
    public function form(array $body): self { return $this->headers(['Content-Type' => 'application/x-www-form-urlencoded'])->body($body); }
    /** @param array<string, mixed> $body */
    public function multipart(array $body): self { return $this->headers(['Content-Type' => 'multipart/form-data'])->body($body); }
    public function timeout(float $seconds): self { $this->options['timeout'] = max(0.001, $seconds); return $this; }
    public function connectTimeout(float $seconds): self { $this->options['connect_timeout'] = max(0.001, $seconds); return $this; }
    public function retry(int $times, int $backoffMs = 100): self { $this->retries = max(0, $times); $this->backoffMs = max(0, $backoffMs); return $this; }
    public function ssl(bool $verify = true): self { $this->options['verify_ssl'] = $verify; return $this; }
    public function option(string $key, mixed $value): self { $this->options[$key] = $value; return $this; }

    public function send(): HttpResponse
    {
        if ($this->logger !== null) ($this->logger)(['event' => 'external_api_call', 'provider' => $this->provider, 'method' => $this->method]);
        if (! function_exists('curl_init')) throw new OutboundRequestException('The cURL extension is required for outbound requests.', 'curl_extension_missing', null, $this->provider);
        $base = rtrim((string) ($this->config['base_url'] ?? ''), '/');
        if ($base === '' || ! filter_var($base, FILTER_VALIDATE_URL)) throw new OutboundRequestException('Provider has no valid base URL.', 'invalid_provider_url', null, $this->provider);
        $allowedHosts = $this->config['allowed_hosts'] ?? [];
        if (is_array($allowedHosts) && $allowedHosts !== [] && ! in_array((string) parse_url($base, PHP_URL_HOST), $allowedHosts, true)) throw new OutboundRequestException('Provider host is not allowed.', 'ssrf_host_not_allowed', null, $this->provider);
        $url = $base.'/'.ltrim($this->path, '/');
        if ($this->query !== []) $url .= (str_contains($url, '?') ? '&' : '?').http_build_query($this->query);
        $attempt = 0; $started = microtime(true);
        do {
            $ch = curl_init($url); $responseHeaders = [];
            $headers = [];
            foreach ($this->headers as $key => $value) $headers[] = $key.': '.$value;
            if ($this->cookies !== []) $headers[] = 'Cookie: '.http_build_query($this->cookies, '', '; ');
            $auth = $this->config['auth'] ?? null;
            if (is_array($auth)) {
                $type = strtolower((string) ($auth['type'] ?? ''));
                if ($type === 'bearer') $headers[] = 'Authorization: Bearer '.($auth['token'] ?? '');
                elseif ($type === 'api_key') $headers[] = ($auth['header'] ?? 'X-API-Key').': '.($auth['key'] ?? '');
                elseif ($type === 'basic') curl_setopt($ch, CURLOPT_USERPWD, ($auth['username'] ?? '').':'.($auth['password'] ?? ''));
            }
            $payload = $this->body;
            if (is_array($payload) && str_contains(strtolower((string) ($this->headers['Content-Type'] ?? '')), 'json')) $payload = json_encode($payload, JSON_THROW_ON_ERROR);
            elseif (is_array($payload) && str_contains(strtolower((string) ($this->headers['Content-Type'] ?? '')), 'form-urlencoded')) $payload = http_build_query($payload);
            curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST => $this->method, CURLOPT_HTTPHEADER => $headers, CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_TIMEOUT_MS => (int) (($this->options['timeout'] ?? $this->config['timeout'] ?? 30) * 1000), CURLOPT_CONNECTTIMEOUT_MS => (int) (($this->options['connect_timeout'] ?? $this->config['connect_timeout'] ?? 10) * 1000), CURLOPT_SSL_VERIFYPEER => $this->options['verify_ssl'] ?? ($this->config['verify_ssl'] ?? true), CURLOPT_SSL_VERIFYHOST => ($this->options['verify_ssl'] ?? ($this->config['verify_ssl'] ?? true)) ? 2 : 0]);
            if ($payload !== null && ! in_array($this->method, ['GET', 'HEAD'], true)) curl_setopt($ch, CURLOPT_POSTFIELDS, is_string($payload) ? $payload : $payload);
            $raw = curl_exec($ch); $errno = curl_errno($ch); $error = curl_error($ch); $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE); $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE); curl_close($ch);
            if ($errno === 0 && is_string($raw)) { foreach (explode("\r\n", trim(substr($raw, 0, $headerSize))) as $line) if (str_contains($line, ':')) { [$k, $v] = explode(':', $line, 2); $responseHeaders[trim($k)] = trim($v); } $body = substr($raw, $headerSize); $maximum = (int) ($this->config['max_response_bytes'] ?? 10485760); if ($maximum > 0 && strlen($body) > $maximum) throw new OutboundRequestException('External response exceeds the configured size limit.', 'response_size_limit_exceeded', $status, $this->provider); $result = new HttpResponse($status, $body, $responseHeaders, microtime(true) - $started, $attempt); if ($result->successful() || $attempt >= $this->retries || ! in_array($status, [408, 425, 429, 500, 502, 503, 504], true)) return $result; }
            elseif ($attempt >= $this->retries) throw new OutboundRequestException($error ?: 'Outbound request failed.', 'external_api_transport_error', null, $this->provider);
            $attempt++; if ($this->backoffMs > 0) usleep($this->backoffMs * (2 ** ($attempt - 1)) * 1000);
        } while ($attempt <= $this->retries);
        throw new OutboundRequestException('Outbound request failed after retries.', 'external_api_retry_exhausted', null, $this->provider);
    }
}
