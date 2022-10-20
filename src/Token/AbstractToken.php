<?php

/**
 * @license MIT
 */

namespace mober\Rememberme\Token;

/**
 * Common utility class for tokens
 *
 * It can output tokens in different lengths and formats - raw bytes, hexadecimal and base64
 */
abstract class AbstractToken implements TokenInterface
{
    public const FORMAT_HEX    = 'hex'; // doubles the space needed
    public const FORMAT_PLAIN  = 'plain';
    public const FORMAT_BASE64 = 'base64'; // space needed * 1.6

    /**
     * @param int    $tokenBytes  How many bytes the token shall contain
     * @param string $tokenFormat How the bytes shall be formatted. Can increase the string returned
     */
    public function __construct(protected int $tokenBytes = 16, protected string $tokenFormat = self::FORMAT_HEX)
    {
        if (!in_array($tokenFormat, [self::FORMAT_HEX, self::FORMAT_PLAIN, self::FORMAT_BASE64])) {
            throw new \InvalidArgumentException("Invalid token format");
        }
    }

    /**
     * @param string $token
     *
     * @return string
     */
    protected function formatBytes(string $token): string
    {
        switch ($this->tokenFormat) {
            case self::FORMAT_HEX:
                return bin2hex($token);
            case self::FORMAT_PLAIN:
                return $token;
            case self::FORMAT_BASE64:
                return base64_encode($token);
        }
    }
}
