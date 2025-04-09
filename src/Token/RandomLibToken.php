<?php

/**
 * @license MIT
 */

declare(strict_types=1);

namespace mober\Rememberme\Token;

use RandomLib\Factory;
use RandomLib\Generator;

/**
 * A token class that uses ircmaxell/random-lib to generate secure random tokens
 */
class RandomLibToken extends AbstractToken
{
    /**
     * @var Generator
     */
    protected Generator $generator;

    protected array $formatMap;

    /**
     * @param int $tokenBytes
     * @param string $tokenFormat
     * @param Generator|null $generator
     */
    public function __construct(
        int $tokenBytes = 32,
        string $tokenFormat = self::FORMAT_HEX,
        ?Generator $generator = null,
    ) {
        parent::__construct($tokenBytes, $tokenFormat);
        if (is_null($generator)) {
            $factory = new Factory();
            $this->generator = $factory->getMediumStrengthGenerator();
        } else {
            $this->generator = $generator;
        }
        $this->formatMap = [
            self::FORMAT_HEX => Generator::CHAR_LOWER_HEX,
            self::FORMAT_PLAIN => Generator::CHAR_BASE64,
            self::FORMAT_BASE64 => Generator::CHAR_BASE64,
        ];
    }

    /**
     * Generate a random, 32-byte Token
     * @return string
     */
    public function createToken(): string
    {
        return $this->generator->generateString($this->tokenBytes, $this->formatMap[$this->tokenFormat]);
    }
}
