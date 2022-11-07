<?php

/**
 * @license MIT
 */

declare(strict_types=1);

namespace mober\Rememberme;

use mober\Rememberme\Cookie\CookieInterface;
use mober\Rememberme\Cookie\PHPCookie;
use mober\Rememberme\Storage\AbstractStorage;
use mober\Rememberme\Token\DefaultToken;
use mober\Rememberme\Token\TokenInterface;
use Exception;

/**
 * Authenticate via "remember me" cookie
 */
class Authenticator
{
    /**
     * @var Cookie\CookieInterface
     */
    protected Cookie\CookieInterface $cookie;

    /**
     * @var Token\TokenInterface
     */
    protected Token\TokenInterface $tokenGenerator;

    /**
     * Number of seconds in the future tokens in the storage will expire (defaults to 1 week)
     * @var int
     */
    protected int $expireTime = 604800;

    /**
     * If the login token was invalid, delete all login tokens of this user
     * @var bool
     */
    protected bool $cleanStoredTokensOnInvalidResult = true;

    /**
     * Always clean expired tokens of users when login is called.
     * Disabled by default for performance reasons, but useful for
     * hosted systems that can't run periodic scripts.
     * @var bool
     */
    protected bool $cleanExpiredTokensOnLogin = false;

    /**
     * Token rotation can be disabled to counter spurious manipulation alerts,
     * which can be caused not only by stolen cookies but also by, for example,
     * bad network connections or concurrent requests.
     * @var bool
     */
    protected bool $tokenRotationEnabled = true;

    /**
     * Additional salt to add more entropy when the tokens are stored as hashes.
     * @var string
     */
    protected string $salt = "";

    /**
     * @param AbstractStorage $storage
     * @param TokenInterface|null $tokenGenerator
     * @param CookieInterface|null $cookie
     */
    public function __construct(
        protected Storage\AbstractStorage $storage,
        TokenInterface $tokenGenerator = null,
        Cookie\CookieInterface $cookie = null,
    ) {
        if (is_null($tokenGenerator)) {
            $tokenGenerator = new DefaultToken();
        }
        if (is_null($cookie)) {
            $cookie = new PHPCookie();
        }
        $this->cookie = $cookie;
        $this->tokenGenerator = $tokenGenerator;
    }

    /**
     * Check Credentials from cookie. Returns false if login was not successful, credential string if it was successful
     * @return LoginResult
     * @throws Exception
     */
    public function login(): LoginResult
    {
        $cookieValue = $this->cookie->getValue();

        if (!$cookieValue) {
            return LoginResult::newNoCookieResult();
        }

        $triplet = Triplet::fromString($cookieValue);

        if (!$triplet->isValid()) {
            return LoginResult::newManipulationResult();
        }

        if ($this->cleanExpiredTokensOnLogin) {
            $this->storage->cleanExpiredTokens(time());
        }

        $tripletLookupResult = $this->storage->findTriplet(
            credential: $triplet->getCredential(),
            token: $triplet->getSaltedOneTimeToken($this->salt),
            persistentToken: $triplet->getSaltedPersistentToken($this->salt),
        );

        switch ($tripletLookupResult) {
            case Storage\AbstractStorage::TRIPLET_FOUND:
                $expire = time() + $this->expireTime;

                if ($this->tokenRotationEnabled) {
                    $newTriplet = new Triplet(
                        credential: $triplet->getCredential(),
                        oneTimeToken: $this->tokenGenerator->createToken(),
                        persistentToken: $triplet->getPersistentToken()
                    );
                } else {
                    $newTriplet = $triplet;
                }

                $this->storage->replaceTriplet(
                    credential: $newTriplet->getCredential(),
                    token: $newTriplet->getSaltedOneTimeToken($this->salt),
                    persistentToken: $newTriplet->getSaltedPersistentToken($this->salt),
                    expire: $expire,
                );
                $this->cookie->setValue((string) $newTriplet);

                return LoginResult::newSuccessResult($triplet->getCredential());

            case Storage\AbstractStorage::TRIPLET_INVALID:
                $this->cookie->deleteCookie();

                if ($this->cleanStoredTokensOnInvalidResult) {
                    $this->storage->cleanAllTriplets($triplet->getCredential());
                }

                return LoginResult::newManipulationResult();

            default:
                $this->cookie->deleteCookie();
                return LoginResult::newExpiredResult();
        }
    }

