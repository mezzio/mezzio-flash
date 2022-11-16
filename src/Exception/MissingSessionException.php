<?php

declare(strict_types=1);

namespace Mezzio\Flash\Exception;

use Psr\Http\Server\MiddlewareInterface;
use RuntimeException;

use function sprintf;

class MissingSessionException extends RuntimeException implements ExceptionInterface
{
    public static function forMiddleware(MiddlewareInterface $middleware): MissingSessionException
    {
        return new self(sprintf(
            'Unable to create flash messages in %s; missing session attribute',
            $middleware::class
        ));
    }
}
