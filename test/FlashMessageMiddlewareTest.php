<?php

declare(strict_types=1);

namespace MezzioTest\Flash;

use Mezzio\Flash\Exception;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Flash\FlashMessagesInterface;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use stdClass;

class FlashMessageMiddlewareTest extends TestCase
{
    public function testConstructorRaisesExceptionIfFlashMessagesClassIsNotAClass(): void
    {
        $this->expectException(Exception\InvalidFlashMessagesImplementationException::class);
        $this->expectExceptionMessage('not-a-class');
        new FlashMessageMiddleware('not-a-class');
    }

    public function testConstructorRaisesExceptionIfFlashMessagesClassDoesNotImplementCorrectInterface(): void
    {
        $this->expectException(Exception\InvalidFlashMessagesImplementationException::class);
        $this->expectExceptionMessage('stdClass');
        new FlashMessageMiddleware(stdClass::class);
    }

    public function testProcessRaisesExceptionIfRequestSessionAttributeDoesNotReturnSessionInterface(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->expects(self::once())
            ->method('getAttribute')
            ->with(SessionMiddleware::SESSION_ATTRIBUTE, false)
            ->willReturn(false);
        $request
            ->expects(self::never())
            ->method('withAttribute')
            ->with(
                FlashMessageMiddleware::FLASH_ATTRIBUTE,
                self::isInstanceOf(FlashMessagesInterface::class)
            );

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects(self::never())
            ->method('handle')
            ->with(self::isInstanceOf(ServerRequestInterface::class));

        $middleware = new FlashMessageMiddleware();

        $this->expectException(Exception\MissingSessionException::class);
        $this->expectExceptionMessage(FlashMessageMiddleware::class);

        $middleware->process($request, $handler);
    }

    // @codingStandardsIgnoreStart
    public function testProcessUsesConfiguredClassAndSessionKeyAndAttributeKeyToCreateFlashMessagesAndPassToHandler(): void
    {
        // @codingStandardsIgnoreEnd
        $session = $this->createMock(SessionInterface::class);

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->expects(self::once())
            ->method('getAttribute')
            ->with(SessionMiddleware::SESSION_ATTRIBUTE, false)
            ->willReturn($session);
        $request
            ->expects(self::once())
            ->method('withAttribute')
            ->with(
                'non-standard-flash-attr',
                self::callback(function (TestAsset\FlashMessages $flash) use ($session): bool {
                    self::assertSame($session, $flash->session);
                    self::assertSame('non-standard-flash-next', $flash->sessionKey);
                    return true;
                })
            )->will(self::returnSelf());

        $response = $this->createMock(ResponseInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler
            ->expects(self::once())
            ->method('handle')
            ->with($request)
            ->willReturn($response);

        $middleware = new FlashMessageMiddleware(
            TestAsset\FlashMessages::class,
            'non-standard-flash-next',
            'non-standard-flash-attr'
        );

        self::assertSame(
            $response,
            $middleware->process($request, $handler)
        );
    }
}
