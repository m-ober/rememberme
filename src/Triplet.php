<?php

/**
 * @license MIT
 */

declare(strict_types=1);

namespace mober\Rememberme;

/**
 * Domain object for credential, persistent and transient token
 */
class Triplet implements \Stringable
{
    /**
     * @param mixed $credential
     * @param string $oneTimeToken
     * @param string $persistentToken
     */
    public function __construct(
        private mixed $credential = '',
        private string $oneTimeToken = '',
        private string $persistentToken = '',
    ) {
    }

    /**
     * @param string $tripletString
     *
     * @return Triplet
     */
    public static function fromString(string $tripletString): Triplet
    {
        $parts = explode("|", $tripletString, 3);

        if (count($parts) < 3) {
            return new Triplet();
        }

        return new Triplet($parts[0], $parts[1], $parts[2]);
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->getCredential() !== '' && $this->getPersistentToken() !== '' && $this->getOneTimeToken() !== '';
    }

    /**
     * @return mixed
     */
    public function getCredential(): mixed
    {
        return $this->credential;
    }

    /**
     * @return string
     */
    public function getPersistentToken(): string
    {
        return $this->persistentToken;
    }

    /**
     * @return string
     */
    public function getOneTimeToken(): string
    {
        return $this->oneTimeToken;
    }

    /**
     * @param string $salt
     *
     * @return string
     */
    public function getSaltedPersistentToken(string $salt): string
    {
        return $this->getPersistentToken() . $salt;
    }

    /**
     * @param string $salt
     *
     * @return string
     */
    public function getSaltedOneTimeToken(string $salt): string
    {
        return $this->getOneTimeToken() . $salt;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return implode('|', [$this->getCredential(), $this->getOneTimeToken(), $this->getPersistentToken()]);
    }
}
