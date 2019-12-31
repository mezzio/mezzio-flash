<?php

/**
 * @see       https://github.com/mezzio/mezzio-flash for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-flash/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-flash/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\Flash;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Mezzio\Flash\Exception;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Flash\FlashMessagesInterface;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;

class FlashMessageMiddlewareTest extends TestCase
{
    public function testConstructorRaisesExceptionIfFlashMessagesClassIsNotAClass()
    {
        $this->expectException(Exception\InvalidFlashMessagesImplementationException::class);
        $this->expectExceptionMessage('not-a-class');
        $middleare = new FlashMessageMiddleware('not-a-class');
    }

    public function testConstructorRaisesExceptionIfFlashMessagesClassDoesNotImplementCorrectInterface()
    {
        $this->expectException(Exception\InvalidFlashMessagesImplementationException::class);
        $this->expectExceptionMessage('stdClass');
        $middleare = new FlashMessageMiddleware(stdClass::class);
    }

    public function testProcessRaisesExceptionIfRequestSessionAttributeDoesNotReturnSessionInterface()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE, false)->willReturn(false);
        $request->withAttribute(
            FlashMessageMiddleware::FLASH_ATTRIBUTE,
            Argument::type(FlashMessagesInterface::class)
        )->shouldNotBeCalled();

        $delegate = $this->prophesize(DelegateInterface::class);
        $delegate->process(Argument::type(ServerRequestInterface::class))->shouldNotBeCalled();

        $middleware = new FlashMessageMiddleware();

        $this->expectException(Exception\MissingSessionException::class);
        $this->expectExceptionMessage(FlashMessageMiddleware::class);

        $middleware->process($request->reveal(), $delegate->reveal());
    }

    public function testProcessUsesConfiguredClassAndSessionKeyAndAttributeKeyToCreateFlashMessagesAndPassToDelegate()
    {
        $session = $this->prophesize(SessionInterface::class)->reveal();

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE, false)->willReturn($session);
        $request->withAttribute(
            'non-standard-flash-attr',
            Argument::that(function (TestAsset\FlashMessages $flash) use ($session) {
                $this->assertSame($session, $flash->session);
                $this->assertSame('non-standard-flash-next', $flash->sessionKey);
                return $flash;
            })
        )->will([$request, 'reveal']);

        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $delegate = $this->prophesize(DelegateInterface::class);
        $delegate->process(Argument::that([$request, 'reveal']))->willReturn($response);

        $middleware = new FlashMessageMiddleware(
            TestAsset\FlashMessages::class,
            'non-standard-flash-next',
            'non-standard-flash-attr'
        );

        $this->assertSame(
            $response,
            $middleware->process($request->reveal(), $delegate->reveal())
        );
    }
}
