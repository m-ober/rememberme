<?php

/**
 * @license MIT
 */

namespace mober\Rememberme\Cookie;

/**
 * Wrapper around setcookie function and $_COOKIE global variable
 */
class PHPCookie implements CookieInterface
{
    /**
     * PHPCookie constructor.
     * @param string $name Name of the cookie
     * @param int $expireTime Number of seconds in the future the cookie and storage will expire (defaults to 1 week)
     * @param string $path Path where the cookie is valid
     * @param string $domain Cookie domain
     * @param bool $secure
     * @param bool $httpOnly
     * @param string $sameSite 'None', 'Lax', or 'Strict'
     */
    public function __construct(
        protected string $name = "REMEMBERME",
        protected int $expireTime = 604800,
        protected string $path = "/",
        protected string $domain = "",
        protected bool $secure = false,
        protected bool $httpOnly = true,
        protected string $sameSite = "Lax",
    ) {
        $this->setSameSite($sameSite);

        if ($this->sameSite === "None" && !$this->secure) {
            trigger_error("Some browsers will reject non-secure Cookies with SameSite=None.", E_USER_NOTICE);
        }
    }

    /**
     * @inheritdoc
     *
     * @param string $value
     */
    public function setValue($value)
    {
        $expire = time() + $this->expireTime;
        $_COOKIE[$this->name] = $value;
        setcookie($this->name, $value, [
            'expires'  => $expire,
            'path'     => $this->path,
            'domain'   => $this->domain,
            'secure'   => $this->secure,
            'httponly' => $this->httpOnly,
            'samesite' => $this->sameSite,
        ]);
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    public function getValue()
    {
        return $_COOKIE[$this->name] ?? "";
    }

    /**
     * @inheritdoc
     */
    public function deleteCookie()
    {
        $expire = time() - $this->expireTime;
        unset($_COOKIE[$this->name]);
        setcookie($this->name, "", [
            'expires'  => $expire,
            'path'     => $this->path,
            'domain'   => $this->domain,
            'secure'   => $this->secure,
            'httponly' => $this->httpOnly,
            'samesite' => $this->sameSite,
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return int
     */
    public function getExpireTime()
    {
        return $this->expireTime;
    }

    /**
     * @param int $expireTime
     */
    public function setExpireTime($expireTime)
    {
        $this->expireTime = $expireTime;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * @param string $domain
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;
    }

    /**
     * @return bool
     */
    public function getSecure()
    {
        return $this->secure;
    }

    /**
     * @param bool $secure
     */
    public function setSecure($secure)
    {
        $this->secure = $secure;
    }

    /**
     * @return bool
     */
    public function getHttpOnly()
    {
        return $this->httpOnly;
    }

    /**
     * @param bool $httponly
     */
    public function setHttpOnly($httponly)
    {
        $this->httpOnly = $httponly;
    }

    /**
     * @return string
     */
    public function getSameSite()
    {
        return $this->sameSite;
    }

    /**
     * @param string $sameSite
     */
    public function setSameSite($sameSite)
    {
        $sameSite = ucfirst($sameSite);
        if (!in_array($sameSite, ["None", "Lax", "Strict"])) {
            throw new \InvalidArgumentException('SameSite must be one of "None", "Lax" or "Strict".');
        }
        $this->sameSite = $sameSite;
    }
}
