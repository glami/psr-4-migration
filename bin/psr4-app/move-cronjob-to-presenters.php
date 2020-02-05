#!/usr/bin/php
<?php declare(strict_types=1);

use Nette\Utils\FileSystem as NetteFileSystem;
use Nette\Utils\Finder;
use Nette\Utils\Strings;

require __DIR__ . '/../../vendor/autoload.php';

$cronjobFiles = Finder::findFiles('*.php')->from(__DIR__ . '/../../app/cronjobs');

foreach ($cronjobFiles as $file) {
    $content = NetteFileSystem::read($file);

    // 1. Check that file contains at least 1 class? if not, skip, because it is probably already done
    $match = Strings::match($content, '/^\w*\s*class (\w*) extends \w*/m');

    if ($match === null) {
        echo "[skip] {$file->getPathname()}\n";
        continue;
    }

    // 2. Take whole file and copy it to new home
    $newFilePath = str_replace(
        [$file->getFilename(), 'cronjobs'],
        [$match[1] . '.php', 'cronjobsPresenter'],
        $file->getPathname()
    );

    // 3. Remove all stupid things and create new file with presenter class
    $sanitize = [
        '#!/usr/bin/php',
        '$container = require_once',
        '$container->getService',
    ];

    $fileLines = file($file->getPathname());

    foreach ($fileLines as $key => $line) {
        foreach ($sanitize as $forbiddenString) {
            if (Strings::contains($line, $forbiddenString)) {
                unset($fileLines[$key]);
                continue 2;
            }
        }
    }

    NetteFileSystem::write($newFilePath, implode($fileLines));
    echo "[cronjobsPresenter] {$newFilePath}\n";


    // 4. Replace old file with require
    $relativeNewFilePath = Strings::after($newFilePath, 'cronjobsPresenter/');
    $directoriesCount = substr_count($relativeNewFilePath, '/');
    $presenterName = str_replace('Presenter', '', $match[1]);
    $fileContent = '#!/usr/bin/php
<?php declare(strict_types=1);

$container = require_once __DIR__ . \'{{EXTRA_DOTS}}/../bootstrap.cron.php\';

// ExecutedPresenter: cronjobsPresenter/{{PATH}}
// Not need to require manually, thanks to composer autoloader file is already loaded

$container->getService(\'router\')[] = new Nette\Application\Routers\CliRouter([\'action\' => \'{{PRESENTER}}:cron\']);
$container->getService(\'application\')->run();
';

    $extraDots = '';
    for ($i=0 ; $i<$directoriesCount ; $i++) {
        $extraDots .= '/..';
    }

    $fileContent = str_replace(
        ['{{EXTRA_DOTS}}', '{{PATH}}', '{{PRESENTER}}'],
        [$extraDots, $relativeNewFilePath, $presenterName],
        $fileContent
    );

    NetteFileSystem::write($file->getPathname(), $fileContent);
    echo "[cronjobs] {$file->getPathName()}\n";
}

