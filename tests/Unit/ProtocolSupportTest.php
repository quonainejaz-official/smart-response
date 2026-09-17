<?php

declare(strict_types=1);

use Quonain\SmartResponse\DTO\SmartResponsePayload;
use Quonain\SmartResponse\Formatters\GraphQLApiFormatter;
use Quonain\SmartResponse\Formatters\SoapApiFormatter;
use Quonain\SmartResponse\Support\GrpcResponse;
use Quonain\SmartResponse\Support\WebhookPayload;
use Quonain\SmartResponse\Support\WebSocketMessage;

it('formats graphql responses', function () {
    $response = (new GraphQLApiFormatter())->format(new SmartResponsePayload(data: ['id' => 1]));

    expect($response->getData(true))->toBe(['data' => ['id' => 1]])
        ->and($response->headers->get('Content-Type'))->toContain('application/graphql-response+json');
});

it('formats soap responses', function () {
    $response = (new SoapApiFormatter())->format(new SmartResponsePayload(data: ['id' => 1]));

    expect($response->getContent())->toContain('Envelope')
        ->and($response->getContent())->toContain('data')
        ->and($response->headers->get('Content-Type'))->toContain('text/xml');
});

it('provides transport-neutral grpc, websocket, and webhook payloads', function () {
    $grpc = new GrpcResponse(data: ['id' => 1]);
    $socket = new WebSocketMessage(data: ['id' => 1], event: 'user.loaded');
    $webhook = WebhookPayload::create('user.loaded', ['id' => 1], secret: 'secret');

    expect($grpc->toArray()['data'])->toBe(['id' => 1])
        ->and(json_decode($socket->encode(), true)['event'])->toBe('user.loaded')
        ->and($webhook)->toHaveKey('signature');
});
