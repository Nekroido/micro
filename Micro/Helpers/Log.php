<?php
/**
 * Date: 12-May-16
 * Time: 15:04
 */

namespace Micro\Helpers;

/**
 * Class Log
 * @package Micro\Helpers
 */
class Log
{
    const TYPE_VERBOSE = 0;
    const TYPE_ERROR = 1;

    const DATE_FORMAT = 'd.m.Y H:i:s P';

    /**
     * Write a line to a log
     * @param string $string
     * @param int $type
     */
    public static function write($string, $type = self::TYPE_VERBOSE)
    {
        $filename = $type == self::TYPE_ERROR ? 'errors' : 'verbose';

        list(, $caller) = debug_backtrace(false);

        $logFile = fopen('logs/' . $filename . '.log', 'a+');
        fputs(
            $logFile,
            '[' . date(Log::DATE_FORMAT) . '] ('
            . (isset($caller['class']) ? $caller['class'] . ' -> ' : '') . $caller['function'] . ') '
            . $string
            . "\r\n"
        );
        fclose($logFile);
    }
}