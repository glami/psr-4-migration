#!/usr/bin/php
<?php declare(strict_types=1);

use Nette\Loaders\RobotLoader;
use Nette\Utils\FileSystem as NetteFileSystem;
use Nette\Utils\Finder;

require __DIR__ . '/../../vendor/autoload.php';

// Delete RobotLoader's cache because of next runs
$tempDir = __DIR__ . '/../../temp/cache/PSR_4';
\Nette\Utils\FileSystem::delete($tempDir);

$robotLoader = new RobotLoader();
$robotLoader->addDirectory(__DIR__ . '/../../app/model')
    ->setTempDirectory($tempDir)
    ->register();

// 1. Check for multiple classes in one file
$classes = $robotLoader->getIndexedClasses();
$multipleClassesInFiles = array_intersect($classes, array_unique(array_diff_key($classes, array_unique($classes))));

$uniqueFiles = array_unique(array_values($multipleClassesInFiles));

foreach ($uniqueFiles as $absoluteFilePath) {
    $file = sprintf('/project/app/%s', \Klarka\Utils\TypeSafeNetteStrings::after($absoluteFilePath, 'app/'));

    $cmd = "docker run -v $(pwd):/project rector-glami process --autoload-file /project/vendor/autoload.php --config /project/config/rector/psr-4-classes-split.yaml --debug $file";
    echo "$cmd\n";
    echo shell_exec($cmd);
    echo "\n";
    echo shell_exec('composer dump-autoload');
    echo "\n";
}
