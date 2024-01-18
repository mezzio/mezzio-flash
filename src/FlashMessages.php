<?php

declare(strict_types=1);

namespace Mezzio\Flash;

use Mezzio\Session\SessionInterface;

use function array_map;
use function array_values;

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
 *
 * @psalm-type StoredMessages = array<non-empty-string, list<array{value:non-empty-string,hops:int}>>
 */
final class FlashMessages implements FlashMessagesInterface
{
    /** @var StoredMessages */
    private array $currentMessages = [];

    /** @param non-empty-string $sessionKey */
    private function __construct(private SessionInterface $session, private string $sessionKey)
    {
        $this->prepareMessages($session, $sessionKey);
    }

    /** @inheritDoc */
    public static function createFromSession(
        SessionInterface $session,
        string $sessionKey = FlashMessagesInterface::FLASH_NEXT
    ): FlashMessagesInterface {
        return new self($session, $sessionKey);
    }

    /**
     * @inheritDoc
     * @throws Exception\InvalidHopsValueException
     */
    public function flash(string $key, string $value, int $hops = 1): void
    {
        /** @psalm-suppress DocblockTypeContradiction, NoValue. Annotated as positive int, but defensive condition remains */
        if ($hops < 1) {
            throw Exception\InvalidHopsValueException::valueTooLow($key, $hops);
        }

        $messages         = $this->getStoredMessages();
        $messages[$key][] = [
            'value' => $value,
            'hops'  => $hops,
        ];
        $this->session->set($this->sessionKey, $messages);
    }

    /** @inheritDoc */
    public function flashNow(string $key, string $value, int $hops = 1): void
    {
        $this->currentMessages[$key][] = ['hops' => 0, 'value' => $value];
        if ($hops > 0) {
            $this->flash($key, $value, $hops);
        }
    }

    /** @inheritDoc */
    public function getFlash(string $key, array $default = []): array
    {
        return $this->getFlashes()[$key] ?? $default;
    }

    /** @return array<string, list<non-empty-string>> */
    public function getFlashes(): array
    {
        return array_map(static function (array $list) {
            return array_map(fn (array $data): string => $data['value'], $list);
        }, $this->currentMessages);
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
        foreach ($this->currentMessages as $key => $list) {
            foreach ($list as $index => $data) {
                if ($data['hops'] > 0) {
                    // We only want to prolong _current_ messages
                    continue;
                }

                /** @psalm-suppress PropertyTypeCoercion This is still a list */
                $this->currentMessages[$key][$index]['hops']++;
            }
        }

        $this->session->set($this->sessionKey, $this->currentMessages);
    }

    /** @param non-empty-string $sessionKey */
    private function prepareMessages(SessionInterface $session, string $sessionKey): void
    {
        if (! $session->has($sessionKey)) {
            return;
        }

        $sessionMessages = $this->getStoredMessages($sessionKey);
        foreach ($sessionMessages as $key => $list) {
            foreach ($list as $index => $data) {
                if ($data['hops'] === 0) {
                    unset($sessionMessages[$key][$index]);
                    continue;
                }

                $sessionMessages[$key][$index]['hops']--;
            }

            $sessionMessages[$key] = array_values($sessionMessages[$key]);

            if ($sessionMessages[$key] === []) {
                unset($sessionMessages[$key]);
            }
        }

        empty($sessionMessages)
            ? $session->unset($sessionKey)
            : $session->set($sessionKey, $sessionMessages);

        /** @psalm-suppress PropertyTypeCoercion $sessionMessages only contains lists */
        $this->currentMessages = $sessionMessages;
    }

    /**
     * @return StoredMessages
     */
    private function getStoredMessages(?string $sessionKey = null): array
    {
        /** @var StoredMessages|null $messages */
        $messages = $this->session->get($sessionKey ?? $this->sessionKey, []);
        return $messages ?? [];
    }
}
