<?php

declare(strict_types=1);

use Vendor\SmartResponse\DTO\SmartResponsePayload;
use Vendor\SmartResponse\Formatters\JsonApiFormatter;

it('formats standardized api json structure', function () {
    $formatter = new JsonApiFormatter(require dirname(__DIR__, 2).'/config/smart-response.php');

    $response = $formatter->format(new SmartResponsePayload(
        data: [['id' => 1]],
        message: 'Success',
        success: true,
        meta: ['total' => 1],
        status: 200,
    ));

    $json = $response->getData(true);

    expect($response->getStatusCode())->toBe(200)
        ->and($json)->toMatchArray([
            'success' => true,
            'message' => 'Success',
            'data' => [['id' => 1]],
            'meta' => ['total' => 1],
            'errors' => null,
        ]);
});

it('formats error responses', function () {
    $formatter = new JsonApiFormatter(require dirname(__DIR__, 2).'/config/smart-response.php');

    $response = $formatter->format(new SmartResponsePayload(
        message: 'Failed',
        success: false,
        errors: ['email' => ['Invalid email']],
        status: 422,
    ));

    $json = $response->getData(true);

    expect($json['success'])->toBeFalse()
        ->and($json['errors'])->toBe(['email' => ['Invalid email']]);
});
