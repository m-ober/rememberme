<?php

/**
 * @license MIT
 */

namespace mober\Rememberme;

/**
 * Represents the current state of the "Remember me" login.
 */
class LoginResult
{
    /**
     * @param bool  $cookieExists
     * @param bool  $tripleWasFound
     * @param bool  $tripleWasValid
     * @param mixed $credential
     */
    private function __construct(
        private bool $cookieExists = false,
        private bool $tripleWasFound = false,
        private bool $tripleWasValid = false,
        private mixed $credential = null,
    ) {
    }

    /**
     * Create new successful result with credentials
     *
     * @param mixed $credential
     *
     * @return LoginResult
     */
    public static function newSuccessResult(mixed $credential): LoginResult
    {
        return new self(true, true, true, $credential);
    }

    /**
     * Create new result that indicates that the tokens might have been manipulated
     *
     * @return LoginResult
     */
    public static function newManipulationResult(): LoginResult
    {
        return new self(true, true, false);
    }

    /**
     * Create new result that indicates the tokens have expired
     *
     * @return LoginResult
     */
    public static function newExpiredResult(): LoginResult
    {
        return new self(true, false, false);
    }

    /**
     * @return LoginResult
     */
    public static function newNoCookieResult(): LoginResult
    {
        return new self(false);
    }

    /**
     * @return bool
     */
    public function cookieExists(): bool
    {
        return $this->cookieExists;
    }

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->cookieExists && $this->tripleWasFound && $this->tripleWasValid;
    }

    /**
     * @return bool
     */
    public function hasPossibleManipulation(): bool
    {
        return $this->cookieExists && $this->tripleWasFound && !$this->tripleWasValid;
    }

    /**
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->cookieExists && !$this->tripleWasFound;
    }

    /**
     * @return mixed|null
     */
    public function getCredential(): mixed
    {
        return $this->credential;
    }
}
