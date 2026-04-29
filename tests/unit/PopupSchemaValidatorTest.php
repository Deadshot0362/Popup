<?php

use PHPUnit\Framework\TestCase;
use PopupPilot\Validation\PopupSchemaValidator;

class PopupSchemaValidatorTest extends TestCase
{
    private $validator;

    protected function setUp(): void
    {
        $this->validator = new PopupSchemaValidator();
    }

    public function test_valid_document()
    {
        $doc = [
            'version' => '1.0.0',
            'meta' => ['name' => 'Test'],
            'steps' => [
                [
                    'id' => 'step-1',
                    'components' => [
                        ['type' => 'text', 'props' => ['text' => 'hello']]
                    ]
                ]
            ]
        ];
        $result = $this->validator->validate($doc);
        $this->assertTrue($result['valid']);
    }

    public function test_invalid_missing_version()
    {
        $doc = [
            'meta' => ['name' => 'Test'],
            'steps' => []
        ];
        $result = $this->validator->validate($doc);
        $this->assertFalse($result['valid']);
        $this->assertContains('Missing or invalid version.', $result['errors']);
    }
}
