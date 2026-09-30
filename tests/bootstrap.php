<?php

declare(strict_types=1);

if (!defined('PHPUNIT_RUN')) {
    define('PHPUNIT_RUN', 1);
}

require_once __DIR__ . '/../../../lib/base.php';

// Fix for "Autoload path not allowed: .../tests/lib/testcase.php"
// OC::$loader no longer exists since Nextcloud 32 (autoloading is done by composer only)
if (property_exists(\OC::class, 'loader')) {
    \OC::$loader->addValidRoot(\OC::$SERVERROOT . '/tests');
}

\OCP\Server::get(\OCP\App\IAppManager::class)->loadApp('diary');

OC_Hook::clear();
