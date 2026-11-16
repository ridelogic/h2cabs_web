<?php

namespace Tests\Unit;

use App\Fares\Money;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_converts_rupee_strings_to_paise_without_floats(): void
    {
        $this->assertSame(1300, Money::toPaise('13'));
        $this->assertSame(1350, Money::toPaise('13.5'));
        $this->assertSame(1335, Money::toPaise('13.35'));
        $this->assertSame(1300, Money::toPaise(13));
        $this->assertNull(Money::toPaise(null));
        $this->assertSame(135, Money::toTenths('13.5'));
        $this->assertSame(130, Money::toTenths('13.0'));
    }

    /** @return array<string, array{int, string}> */
    public static function indianFormats(): array
    {
        return [
            'zero' => [0, '₹0'],
            'hundreds' => [16_900, '₹169'],
            'thousands' => [203_321, '₹2,033.21'],
            'lakh' => [12_345_600, '₹1,23,456'],
            'crore' => [1_234_567_800, '₹1,23,45,678'],
        ];
    }

    #[DataProvider('indianFormats')]
    public function test_formats_in_indian_grouping(int $paise, string $expected): void
    {
        $this->assertSame($expected, Money::format($paise));
    }

    public function test_rejects_negative_or_malformed_amounts(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::toPaise('-5');
    }

    public function test_rejects_too_many_decimals(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::toPaise('13.555');
    }
}
