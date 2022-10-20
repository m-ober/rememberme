<?php

/**
 * @license MIT
 */

namespace mober\Rememberme\Storage;

use PDO;
use PDOException;

/**
 * Store login tokens in database with PDO class
 *
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
     * @param mixed $credential
     * @param string $token
     * @param string $persistentToken
     *
     * @return int
     */
    public function findTriplet(mixed $credential, string $token, string $persistentToken): int
    {
        $sql = "SELECT $this->tokenColumn as token FROM {$this->tableName} WHERE {$this->credentialColumn} = ? " .
            "AND {$this->persistentTokenColumn} = ? AND {$this->expiresColumn} > ? LIMIT 1";

        $query = $this->connection->prepare($sql);
        $query->execute(array($credential, $this->hash($persistentToken), date("Y-m-d H:i:s")));

        $result = $query->fetchColumn();

        if (!$result) {
            return self::TRIPLET_NOT_FOUND;
        }

        if ($this->hash($token) === $result) {
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
        $sql = "INSERT INTO {$this->tableName}({$this->credentialColumn}, " .
            "{$this->tokenColumn}, {$this->persistentTokenColumn}, " .
            "{$this->expiresColumn}) VALUES(?, ?, ?, ?)";

        $query = $this->connection->prepare($sql);
        $query->execute(
            array($credential, $this->hash($token), $this->hash($persistentToken), date("Y-m-d H:i:s", $expire))
        );
    }

    /**
     * @param mixed $credential
     * @param string $persistentToken
     */
    public function cleanTriplet(mixed $credential, string $persistentToken): void
    {
        $sql = "DELETE FROM {$this->tableName} WHERE {$this->credentialColumn} = ? " .
            "AND {$this->persistentTokenColumn} = ?";

        $query = $this->connection->prepare($sql);
        $query->execute(array($credential, $this->hash($persistentToken)));
    }

    /**
     * Replace current token after successful authentication
     * @param mixed $credential
     * @param string $token
     * @param string $persistentToken
     * @param int $expire
     *
     * @throws PDOException
     */
    public function replaceTriplet(mixed $credential, string $token, string $persistentToken, int $expire): void
    {
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
        $query->execute(array($credential));
    }

    /**
     * Remove all expired triplets of all users.
     *
     * @param int $expiryTime Timestamp, all tokens before this time will be deleted
     *
     * @return void
     */
    public function cleanExpiredTokens(int $expiryTime): void
    {
        $sql = "DELETE FROM {$this->tableName} WHERE {$this->expiresColumn} < ? ";

        $query = $this->connection->prepare($sql);
        $query->execute(array(date("Y-m-d H:i:s", $expiryTime)));
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
}
