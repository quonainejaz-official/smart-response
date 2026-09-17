<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Formatters;

use SimpleXMLElement;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Response as BaseResponse;
use Quonain\SmartResponse\Contracts\ResponseFormatterInterface;
use Quonain\SmartResponse\DTO\SmartResponsePayload;

/** Formats a payload inside a SOAP 1.1 envelope. */
final class SoapApiFormatter implements ResponseFormatterInterface
{
    public function format(SmartResponsePayload $payload): BaseResponse
    {
        $xml = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>'.
            '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body/></soap:Envelope>'
        );
        $body = $xml->children('http://schemas.xmlsoap.org/soap/envelope/')->Body;
        $response = $body->addChild($payload->success ? 'SmartResponse' : 'SmartResponseFault');
        $this->add($response, 'success', $payload->success);
        $this->add($response, 'message', $payload->message);
        $this->add($response, 'data', $payload->normalizedData());
        $this->add($response, 'meta', $payload->meta);
        $this->add($response, 'errors', $payload->errors);

        return new Response($xml->asXML(), $payload->status, [
            'Content-Type' => 'text/xml; charset=UTF-8',
            ...($payload->headers ?? []),
        ]);
    }

    private function add(SimpleXMLElement $parent, string $name, mixed $value): void
    {
        if (is_array($value)) {
            $node = $parent->addChild($name);
            foreach ($value as $key => $item) {
                $this->add($node, is_numeric($key) ? 'item' : (string) $key, $item);
            }
            return;
        }

        $parent->addChild($name, htmlspecialchars((string) ($value ?? ''), ENT_XML1, 'UTF-8'));
    }
}
