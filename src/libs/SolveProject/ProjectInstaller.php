<?php
/*
 * This file is a part of Solve framework.
 *
 * @author Alexandr Viniychuk <alexandr.viniychuk@icloud.com>
 * @copyright 2009-2014, Alexandr Viniychuk
 * created: 11/5/14 6:09 PM
 */

namespace SolveProject;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Script\CommandEvent;
use Solve\Utils\FSService;

class ProjectInstaller {

    /**
     * @var IOInterface
     */
    protected static $io;

    /**
     * @var FSService
     */
    protected static $fs;

    /**
     * @var Composer
     */
    protected static $composer;

    /**
     * @var CommandEvent
     */
    protected static $event;

    protected static $_packagesAwareOf = array(
        'solve/admin' => 'Solve\\AdminPackage\\Installer'
    );

    public static function configureWithDependencies(CommandEvent $event) {
        self::$event    = $event;
        self::$io       = $event->getIO();
        self::$composer = $event->getComposer();
        self::$fs       = new FSService();
        if (is_dir(getcwd() . '/app') && is_file(getcwd() . '/config/project.yml')) {
            self::checkAwarePackages();
            return true;
        }

        if (!self::$io->askConfirmation('Would you like to setup project?(Y/n)')) {
            self::$io->write('Exiting...');
            return false;
        }
        self::createStructure();
        self::generateProjectConfig();
        self::generateDatabaseConfig();
        self::geenrateFrontendApp();
    }

    protected static function checkAwarePackages() {
        $requires = self::$composer->getPackage()->getRequires();
        foreach (self::$_packagesAwareOf as $packageName => $handlerClass) {
            if (!empty($requires[$packageName])) {
                call_user_func(array($handlerClass, "onPostPackageInstall"), self::$event);
            }
        }
    }

    protected static function createStructure() {
        self::$io->write('Creating directory structure...', false);

        $root      = getcwd();
        $structure = array(
            $root,
            $root . '/app/Frontend/Controllers',
            $root . '/app/Frontend/Views/index',
            $root . '/config',
            $root . '/src/classes',
            $root . '/src/db',
            $root . '/tmp/cache',
            $root . '/tmp/log',
            $root . '/tmp/templates',
            $root . '/web/css',
            $root . '/web/js',
        );
        self::$fs->makeWritable($structure);
        self::$io->write('done');
    }

    protected static function generateProjectConfig() {
        if (!self::$io->askConfirmation('Would you like to generate project config?(Y/n)')) {
            return false;
        }

        $content     = <<<EOF
applications:
  frontend: ~
defaultApplication: frontend
name: __NAME__
devKey: __DEVKEY__
EOF;
        $projectName = null;
        while (!($projectName = self::$io->ask('Enter project name:'))) {
        }
        $devKey  = $projectName . '_' . substr(md5(time()), 0, 10);
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
            'name' => array('DB name'),
            'user' => array('DB user', 'root'),
            'pass' => array('DB password', 'root'),
            'host' => array('DB host', '127.0.0.1'),
        ));
        file_put_contents(getcwd() . '/config/database.yml', self::getContentWithParams($content, $replace));
        self::$io->write('Database config created.');
    }

    protected static function geenrateFrontendApp() {
        self::$io->write('Generating Frontend app...', false);
        $appConfig = <<<CONFIG
routes:
  default:
    pattern: '/'

dependencies:
CONFIG;
        file_put_contents(getcwd() . '/app/Frontend/config.yml', $appConfig);


        $indexController = <<<EOF
<?php
/*
 * This file is a part of Solve framework.
 */

namespace Frontend\Controllers;

use Solve\Controller\BaseController;

class IndexController extends BaseController {

    public function defaultAction() {
    }

}
EOF;
        file_put_contents(getcwd() . '/app/Frontend/Controllers/IndexController.php', $indexController);

        $layoutView = <<<EOF
Layout content<br/>
and<br/>
inner template content:<br/>
{{ innerTemplateContent }}
EOF;
        file_put_contents(getcwd() . '/app/Frontend/Views/_layout.slot', $layoutView);
        $indexView = <<<EOF
INDEX VIEW CONTENT
EOF;
        file_put_contents(getcwd() . '/app/Frontend/Views/index/default.slot', $indexView);


        self::$io->write('done.');
    }

    protected static function askParameters($params) {
        $result = array();
        foreach ($params as $name => $info) {
            if (!is_array($info)) {
                $params[$name] = $info;
                continue;
            }
            $result[$name] = null;
            $question      = $info[0] . (array_key_exists(1, $info) ? '(' . $info[1] . ')' : '') . ':';
            while (!($result[$name] = self::$io->ask($question, array_key_exists(1, $info) ? $info[1] : null))) ;
        }
        return $result;
    }

    protected static function getContentWithParams($content, $params) {
        $keys = array();
        foreach (array_keys($params) as $key) $keys[] = '__' . $key . '__';
        return str_replace($keys, $params, $content);
    }

}