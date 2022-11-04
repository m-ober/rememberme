<?php

/**
 * @license MIT
 */

declare(strict_types=1);

namespace mober\Rememberme\Storage;

/**
 * This abstract class contains properties with getters and setters for all
 * database storage classes
 * @author Gabriel Birke
 */
abstract class AbstractDBStorage extends AbstractStorage
{
    /**
     * @var string
     */
    protected string $tableName = "";

    /**
     * @var string
     */
    protected string $credentialColumn = "";

    /**
     * @var string
     */
    protected string $tokenColumn = "";

    /**
     * @var string
     */
    protected string $persistentTokenColumn = "";

    /**
     * @var string
     */
    protected string $expiresColumn = "";

    /**
     * @param array $options
     */
    public function __construct(array $options)
    {
        foreach ($options as $prop => $value) {
            $setter = "set" . ucfirst($prop);
            if (method_exists($this, $setter)) {
                $this->$setter($value);
            } else {
                trigger_error("Unknown option: $prop", E_USER_WARNING);
            }
        }
    }

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * @param string $tableName
     * @return $this
     */
    public function setTableName(string $tableName): static
    {
        $this->tableName = $tableName;

        return $this;
    }

    /**
     * @return string
     */
    public function getCredentialColumn(): string
    {
        return $this->credentialColumn;
    }

    /**
     * @param string $credentialColumn
     * @return $this
     */
    public function setCredentialColumn(string $credentialColumn): static
    {
        $this->credentialColumn = $credentialColumn;

        return $this;
    }

    /**
     * @return string
     */
    public function getTokenColumn(): string
    {
        return $this->tokenColumn;
    }

    /**
     * @param string $tokenColumn
     * @return $this
     */
    public function setTokenColumn(string $tokenColumn): static
    {
        $this->tokenColumn = $tokenColumn;

        return $this;
    }

    /**
     * @return string
     */
    public function getPersistentTokenColumn(): string
    {
        return $this->persistentTokenColumn;
    }

    /**
     * @param string $persistentTokenColumn
     * @return $this
     */
    public function setPersistentTokenColumn(string $persistentTokenColumn): static
    {
        $this->persistentTokenColumn = $persistentTokenColumn;

        return $this;
    }

    /**
     * @return string
     */
    public function getExpiresColumn(): string
    {
        return $this->expiresColumn;
    }

    /**
     * @param string $expiresColumn
     * @return $this
     */
    public function setExpiresColumn(string $expiresColumn): static
    {
        $this->expiresColumn = $expiresColumn;

        return $this;
    }
}
