<?php
class Ping {
    public static function pingServer($host) {
        $begin = microtime(true);
        $timeout = 2; // micro-seconds
        exec(sprintf('ping -n 1 -w ' . $timeout . ' %s', escapeshellarg($host)), $res, $rval);
        $end = microtime(true);
        CakeLog::info( "pingServer($host) rval[$rval] running offset time:" . ($end - $begin));
        return $rval === 0;
    }

    public static function pingServerPort($host, $port) {
        $timeout = 1; // seconds
        $status = true;
        $begin = microtime(true);
        $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
        $end = microtime(true);
        CakeLog::info( "pingServerPort($host, $port) running offset time:" . ($end - $begin));
        if (!$fp) {
            return false;
        }
        fclose($fp);
        return $status;
    }
}
