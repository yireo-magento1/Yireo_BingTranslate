<?php
// Namespace
namespace Yireo\Common\System;

/**
 * Class Autoloader
 *
 * @package Yireo\System
 */
class Autoloader
{
    /**
     * Autoloader constructor.
     */
    public function __construct()
    {
        self::$paths[] = dirname(__DIR__) . '/';
    }

    /**
     * @var array
     */
    static public $paths = [];

    /**
     *
     */
    static public function init()
    {
        spl_autoload_register(array(new self, 'load'), false, true);
    }

    /**
     * @param $path
     */
    static public function addPath($path)
    {
        self::$paths[] = $path;
    }

    /**
     * Main autoloading function
     *
     * @param $className
     *
     * @return bool
     */
    public function load($className)
    {
        if (stristr($className, 'yireo') === false) {
            return false;
        }

        // Try to include namespaced files
        $rt = $this->loadNamespaced($className);

        if ($rt === true) {
            return true;
        }

        return false;
    }

    /**
     * Autoloading function for namespaced classes
     *
     * @param $className
     *
     * @return bool
     */
    protected function loadNamespaced($className)
    {
        $prefix = 'Yireo\\';
        $len = strlen($prefix);

        if (strncmp($prefix, $className, $len) !== 0) {
            return false;
        }

        $relativeClass = substr($className, $len);

        $filename = str_replace('\\', '/', $relativeClass) . '.php';

        foreach (self::$paths as $path) {
            $realPath = $path . '/' . $filename;
            if (file_exists($realPath)) {
                include_once $realPath;

                return true;
            }
        }

        return false;
    }
}
