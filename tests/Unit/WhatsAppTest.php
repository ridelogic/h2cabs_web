<?php

namespace Tests\Unit;

use App\Support\WhatsApp;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WhatsAppTest extends TestCase
{
    /** @return array<string, array{?string, ?string}> */
    public static function numbers(): array
    {
        return [
            'indian mobile' => ['98765 43210', '919876543210'],
            'with country code' => ['+91 98765-43210', '919876543210'],
            'leading zero' => ['098765 43210', '919876543210'],
            'international' => ['+44 7700 900123', '447700900123'],
            'ten digits not a mobile' => ['0413222222', null],
            'too short' => ['12345', null],
            'too long' => ['+1234567890123456', null],
            'letters' => ['call me', null],
            'empty' => ['', null],
            'null' => [null, null],
        ];
    }

    #[DataProvider('numbers')]
    public function test_normalize(?string $input, ?string $expected): void
    {
        $this->assertSame($expected, WhatsApp::normalize($input));
    }

    public function test_chat_url_encodes_the_message(): void
    {
        config(['site.contact.whatsapp' => '+91 98765 43210']);

        $this->assertSame(
            'https://wa.me/919876543210?text=Hi%20%26%20hello%0ALine%202',
            WhatsApp::chatUrl("Hi & hello\nLine 2"),
        );
        $this->assertSame('https://wa.me/919876543210', WhatsApp::chatUrl());
    }

    public function test_chat_url_is_null_when_not_configured_or_invalid(): void
    {
        config(['site.contact.whatsapp' => null]);
        $this->assertNull(WhatsApp::chatUrl('Hello'));
        $this->assertFalse(WhatsApp::isConfigured());

        config(['site.contact.whatsapp' => 'not a number']);
        $this->assertNull(WhatsApp::chatUrl('Hello'));
    }
}
