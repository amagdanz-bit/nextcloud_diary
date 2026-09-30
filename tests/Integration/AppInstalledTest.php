<?php

declare(strict_types=1);

namespace OCA\Diary\Tests\Integration\Controller;

use OCP\AppFramework\App;
use PHPUnit\Framework\TestCase;


class AppInstalledTest extends TestCase
{

    private $container;

    public function setUp(): void
    {
        parent::setUp();
        $app = new App('diary');
        $this->container = $app->getContainer();
    }

    public function testAppInstalled(): void
    {
        $appManager = $this->container->get('OCP\App\IAppManager');
        $this->assertTrue($appManager->isInstalled('diary'));
    }

}
