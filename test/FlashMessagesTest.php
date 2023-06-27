<?php

declare(strict_types=1);

namespace MezzioTest\Flash;

use Mezzio\Flash\Exception\InvalidHopsValueException;
use Mezzio\Flash\FlashMessages;
use Mezzio\Flash\FlashMessagesInterface;
use Mezzio\Session\SessionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use function in_array;

class FlashMessagesTest extends TestCase
{
    private SessionInterface&MockObject $session;

    public function setUp(): void
    {
        $this->session = $this->createMock(SessionInterface::class);
    }

    public function testCreationAggregatesNothingIfNoMessagesExistUnderSpecifiedSessionKey(): void
    {
        $this->session
            ->expects(self::once())
            ->method('has')
            ->with(FlashMessagesInterface::FLASH_NEXT)
            ->willReturn(false);
        $this->session
            ->expects(self::never())
            ->method('get')
            ->with(FlashMessagesInterface::FLASH_NEXT);

        $flash = FlashMessages::createFromSession($this->session);
        self::assertInstanceOf(FlashMessages::class, $flash);
        self::assertSame([], $flash->getFlashes());
    }

    public function testCreationAggregatesItemsMarkedNextAndRemovesThemFromSession(): void
    {
        $messages = [
            'test'   => [
                'hops'  => 1,
                'value' => 'value1',
            ],
            'test-2' => [
                'hops'  => 1,
                'value' => 'value2',
            ],
        ];

        $this->session
            ->expects(self::once())
            ->method('has')
            ->with(FlashMessagesInterface::FLASH_NEXT)
            ->willReturn(true);
        $this->session
            ->expects(self::once())
            ->method('get')
            ->with(FlashMessagesInterface::FLASH_NEXT)
            ->willReturn($messages);
        $this->session
            ->expects(self::once())
            ->method('unset')
            ->with(FlashMessagesInterface::FLASH_NEXT);

        $flash = FlashMessages::createFromSession($this->session);
        self::assertInstanceOf(FlashMessages::class, $flash);

        self::assertSame('value1', $flash->getFlash('test'));
        self::assertSame('value2', $flash->getFlash('test-2'));
        self::assertSame(['test' => 'value1', 'test-2' => 'value2'], $flash->getFlashes());
    }

    public function testCreationAggregatesPersistsItemsWithMultipleHopsInSessionWithDecrementedHops(): void
    {
        $messages                           = [
            'test'   => [
                'hops'  => 3,
                'value' => 'value1',
            ],
            'test-2' => [
                'hops'  => 2,
                'value' => 'value2',
            ],
        ];
        $messagesExpected                   = $messages;
        $messagesExpected['test']['hops']   = 2;
        $messagesExpected['test-2']['hops'] = 1;

        $this->session
            ->expects(self::once())
            ->method('has')
            ->with(FlashMessagesInterface::FLASH_NEXT)
            ->willReturn(true);
        $this->session
            ->expects(self::once())
            ->method('get')
            ->with(FlashMessagesInterface::FLASH_NEXT)
            ->willReturn($messages);
        $this->session
            ->expects(self::once())
            ->method('set')
            ->with(
                FlashMessagesInterface::FLASH_NEXT,
                $messagesExpected
            );

        $flash = FlashMessages::createFromSession($this->session);
        self::assertInstanceOf(FlashMessages::class, $flash);

        self::assertSame('value1', $flash->getFlash('test'));
        self::assertSame('value2', $flash->getFlash('test-2'));
        self::assertSame(['test' => 'value1', 'test-2' => 'value2'], $flash->getFlashes());
    }

    public function testFlashingAValueMakesItAvailableInNextSessionButNotFlashMessages(): void
    {
        $this->session
            ->expects(self::once())
            ->method('has')
            ->with(FlashMessagesInterface::FLASH_NEXT)
            ->willReturn(false);
        $this->session
            ->expects(self::once())
            ->method('get')
            ->with(FlashMessagesInterface::FLASH_NEXT, [])
            ->willReturn([]);
        $this->session
            ->expects(self::once())
            ->method('set')
            ->with(
                FlashMessagesInterface::FLASH_NEXT,
                [
                    'test' => [
                        'value' => 'value',
                        'hops'  => 1,
                    ],
                ]
            );

        $flash = FlashMessages::createFromSession($this->session);
        $flash->flash('test', 'value');

        self::assertNull($flash->getFlash('test'));
        self::assertSame([], $flash->getFlashes());
    }

    public function testFlashNowMakesValueAvailableBothInNextSessionAndCurrentFlashMessages(): void
    {
        $this->session
            ->expects(self::once())
            ->method('has')
            ->with(FlashMessagesInterface::FLASH_NEXT)
            ->willReturn(false);
        $this->session
            ->expects(self::once())
            ->method('get')
            ->with(FlashMessagesInterface::FLASH_NEXT, [])
            ->willReturn([]);
        $this->session
            ->expects(self::once())
            ->method('set')
            ->with(
                FlashMessagesInterface::FLASH_NEXT,
                [
                    'test' => [
                        'value' => 'value',
                        'hops'  => 1,
                    ],
                ]
            );

        $flash = FlashMessages::createFromSession($this->session);
        $flash->flashNow('test', 'value');

        self::assertSame('value', $flash->getFlash('test'));
        self::assertSame(['test' => 'value'], $flash->getFlashes());
    }

