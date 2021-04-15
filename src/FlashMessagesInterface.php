<?php

/**
 * @see       https://github.com/mezzio/mezzio-flash for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-flash/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-flash/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Flash;

use Mezzio\Session\SessionInterface;

interface FlashMessagesInterface
{
    /**
     * Flash values scheduled for next request.
     */
    public const FLASH_NEXT = self::class . '::FLASH_NEXT';

    /**
     * Create an instance from a session container.
     *
     * Flash messages will be retrieved from and persisted to the session via
     * the `$sessionKey`.
     */
    public static function createFromSession(
        SessionInterface $session,
        string $sessionKey = self::FLASH_NEXT
    ): FlashMessagesInterface;

    /**
     * Set a flash value with the given key.
     *
     * Flash values are accessible on the next "hop", where a hop is the
     * next time the session is accessed; you may pass an additional $hops
     * integer to allow access for more than one hop.
     *
     * @param mixed $value
     */
    public function flash(string $key, $value, int $hops = 1): void;

    /**
     * Set a flash value with the given key, visible to the current request.
     *
     * Values set with this method are visible *only* in the current request; if
     * you want values to be visible in subsequent requests, you may pass a
     * positive integer as the third argument.
     *
     * @param mixed $value
     */
    public function flashNow(string $key, $value, int $hops = 0): void;

    /**
     * Retrieve a flash value.
     *
     * Will return a value only if a flash value was set in a previous request,
     * or if `flashNow()` was called in this request with the same `$key`.
     *
     * WILL NOT return a value if set in the current request via `flash()`.
     *
     * @param mixed $default Default value to return if no flash value exists.
     * @return mixed
     */
    public function getFlash(string $key, $default = null);

    /**
     * Retrieve all flash values.
     *
     * Will return all values was set in a previous request, or if `flashNow()`
     * was called in this request.
     *
     * WILL NOT return values set in the current request via `flash()`.
     *
     * @return array
     */
    public function getFlashes(): array;

    /**
     * Clear all flash values.
     *
     * Affects the next and subsequent requests.
     */
    public function clearFlash(): void;

    /**
     * Prolongs any current flash messages for one more hop.
     */
    public function prolongFlash(): void;
}
