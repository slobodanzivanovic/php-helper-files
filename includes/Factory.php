<?php

!defined('DS') ? define('DS', DIRECTORY_SEPARATOR) : null;

class Factory
{
    /** @var  array $instance */
    private static $instance = [];
    public static $app_path = __DIR__ . DS . '..';

    /**
     * The method automatically loads files and returns a new object for the given class name.
     * Can return a singleton object or an entirely new object
     *
     * @param string $class
     * @param bool   $singleton
     *
     * @return mixed|null
     */
    public static function getInstance($class, $singleton = true)
    {
        if ($singleton === true) {
            if (isset(self::$instance[$class])) {
                return self::$instance[$class];
            }

            self::$instance[$class] = new $class();
            return self::$instance[$class];
        }
        return new $class();
    }

    public static function setInstance($class, $object)
    {
        self::$instance[$class] = $object;
    }

    /**
     * Automatically loads the class for the given name $class.
     *
     * @param string $class
     * @param bool   $autoload
     *
     * @return bool returns true if it succeeds, false if it fails.
     */
    public static function autoload($class, $autoload = true)
    {
        $paths = [
            'tables',
            'includes'
        ];
        foreach ($paths as $path) {
            $file = self::$app_path . DS . $path . DS . $class . '.php';
            if (file_exists($file)) {
                if ($autoload) {
                    require_once $file;
                }
                return true;
            }
        }

        return false;
    }
}
