#!/usr/bin/php
<?php declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

if (!isset($argv[1])) {
    die('Path to log file must be provided as parameter.');
}

$logFilename = $argv[1];

if (!is_file($logFilename)) {
    die("File '$logFilename' does not exist");
}

$file = fopen($logFilename, 'rb');

$calls = [];

while(!feof($file))  {
    if ($line = fgets($file)) {
        $logEntry = \Klarka\Utils\TypeSafeNetteJson::decode(trim($line));

        if (isset($calls[$logEntry->class][$logEntry->method][$logEntry->argument][$logEntry->type])) {
            $calls[$logEntry->class][$logEntry->method][$logEntry->argument][$logEntry->type]++;
        } else {
            $calls[$logEntry->class][$logEntry->method][$logEntry->argument][$logEntry->type] = 1;
        }
    }
}

foreach ($calls as $className => $methodCalls) {
    foreach ($methodCalls as $methodName => $arguments) {
        echo "\n";
        echo "$className::$methodName()\n";
        foreach ($arguments as $argumentName => $types) {
            echo ' | - $' . $argumentName . ': ';

            if (count($types) > 1) {
                echo "\n";
                foreach ($types as $type => $count) {
                    echo '     | ' . $type .' (' . $count .'x)' . "\n";
                }
            } else {
                $type = array_keys($types)[0];
                echo $type . ' (' . $types[$type] .'x)' . "\n";
            }
        }

        echo "\n---\n";
    }
}

fclose($file);
