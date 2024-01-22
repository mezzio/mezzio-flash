<?php

declare(strict_types=1);

namespace MezzioTest\Flash;

use Mezzio\Flash\Exception\InvalidHopsValueException;
use Mezzio\Flash\FlashMessages;
use Mezzio\Flash\FlashMessagesInterface;
use Mezzio\Session\Session;
use Mezzio\Session\SessionInterface;
use PHPUnit\Framework\TestCase;

class FlashMessagesTest extends TestCase
{
    private SessionInterface $session;

    public function setUp(): void
    {
        $this->session = new Session([]);
    }

    public function testCreationAggregatesNothingIfNoMessagesExistUnderSpecifiedSessionKey(): void
    {
        $flash = FlashMessages::createFromSession($this->session);
        self::assertInstanceOf(FlashMessages::class, $flash);
        self::assertSame([], $flash->getFlashes());
    }

    public function testCreationAggregatesItemsMarkedNextAndRemovesThemFromSession(): void
    {
        $messages = [
            'info'    => [
                [
                    'hops'  => 1,
                    'value' => 'value1',
                ],
            ],
            'warning' => [
                [
                    'hops'  => 1,
                    'value' => 'value2',
                ],
            ],
        ];

        $this->session->set(FlashMessagesInterface::FLASH_NEXT, $messages);
        $flash = FlashMessages::createFromSession($this->session);

        self::assertSame(['value1'], $flash->getFlash('info'));
        self::assertSame(['value2'], $flash->getFlash('warning'));
        self::assertSame(['info' => ['value1'], 'warning' => ['value2']], $flash->getFlashes());

        $flash = FlashMessages::createFromSession($this->session);
        self::assertSame([], $flash->getFlash('info'));
        self::assertSame([], $flash->getFlash('warning'));
        self::assertSame([], $flash->getFlashes());
    }

    public function testCreationAggregatesItemsWithMultipleHopsInSessionWithDecrementedHops(): void
    {
        $messages = [
            'test'   => [
                [
                    'hops'  => 3,
                    'value' => 'value1',
                ],
            ],
            'test-2' => [
                [
                    'hops'  => 2,
                    'value' => 'value2',
                ],
            ],
        ];
        $this->session->set(FlashMessagesInterface::FLASH_NEXT, $messages);
        $messagesExpected                      = $messages;
        $messagesExpected['test'][0]['hops']   = 2;
        $messagesExpected['test-2'][0]['hops'] = 1;

        $flash = FlashMessages::createFromSession($this->session);
        self::assertSame(['value1'], $flash->getFlash('test'));
        self::assertSame(['value2'], $flash->getFlash('test-2'));
        self::assertSame(['test' => ['value1'], 'test-2' => ['value2']], $flash->getFlashes());

        $sessionValues = $this->session->get(FlashMessagesInterface::FLASH_NEXT);
        self::assertSame($messagesExpected, $sessionValues);
    }

    public function testFlashingAValueMakesItAvailableInNextSessionButNotFlashMessages(): void
    {
        $flash = FlashMessages::createFromSession($this->session);
        $flash->flash('test', 'value');

        self::assertSame([], $flash->getFlash('test'));
        self::assertSame([], $flash->getFlashes());
    }

    public function testFlashNowMakesValueAvailableBothInNextSessionAndCurrentFlashMessages(): void
    {
        $flash = FlashMessages::createFromSession($this->session);
        $flash->flashNow('test', 'value');

        self::assertSame(['value'], $flash->getFlash('test'));
        self::assertSame(['test' => ['value']], $flash->getFlashes());

        $flash = FlashMessages::createFromSession($this->session);

        self::assertSame(['value'], $flash->getFlash('test'));
        self::assertSame(['test' => ['value']], $flash->getFlashes());

        $flash = FlashMessages::createFromSession($this->session);

        self::assertSame([], $flash->getFlash('test'));
        self::assertSame([], $flash->getFlashes());
    }