    /**
     * @param mixed $credential
     * @return $this
     * @throws Exception
     */
    public function createCookie(mixed $credential): static
    {
        $newToken = $this->tokenGenerator->createToken();
        $newPersistentToken = $this->tokenGenerator->createToken();

        $expire = time() + $this->expireTime;

        $this->storage->storeTriplet(
            credential: $credential,
            token: $newToken . $this->salt,
            persistentToken: $newPersistentToken . $this->salt,
            expire: $expire,
        );
        $this->cookie->setValue(implode(Triplet::SEPARATOR, [$credential, $newToken, $newPersistentToken]));

        return $this;
    }

    /**
     * Expire the rememberme cookie, unset $_COOKIE[$this->cookieName] value and
     * remove current login triplet from storage.
     * @return boolean
     */
    public function clearCookie(): bool
    {
        $triplet = Triplet::fromString($this->cookie->getValue());

        $this->cookie->deleteCookie();

        if (!$triplet->isValid()) {
            return false;
        }

        $this->storage->cleanTriplet($triplet->getCredential(), $triplet->getSaltedPersistentToken($this->salt));

        return true;
    }

    /**
     * @param CookieInterface $cookie
     * @return $this
     */
    public function setCookie(CookieInterface $cookie): static
    {
        $this->cookie = $cookie;

        return $this;
    }

    /**
     * @return CookieInterface
     */
    public function getCookie(): CookieInterface
    {
        return $this->cookie;
    }

    /**
     * @param bool $cleanStoredCookies
     * @return static
     */
    public function setCleanStoredTokensOnInvalidResult(bool $cleanStoredCookies): static
    {
        $this->cleanStoredTokensOnInvalidResult = $cleanStoredCookies;

        return $this;
    }

    /**
     * @return bool
     */
    public function getCleanStoredTokensOnInvalidResult(): bool
    {
        return $this->cleanStoredTokensOnInvalidResult;
    }

    /**
     * Return how many seconds in the future that the cookie will expire
     * @return int
     */
    public function getExpireTime(): int
    {
        return $this->expireTime;
    }

    /**
     * @param int $expireTime How many seconds in the future the cookie will expire
     *                        Default is 604800 (1 week)
     * @return static
     */
    public function setExpireTime(int $expireTime): static
    {
        $this->expireTime = $expireTime;

        return $this;
    }

    /**
     * @return string
     */
    public function getSalt(): string
    {
        return $this->salt;
    }

    /**
     * The salt is additional information that is added to the tokens to make
     * them more unique and secure. The salt is not stored in the cookie and
     * should not be saved in the storage.
     * For example, to bind a token to an IP address use $_SERVER['REMOTE_ADDR'].
     * To bind a token to the browser (user agent), use $_SERVER['HTTP_USER_AGENT].
     * You could also use a long random string that is unique to your application.
     * @param string $salt
     */
    public function setSalt(string $salt): void
    {
        $this->salt = $salt;
    }

    /**
     * @return boolean
     */
    public function isCleanExpiredTokensOnLogin(): bool
    {
        return $this->cleanExpiredTokensOnLogin;
    }

    /**
     * @param boolean $cleanExpiredTokensOnLogin
     */
    public function setCleanExpiredTokensOnLogin(bool $cleanExpiredTokensOnLogin): void
    {
        $this->cleanExpiredTokensOnLogin = $cleanExpiredTokensOnLogin;
    }

    /**
     * @return bool
     */
    public function isTokenRotationEnabled(): bool
    {
        return $this->tokenRotationEnabled;
    }

    /**
     * @param bool $tokenRotationEnabled
     */
    public function setTokenRotationEnabled(bool $tokenRotationEnabled): void
    {
        $this->tokenRotationEnabled = $tokenRotationEnabled;
    }
}
