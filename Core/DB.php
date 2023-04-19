<?php

namespace Core;

class DB
{
    private static $instance;

    public static function getConnect()
    {
        if (self::$instance === null)
        {
            self::$instance = self::getPDO();
        }
        return self::$instance;
    }

    private static function getPDO()
    {
        $dsn = sprintf('%s:host=%s;dbname=%s', DB_DRIVER , DB_HOST, DB_NAME);
        $db = new \PDO($dsn, DB_USER, DB_USER_PASS);
        $db->exec('SET NAMES UTF8');

        return $db;
    }
}   
