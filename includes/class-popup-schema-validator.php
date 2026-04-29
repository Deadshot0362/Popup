<?php

declare(strict_types=1);

namespace PopupPilot\Validation;

if (!defined('ABSPATH')) {
    exit;
}

final class PopupSchemaValidator
{
    /**
     * @param array<string, mixed> $document
     * @return array{valid: bool, errors: array<int, string>}
     */
    public function validate(array $document): array
    {
        $errors = [];

        if (!isset($document['version']) || !is_string($document['version']) || $document['version'] === '') {
            $errors[] = 'Missing or invalid version.';
        }

        if (!isset($document['meta']) || !is_array($document['meta'])) {
            $errors[] = 'Missing meta object.';
        } elseif (!isset($document['meta']['name']) || !is_string($document['meta']['name']) || trim($document['meta']['name']) === '') {
            $errors[] = 'Missing meta.name.';
        }

        if (!isset($document['steps']) || !is_array($document['steps']) || count($document['steps']) < 1) {
            $errors[] = 'At least one step is required.';
        } else {
            foreach ($document['steps'] as $index => $step) {
                if (!is_array($step)) {
                    $errors[] = sprintf('Step %d must be an object.', $index);
                    continue;
                }

                if (!isset($step['id']) || !is_string($step['id']) || $step['id'] === '') {
                    $errors[] = sprintf('Step %d missing id.', $index);
                }

                if (!isset($step['components']) || !is_array($step['components'])) {
                    $errors[] = sprintf('Step %d missing components array.', $index);
                    continue;
                }

                foreach ($step['components'] as $componentIndex => $component) {
                    if (!is_array($component)) {
                        $errors[] = sprintf('Step %d component %d must be an object.', $index, $componentIndex);
                        continue;
                    }

                    $type = $component['type'] ?? null;
                    $allowedTypes = ['text', 'image', 'button', 'form', 'countdown', 'video'];

                    if (!is_string($type) || !in_array($type, $allowedTypes, true)) {
                        $errors[] = sprintf('Step %d component %d has invalid type.', $index, $componentIndex);
                    }
                }
            }
        }

        return [
            'valid' => count($errors) === 0,
            'errors' => $errors,
        ];
    }
}
