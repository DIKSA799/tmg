<?php

namespace Tests\Unit\Support;

use App\Models\State;
use App\Support\Abbreviator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AbbreviatorTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function names(): array
    {
        return [
            'single word' => ['Albasu', 'ALB'],
            'two words' => ['Ndokwa West', 'NDW'],
            'slash separated' => ['Ajeromi/Ifelodun', 'AJI'],
            'qualifier kept' => ['Albasu Central', 'ALC'],
            'place and centre' => ['Gwagwalada Centre', 'GWC'],
            'three words' => ['Ovia North East', 'ONE'],
            'roman numeral' => ['Bakana I', 'BAK1'],
            'roman numeral two' => ['Bakana II', 'BAK2'],
            'arabic numeral' => ['Ward 03', 'WAR3'],
            'empty' => ['', 'UNK'],
        ];
    }

    #[DataProvider('names')]
    public function test_it_abbreviates_names(string $name, string $expected): void
    {
        $this->assertSame($expected, (new Abbreviator)->make($name));
    }

    public function test_it_prefers_standard_state_codes(): void
    {
        $abbreviator = new Abbreviator;

        $this->assertSame('DEL', $abbreviator->state(new State(['name' => 'Delta', 'slug' => 'delta'])));
        $this->assertSame('CRS', $abbreviator->state(new State(['name' => 'Cross River', 'slug' => 'cross-river'])));
        $this->assertSame('FCT', $abbreviator->state(new State(['name' => 'FCT', 'slug' => 'fct'])));
        $this->assertSame('UNK', $abbreviator->state(null));
    }
}
