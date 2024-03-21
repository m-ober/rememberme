<?php

/**
 * @license MIT
 */

declare(strict_types=1);

namespace mober\Rememberme\Storage;

use SensitiveParameter;

/**
 * File-Based Storage
 */
class FileStorage extends AbstractStorage
{
    /**
     * @param string $path
     * @param string $suffix
     */
    public function __construct(protected string $path = "", protected string $suffix = ".txt")
    {
    }

    /**
     * @param mixed $credential
     * @param string $token
     * @param string $persistentToken
     * @return int
     */
    public function findTriplet(
        mixed $credential,
        #[SensitiveParameter] string $token,
        #[SensitiveParameter] string $persistentToken,
    ): int
    {
        // Hash the tokens, because they can contain a salt and can be accessed in the file system
        $persistentToken = $this->hash($persistentToken);
        $token = $this->hash($token);
        $fn = $this->getFilename($credential, $persistentToken);

        if (!file_exists($fn)) {
            return self::TRIPLET_NOT_FOUND;
        }

        $fileToken = trim(file_get_contents($fn));

        if (hash_equals($fileToken, $token)) {
            return self::TRIPLET_FOUND;
        }

        return self::TRIPLET_INVALID;
    }

    /**
     * @param mixed $credential
     * @param string $token
     * @param string $persistentToken
     * @param int $expire
     */
    public function storeTriplet(
        mixed $credential,
        #[SensitiveParameter] string $token,
        #[SensitiveParameter] string $persistentToken,
        int $expire,
    ): void
    {
        // Hash the tokens, because they can contain a salt and can be accessed in the file system
        $persistentToken = $this->hash($persistentToken);
        $token = $this->hash($token);
        $fn = $this->getFilename($credential, $persistentToken);
        file_put_contents($fn, $token);
    }

    /**
     * @param mixed $credential
     * @param string $persistentToken
     */
    public function cleanTriplet(mixed $credential, #[SensitiveParameter] string $persistentToken): void
    {
        $persistentToken = $this->hash($persistentToken);
        $fn = $this->getFilename($credential, $persistentToken);

        if (file_exists($fn)) {
            unlink($fn);
        }
    }

    /**
     * Replace current token after successful authentication
     * @param mixed $credential
     * @param string $token
     * @param string $persistentToken
     * @param int $expire
     */
    public function replaceTriplet(
        mixed $credential,
        #[SensitiveParameter] string $token,
        #[SensitiveParameter] string $persistentToken,
        int $expire,
    ): void
    {
        $this->cleanTriplet($credential, $persistentToken);
        $this->storeTriplet($credential, $token, $persistentToken, $expire);
    }

    /**
     * @param mixed $credential
     */
    public function cleanAllTriplets(mixed $credential): void
    {
        foreach (glob($this->path . DIRECTORY_SEPARATOR . $credential . ".*" . $this->suffix) as $file) {
            unlink($file);
        }
    }

    /**
     * Remove all expired triplets of all users.
     * @param int $expiryTime Timestamp, all tokens before this time will be deleted
     * @return void
     */
    public function cleanExpiredTokens(int $expiryTime): void
    {
        foreach (glob($this->path . DIRECTORY_SEPARATOR . "*" . $this->suffix) as $file) {
            if (filemtime($file) < $expiryTime) {
                unlink($file);
            }
        }
    }

    /**
     * @param mixed $credential
     * @param string $persistentToken
     * @return string
     */
    protected function getFilename(mixed $credential, string $persistentToken): string
    {
        return $this->path . DIRECTORY_SEPARATOR . $credential . "." . $persistentToken . $this->suffix;
    }
}
