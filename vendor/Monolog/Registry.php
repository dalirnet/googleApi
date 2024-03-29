<?php

namespace Monolog;

use InvalidArgumentException;

class Registry
{
    private static $loggers = array();

    public static function addLogger(Logger $logger, $name = null, $overwrite = false)
    {
        $name = $name ?: $logger->getName();
        if (isset(self::$loggers[$name]) && !$overwrite) {
            throw new InvalidArgumentException('Logger with the given name already exists');
        }
        self::$loggers[$name] = $logger;
    }

    public static function hasLogger($logger)
    {
        if ($logger instanceof Logger) {
            $index = array_search($logger, self::$loggers, true);
            return false !== $index;
        } else {
            return isset(self::$loggers[$logger]);
        }
    }

    public static function removeLogger($logger)
    {
        if ($logger instanceof Logger) {
            if (false !== ($idx = array_search($logger, self::$loggers, true))) {
                unset(self::$loggers[$idx]);
            }
        } else {
            unset(self::$loggers[$logger]);
        }
    }

    public static function clear()
    {
        self::$loggers = array();
    }

    public static function __callStatic($name, $arguments)
    {
        return self::getInstance($name);
    }

    public static function getInstance($name)
    {
        if (!isset(self::$loggers[$name])) {
            throw new InvalidArgumentException(sprintf('Requested "%s" logger instance is not in the registry', $name));
        }
        return self::$loggers[$name];
    }
}
