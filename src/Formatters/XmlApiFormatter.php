<?php

declare(strict_types=1);

namespace Vendor\SmartResponse\Formatters;

use SimpleXMLElement;
use Symfony\Component\HttpFoundation\Response as BaseResponse;
use Vendor\SmartResponse\Contracts\ResponseFormatterInterface;
use Vendor\SmartResponse\DTO\SmartResponsePayload;

final class XmlApiFormatter implements ResponseFormatterInterface
{
    public function __construct(
        private readonly array $config,
    ) {}

    public function format(SmartResponsePayload $payload): BaseResponse
    {
        $keys = $this->config['api'];

        $structure = [
            $keys['success_key'] => $payload->success,
            $keys['message_key'] => $payload->message,
            $keys['data_key'] => $payload->normalizedData(),
            $keys['meta_key'] => $payload->meta,
            $keys['errors_key'] => $payload->errors,
        ];

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><response/>');
        $this->arrayToXml($structure, $xml);

        return response($xml->asXML(), $payload->status, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            ...($payload->headers ?? []),
        ]);
    }

    private function arrayToXml(mixed $data, SimpleXMLElement $xml): void
    {
        if (! is_array($data)) {
            $xml[0] = htmlspecialchars((string) $data);

            return;
        }

        foreach ($data as $key => $value) {
            $elementKey = is_numeric($key) ? 'item' : (string) $key;

            if (is_array($value)) {
                $child = $xml->addChild($elementKey);
                $this->arrayToXml($value, $child);
            } else {
                $xml->addChild($elementKey, htmlspecialchars((string) $value));
            }
        }
    }
}
