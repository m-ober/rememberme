<?php

/**
 * @license MIT
 */

declare(strict_types=1);

namespace mober\Rememberme\Storage;

/**
 * Redis-Based Storage
 * @author Michaël Thieulin
 * @psalm-suppress UndefinedClass
 */
class RedisStorage extends AbstractStorage
{
    /**
     * @psalm-suppress UndefinedClass
     * @param \Predis\Client $client
     * @param string $keyPrefix
     */
    public function __construct(protected \Predis\Client $client, protected string $keyPrefix = 'rememberme')
    {
    }

    /**
     * @param mixed $credential
     * @param string $token
     * @param string $persistentToken
     * @return int
     */
    public function findTriplet(mixed $credential, string $token, string $persistentToken): int
    {
        // Hash the tokens, because they can contain a salt and can be accessed in redis
        $persistentToken = $this->hash($persistentToken);
        $token = $this->hash($token);
        $key = $this->getKeyname($credential, $persistentToken);

        if ($this->client->exists($key) === 0) {
            return self::TRIPLET_NOT_FOUND;
        }

        $redisToken = trim($this->client->get($key));

        if (hash_equals($redisToken, $token)) {
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
    public function storeTriplet(mixed $credential, string $token, string $persistentToken, int $expire): void
    {
        // Hash the tokens, because they can contain a salt and can be accessed in redis
        $persistentToken = $this->hash($persistentToken);
        $token = $this->hash($token);
        $key = $this->getKeyname($credential, $persistentToken);
        $this->client->set($key, $token);

        if ($expire > 0) {
            $this->client->expireat($key, $expire);
        }
    }

    /**
     * Replace current token after successful authentication
     * @param mixed $credential
     * @param string $token
     * @param string $persistentToken
     * @param int $expire
     */
    public function replaceTriplet(mixed $credential, string $token, string $persistentToken, int $expire): void
    {
        $this->cleanTriplet($credential, $persistentToken);
        $this->storeTriplet($credential, $token, $persistentToken, $expire);
    }

    /**
     * @param mixed $credential
     * @param string $persistentToken
     */
    public function cleanTriplet(mixed $credential, string $persistentToken): void
    {
        $persistentToken = $this->hash($persistentToken);
        $key = $this->getKeyname($credential, $persistentToken);

        if ($this->client->exists($key) === 1) {
            $this->client->del($key);
        }
    }

    /**
     * @param mixed $credential
     */
    public function cleanAllTriplets(mixed $credential): void
    {
        foreach ($this->client->keys($this->keyPrefix . ':' . $credential . ':*') as $key) {
            $this->client->del($key);
        }
    }

    /**
     * Remove all expired triplets of all users.
     * @param int $expiryTime Timestamp, all tokens before this time will be deleted
     * @return void
     */
    public function cleanExpiredTokens(int $expiryTime): void
    {
        // Redis will automatically delete the key after the timeout has expired.
    }

    /**
     * @param string $credential
     * @param string $persistentToken
     * @return string
     */
    protected function getKeyname(string $credential, string $persistentToken): string
    {
        return $this->keyPrefix . ':' . $credential . ':' . $persistentToken;
    }
}
