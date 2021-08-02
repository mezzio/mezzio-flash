<?php

declare(strict_types=1);

namespace Mezzio\Flash;

use Mezzio\Session\SessionInterface;

use function is_array;

/**
 * Create, retrieve, and manipulate flash messages.
 *
 * Given a session container, aggregates flash messages from it. Messages are
 * both found and persisted in the `Mezzio\Session\Flash\FlashMessagesInterface::FLASH_NEXT`
 * session variable. To change the variable name under which they persist, pass
 * a key to `createFromSession()`.
 *
 * On instantiation, this class pulls and expires any existing flash messages,
 * based on the number of hops left; flash messages are then instantly available
 * via `getFlash()`.
 *
 * Calling `flash()` makes a message available on the next request in which
 * flash messages are retrieved. If you also want the message available in the
 * current request, use `flashNow()` instead.
 *
 * In order to keep messages made available to the current request for another
 * hop, use the `prolongFlash()` method.
 */
class FlashMessages implements FlashMessagesInterface
{
    /** @var array */
    private $currentMessages = [];

    /** @var SessionInterface */
    private $session;

    /** @var string */
    private $sessionKey;

    private function __construct(SessionInterface $session, string $sessionKey)
    {
        $this->session    = $session;
        $this->sessionKey = $sessionKey;
        $this->prepareMessages($session, $sessionKey);
    }

    /**
     * Create an instance from a session container.
     */
    public static function createFromSession(
        SessionInterface $session,
        string $sessionKey = FlashMessagesInterface::FLASH_NEXT
    ): FlashMessagesInterface {
        return new self($session, $sessionKey);
    }

    /**
     * Set a flash value with the given key.
     *
     * Flash values are accessible on the next "hop", where a hop is the
     * next time the session is accessed; you may pass an additional $hops
     * integer to allow access for more than one hop.
     *
     * @param mixed $value
     * @throws Exception\InvalidHopsValueException
     */
    public function flash(string $key, $value, int $hops = 1): void
    {
        if ($hops < 1) {
            throw Exception\InvalidHopsValueException::valueTooLow($key, $hops);
        }

        $messages       = $this->session->get($this->sessionKey, []);
        $messages[$key] = [
            'value' => $value,
            'hops'  => $hops,
        ];
        $this->session->set($this->sessionKey, $messages);
    }

    /**
     * Set a flash value with the given key, visible in the current request.
     *
     * Values set by flashNow are not visible on subsequent requests by default.
     * If you want want your value to be visible in subsequent requests, you may
     * set pass a positive integer as the third argument.
     *
     * @param mixed $value
     */
    public function flashNow(string $key, $value, int $hops = 0): void
    {
        $this->currentMessages[$key] = $value;
        if ($hops > 0) {
            $this->flash($key, $value, $hops);
        }
    }

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
    public function getFlash(string $key, $default = null)
    {
        return $this->currentMessages[$key] ?? $default;
    }

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
    public function getFlashes(): array
    {
        return $this->currentMessages;
    }

    /**
     * Clear all flash values.
     *
     * Affects the next and subsequent requests.
     */
    public function clearFlash(): void
    {
        $this->session->unset($this->sessionKey);
    }

    /**
     * Prolongs any current flash messages for one more hop.
     */
    public function prolongFlash(): void
    {
        $messages = $this->session->get($this->sessionKey, []);
        foreach ($this->currentMessages as $key => $value) {
            if (isset($messages[$key])) {
                continue;
            }

            $this->flash($key, $value);
        }
    }

    public function prepareMessages(SessionInterface $session, string $sessionKey): void
    {
        if (! $session->has($sessionKey)) {
            return;
        }

        $sessionMessages = $session->get($sessionKey);
        $sessionMessages = ! is_array($sessionMessages) ? [] : $sessionMessages;

        $currentMessages = [];
        foreach ($sessionMessages as $key => $data) {
            $currentMessages[$key] = $data['value'];

            if ($data['hops'] === 1) {
                unset($sessionMessages[$key]);
                continue;
            }

            $data['hops']         -= 1;
            $sessionMessages[$key] = $data;
        }

        empty($sessionMessages)
            ? $session->unset($sessionKey)
            : $session->set($sessionKey, $sessionMessages);

        $this->currentMessages = $currentMessages;
    }
}
