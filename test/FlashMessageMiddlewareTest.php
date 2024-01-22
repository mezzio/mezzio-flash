<?php

declare(strict_types=1);

namespace MezzioTest\Flash;

use Laminas\Diactoros\ServerRequest;
use Mezzio\Flash\Exception;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Session\Session;
use Mezzio\Session\SessionMiddleware;
use MezzioTest\Flash\TestAsset\FlashMessages;
use MezzioTest\Flash\TestAsset\TestHandler;
use PHPUnit\Framework\TestCase;
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
        $middleware = new FlashMessageMiddleware();

        $this->expectException(Exception\MissingSessionException::class);
        $this->expectExceptionMessage(FlashMessageMiddleware::class);

        $middleware->process(new ServerRequest(), new TestHandler());
    }

    public function testProcessUsesConfiguredClassAndSessionKeyAndAttributeKeyToCreateFlashMessagesAndPassToHandler(): void // phpcs:ignore
    {
        $session = new Session([]);
        $request = (new ServerRequest())->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $session);
        $handler = new TestHandler();

        $middleware = new FlashMessageMiddleware(
            FlashMessages::class,
            'non-standard-flash-next',
            'non-standard-flash-attr'
        );

        $response = $middleware->process($request, $handler);

        self::assertTrue($handler->requestWasReceived());
        self::assertSame($handler->response, $response);
        self::assertNotSame($request, $handler->receivedRequest());
        $flash = $handler->receivedRequest()->getAttribute('non-standard-flash-attr');
        self::assertInstanceOf(FlashMessages::class, $flash);
        self::assertSame('non-standard-flash-next', $flash->sessionKey);
        self::assertSame($session, $flash->session);
    }
}
