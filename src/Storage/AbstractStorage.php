<?php

/**
 * @license MIT
 */

namespace mober\Rememberme\Storage;

/**
 * This abstract class is for storing the credential/token/persistentToken triplets
 *
 * IMPORTANT SECURITY NOTICE: The storage should not store the token values in the clear.
 * Always use a secure hash function!
 */
abstract class AbstractStorage
{
    public const TRIPLET_FOUND = 1;
    public const TRIPLET_NOT_FOUND = 0;
    public const TRIPLET_INVALID = -1;

    /**
     * @var string
     */
    protected string $hashAlgo = 'sha1';

    /**
     * Return Tri-state value constant
     *
     * @param mixed  $credential      Unique credential (user id, email address, user name)
     * @param string $token           One-Time Token
     * @param string $persistentToken Persistent Token
     *
     * @return int
     */
    abstract public function findTriplet(mixed $credential, string $token, string $persistentToken): int;

    /**
     * Store the new token for the credential and the persistent token.
     * Create a new storage entry, if the combination of credential and persistent
     * token does not exist.
     *
     * @param mixed  $credential
     * @param string $token
     * @param string $persistentToken
     * @param int $expire          Timestamp when this triplet will expire
     */
    abstract public function storeTriplet(mixed $credential, string $token, string $persistentToken, int $expire): void;

    /**
     * Replace current token after successful authentication
     * @param mixed  $credential
     * @param string $token
     * @param string $persistentToken
     * @param int $expire
     */
    abstract public function replaceTriplet(
        mixed $credential,
        string $token,
        string $persistentToken,
        int $expire
    ): void;

    /**
     * Remove one triplet of the user from the store
     *
     * @abstract
     *
     * @param mixed  $credential
     * @param string $persistentToken
     *
     * @return void
     */
    abstract public function cleanTriplet(mixed $credential, string $persistentToken): void;

    /**
     * Remove all triplets of a user, effectively logging him out on all machines
     *
     * @abstract
     *
     * @param mixed $credential
     *
     * @return void
     */
    abstract public function cleanAllTriplets(mixed $credential): void;

    /**
     * Remove all expired triplets of all users.
     *
     * @abstract
     *
     * @param int $expiryTime Timestamp, all tokens before this time will be deleted
     *
     * @return void
     */
    abstract public function cleanExpiredTokens(int $expiryTime): void;

    /**
     * @return string
     */
    public function getHashAlgo(): string
    {
        return $this->hashAlgo;
    }

    /**
     * @param string $hashAlgo
     */
    public function setHashAlgo(string $hashAlgo): void
    {
        if (!in_array($hashAlgo, hash_hmac_algos())) {
            throw new \InvalidArgumentException("Hash algorithm \"{$hashAlgo}\" is not supported.");
        }
        $this->hashAlgo = $hashAlgo;
    }

    /**
     * @param string $value
     *
     * @return string
     */
    protected function hash(string $value): string
    {
        return hash($this->hashAlgo, $value);
    }
}
