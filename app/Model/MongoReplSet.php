<?php
class MongoReplSet extends AppModel{
	public $primaryKey = '_id';

    private $_conn = false;
	//var $useDbConfig = 'mongo';

    var $mongoSchema = array(
            'rs_name' => array('type'=>'string'),
            'members'=>array(
                array(
                    'host' => array('type' => 'string'),
                    'port' => array('type' => 'string'),
                )
            ),
            'created'=>array('type'=>'datetime'),
            'modified'=>array('type'=>'datetime'),
    );
 
    /**
     * connectRs
     * @return array("success" => true| false, "message" => xxx, "data" => array(), "code" => "SUCCESS|ERROR")
     * @author Neil.zhou
     **/
    public function connectRs($rs_name, $members, $check_status = true){

        $this->_conn = false;
        $result = array(
            'success' => false,
            'message' => 'Connect mongo failed.',
            'data' => array(),
            'code' => 'ERROR-INIT'
        );
        if(empty($rs_name) || empty($members)) {
            $result['code'] = 'ERROR-EMPTY-NAME-MEMBERS';
        }

        $members_str = array();
        foreach($members as $m) {
            $members_str[] = $m['host'] . ':' . $m['port'];
        }
        try {
            App::uses('MongodbSource', 'Mongodb.Model/Datasource');
            $conf = array(
                'database' => 'test',
                'replicaset' => array(
                    'host' => 'mongodb://' . implode(',', $members_str), 
                    'options' => array('replicaSet' => $rs_name)
                ),
            );
            $this->_conn = new MongodbSource($conf, true);
            if ($this->_conn->connected) {
                // code...
                if ($check_status) {
                    $result = $this->getRsStatus($this->_conn, $rs_name, $members);
                } else {
                    $result['success'] = true;
                    $result['message'] = "OK!";
                    $result['code'] = 'SUCCESS';
                }
            } else {
                $result['message'] = $this->_conn->error;
                $result['code'] = 'ERROR-CONNECT-FAILED';
            }
        } catch(MongoConnetionException $e){

            $result['message'] = $e->getMessage();
            $result['code'] = 'ERROR-MongoConnetionException';
        } catch(MongoException $e){
            $result['message'] = $e->getMessage();
            $result['code'] = 'ERROR-MongoException';
        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
            $result['code'] = 'ERROR-Exception';
        }
        return $result;
    }

    public function getRsStatus($conn, $rs_name, $members) {
        $result = array(
            'success' => false,
            'message' => 'Connect mongo failed.',
            'data' => array(),
            'code' => 'ERROR-INIT'
        );
        $rsStatus = $conn->execute("rs.status()");
        $rsMembers = array();
        if(!empty($rsStatus) || !isset($rsStatus['ok'])){
            $result['success'] = true;
            $result['message'] = "OK!";
            $result['code'] = 'SUCCESS';

            foreach ($rsStatus['members'] as $m) {
                $rsMembers[$m['name']] = $m['stateStr'];
                list($host, $port) = explode(':', $m['name']);
                $result['data'][$m['name']] = array(
                    'host' => $host,
                    'port' => $port,
                    'check_name' => $m['name'],
                    'rs_status' => $m['stateStr'],
                    'conn_status' => 'Success',
                    'success' => ($m['health']) ? true : false,
                    'message' => $m['stateStr']
                );
            }

            foreach ($members as $m) {
                $check = $m['host'] . ':' . $m['port'];
                if (isset($result['data'][$check])) {
                    continue;
                }
                $success = false;
                $result['data'][$check] = array(
                    'host' => $m['host'],
                    'port' => $m['port'],
                    'check_name' => $check,
                    'rs_status' => (isset($rsMembers[$check]) ? $rsMembers[$check] : 'Unknow'),
                    'conn_status' => $success ? 'Success' : 'Failed',
                    'success' => $success,
                    'message' => empty($rsMembers[$check]) ? 'Not a replica set member' : $rsMembers[$check]
                );
            }
        } else {
            $result['data'] = $rsStatus; 
            $result['code'] = 'ERROR-ISMASTER-FAILED';
        }
        return $result;
    }

