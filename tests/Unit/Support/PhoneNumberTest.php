<?php

namespace Tests\Unit\Support;

use App\Support\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    /**
     * @return array<string, array{string, ?string}>
     */
    public static function numbers(): array
    {
        return [
            'local with leading zero' => ['0800 000 0000', '+2348000000000'],
            'local without spaces' => ['08031234567', '+2348031234567'],
            'already international with spaces' => ['+234 803 123 4567', '+2348031234567'],
            'without plus prefix' => ['2348031234567', '+2348031234567'],
            'already normalised' => ['+2348031234567', '+2348031234567'],
            'numeric separators' => ['0803-123-4567', '+2348031234567'],
            'empty string' => ['', null],
            'no digits' => ['not a number', null],
        ];
    }

    #[DataProvider('numbers')]
    public function test_normalises_nigerian_numbers_to_e164(string $input, ?string $expected): void
    {
        $this->assertSame($expected, PhoneNumber::normalize($input));
    }
}
