#!/usr/bin/php
<?php declare(strict_types=1);

use Nette\Loaders\RobotLoader;
use Nette\Utils\FileSystem as NetteFileSystem;
use Nette\Utils\Finder;
use Nette\Utils\Strings;

require __DIR__ . '/../vendor/autoload.php';

// Delete RobotLoader's cache because of next runs
$tempDir = __DIR__ . '/../temp/cache/PSR_4_Classes_as_string';
NetteFileSystem::delete($tempDir);

$pathsToProcess = [
    __DIR__ . '/../src',
    __DIR__ . '/../app',
    __DIR__ . '/../tests',
    __DIR__ . '/../tests_db',
    __DIR__ . '/../tests_selenium',
    __DIR__ . '/../www',
];

$robotLoader = new RobotLoader();
$robotLoader->addDirectory($pathsToProcess)
    ->setTempDirectory($tempDir)
    ->register();

$classes = array_keys($robotLoader->getIndexedClasses());

$files = Finder::findFiles('*.php')->from($pathsToProcess);

foreach ($files as $file) {
    $filePath = $file->getPathname();
    $content = NetteFileSystem::read($filePath);

    foreach ($classes as $class) {
        $pattern = '/["\']\\?' . str_replace('\\', '\\\\', $class) . '["\']/m'; // do we need to escape \ in class?
        $match = Strings::match($content, $pattern);

        if ($match !== null) {
            echo "\n[NOOK] $filePath - '$class'\n";
        }
    }

    echo '.';
}
