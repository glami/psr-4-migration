#!/usr/bin/php
<?php declare(strict_types=1);

use Nette\Utils\FileSystem as NetteFileSystem;
use Nette\Utils\Finder;
use Nette\Utils\Json;
use Nette\Utils\Strings;

require __DIR__ . '/../../vendor/autoload.php';

$files = Finder::findFiles('*.php')->from(__DIR__ . '/../../app/model');
$renames = [];

foreach ($files as $file) {
    $filePath = $file->getPathname();
    $content = NetteFileSystem::read($filePath);
    $match = Strings::match($content, '/^\w*\s*(class|interface|trait) *(\w*)/m');

    // File does not contain class
    if ($match === null) {
        continue;
    }

    $fileName = $file->getFilename();
    $className = $match[2];
    $correctFileName = $className . '.php';

    // File is already same as class name
    if ($fileName === $correctFileName) {
        continue;
    }

    $renames[$fileName] = $correctFileName;
    $newFilePath = str_replace($fileName, $correctFileName, $filePath);

    shell_exec(sprintf('git mv %s %s', $filePath, $newFilePath));
}

echo Json::encode($renames, Json::PRETTY);

