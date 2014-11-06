<?php
$vendorPath = __DIR__ . '/../vendor/';

if (!is_file($vendorPath . 'autoload.php')) {
    $invalidMessage = "You have corrupted installation of Solve Framework.\n"
        . "Please, follow the instructions from http://solve-project.org/install/\n"
        . "or run \"php -f http://solve-project.org/install-script/\" to setup new instance!";
    if (!empty($_SERVER['DOCUMENT_ROOT'])) {
        $invalidMessage = nl2br($invalidMessage);
    }
    die($invalidMessage);
}

require_once $vendorPath . 'autoload.php';
use Solve\Kernel\Kernel;
Kernel::getMainInstance()->run();
