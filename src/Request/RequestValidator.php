<?php

namespace App\Request;

class RequestValidator
{
    private const MAX_BODY_BYTES = 1048576;
    private const PLATE_PATTERNS = '/^(?:[A-Z]{3}[0-9]{4}|[A-Z]{3}[0-9][A-Z][0-9]{2})$/';
    private const ALLOWED_KEYS = ['placa'];

    public function validate(string $input): array
    {
        if (strlen($input) > self::MAX_BODY_BYTES) {
            throw new RequestValidationException('invalid_request', 'Request body exceeds 1 MiB limit.');
        }

        $data = json_decode($input, true);

        if (! is_array($data)) {
            throw new RequestValidationException('invalid_request', 'Invalid JSON body.');
        }

        $unknownKeys = array_diff(array_keys($data), self::ALLOWED_KEYS);
        if (! empty($unknownKeys)) {
            throw new RequestValidationException('invalid_request', 'Unknown JSON field: ' . implode(', ', $unknownKeys));
        }

        if (empty($data['placa']) || ! is_string($data['placa'])) {
            throw new RequestValidationException('invalid_plate', 'Plate is required.');
        }

        $plate = strtoupper(trim($data['placa']));

        if (! preg_match(self::PLATE_PATTERNS, $plate)) {
            throw new RequestValidationException('invalid_plate', 'Plate does not match known Mercosul or old pattern.');
        }

        return ['placa' => $plate];
    }
}
