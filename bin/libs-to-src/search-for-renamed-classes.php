#!/usr/bin/php
<?php declare(strict_types=1);

use Nette\Utils\FileSystem as NetteFileSystem;
use Nette\Utils\Finder;
use Nette\Utils\Strings;
use Symfony\Component\Yaml\Yaml;

require __DIR__ . '/../../vendor/autoload.php';

$filesForSearch = Finder::findFiles('*')
    ->exclude('UpdateImportClassNamePresenter.php')
    ->from(__DIR__ . '/../../')
    ->exclude('vendor', '.git', '.idea', 'temp', 'reports', 'schema', 'libs', 'config/rector');

$yaml = Yaml::parseFile(__DIR__ . '/../../config/rector/rename_classmap_libs.yaml');
$classes = $yaml['services']['Rector\Rector\Class_\RenameClassRector'];

foreach ($filesForSearch as $file) {
    $filePath = $file->getPathname();
    $content = NetteFileSystem::read($filePath);

    foreach ($classes as $oldClassName => $newClassName) {
        if (Strings::contains($content, $oldClassName) && Strings::contains($oldClassName, '\\')) {
            echo "[$filePath] bad string found '$oldClassName'\n";
        }
    }
}
