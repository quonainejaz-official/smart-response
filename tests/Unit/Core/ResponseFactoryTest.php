<?php

declare(strict_types=1);

use Quonain\SmartResponse\Core\AcceptHeaderNegotiator;
use Quonain\SmartResponse\Core\Formatters\JsonFormatter;
use Quonain\SmartResponse\Core\Formatters\TextFormatter;
use Quonain\SmartResponse\Core\Formatters\XmlFormatter;
use Quonain\SmartResponse\Core\NativeSerializer;
use Quonain\SmartResponse\Core\ResponseFactory;

it('negotiates registered standalone formatters by quality', function (): void {
    $factory = new ResponseFactory(new NativeSerializer(), new AcceptHeaderNegotiator());
    $factory->register('json', new JsonFormatter(new NativeSerializer()))
        ->register('xml', new XmlFormatter(new NativeSerializer()))
        ->register('text', new TextFormatter());

    $response = $factory->negotiate(['ok' => true], 'application/xml;q=0.9, application/json;q=1');

    expect($response->status())->toBe(200)
        ->and($response->headers()['Content-Type'])->toBe('application/json; charset=UTF-8')
        ->and($response->content())->toContain('"ok":true');
});

it('rejects an unsupported standalone format', function (): void {
    $factory = new ResponseFactory();

    expect(fn () => $factory->make('hello', 'yaml'))
        ->toThrow(InvalidArgumentException::class);
});

it('escapes unsafe XML values and element names', function (): void {
    $factory = new ResponseFactory(new NativeSerializer());
    $factory->register('xml', new XmlFormatter(new NativeSerializer()));

    $content = $factory->make(['bad key' => '<value>'], 'xml')->content();

    expect($content)->toContain('<bad-key>&lt;value&gt;</bad-key>');
});
