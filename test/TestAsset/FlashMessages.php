<?php

declare(strict_types=1);

namespace MezzioTest\Flash\TestAsset;

use Mezzio\Flash\FlashMessagesInterface;
use Mezzio\Session\SessionInterface;

class FlashMessages implements FlashMessagesInterface
{
    /** @var SessionInterface */
    public $session;
    /** @var string */
    public $sessionKey;

    public function __construct(SessionInterface $session, string $sessionKey)
    {
        $this->session    = $session;
        $this->sessionKey = $sessionKey;
    }

    public static function createFromSession(
        SessionInterface $session,
        string $sessionKey = 'this-should-not-be-used'
    ): FlashMessagesInterface {
        return new self($session, $sessionKey);
    }

    /**
     * @param mixed $value
     */
    public function flash(string $key, $value, int $hops = 1): void
    {
    }

    /**
     * @param mixed $value
     */
    public function flashNow(string $key, $value, int $hops = 1): void
    {
    }

    /**
     * @param mixed|null $default
     * @return mixed|void
     */
    public function getFlash(string $key, $default = null)
    {
    }

    public function getFlashes(): array
    {
        return [];
    }

    public function clearFlash(): void
    {
    }

    public function prolongFlash(): void
    {
    }
}
