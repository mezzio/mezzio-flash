<?php

declare(strict_types=1);

namespace MezzioTest\Flash\TestAsset;

use Mezzio\Flash\FlashMessagesInterface;
use Mezzio\Session\SessionInterface;

class FlashMessages implements FlashMessagesInterface
{
    public function __construct(
        public readonly SessionInterface $session,
        public readonly string $sessionKey,
    ) {
    }

    public static function createFromSession(
        SessionInterface $session,
        string $sessionKey = 'this-should-not-be-used'
    ): FlashMessagesInterface {
        return new self($session, $sessionKey);
    }

    /** @inheritDoc */
    public function flash(string $key, string $value, int $hops = 1): void
    {
    }

    /** @inheritDoc */
    public function flashNow(string $key, string $value, int $hops = 1): void
    {
    }

    /** @inheritDoc */
    public function getFlash(string $key, array $default = []): array
    {
        return [];
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
