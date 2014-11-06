<?php
/*
 * This file is a part of Solve framework.
 *
 * @author Alexandr Viniychuk <alexandr.viniychuk@icloud.com>
 * @copyright 2009-2014, Alexandr Viniychuk
 * created: 11/5/14 6:09 PM
 */

namespace SolveProject;

use Composer\IO\IOInterface;
use Composer\Script\CommandEvent;
use Solve\Utils\FSService;

class ProjectInstaller {

    /**
     * @var IOInterface
     */
    protected static $io;

    public static function onPostRootPackageInstall(CommandEvent $event) {
        self::$io = $event->getIO();
        if (!self::$io->askConfirmation('Would you like to setup project?(Y/n)')) {
            self::$io->write('Exiting...');
            return false;
        }
        self::createStructure();
        self::generateProjectConfig();
        self::generateDatabaseConfig();
    }

    protected static function createStructure() {
        self::$io->write('Creating directory structure...', false);

        $root = getcwd();
        $fs = new FSService();
        $structure = array(
            $root,
            $root . '/app/Frontend/Controllers',
            $root . '/app/Frontend/Views',
            $root . '/config',
            $root . '/src/classes',
            $root . '/src/db',
            $root . '/tmp/cache',
            $root . '/tmp/log',
            $root . '/tmp/templates',
            $root . '/web/css',
            $root . '/web/js',
        );
        $fs->makeWritable($structure);
        self::$io->write('done');
    }

    protected static function generateProjectConfig() {
        if (!self::$io->askConfirmation('Would you like to generate project config?(Y/n)')) {
            return false;
        }

        $content = <<<EOF
applications:
  frontend: ~
defaultApplication: frontend
name: __NAME__
devKey: __DEVKEY__
EOF;
        $projectName = null;
        while(!($projectName = self::$io->ask('Enter project name:'))) {}
        $devKey = $projectName . '_' . substr(md5(time()), 0, 10);
        $content = str_replace(array('__NAME__', '__DEVKEY__'), array($projectName, $devKey), $content);
        file_put_contents(getcwd() . '/config/project.yml', $content);
        self::$io->write('Project config created.');
    }

    public static function generateDatabaseConfig() {
        if (!self::$io->askConfirmation('Would you like to generate database config?(Y/n)')) {
            return false;
        }
        $content = <<<EOF
autoUpdateAll: true
profiles:
  default:
    dbtype: mysql
    charset: utf8
    collate: utf8_general_ci
    name: __name__
    user: __user__
    pass: __pass__
    host: __host__
EOF;
        $replace = self::askParameters(array(
            'name'  => array('DB name'),
            'user'  => array('DB user', 'root'),
            'pass'  => array('DB password', 'root'),
            'host'  => array('DB host', '127.0.0.1'),
        ));
        file_put_contents(getcwd() . '/config/database.yml', self::getContentWithParams($content, $replace));
        self::$io->write('Database config created.');
    }

    protected static function askParameters($params) {
        $result = array();
        foreach($params as $name=>$info) {
            if (!is_array($info)) {
                $params[$name] = $info;
                continue;
            }
            $result[$name] = null;
            $question = $info[0] . ( array_key_exists(1, $info) ? '('.$info[1].')' : '') . ':';
            while (!($result[$name] = self::$io->ask($question, array_key_exists(1, $info) ? $info[1] : null)));
        }
        return $result;
    }

    protected static function getContentWithParams($content, $params) {
        $keys = array();
        foreach(array_keys($params) as $key) $keys[] = '__' . $key . '__';
        return str_replace($keys, $params, $content);
    }
}