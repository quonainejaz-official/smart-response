<?php

declare(strict_types=1);

use Quonain\SmartResponse\DTO\SmartResponsePayload;
use Quonain\SmartResponse\Formatters\LegacyApiFormatter;

it('formats a legacy api response without changing the modern envelope', function () {
    $config = require dirname(__DIR__, 2).'/config/smart-response.php';
    $response = (new LegacyApiFormatter($config))->format(new SmartResponsePayload(
        data: ['id' => 1], message: 'Loaded', success: true, status: 200,
    ));

    expect($response->getData(true))->toBe([
        'status' => true, 'message' => 'Loaded', 'data' => ['id' => 1], 'errors' => null,
    ]);
});

it('supports custom legacy keys', function () {
    $config = require dirname(__DIR__, 2).'/config/smart-response.php';
    $config['legacy']['keys'] = [
        'status' => 'ok', 'message' => 'msg', 'data' => 'result', 'errors' => 'problems',
    ];

    $json = (new LegacyApiFormatter($config))->format(new SmartResponsePayload(
        data: ['id' => 1], success: true, status: 200,
    ))->getData(true);

    expect($json)->toHaveKey('ok')->toHaveKey('msg')->toHaveKey('result')->toHaveKey('problems');
});
