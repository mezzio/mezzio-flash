<?php

/**
 * @see       https://github.com/mezzio/mezzio-flash for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-flash/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-flash/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Flash;

use Mezzio\Flash\FlashMessages;
use Mezzio\Flash\FlashMessagesInterface;
use Mezzio\Session\SessionInterface;
use PHPUnit\Framework\TestCase;

class FlashMessagesTest extends TestCase
{
    public function setUp()
    {
        $this->session = $this->prophesize(SessionInterface::class);
    }

    public function testCreationAggregatesNothingIfNoMessagesExistUnderSpecifiedSessionKey()
    {
        $this->session->has(FlashMessagesInterface::FLASH_NEXT)->willReturn(false);
        $this->session->get(FlashMessagesInterface::FLASH_NEXT)->shouldNotBeCalled();

        $flash = FlashMessages::createFromSession($this->session->reveal());
        $this->assertInstanceOf(FlashMessages::class, $flash);
        $this->assertAttributeSame([], 'currentMessages', $flash);
    }

    public function testCreationAggregatesItemsMarkedNextAndRemovesThemFromSession()
    {
        $messages = [
            'test' => [
                'hops'  => 1,
                'value' => 'value1',
            ],
            'test-2' => [
                'hops'  => 1,
                'value' => 'value2',
            ],
        ];

        $this->session->has(FlashMessagesInterface::FLASH_NEXT)->willReturn(true);
        $this->session->get(FlashMessagesInterface::FLASH_NEXT)->willReturn($messages);
        $this->session->unset(FlashMessagesInterface::FLASH_NEXT)->shouldBeCalled();

        $flash = FlashMessages::createFromSession($this->session->reveal());
        $this->assertInstanceOf(FlashMessages::class, $flash);

        $this->assertSame('value1', $flash->getFlash('test'));
        $this->assertSame('value2', $flash->getFlash('test-2'));
        $this->assertSame(['test' => 'value1', 'test-2' => 'value2'], $flash->getFlashes());
    }

    public function testCreationAggregatesPersistsItemsWithMultipleHopsInSessionWithDecrementedHops()
    {
        $messages = [
            'test' => [
                'hops'  => 3,
                'value' => 'value1',
            ],
            'test-2' => [
                'hops'  => 2,
                'value' => 'value2',
            ],
        ];
        $messagesExpected = $messages;
        $messagesExpected['test']['hops'] = 2;
        $messagesExpected['test-2']['hops'] = 1;

        $this->session->has(FlashMessagesInterface::FLASH_NEXT)->willReturn(true);
        $this->session->get(FlashMessagesInterface::FLASH_NEXT)->willReturn($messages);
        $this->session
            ->set(
                FlashMessagesInterface::FLASH_NEXT,
                $messagesExpected
            )
            ->shouldBeCalled();

        $flash = FlashMessages::createFromSession($this->session->reveal());
        $this->assertInstanceOf(FlashMessages::class, $flash);

        $this->assertSame('value1', $flash->getFlash('test'));
        $this->assertSame('value2', $flash->getFlash('test-2'));
        $this->assertSame(['test' => 'value1', 'test-2' => 'value2'], $flash->getFlashes());
    }

    public function testFlashingAValueMakesItAvailableInNextSessionButNotFlashMessages()
    {
        $this->session->has(FlashMessagesInterface::FLASH_NEXT)->willReturn(false);
        $this->session->get(FlashMessagesInterface::FLASH_NEXT)->shouldNotBeCalled();
        $this->session->get(FlashMessagesInterface::FLASH_NEXT, [])->willReturn([]);
        $this->session
            ->set(
                FlashMessagesInterface::FLASH_NEXT,
                [
                    'test' => [
                        'value' => 'value',
                        'hops'  => 1,
                    ],
                ]
            )
            ->shouldBeCalled();

        $flash = FlashMessages::createFromSession($this->session->reveal());
        $flash->flash('test', 'value');

        $this->assertNull($flash->getFlash('test'));
        $this->assertSame([], $flash->getFlashes());
    }

    public function testFlashNowMakesValueAvailableBothInNextSessionAndCurrentFlashMessages()
    {
        $this->session->has(FlashMessagesInterface::FLASH_NEXT)->willReturn(false);
        $this->session->get(FlashMessagesInterface::FLASH_NEXT)->shouldNotBeCalled();
        $this->session->get(FlashMessagesInterface::FLASH_NEXT, [])->willReturn([]);
        $this->session
            ->set(
                FlashMessagesInterface::FLASH_NEXT,
                [
                    'test' => [
                        'value' => 'value',
                        'hops'  => 1,
                    ],
                ]
            )
            ->shouldBeCalled();

        $flash = FlashMessages::createFromSession($this->session->reveal());
        $flash->flashNow('test', 'value');

        $this->assertSame('value', $flash->getFlash('test'));
        $this->assertSame(['test' => 'value'], $flash->getFlashes());
    }

    public function testProlongFlashAddsCurrentMessagesToNextSession()
    {
        $messages = [
            'test' => [
                'hops'  => 1,
                'value' => 'value1',
            ],
            'test-2' => [
                'hops'  => 1,
                'value' => 'value2',
            ],
        ];

        $this->session->has(FlashMessagesInterface::FLASH_NEXT)->willReturn(true);
        $this->session->get(FlashMessagesInterface::FLASH_NEXT)->willReturn($messages);
        $this->session->unset(FlashMessagesInterface::FLASH_NEXT)->shouldBeCalled();

        $this->session->get(FlashMessagesInterface::FLASH_NEXT, [])->willReturn([]);

        $this->session
            ->set(
                FlashMessagesInterface::FLASH_NEXT,
                [
                    'test' => [
                        'value' => 'value1',
                        'hops'  => 1,
                    ],
                ]
            )
            ->shouldBeCalled();
        $this->session
            ->set(
                FlashMessagesInterface::FLASH_NEXT,
                [
                    'test-2' => [
                        'value' => 'value2',
                        'hops'  => 1,
                    ],
                ]
            )
            ->shouldBeCalled();

        $flash = FlashMessages::createFromSession($this->session->reveal());
        $this->assertInstanceOf(FlashMessages::class, $flash);

        $this->assertSame('value1', $flash->getFlash('test'));
        $this->assertSame('value2', $flash->getFlash('test-2'));
        $this->assertSame(['test' => 'value1', 'test-2' => 'value2'], $flash->getFlashes());

        $flash->prolongFlash();
    }

    public function testProlongFlashDoesNotReFlashMessagesThatAlreadyHaveMoreHops()
    {
        $messages = [
            'test' => [
                'hops'  => 3,
                'value' => 'value1',
            ],
            'test-2' => [
                'hops'  => 2,
                'value' => 'value2',
            ],
        ];
        $messagesExpected = $messages;
        $messagesExpected['test']['hops'] = 2;
        $messagesExpected['test-2']['hops'] = 1;

        $this->session->has(FlashMessagesInterface::FLASH_NEXT)->willReturn(true);
        $this->session->get(FlashMessagesInterface::FLASH_NEXT)->willReturn($messages);
        $this->session
            ->set(
                FlashMessagesInterface::FLASH_NEXT,
                $messagesExpected
            )
            ->shouldBeCalledTimes(1);

        $this->session
            ->get(FlashMessagesInterface::FLASH_NEXT, [])
            ->willReturn($messagesExpected)
            ->shouldBeCalledTimes(1);

        $flash = FlashMessages::createFromSession($this->session->reveal());
        $this->assertInstanceOf(FlashMessages::class, $flash);

        $this->assertSame('value1', $flash->getFlash('test'));
        $this->assertSame('value2', $flash->getFlash('test-2'));
        $this->assertSame(['test' => 'value1', 'test-2' => 'value2'], $flash->getFlashes());

        $flash->prolongFlash();
    }

    public function testClearFlashShouldRemoveAnyUnexpiredMessages()
    {
        $messages = [
            'test' => [
                'hops'  => 3,
                'value' => 'value1',
            ],
            'test-2' => [
                'hops'  => 2,
                'value' => 'value2',
            ],
        ];
        $messagesExpected = $messages;
        $messagesExpected['test']['hops'] = 2;
        $messagesExpected['test-2']['hops'] = 1;

        $this->session->has(FlashMessagesInterface::FLASH_NEXT)->willReturn(true);
        $this->session->get(FlashMessagesInterface::FLASH_NEXT)->willReturn($messages);
        $this->session
            ->set(
                FlashMessagesInterface::FLASH_NEXT,
                $messagesExpected
            )
            ->shouldBeCalled();
        $this->session->unset(FlashMessagesInterface::FLASH_NEXT)->shouldBeCalled();

        $flash = FlashMessages::createFromSession($this->session->reveal());
        $this->assertInstanceOf(FlashMessages::class, $flash);

        $this->assertSame('value1', $flash->getFlash('test'));
        $this->assertSame('value2', $flash->getFlash('test-2'));
        $this->assertSame(['test' => 'value1', 'test-2' => 'value2'], $flash->getFlashes());
        $flash->clearFlash();
    }
}
