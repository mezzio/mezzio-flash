<?php

declare(strict_types=1);

use Boundwize\StructArmed\Architecture;
use Boundwize\StructArmed\Preset\Preset;

return Architecture::define()
    ->withPresets(Preset::PSR4(), Preset::CODEQUALITY())
    ->layer('Exception', 'src/Exception', 'src/Exception/InvalidFlashMessagesImplementationException.php')
    ->layer('FlashMessages', [
        'src/FlashMessagesInterface.php',
        'src/FlashMessages.php',
    ])
    ->layer('Middleware', [
        'src/FlashMessageMiddleware.php',
        'src/Exception/InvalidFlashMessagesImplementationException.php',
    ])
    ->layer('ConfigProvider', 'src/ConfigProvider.php')
    ->ruleset([
        'Exception'      => [],
        'FlashMessages'  => ['Exception'],
        'Middleware'     => ['+FlashMessages'],
        'ConfigProvider' => ['Middleware'],
    ]);
