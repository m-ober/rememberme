<?php

/**
 * @license MIT
 */

namespace mober\Rememberme\Test;

use mober\Rememberme\Token\ClassicToken;
use PHPUnit\Framework\TestCase;

class ClassicTokenTest extends TestCase
{

    /**
     * @var ClassicToken
     */
    protected $token;

    protected function setUp(): void
    {
        $this->token = new ClassicToken();
    }

    public function testTokenIs32CharsInHexadecimal()
    {
        $this->assertMatchesRegularExpression("/^[\\da-f]{32}$/", $this->token->createToken());
    }
}