    public function testProlongFlashAddsCurrentMessagesToNextSession(): void
    {
        $messages = [
            'test'   => [
                'hops'  => 1,
                'value' => 'value1',
            ],
            'test-2' => [
                'hops'  => 1,
                'value' => 'value2',
            ],
        ];

        $this->session
            ->expects(self::once())
            ->method('has')
            ->with(FlashMessagesInterface::FLASH_NEXT)
            ->willReturn(true);
        $this->session
            ->expects(self::exactly(4))
            ->method('get')
            ->with(self::callback(static function (string $key): bool {
                self::assertSame(FlashMessagesInterface::FLASH_NEXT, $key);

                return true;
            }))
            ->willReturnOnConsecutiveCalls($messages, []);
        $this->session
            ->expects(self::once())
            ->method('unset')
            ->with(FlashMessagesInterface::FLASH_NEXT);

        $expectInSet = [
            [
                'test' => [
                    'value' => 'value1',
                    'hops'  => 1,
                ],
            ],
            [
                'test-2' => [
                    'value' => 'value2',
                    'hops'  => 1,
                ],
            ],
        ];

        $this->session
            ->expects(self::exactly(2))
            ->method('set')
            ->with(
                self::identicalTo(FlashMessagesInterface::FLASH_NEXT),
                self::callback(fn ($arg): bool => in_array($arg, $expectInSet, true)),
            );

        $flash = FlashMessages::createFromSession($this->session);
        self::assertInstanceOf(FlashMessages::class, $flash);

        self::assertSame('value1', $flash->getFlash('test'));
        self::assertSame('value2', $flash->getFlash('test-2'));
        self::assertSame(['test' => 'value1', 'test-2' => 'value2'], $flash->getFlashes());

        $flash->prolongFlash();
    }

    public function testProlongFlashDoesNotReFlashMessagesThatAlreadyHaveMoreHops(): void
    {
        $messages                           = [
            'test'   => [
                'hops'  => 3,
                'value' => 'value1',
            ],
            'test-2' => [
                'hops'  => 2,
                'value' => 'value2',
            ],
        ];
        $messagesExpected                   = $messages;
        $messagesExpected['test']['hops']   = 2;
        $messagesExpected['test-2']['hops'] = 1;

        $this->session
            ->expects(self::once())
            ->method('has')
            ->with(FlashMessagesInterface::FLASH_NEXT)
            ->willReturn(true);
        $this->session
            ->expects(self::atMost(2))
            ->method('get')
            ->with(
                self::identicalTo(FlashMessagesInterface::FLASH_NEXT),
                self::callback(fn ($arg): bool => in_array($arg, [null, []], true)),
            )
            ->willReturnOnConsecutiveCalls($messages, $messagesExpected);
        $this->session
            ->expects(self::once())
            ->method('set')
            ->with(
                FlashMessagesInterface::FLASH_NEXT,
                $messagesExpected
            );

        $flash = FlashMessages::createFromSession($this->session);
        self::assertInstanceOf(FlashMessages::class, $flash);

        self::assertSame('value1', $flash->getFlash('test'));
        self::assertSame('value2', $flash->getFlash('test-2'));
        self::assertSame(['test' => 'value1', 'test-2' => 'value2'], $flash->getFlashes());

        $flash->prolongFlash();
    }

    public function testClearFlashShouldRemoveAnyUnexpiredMessages(): void
    {
        $messages                           = [
            'test'   => [
                'hops'  => 3,
                'value' => 'value1',
            ],
            'test-2' => [
                'hops'  => 2,
                'value' => 'value2',
            ],
        ];
        $messagesExpected                   = $messages;
        $messagesExpected['test']['hops']   = 2;
        $messagesExpected['test-2']['hops'] = 1;

        $this->session
            ->expects(self::once())
            ->method('has')
            ->with(FlashMessagesInterface::FLASH_NEXT)
            ->willReturn(true);
        $this->session
            ->expects(self::once())
            ->method('get')
            ->with(FlashMessagesInterface::FLASH_NEXT)
            ->willReturn($messages);
        $this->session
            ->expects(self::once())
            ->method('set')
            ->with(
                FlashMessagesInterface::FLASH_NEXT,
                $messagesExpected
            );
        $this->session
            ->expects(self::once())
            ->method('unset')
            ->with(FlashMessagesInterface::FLASH_NEXT);

        $flash = FlashMessages::createFromSession($this->session);
        self::assertInstanceOf(FlashMessages::class, $flash);

        self::assertSame('value1', $flash->getFlash('test'));
        self::assertSame('value2', $flash->getFlash('test-2'));
        self::assertSame(['test' => 'value1', 'test-2' => 'value2'], $flash->getFlashes());
        $flash->clearFlash();
    }

    public function testCreationAggregatesThrowsExceptionIfInvalidNumberOfHops(): void
    {
        $this->expectException(InvalidHopsValueException::class);

        $this->session
            ->expects(self::once())
            ->method('has')
            ->with(FlashMessagesInterface::FLASH_NEXT)
            ->willReturn(false);
        $this->session
            ->expects(self::never())
            ->method('get')
            ->with(FlashMessagesInterface::FLASH_NEXT, [])
            ->willReturn([]);
        $this->session
            ->expects(self::never())
            ->method('set')
            ->with(
                self::anything(),
                self::anything()
            );

        $flash = FlashMessages::createFromSession($this->session);
        $flash->flash('test', 'value', 0);
    }

    public function testFlashNowAcceptsZeroHops(): void
    {
        $flash = FlashMessages::createFromSession($this->session);
        $flash->flashNow('test', 'value', 0);

        self::assertSame('value', $flash->getFlash('test'));
    }

    public function testFlashNowWithZeroHopsShouldNotSetValueToSession(): void
    {
        $this->session
            ->expects(self::never())
            ->method('set');

        $flash = FlashMessages::createFromSession($this->session);
        $flash->flashNow('test', 'value', 0);
    }
}
