<?php

declare(strict_types=1);

namespace OCA\Diary\AppInfo;

use OCA\Diary\Listener\UserDeletedListener;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\User\Events\UserDeletedEvent;

class Application extends App implements IBootstrap
{
    public const string APP_ID = 'diary';

    public function __construct(array $urlParams = [])
    {
        parent::__construct(self::APP_ID, $urlParams);
    }

    public function register(IRegistrationContext $context): void
    {
        // Load the third party libraries (dompdf, commonmark, libmergepdf)
        $autoload = __DIR__.'/../../vendor/autoload.php';
        if (is_file($autoload)) {
            include_once $autoload;
        }
        $context->registerEventListener(UserDeletedEvent::class, UserDeletedListener::class);
    }

    public function boot(IBootContext $context): void
    {
    }
}
