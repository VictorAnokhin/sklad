<?php

namespace Tests\Unit;

use App\Models\Conf;
use PHPUnit\Framework\TestCase;

class ConfWeb3ChainIdTest extends TestCase
{
    /**
     * @dataProvider chainIdToHexProvider
     */
    public function test_normalize_web3_chain_id_to_hex(mixed $input, ?string $expected): void
    {
        $this->assertSame($expected, Conf::normalizeWeb3ChainIdToHex($input));
    }

    public static function chainIdToHexProvider(): array
    {
        return [
            'null' => [null, null],
            'int 1' => [1, '0x1'],
            'string decimal' => ['56', '0x38'],
            'hex with 0x' => ['0x38', '0x38'],
            'hex no 0x' => ['a4b1', '0xa4b1'],
            'upper hex with 0x' => ['0XA4B1', '0xa4b1'],
            'invalid' => ['not-a-chain', null],
        ];
    }

    /**
     * @dataProvider chainIdToDecimalProvider
     */
    public function test_normalize_web3_chain_id_to_decimal_string(mixed $input, ?string $expected): void
    {
        $this->assertSame($expected, Conf::normalizeWeb3ChainIdToDecimalString($input));
    }

    public static function chainIdToDecimalProvider(): array
    {
        return [
            'null' => [null, null],
            'int 56' => [56, '56'],
            'string decimal' => ['56', '56'],
            'padded decimal' => ['00056', '56'],
            'hex with 0x' => ['0x38', '56'],
            'hex no 0x' => ['a4b1', '42161'],
            'invalid' => ['??', null],
        ];
    }
}

