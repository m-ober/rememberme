<?php

/**
 * @license MIT
 */

declare(strict_types=1);

namespace mober\Rememberme\Token;

use Base64Url\Base64Url;

/**
 * Common utility class for tokens
 * It can output tokens in different lengths and formats - raw bytes, hexadecimal and base64
 */
abstract class AbstractToken implements TokenInterface
{
    public const FORMAT_HEX = 'hex'; // doubles the space needed
    public const FORMAT_PLAIN = 'plain';
    public const FORMAT_BASE64 = 'base64'; // space needed * 1.6

    /**
     * @param int $tokenBytes How many bytes the token shall contain
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
     * @return string
     */
    protected function formatBytes(string $token): string
    {
        return match ($this->tokenFormat) {
            self::FORMAT_HEX => bin2hex($token),
            self::FORMAT_BASE64 => Base64Url::encode($token),
            default => $token,
        };
    }
}
