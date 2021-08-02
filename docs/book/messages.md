# Flash Messages

Flash messages are contained within a `Mezzio\Flash\FlashMessagesInterface`
implementation. That interface is defined as follows:

```php
namespace Mezzio\Flash;

use Mezzio\Session\SessionInterface;

interface FlashMessagesInterface
{
    /**
     * Flash values scheduled for next request.
     */
    public const FLASH_NEXT = self::class . '::FLASH_NEXT';

    /**
     * Create an instance from a session container.
     *
     * Flash messages will be retrieved from and persisted to the session via
     * the `$sessionKey`.
     */
    public static function createFromSession(
        SessionInterface $session,
        string $sessionKey = self::FLASH_NEXT
    ) : FlashMessagesInterface;

    /**
     * Set a flash value with the given key.
     *
     * Flash values are accessible on the next "hop", where a hop is the
     * next time the session is accessed; you may pass an additional $hops
     * integer to allow access for more than one hop.
     *
     * @param mixed $value
     */
    public function flash(string $key, $value, int $hops = 1) : void;

    /**
     * Set a flash value with the given key, visible to the current request.
     *
     * Values set with this method are visible *only* in the current request; if 
     * you want values to be visible in subsequent requests, you may pass a 
     * positive integer as the third argument.
     *
     * @param mixed $value
     */
     public function flashNow(string $key, $value, int $hops = 0) : void;

    /**
     * Retrieve a flash value.
     *
     * Will return a value only if a flash value was set in a previous request,
     * or if `flashNow()` was called in this request with the same `$key`.
     *
     * WILL NOT return a value if set in the current request via `flash()`.
     *
     * @param mixed $default Default value to return if no flash value exists.
     * @return mixed
     */
    public function getFlash(string $key, $default = null);

    /**
     * Retrieve all flash values.
     *
     * Will return all values was set in a previous request, or if `flashNow()`
     * was called in this request.
     *
     * WILL NOT return values set in the current request via `flash()`.
     *
     * @return array
     */
    public function getFlashes() : array;

    /**
     * Clear all flash values.
     *
     * Affects the next and subsequent requests.
     */
    public function clearFlash() : void;

    /**
     * Prolongs any current flash messages for one more hop.
     */
    public function prolongFlash() : void;
}
```

A default implementation is provided in the class
`Mezzio\Flash\FlashMessages`, but you may implement the interface
yourself if you have special needs that fall outside this standard
implementation.

The instance will generally be injected into your request under the attribute
`Mezzio\Flash\FlashMessageMiddleware::FLASH_ATTRIBUTE`, which evaluates
to `flash`.

## Usage

First, pull the flash messages from the request:

```php
$flashMessages = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);

// or
$flashMessages = $request->getAttribute('flash');
```

To create a flash message for the next request:

```php
$flashMessages->flash($messageName, $messageValue);
```

To retrieve a message you previously flashed:

```php
$message = $flashMessages->getFlash($messageName);
```

To retrieve all messages you previously flashed:

```php
$messages = $flashMessages->getFlashes();
```

## Hops

Sometimes you may want a flash message to persist for longer than a single
request. As an example, with a multi-page form, you may want to store messages
until all pages have been filled.

mezzio-flash allows you to specify _hops_, indicating how many requests
the flash message will persist for. The default value is `1`, indicating a
single hop. This value is provided when you call `flash()` as an optional third
argument.

The `$hops` value passed to `flash()` must be greater than zero. Passing a value
less than one will result in `flash()` throwing an exception of type
`Mezzio\Flash\Exception\InvalidHopsValueException`.

To have a message persist for three "hops", you might call `flash()` as follows:

```php
$flashMessages->flash($messageName, $messageValue, 3);
```

Sometimes you may want to ensure all messages persist for one more hop. To do
that:

```php
$flashMessages->prolongFlash();
```

If you want to clear all flash methods, no matter the number of hops:

```php
$flashMessages->clearFlash();
```

Note, however, that this clears them for the _next request_, not the current
one.

## Accessing messages in the current request

When you create a flash message, it is available _in the next request_, but not
the _current request_. If you want access to it in the current request, use the
`flashNow()` method instead of `flash()`:

```php
$flashMessages->flashNow($messageName, $messageValue);
```

By default, messages set via `flashNow()` are visible _only_ in the current
request. If you want your message to visible on subsequent requests, you may
pass a positive integer `$hops` as the third argument:

```php
// Message will be visible both in the current request, and the next.
$flashMessages->flashNow($messageName, $messageValue, 1);
```

The above is equivalent to calling both `flash()` and `flashNow()` with the same
name and value arguments:

```php
// Message will be visible both in the current request, and the next.
$flashMessages->flashNow($messageName, $messageValue);
$flashMessages->flash($messageName, $messageValue);
```
