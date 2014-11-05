<?php
/*
 * This file is a part of Solve framework.
 *
 * @author Alexandr Viniychuk <alexandr.viniychuk@icloud.com>
 * @copyright 2009-2014, Alexandr Viniychuk
 * created: 11/5/14 6:09 PM
 */

namespace SolveProject;

use Composer\Script\CommandEvent;
use Solve\Utils\FSService;

class Composer {
    public static function onPostRootPackageInstall(CommandEvent $event) {
        $event->getIO()->write('Creating directory structure...');
        self::createStructure();
    }

    protected static function createStructure() {
        $root = getcwd();
        $fs = new FSService();
        $structure = array(
            $root,
            $root . '/app',
            $root . '/src/classes',
            $root . '/src/db',
        );
        $fs->makeWritable($structure);
    }
}