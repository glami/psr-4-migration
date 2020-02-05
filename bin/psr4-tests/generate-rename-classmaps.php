#!/usr/bin/php
<?php declare(strict_types=1);

// RobotLoader - load classes to change
use Nette\Loaders\RobotLoader;
use Nette\Utils\Json;
use Nette\Utils\Strings;
use Symfony\Component\Yaml\Yaml;

require __DIR__ . '/../../vendor/autoload.php';

// Delete RobotLoader's cache because of next runs
$tempDir = __DIR__ . '/../../temp/cache/PSR_4_Class_Map_Generation';
\Nette\Utils\FileSystem::delete($tempDir);

$pathToProcess = __DIR__ . '/../../tests_selenium/app';

$robotLoader = new RobotLoader();
$robotLoader->addDirectory($pathToProcess)
    ->setTempDirectory($tempDir)
    ->register();

$classes = $robotLoader->getIndexedClasses();
$multipleClassesInFiles = array_intersect($classes, array_unique(array_diff_key($classes, array_unique($classes))));
$uniqueFiles = array_unique(array_values($multipleClassesInFiles));

if (count($multipleClassesInFiles) > 0) {
    throw new RuntimeException('Multiple classes in one file found, please run Rector to fix issue first.');
}

$classMap = [];

foreach ($classes as $oldClass => $filePath) {
    /** @var string $filePath */
    if (! Strings::contains($filePath, $pathToProcess)) {
        continue;
    }

    $newClass = computeClassName($filePath, $pathToProcess);

    if ($newClass === $oldClass) {
        continue;
    }

    $classMap[$oldClass] = $newClass;

    if (class_exists($newClass)) {
        throw new \LogicException(sprintf("New class name '%s' already exists.", $newClass));
    }
}

$rectorConfig = [
    'parameters' => [
        'exclude_paths' => [
            '**/template.php',
        ],
    ],
    'services' => [
        'Rector\Renaming\Rector\Class_\RenameClassRector' => [
            '$oldToNewClasses' => $classMap,
        ],
    ],
];

\Nette\Utils\FileSystem::write(__DIR__ . '/../../config/rector/rename_classmap_tests.yaml', Yaml::dump($rectorConfig, 10));

echo Json::encode($classMap, Json::PRETTY);


function computeClassName(string $classPath, string $pathToProcess): string
{
    $classPath = str_replace([$pathToProcess . '/', '.php'], '', $classPath);
    $classWithoutPrefix = str_replace('/', '\\', $classPath);

    return 'Klarka\\TestsSelenium\\App\\' . $classWithoutPrefix;
}

// Then run it:
// docker run -v $(pwd):/project rector/rector:0.6.13 process --ansi /project/tests --config /project/config/rector/rename_classmap_tests.yaml --autoload-file /project/vendor/autoload.php
// or without docker
// vendor/bin/rector process --ansi tests --config config/rector/rename_classmap_tests.yaml