    public function getConnection() {
        return $this->_conn;
    }
    public function formatSql($format, $args) {
        return sprintf($format, addslashes($args));
    }

    /**
     * checkMongoConnection
     * @return void
     * @author Neil.zhou
     **/
    public function checkReplSetConn($rs_name, $members)
    {
        $time1 = microtime(true);
        $status = $this->connectRs($rs_name, $members);
        $time2 = microtime(true);
        CakeLog::info("checkReplSetConn connectRS rsname[$rs_name] offset:" . ($time2 - $time1));
        $failedCodes = [
            'ERROR-CALL-WIN-MONGO-CMD',
            'ERROR-CONNECTION-FAILED',
        ];
        if (!$status['success']) {
            // code...
            $initReplSet = false;
            foreach($members as $m) {
                $mStatus = $this->mongoReplSetConnectStatus($m['host'], $m['port'], $rs_name);
                if ($mStatus['code'] == 'ERROR-NO-REPLSET-CONFIG') {
                    $initReplSet = true;
                }
                $connStatus = in_array($mStatus['code'], $failedCodes) ? 'Failed' : 'Success';
                $mStatus['host'] = $m['host'];
                $mStatus['port'] = $m['port'];
                $mStatus['check_name'] = $m['host'] . ':' . $m['port'];
                $mStatus['rs_status'] = 'Unkown';
                $mStatus['conn_status'] = $connStatus;
                $status['data'][$mStatus['check_name']] = $mStatus;
            }
            $status['init_replset'] = $initReplSet;
        }

        $status['rs_name'] = $rs_name;
        $time3 = microtime(true);
        CakeLog::info("checkReplSetConn rsname[$rs_name] offset:" .($time3 - $time1). ", cmd check members offset:" . ($time3 - $time2));
        return $status;
    }

    public function mongoReplSetConnectStatus($host, $port, $rs_name = 'rs0')
    {
        $result = array(
            'success' => false, 
            'code' => 'ERROR-INIT-MONGO-COMMAND',
            'data' => array(),
            'message' => 'Connect mongoDB failed.'
        );
        if (empty($host) || empty($port)) {
            // code...
            $result['code'] = 'ERROR-EMPTY-HOST-PORT';
            return $result;
        }

        $status = $this->callWindowsMongoCmd($host, $port, 'rs.status()');
        $result['detail'] = $status;
        if (empty($status)) {
            $result['code'] = 'ERROR-CALL-WIN-MONGO-CMD';

        } else if(strpos($status, 'run rs.initiate(') !== false) {
            $result['code'] = 'ERROR-NO-REPLSET-CONFIG';
            $result['message'] = 'Does not have a vallid replica set config.';

        } else if(strpos($status, 'not running with --replSet') !== false) {
            $result['code'] = 'ERROR-NO-RUNNING-WITH-REPLSET';
            $result['message'] = 'Does not running with --replSet.';

        } else if(strpos($status, 'connection attempt failed') !== false) {
            $result['code'] = 'ERROR-CONNECTION-FAILED';
            $result['message'] = 'Couldn\'t connect to server ' . $host . ':' . $port . '.';

        } else if(strpos($status, '"set" : "'.$rs_name.'"') === false) {
            $result['code'] = 'ERROR-REPLSET-NAME-NOT-MATCH';
            $result['message'] = 'Couldn\'t connect to replset name: ' . $rs_name;

        } else if(strpos($status, '"myState" : 1') !== false) {
            $result['success'] = true;
            $result['code'] = 'SUCCESS';
            $result['message'] = 'OK!';

        } else {
            $result['code'] = 'ERROR-UNKNOWN';
        }

        return $result;
    }

    public function pingServer($host) {
        $begin = microtime(true);
        $timeout = 2; // micro-seconds
        exec(sprintf('ping -n 1 -w ' . $timeout . ' %s', escapeshellarg($host)), $res, $rval);
        $end = microtime(true);
        CakeLog::info( "pingServer($host) running offset time:" . ($end - $begin));
        return $rval === 0;
    }

    public function pingServerPort($host, $port) {
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

    public function callWindowsMongoCmd($host, $port, $shell_command)
    {
    
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
        return shell_exec($commandLine); 
    }
}
