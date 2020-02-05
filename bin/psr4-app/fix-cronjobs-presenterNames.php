#!/usr/bin/php
<?php declare(strict_types=1);

use Nette\Utils\FileSystem as NetteFileSystem;
use Nette\Utils\Finder;
use Nette\Utils\Strings;

require __DIR__ . '/../../vendor/autoload.php';

$cronjobFiles = Finder::findFiles('*.php')->from(__DIR__ . '/../../app/cronjobs');

foreach ($cronjobFiles as $file) {
    /** @var SplFileInfo $file */

    $content = NetteFileSystem::read($file->getPathname());

    $match = Strings::match($content, "/['\"]action['\"]\s*=>\s*['\"]((\S*):cron)['\"]]/");

    if (!isset($match[2])) {
        throw new \Exception('Match not found for file ' . $file);
    }

    $presenterPath = Strings::after($file->getPath(), 'cronjobs');
    $presenterPath = trim($presenterPath, '/');
    $presenterAction = str_replace('/', ':', $presenterPath);
    $fullAction = 'Cron:' . ($presenterAction ? $presenterAction . ':' : '') . $match[1];
    $presenterFile = __DIR__ . '/../../app/CronjobsPresenter/' . ($presenterPath ? $presenterPath . '/' : '') . $match[2] . 'Presenter.php';

    if (! is_file($presenterFile)) {
        throw new \Exception('File does not exist ' . $presenterFile);
    }

    $newContent = str_replace($match[1], $fullAction, $content);

    NetteFileSystem::write($file, $newContent);
}

