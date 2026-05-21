<?php

declare(strict_types=1);

namespace Vendor\SmartResponse\Support;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\ValidationException;

final class ValidationErrorFormatter
{
    /**
     * @return array<string, array<int, string>>
     */
    public function format(mixed $errors): array
    {
        if ($errors instanceof ValidationException) {
            return $errors->errors();
        }

        if ($errors instanceof Validator) {
            return $errors->errors()->toArray();
        }

        if ($errors instanceof MessageBag) {
            return $errors->toArray();
        }

        if (is_array($errors)) {
            return $errors;
        }

        return ['message' => [(string) $errors]];
    }
}
