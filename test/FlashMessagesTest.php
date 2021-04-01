<?php

/**
 * @see       https://github.com/mezzio/mezzio-flash for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-flash/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-flash/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Flash;

use Mezzio\Flash\Exception\InvalidHopsValueException;
use Mezzio\Flash\FlashMessages;
use Mezzio\Flash\FlashMessagesInterface;
use Mezzio\Session\SessionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FlashMessagesTest extends TestCase
{
    /** @var SessionInterface|MockObject */
    private $session;

    public function setUp(): void
    {
        $this->session = $this->createMock(SessionInterface::class);
    }

    public function testCreationAggregatesNothingIfNoMessagesExistUnderSpecifiedSessionKey()
    {
        $this->session
            ->expects($this->once())
            ->method('has')
            ->with(FlashMessagesInterface::FLASH_NEXT)
            ->willReturn(false);
        $this->session
            ->expects($this->never())
            ->method('get')
            ->with(FlashMessagesInterface::FLASH_NEXT);

        $flash = FlashMessages::createFromSession($this->session);
        $this->assertInstanceOf(FlashMessages::class, $flash);
        $this->assertSame([], $flash->getFlashes());
    }

    public function testCreationAggregatesItemsMarkedNextAndRemovesThemFromSession()
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
            ->expects($this->once())
            ->method('has')
            ->with(FlashMessagesInterface::FLASH_NEXT)
            ->willReturn(true);
        $this->session
            ->expects($this->once())
            ->method('get')
            ->with(FlashMessagesInterface::FLASH_NEXT)
            ->willReturn($messages);
        $this->session
            ->expects($this->once())
            ->method('unset')
            ->with(FlashMessagesInterface::FLASH_NEXT);

        $flash = FlashMessages::createFromSession($this->session);
        $this->assertInstanceOf(FlashMessages::class, $flash);

        $this->assertSame('value1', $flash->getFlash('test'));
        $this->assertSame('value2', $flash->getFlash('test-2'));
        $this->assertSame(['test' => 'value1', 'test-2' => 'value2'], $flash->getFlashes());
    }

    public function testCreationAggregatesPersistsItemsWithMultipleHopsInSessionWithDecrementedHops()
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
            ->expects($this->once())
            ->method('has')
            ->with(FlashMessagesInterface::FLASH_NEXT)
            ->willReturn(true);
        $this->session
            ->expects($this->once())
            ->method('get')
            ->with(FlashMessagesInterface::FLASH_NEXT)
            ->willReturn($messages);
        $this->session
            ->expects($this->once())
            ->method('set')
            ->with(
                FlashMessagesInterface::FLASH_NEXT,
                $messagesExpected
            );

        $flash = FlashMessages::createFromSession($this->session);
        $this->assertInstanceOf(FlashMessages::class, $flash);

        $this->assertSame('value1', $flash->getFlash('test'));
        $this->assertSame('value2', $flash->getFlash('test-2'));
        $this->assertSame(['test' => 'value1', 'test-2' => 'value2'], $flash->getFlashes());
    }

    public function testFlashingAValueMakesItAvailableInNextSessionButNotFlashMessages()
    {
        $this->session
            ->expects($this->once())
            ->method('has')
            ->with(FlashMessagesInterface::FLASH_NEXT)
            ->willReturn(false);
        $this->session
            ->expects($this->once())
            ->method('get')
            ->with(FlashMessagesInterface::FLASH_NEXT, [])
            ->willReturn([]);
        $this->session
            ->expects($this->once())
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

        $this->assertNull($flash->getFlash('test'));
        $this->assertSame([], $flash->getFlashes());
    }

    public function testFlashNowMakesValueAvailableInCurrentMessagesOnlyByDefault()
    {
        $this->session
            ->expects($this->once())
            ->method('has')
            ->with(FlashMessagesInterface::FLASH_NEXT)
            ->willReturn(false);
        $this->session
            ->expects($this->never())
            ->method('get')
            ->with(FlashMessagesInterface::FLASH_NEXT, [])
            ->willReturn([]);
        $this->session
            ->expects($this->never())
            ->method('set')
            ->with(
                FlashMessagesInterface::FLASH_NEXT,
                [
                    'test' => [
                        'value' => 'value',
                        'hops'  => 0,
                    ],
                ]
            );

        $flash = FlashMessages::createFromSession($this->session);
        $flash->flashNow('test', 'value');

        $this->assertSame('value', $flash->getFlash('test'));
        $this->assertSame(['test' => 'value'], $flash->getFlashes());
    }

    public function testFlashNowCanMakeValueAvailableBothInNextSessionAndCurrentFlashMessages()
    {
        $this->session
            ->expects($this->once())
            ->method('has')
            ->with(FlashMessagesInterface::FLASH_NEXT)
            ->willReturn(false);
        $this->session
            ->expects($this->once())
            ->method('get')
            ->with(FlashMessagesInterface::FLASH_NEXT, [])
            ->willReturn([]);
        $this->session
            ->expects($this->once())
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
        $flash->flashNow('test', 'value', 1);

        $this->assertSame('value', $flash->getFlash('test'));
        $this->assertSame(['test' => 'value'], $flash->getFlashes());
    }

    public function testProlongFlashAddsCurrentMessagesToNextSession()
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
            ->expects($this->once())
            ->method('has')
            ->with(FlashMessagesInterface::FLASH_NEXT)
            ->willReturn(true);
        $this->session
            ->method('get')
            ->withConsecutive(
                [FlashMessagesInterface::FLASH_NEXT],
                [FlashMessagesInterface::FLASH_NEXT, []]
            )
            ->willReturnOnConsecutiveCalls($messages, []);
        $this->session
            ->expects($this->once())
            ->method('unset')
            ->with(FlashMessagesInterface::FLASH_NEXT);

        $this->session
            ->method('set')
            ->withConsecutive(
                [
                    FlashMessagesInterface::FLASH_NEXT,
                    [
                        'test' => [
                            'value' => 'value1',
                            'hops'  => 1,
                        ],
                    ],
                ],
                [
                    FlashMessagesInterface::FLASH_NEXT,
                    [
                        'test-2' => [
                            'value' => 'value2',
                            'hops'  => 1,
                        ],
                    ],
                ]
            );

        $flash = FlashMessages::createFromSession($this->session);
        $this->assertInstanceOf(FlashMessages::class, $flash);

        $this->assertSame('value1', $flash->getFlash('test'));
        $this->assertSame('value2', $flash->getFlash('test-2'));
        $this->assertSame(['test' => 'value1', 'test-2' => 'value2'], $flash->getFlashes());

        $flash->prolongFlash();
    }

    public function testProlongFlashDoesNotReFlashMessagesThatAlreadyHaveMoreHops()
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
            ->expects($this->once())
            ->method('has')
            ->with(FlashMessagesInterface::FLASH_NEXT)
            ->willReturn(true);
        $this->session
            ->expects($this->atMost(2))
            ->method('get')
            ->withConsecutive(
                [FlashMessagesInterface::FLASH_NEXT],
                [FlashMessagesInterface::FLASH_NEXT, []]
            )
            ->willReturnOnConsecutiveCalls($messages, $messagesExpected);
        $this->session
            ->expects($this->once())
            ->method('set')
            ->with(
                FlashMessagesInterface::FLASH_NEXT,
                $messagesExpected
            );

        $flash = FlashMessages::createFromSession($this->session);
        $this->assertInstanceOf(FlashMessages::class, $flash);

        $this->assertSame('value1', $flash->getFlash('test'));
        $this->assertSame('value2', $flash->getFlash('test-2'));
        $this->assertSame(['test' => 'value1', 'test-2' => 'value2'], $flash->getFlashes());

        $flash->prolongFlash();
    }

    public function testClearFlashShouldRemoveAnyUnexpiredMessages()
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
            ->expects($this->once())
            ->method('has')
            ->with(FlashMessagesInterface::FLASH_NEXT)
            ->willReturn(true);
        $this->session
            ->expects($this->once())
            ->method('get')
            ->with(FlashMessagesInterface::FLASH_NEXT)
            ->willReturn($messages);
        $this->session
            ->expects($this->once())
            ->method('set')
            ->with(
                FlashMessagesInterface::FLASH_NEXT,
                $messagesExpected
            );
        $this->session
            ->expects($this->once())
            ->method('unset')
            ->with(FlashMessagesInterface::FLASH_NEXT);

        $flash = FlashMessages::createFromSession($this->session);
        $this->assertInstanceOf(FlashMessages::class, $flash);

        $this->assertSame('value1', $flash->getFlash('test'));
        $this->assertSame('value2', $flash->getFlash('test-2'));
        $this->assertSame(['test' => 'value1', 'test-2' => 'value2'], $flash->getFlashes());
        $flash->clearFlash();
    }

    public function testCreationAggregatesThrowsExceptionIfInvalidNumberOfHops()
    {
        $this->expectException(InvalidHopsValueException::class);

        $this->session
            ->expects($this->once())
            ->method('has')
            ->with(FlashMessagesInterface::FLASH_NEXT)
            ->willReturn(false);
        $this->session
            ->expects($this->never())
            ->method('get')
            ->with(FlashMessagesInterface::FLASH_NEXT, [])
            ->willReturn([]);
        $this->session
            ->expects($this->never())
            ->method('set')
            ->with(
                $this->anything(),
                $this->anything()
            );

        $flash = FlashMessages::createFromSession($this->session);
        $flash->flash('test', 'value', 0);
    }

    public function testFlashNowAcceptsZeroHops()
    {
        $flash = FlashMessages::createFromSession($this->session);
        $flash->flashNow('test', 'value', 0);

        $this->assertSame('value', $flash->getFlash('test'));
    }

    public function testFlashNowWithZeroHopsShouldNotSetValueToSession()
    {
        $this->session
            ->expects($this->never())
            ->method('set');

        $flash = FlashMessages::createFromSession($this->session);
        $flash->flashNow('test', 'value', 0);
    }
}