    public function testProlongFlashAddsCurrentMessagesToNextSession(): void
    {
        $messages = [
            'test'   => [
                [
                    'hops'  => 1,
                    'value' => 'value1',
                ],
            ],
            'test-2' => [
                [
                    'hops'  => 1,
                    'value' => 'value2',
                ],
            ],
        ];
        $this->session->set(FlashMessagesInterface::FLASH_NEXT, $messages);

        $flash = FlashMessages::createFromSession($this->session);

        self::assertSame(['test' => ['value1'], 'test-2' => ['value2']], $flash->getFlashes());

        $flash->prolongFlash();

        $flash = FlashMessages::createFromSession($this->session);

        self::assertSame(['test' => ['value1'], 'test-2' => ['value2']], $flash->getFlashes());

        $flash = FlashMessages::createFromSession($this->session);

        self::assertSame([], $flash->getFlashes());
    }

    public function testProlongFlashDoesNotReFlashMessagesThatAlreadyHaveMoreHops(): void
    {
        $messages = [
            'test'   => [
                [
                    'hops'  => 3,
                    'value' => 'value1',
                ],
            ],
            'test-2' => [
                [
                    'hops'  => 2,
                    'value' => 'value2',
                ],
            ],
            'test-3' => [
                [
                    'hops'  => 1,
                    'value' => 'value3',
                ],
            ],
        ];
        $this->session->set(FlashMessagesInterface::FLASH_NEXT, $messages);

        $flash = FlashMessages::createFromSession($this->session);
        self::assertSame(['test' => ['value1'], 'test-2' => ['value2'], 'test-3' => ['value3']], $flash->getFlashes());

        $flash->prolongFlash();

        $flash = FlashMessages::createFromSession($this->session);
        self::assertSame(['test' => ['value1'], 'test-2' => ['value2'], 'test-3' => ['value3']], $flash->getFlashes());

        $flash = FlashMessages::createFromSession($this->session);
        self::assertSame(['test' => ['value1']], $flash->getFlashes());

        $flash = FlashMessages::createFromSession($this->session);
        self::assertSame([], $flash->getFlashes());
    }

    public function testClearFlashShouldRemoveAnyUnexpiredMessages(): void
    {
        $messages = [
            'test'   => [
                ['hops' => 3, 'value' => 'value1'],
            ],
            'test-2' => [
                ['hops' => 2, 'value' => 'value2'],
            ],
            'test-3' => [
                ['hops' => 1, 'value' => 'value3'],
            ],
        ];
        $this->session->set(FlashMessagesInterface::FLASH_NEXT, $messages);

        $flash = FlashMessages::createFromSession($this->session);
        self::assertSame(['test' => ['value1'], 'test-2' => ['value2'], 'test-3' => ['value3']], $flash->getFlashes());
        $flash->clearFlash();

        $flash = FlashMessages::createFromSession($this->session);
        self::assertSame([], $flash->getFlashes());
        $flash->clearFlash();
    }

    public function testCreationAggregatesThrowsExceptionIfInvalidNumberOfHops(): void
    {
        $this->expectException(InvalidHopsValueException::class);
        $flash = FlashMessages::createFromSession($this->session);
        /** @psalm-suppress InvalidArgument */
        $flash->flash('test', 'value', 0);
    }

    public function testFlashNowAcceptsZeroHops(): void
    {
        $flash = FlashMessages::createFromSession($this->session);
        $flash->flashNow('test', 'value', 0);

        self::assertSame(['value'], $flash->getFlash('test'));
    }

    public function testFlashNowWithZeroHopsShouldNotBePresentInNextSession(): void
    {
        $flash = FlashMessages::createFromSession($this->session);
        $flash->flashNow('test', 'value', 0);
        self::assertSame(['value'], $flash->getFlash('test'));
        $flash = FlashMessages::createFromSession($this->session);
        self::assertSame([], $flash->getFlash('test'));
    }
}
