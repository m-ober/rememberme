<?php

/**
 * @license MIT
 */

namespace mober\Rememberme\Test;

use mober\Rememberme\Token\AbstractToken;
use mober\Rememberme\Token\DefaultToken;
use PHPUnit\Framework\TestCase;

class DefaultTokenTest extends TestCase
{
    public function testDefaultTokenReturns32CharsInHexadecimal()
    {
        $token = new DefaultToken(tokenBytes: 16, tokenFormat: AbstractToken::FORMAT_HEX);
        $this->assertMatchesRegularExpression("/^[\\da-f]{32}$/", $token->createToken());
    }

    public function testTokenLengthDoublesWhenUsingHexFormat()
    {
        $token = new DefaultToken(tokenBytes: 32, tokenFormat: AbstractToken::FORMAT_HEX);
        $this->assertMatchesRegularExpression("/^[\\da-f]{64}$/", $token->createToken());
    }

    public function testTokenLengthIncreasesWhenUsingBase64Format()
    {
        $token = new DefaultToken(tokenBytes: 32, tokenFormat: AbstractToken::FORMAT_BASE64);
        $this->assertMatchesRegularExpression("/^[\\da-zA-Z_-]{43}$/", $token->createToken());
    }

    public function testTokenLengthIsExactWhenUsingPlainFormat()
    {
        $token = new DefaultToken(tokenBytes: 32, tokenFormat: AbstractToken::FORMAT_PLAIN);
        $this->assertSame(32, strlen($token->createToken()));
    }
}
