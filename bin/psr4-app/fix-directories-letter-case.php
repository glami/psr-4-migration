#!/usr/bin/php
<?php declare(strict_types=1);

use Nette\Loaders\RobotLoader;
use Nette\Utils\FileSystem as NetteFileSystem;
use Nette\Utils\Finder;

require __DIR__ . '/../../vendor/autoload.php';

$directories = Finder::findDirectories('*')
    ->exclude('config', 'templates', 'i18n', 'db_schema', 'cronjobs', 'v201810', 'bin', 'app', 'src', 'data', 'test_files')
    ->from(__DIR__ . '/../../tests_db')
    ->exclude('config', 'templates', 'i18n', 'db_schema', 'cronjobs', 'v201810', 'bin', 'app', 'src', 'data', 'test_files');

foreach ($directories as $directory) {
    /** @var \SplFileInfo $directory */

    $filename = $directory->getFilename();

    if (isFirstLetterUppercase($filename)) {
        continue;
    }

    $path = $directory->getPath();
    $originalDirectory = $directory->getPathname();
    $newDirectory = $path . '/' . ucwords($filename);
    $tempDirectory = $path . '/TEMP_' . $filename;

    echo "$originalDirectory -> $newDirectory\n";

    shell_exec(sprintf('git mv %s %s', $originalDirectory, $tempDirectory));
    shell_exec(sprintf('git mv %s %s', $tempDirectory, $newDirectory));
}

function isFirstLetterUppercase(string $string): bool
{
    $letter = \Klarka\Utils\TypeSafeNetteStrings::substring($string, 0, 1);

    return ctype_upper($letter);
}