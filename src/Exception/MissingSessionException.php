<?php

/**
 * @see       https://github.com/mezzio/mezzio-flash for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-flash/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-flash/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Flash\Exception;

use Interop\Http\ServerMiddleware\MiddlewareInterface;
use RuntimeException;

class MissingSessionException extends RuntimeException implements ExceptionInterface
{
    public static function forMiddleware(MiddlewareInterface $middleware)
    {
        return new self(sprintf(
            'Unable to create flash messages in %s; missing session attribute',
            get_class($middleware)
        ));
    }
}
