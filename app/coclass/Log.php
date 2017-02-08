<?php

use Phalcon\Mvc\Controller;

class Log extends Controller
{
    /**
     * 记录错误日志信息
     *
     * @param Exception $e
     *
     * @return mixed
     */
    public static function error(Exception $e)
    {
        $message = $e->getMessage() . PHP_EOL . $e->getTraceAsString();

        $object = new static;

        if ($object->config->log->enabled) {
            return $object->log->error($message);
        }

        return null;
    }
}