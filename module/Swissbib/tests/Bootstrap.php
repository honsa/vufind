<?php
/**
 * Bootstrap
 *
 * PHP version 5
 *
 * Copyright (C) project swissbib, University Library Basel, Switzerland
 * http://www.swissbib.org  / http://www.swissbib.ch / http://www.ub.unibas.ch
 *
 * Date: 1/2/13
 * Time: 4:09 PM
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category Swissbib_VuFind2
 * @package  SwissbibTest
 * @author   Guenter Hipler  <guenter.hipler@unibas.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://www.swissbib.org
 */
namespace SwissbibTest;

use Zend\Loader\AutoloaderFactory;
use Zend\Mvc\Service\ServiceManagerConfig;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\ArrayUtils;
use RuntimeException;

error_reporting(E_ALL | E_STRICT);
chdir(__DIR__);

/**
 * Bootstrap
 *
 * @category Swissbib_VuFind2
 * @package  SwissbibTest
 * @author   Guenter Hipler  <guenter.hipler@unibas.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org
 */
class Bootstrap
{
    /**
     * ServiceManager
     *
     * @var ServiceManager
     */
    protected static $serviceManager;

    /**
     * Config
     *
     * @var Config
     */
    protected static $config;

    /**
     * Bootstrap
     *
     * @var
     */
    protected static $bootstrap;

    /**
     * Init
     *
     * @return void
     */
    public static function init()
    {
        // Load the user-defined test configuration file, if it exists;
        // otherwise, load
        if (is_readable(__DIR__ . '/TestConfig.php')) {
            $testConfig = include __DIR__ . '/TestConfig.php';
        } else {
            $testConfig = include __DIR__ . '/TestConfig.php.dist';
        }

        $zf2ModulePaths = [];

        if (isset($testConfig['module_listener_options']['module_paths'])) {
            $modulePaths = $testConfig['module_listener_options']['module_paths'];
            foreach ($modulePaths as $modulePath) {
                if (($path = static::findParentPath($modulePath))) {
                    $zf2ModulePaths[] = $path;
                }
            }
        }

        $zf2ModulePaths = implode(PATH_SEPARATOR, $zf2ModulePaths) . PATH_SEPARATOR;
        $zf2ModulePaths .= getenv('ZF2_MODULES_TEST_PATHS') ?: (defined('ZF2_MODULES_TEST_PATHS') ? ZF2_MODULES_TEST_PATHS : '');

        static::initAutoloader();

        // use ModuleManager to load this module and it's dependencies
        $baseConfig = [
            'module_listener_options' => [
                'module_paths' => explode(PATH_SEPARATOR, $zf2ModulePaths),
            ],
        ];

        self::initVuFind();

        $config = ArrayUtils::merge($baseConfig, $testConfig);

        $serviceManager = new ServiceManager(new ServiceManagerConfig());
        $serviceManager->setService('ApplicationConfig', $config);
        $serviceManager->get('ModuleManager')->loadModules();

        static::$serviceManager = $serviceManager;
        static::$config         = $config;
    }

    /**
     * InitVuFind
     *
     * @return void
     */
    public static function initVuFind()
    {
        define('APPLICATION_ENV', 'development');
        define('SWISSBIB_TEST_FIXTURES', realpath(__DIR__ . '/fixtures'));

        // Setup autoloader for VuFindTest classes
        $loader = \Zend\Loader\AutoloaderFactory::getRegisteredAutoloader(
            \Zend\Loader\AutoloaderFactory::STANDARD_AUTOLOADER
        );

        $loader->registerNamespace('VuFindTest', __DIR__ . '/../../VuFind/src/VuFindTest');
    }

    /**
     * GetServiceManager
     *
     * @return ServiceManager
     */
    public static function getServiceManager()
    {
        return static::$serviceManager;
    }

    /**
     * GetConfig
     *
     * @return Config
     */
    public static function getConfig()
    {
        return static::$config;
    }

    /**
     * InitAutoloader
     *
     * @return void
     */
    protected static function initAutoloader()
    {
        $vendorPath = static::findParentPath('vendor');

        if (is_readable($vendorPath . '/autoload.php')) {
            $loader = include $vendorPath . '/autoload.php';
        } else {
            $zf2Path = getenv('ZF2_PATH') ?: (defined('ZF2_PATH') ? ZF2_PATH : (is_dir($vendorPath . '/ZF2/library') ? $vendorPath . '/ZF2/library' : false));

            if (!$zf2Path) {
                throw new RuntimeException('Unable to load ZF2. Run `php composer.phar install` or define a ZF2_PATH environment variable.');
            }

            include $zf2Path . '/Zend/Loader/AutoloaderFactory.php';

        }

        AutoloaderFactory::factory(
            [
                'Zend\Loader\StandardAutoloader' => [
                    'autoregister_zf' => true,
                    'namespaces'      => [
                        __NAMESPACE__ => __DIR__ . '/' . __NAMESPACE__,
                    ],
                ],
            ]
        );
    }

    /**
     * FindParentPath
     *
     * @param String $path Path
     *
     * @return bool|string
     */
    protected static function findParentPath($path)
    {
        $dir         = __DIR__;
        $previousDir = '.';
        while (!is_dir($dir . '/' . $path)) {
            $dir = dirname($dir);

            if ($previousDir === $dir) {
                return false;
            }

            $previousDir = $dir;
        }

        return $dir . '/' . $path;
    }
}

Bootstrap::init();
