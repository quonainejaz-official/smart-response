<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Core\Formatters;

use Quonain\SmartResponse\Contracts\CoreResponseFormatterInterface;
use Quonain\SmartResponse\Contracts\SerializerInterface;
use Quonain\SmartResponse\Core\Response;

final class XmlFormatter implements CoreResponseFormatterInterface
{
    public function __construct(private readonly SerializerInterface $serializer)
    {
    }

    public function mediaType(): string { return 'application/xml'; }

    /** @param array<string, mixed> $context */
    public function format(mixed $data, array $context = []): Response
    {
        $root = (string) ($context['root'] ?? 'response');
        $root = $this->elementName($root);
        $xml = '<?xml version="1.0" encoding="UTF-8"?><'.$root.'>';
        $xml .= $this->render($this->serializer->normalize($data, $context));
        $xml .= '</'.$root.'>';

        return new Response($xml, (int) ($context['status'] ?? 200), [
            'Content-Type' => $this->mediaType().'; charset=UTF-8',
            ...($context['headers'] ?? []),
        ]);
    }

    private function render(mixed $value, string $name = 'item'): string
    {
        $name = $this->elementName($name);
        if (! is_array($value)) {
            return '<'.$name.'>'.htmlspecialchars((string) ($value ?? ''), ENT_XML1 | ENT_QUOTES, 'UTF-8').'</'.$name.'>';
        }
        $children = '';
        foreach ($value as $key => $item) {
            $children .= $this->render($item, is_int($key) ? 'item' : (string) $key);
        }
        return '<'.$name.'>'.$children.'</'.$name.'>';
    }

    private function elementName(string $name): string
    {
        $name = trim(preg_replace('/[^A-Za-z0-9_.-]/', '-', $name) ?? '', '.-');
        return $name !== '' && (ctype_alpha($name[0]) || $name[0] === '_') ? $name : 'item';
    }
}
