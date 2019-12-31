<?php

/**
 * @see       https://github.com/mezzio/mezzio-flash for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-flash/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-flash/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Flash\Exception;

use InvalidArgumentException;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Flash\FlashMessagesInterface;

use function sprintf;

class InvalidFlashMessagesImplementationException extends InvalidArgumentException implements ExceptionInterface
{
    public static function forClass(string $class) : self
    {
        return new self(sprintf(
            'Cannot use "%s" within %s; does not implement %s',
            $class,
            FlashMessageMiddleware::class,
            FlashMessagesInterface::class
        ));
    }
}
