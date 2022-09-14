<?php

declare(strict_types=1);

namespace Mezzio\Flash;

use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function is_callable;

class FlashMessageMiddleware implements MiddlewareInterface
{
    public const FLASH_ATTRIBUTE = 'flash';

    private string $attributeKey;

    /** @psalm-var callable(SessionInterface, string): FlashMessagesInterface */
    private $flashMessageFactory;

    private string $sessionKey;

    public function __construct(
        string $flashMessagesClass = FlashMessages::class,
        string $sessionKey = FlashMessagesInterface::FLASH_NEXT,
        string $attributeKey = self::FLASH_ATTRIBUTE
    ) {
        $factory = [$flashMessagesClass, 'createFromSession'];
        if (! is_callable($factory)) {
            throw Exception\InvalidFlashMessagesImplementationException::forClass($flashMessagesClass);
        }

        $this->flashMessageFactory = $factory;
        $this->sessionKey          = $sessionKey;
        $this->attributeKey        = $attributeKey;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE, false);
        if (! $session instanceof SessionInterface) {
            throw Exception\MissingSessionException::forMiddleware($this);
        }

        $flashMessages = ($this->flashMessageFactory)($session, $this->sessionKey);

        return $handler->handle($request->withAttribute($this->attributeKey, $flashMessages));
    }
}
