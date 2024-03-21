<?php

/**
 * @license MIT
 */

declare(strict_types=1);

namespace mober\Rememberme\Storage;

use Closure;
use PDO;
use PDOException;
use SensitiveParameter;

/**
 * Store login tokens in database with PDO class
 * @author birke
 */
class PDOStorage extends AbstractDBStorage
{
    /**
     * @var PDO
     * @psalm-suppress PropertyNotSetInConstructor
     */
    protected PDO $connection;

    /**
     * @var Closure|null
     */
    protected ?Closure $credentialVerifier = null;

    /**
     * @param mixed  $credential
     * @param string $token
     * @param string $persistentToken
     * @return int
     */
    public function findTriplet(
        mixed $credential,
        #[SensitiveParameter] string $token,
        #[SensitiveParameter] string $persistentToken,
    ): int {
        if (!is_null($this->credentialVerifier) && ($this->credentialVerifier)($credential) === false) {
            return self::TRIPLET_NOT_FOUND;
        }

        $sql = "SELECT $this->tokenColumn as token FROM {$this->tableName} WHERE {$this->credentialColumn} = ? " .
            "AND {$this->persistentTokenColumn} = ? AND {$this->expiresColumn} > ? LIMIT 1";

        $query = $this->connection->prepare($sql);
        $query->execute([$credential, $this->hash($persistentToken), date("Y-m-d H:i:s")]);

        $result = $query->fetchColumn();

        if (!$result) {
            return self::TRIPLET_NOT_FOUND;
        }

        if (hash_equals($result, $this->hash($token))) {
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
    ): void {
        $sql = "INSERT INTO {$this->tableName}({$this->credentialColumn}, " .
            "{$this->tokenColumn}, {$this->persistentTokenColumn}, " .
            "{$this->expiresColumn}) VALUES(?, ?, ?, ?)";

        $query = $this->connection->prepare($sql);
        $query->execute(
            [$credential, $this->hash($token), $this->hash($persistentToken), date("Y-m-d H:i:s", $expire)],
        );
    }

    /**
     * @param mixed $credential
     * @param string $persistentToken
     */
    public function cleanTriplet(mixed $credential, #[SensitiveParameter] string $persistentToken): void
    {
        $sql = "DELETE FROM {$this->tableName} WHERE {$this->credentialColumn} = ? " .
            "AND {$this->persistentTokenColumn} = ?";

        $query = $this->connection->prepare($sql);
        $query->execute([$credential, $this->hash($persistentToken)]);
    }

    /**
     * Replace current token after successful authentication
     * @param mixed $credential
     * @param string $token
     * @param string $persistentToken
     * @param int $expire
     * @throws PDOException
     */
    public function replaceTriplet(
        mixed $credential,
        #[SensitiveParameter] string $token,
        #[SensitiveParameter] string $persistentToken,
        int $expire,
    ): void {
        try {
            $this->connection->beginTransaction();
            $this->cleanTriplet($credential, $persistentToken);
            $this->storeTriplet($credential, $token, $persistentToken, $expire);
            $this->connection->commit();
        } catch (PDOException $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    /**
     * @param mixed $credential
     */
    public function cleanAllTriplets(mixed $credential): void
    {
        $sql = "DELETE FROM {$this->tableName} WHERE {$this->credentialColumn} = ? ";

        $query = $this->connection->prepare($sql);
        $query->execute([$credential]);
    }

    /**
     * Remove all expired triplets of all users.
     * @param int $expiryTime Timestamp, all tokens before this time will be deleted
     * @return void
     */
    public function cleanExpiredTokens(int $expiryTime): void
    {
        $sql = "DELETE FROM {$this->tableName} WHERE {$this->expiresColumn} < ? ";

        $query = $this->connection->prepare($sql);
        $query->execute([date("Y-m-d H:i:s", $expiryTime)]);
    }


    /**
     * @return PDO
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }

    /**
     * @param PDO $connection
     */
    public function setConnection(PDO $connection): void
    {
        $this->connection = $connection;
    }

    /**
     * @return Closure|null
     */
    public function getCredentialVerifier(): ?Closure
    {
        return $this->credentialVerifier;
    }

    /**
     * @param Closure|null $credentialVerifier
     */
    public function setCredentialVerifier(?Closure $credentialVerifier): void
    {
        $this->credentialVerifier = $credentialVerifier;
    }
}
