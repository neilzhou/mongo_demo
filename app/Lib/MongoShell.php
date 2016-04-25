<?php
class MongoShell {

    /**
     * @desc call mongo shell command on windows OS, where ATMongodService has been installed.
     */
    public static function callWindowsCmd($host, $port, $shell_command, $parsed = false) {
    
        if(empty($host) || empty($port) || empty($shell_command)) return false;

        $Wshshell= new COM('WScript.Shell');
        $installDir= $Wshshell->regRead('HKEY_LOCAL_MACHINE\SOFTWARE\Active Network\ATMongodbService\InstallDir');
        if (empty($installDir)) {
            return false;
        }

        $exe = $installDir . 'MongoDB\bin\mongo.exe';
        $command = addslashes($shell_command);
        $commandLine = <<<STR
start /B "mongoClientWindow" "$exe" $host:$port --quiet --eval "$command"
STR;
        $result = shell_exec($commandLine); 
        if ($parsed) {
            $result_parsed = self::parse($result);
            if (!empty($result_parsed)) {
                $result = $result_parsed;
            }
        }
        return $result;
    }

    /**
     * @desc parse the response of mongo shell command to array, and convert the Mongo Shell object to string.
     */
    public static function parse($resp) {
        $result = array();
        $resp = str_replace(array("\r\n", "\r", "\n", "\t"), '', $resp);
        $resp = preg_replace(array(
            '/ISODate\((.*?)\)/',
            '/NumberLong\((.*?)\)/',
            '/Timestamp\((\d+),.*?\)/', 
            '/ISODate\((.*?)\)/', 
        ), array('\1', '\1', '\1', '\1'), $resp);
        if (preg_match("/{.*}/", $resp, $matches)) {
            $result = json_decode(stripslashes($matches[0]), true);
        }

        return $result;
    }
}
