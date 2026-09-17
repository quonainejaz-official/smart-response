<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Support;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Quonain\SmartResponse\Contracts\SmartResponseManagerInterface;
use Quonain\SmartResponse\DTO\SmartResponsePayload;

/** Fluent builder for one controller response across supported protocols. */
final class SmartResponseBuilder
{
    private SmartResponsePayload $payload;

    public function __construct(
        private readonly SmartResponseManagerInterface $manager,
        mixed $data = null,
    ) {
        $this->payload = new SmartResponsePayload(data: $data);
    }

    public function request(?Request $request): self { return $this->change(request: $request); }
    public function data(mixed $data): self { return $this->change(data: $data); }
    public function message(?string $message): self { return $this->change(message: $message); }
    public function errors(mixed $errors): self { return $this->change(errors: $errors, success: false); }
    public function status(int $status): self { return $this->change(status: $status); }
    public function format(?string $format): self { return $this->change(format: $format); }
    public function profile(?string $profile): self { return $this->change(profile: $profile); }
    public function view(?string $view): self { return $this->change(view: $view); }
    public function viewData(?array $viewData): self { return $this->change(viewData: $viewData); }
    public function meta(array $meta): self { return $this->change(meta: array_merge($this->payload->meta, $meta)); }
    public function headers(array $headers): self { return $this->change(headers: $headers); }
    public function redirect(?string $url): self { return $this->change(redirect: $url); }
    public function route(?string $route, array $parameters = []): self { return $this->change(route: $route, routeParameters: $parameters); }
    public function success(bool $success = true): self { return $this->change(success: $success); }
    public function send(?Request $request = null): Response { return $this->manager->respond($this->payload, $request); }

    private function change(mixed ...$changes): self
    {
        $this->payload = $this->payload->replicate(...$changes);

        return $this;
    }
}
