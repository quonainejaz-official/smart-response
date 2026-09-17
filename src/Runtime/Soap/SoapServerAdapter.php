<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Runtime\Soap;

use RuntimeException;
use SoapServer;

/** Runs a real PHP SOAP 1.1/1.2 server around an application service object. */
final class SoapServerAdapter
{
    public function __construct(
        private readonly object $service,
        private readonly ?string $wsdl = null,
        /** @var array<string, mixed> */
        private readonly array $options = [],
    ) {}

    public function handle(?string $request = null): string
    {
        if (! class_exists(SoapServer::class)) {
            throw new RuntimeException('The PHP SOAP extension is required to run the SOAP server.');
        }

        $options = $this->options;
        if ($this->wsdl === null && !isset($options['uri'])) {
            $options['uri'] = 'urn:smart-response';
        }

        $server = new SoapServer($this->wsdl, $options);
        $server->setObject($this->service);

        $bufferLevel = ob_get_level();
        ob_start();
        try {
            $server->handle($request);
            return ob_get_level() > $bufferLevel ? (string) ob_get_contents() : '';
        } finally {
            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }
        }
    }
}
