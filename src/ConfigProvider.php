<?php

declare(strict_types=1);

namespace Mezzio\Flash;

class ConfigProvider
{
    /** @return array<string, mixed> */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    /** @return array<string, mixed> */
    public function getDependencies(): array
    {
        return [
            // Legacy Zend Framework aliases
            'aliases'    => [
                'Zend\Expressive\Flash\FlashMessageMiddleware' => FlashMessageMiddleware::class,
            ],
            'invokables' => [
                FlashMessageMiddleware::class => FlashMessageMiddleware::class,
            ],
        ];
    }
}
