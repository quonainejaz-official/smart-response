<?php

declare(strict_types=1);

use Illuminate\Support\MessageBag;
use Quonain\SmartResponse\Support\ValidationErrorFormatter;

it('formats message bag errors', function () {
    $formatter = new ValidationErrorFormatter();

    $bag = new MessageBag(['email' => ['Invalid']]);

    expect($formatter->format($bag))->toBe(['email' => ['Invalid']]);
});

it('passes through array errors', function () {
    $formatter = new ValidationErrorFormatter();

    $errors = ['name' => ['Required']];

    expect($formatter->format($errors))->toBe($errors);
});
