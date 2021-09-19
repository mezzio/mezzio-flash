<?php

declare(strict_types=1);

namespace Mezzio\Flash;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

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
