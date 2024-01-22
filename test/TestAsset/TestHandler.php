<?php

declare(strict_types=1);

namespace MezzioTest\Flash\TestAsset;

use Laminas\Diactoros\Response\TextResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

final class TestHandler implements RequestHandlerInterface
{
    public ?ServerRequestInterface $request = null;
    public ResponseInterface $response;

    public function __construct(ResponseInterface|null $response = null)
    {
        $this->response = $response ?? new TextResponse('Default Response');
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->request = $request;

        return $this->response;
    }

    public function receivedRequest(): ServerRequestInterface
    {
        if (! $this->request) {
            throw new RuntimeException('No request has been received');
        }

        return $this->request;
    }

    public function requestWasReceived(): bool
    {
        return $this->request !== null;
    }
}
