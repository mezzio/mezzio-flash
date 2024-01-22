<?php

declare(strict_types=1);

namespace Mezzio\Flash;

use Mezzio\Session\RetrieveSession;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function is_callable;

final class FlashMessageMiddleware implements MiddlewareInterface
{
    public const FLASH_ATTRIBUTE = 'flash';

    /** @psalm-var callable(SessionInterface, string): FlashMessagesInterface */
    private $flashMessageFactory;

    public function __construct(
        string $flashMessagesClass = FlashMessages::class,
        private readonly string $sessionKey = FlashMessagesInterface::FLASH_NEXT,
        private readonly string $attributeKey = self::FLASH_ATTRIBUTE
    ) {
        $factory = [$flashMessagesClass, 'createFromSession'];
        if (! is_callable($factory)) {
            throw Exception\InvalidFlashMessagesImplementationException::forClass($flashMessagesClass);
        }

        $this->flashMessageFactory = $factory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $session = RetrieveSession::fromRequestOrNull($request);
        if (! $session instanceof SessionInterface) {
            throw Exception\MissingSessionException::forMiddleware($this);
        }

        $flashMessages = ($this->flashMessageFactory)($session, $this->sessionKey);

        return $handler->handle($request->withAttribute($this->attributeKey, $flashMessages));
    }
}
