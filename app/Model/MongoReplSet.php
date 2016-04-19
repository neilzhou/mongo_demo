<?php
class MongoReplSet extends AppModel{
	public $primaryKey = '_id';

    private $_conn = false;
	//var $useDbConfig = 'mongo';

    var $mongoSchema = array(
            'rs_name' => array('type'=>'string'),
            'members'=>array(
                array(
                    '_id'  => array('type' => 'integer'),
                    'host' => array('type' => 'string'),
                    'port' => array('type' => 'string'),
                    'status' => array('type' => 'boolean'),
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
            if (empty($m['status'])) {
                //continue;
            }
            $members_str[] = $m['host'] . ':' . $m['port'];
        }
        if (empty($members_str)) {
            $result['message'] = 'No candidate servers found';
            $result['code'] = 'ERROR-EMPTY-VALID-MEMBER';
            return $result;
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
    public function checkReplSetConnOld($rs_name, $members)
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

    /**
     * checkReplSetConn
     *
     * @return array(
     *         'success' => true|false
     *         'code' => xxx
     *         'rs_name' => xxx
     *         'message' => xxx
     *         'data' => array(
     *          '0' => array(
     *             'success' => true|false,
     *             'code' => xxx,
     *             'message' => xxx,
     *             'host' => xxx
     *             'port' => xxx
     *             'check_name'' => xxx,
     *             'rs_status' => xxx,
     *             'conn_status' => xxx
     *         )
     *       )
     * )
     *
     */
    public function checkReplSetConn($rs_name, $members)
    {
        $time1 = microtime(true);
        $rs_name = empty($rs_name) ? '' : $rs_name;
        $status = array(
            'success' => false,
            'code'    => 'ERROR-init',
            'message' => 'Connect MongoDB failed.',
            'rs_name' => $rs_name,
            'init_replset' => false,
            'data' => array()
        );
        if (empty($members)) {
            return $status;
        }

        // if  some members of the replica set are not config replica set and the replica set is not setup, then set it true. which notice end-user to initiate the replica set.
        $initReplSet = false;
        $checkedMembers = array();
        // if one replica set has two primary member, means conflict in one replica set. which is caused by manually adding another replica set members.
        $conflictRs = false; 

        foreach($members as $m) {
            $check_name = $m['host'] . ":" . $m['port'];
            if (isset($checkedMembers[$check_name])) {
                continue;
            }
            $mStatus = $this->mongoReplSetConnectStatus($m['host'], $m['port'], $rs_name);
            if ($status['success'] && $mStatus['success']) {
                $conflictRs = true;
                $status['code'] = 'ERROR-CONFLICT-REPLSET';
            } else {
                $conflictRs = false; // reset to false if current status is false.
            }
            if ($mStatus['success']) {
                $status['success'] = true;
                $status['code'] = $mStatus['code'];
                $status['message'] = $mStatus['message'];
            }
            
            // set error code and error message if replica set is not set up.
            if ($mStatus['init_replset']) {
                $status['init_replset'] = true;
                $status['code'] = $mStatus['code'];
                $status['message'] = $mStatus['message'];
            } elseif (empty($status['success']) && empty($status['init_replset'])) {
                $status['code'] = $mStatus['code'];
                $status['message'] = $mStatus['message'];
            }

            $status['rs_name'] = empty($mStatus['rs_name']) ? $status['rs_name'] : $mStatus['rs_name'];
            foreach ($mStatus['data'] as $member) {
                if (isset($checkedMembers[$member['check_name']])) {
                    continue;
                }
                if ($conflictRs) {
                    $member['is_conflict'] = true;
                    $member['success'] = false;
                    $member['message'] = 'The replica set has two primary member, which is caused by adding another replica set member.';
                }
                $checkedMembers[$member['check_name']] = 1;
                $status['data'][] = $member;
            }
        }
        $time3 = microtime(true);
        CakeLog::info("checkReplSetConn rsname[$rs_name] offset:" .($time3 - $time1). ", cmd check members offset:" . ($time3 - $time1));
        return $status;
    }
    

    public function mongoReplSetConnectStatus($host, $port, $rs_name = 'rs0')
    {
        $host_port_key = $host . ':' . $port;
        $result = array(
            'success' => false, 
            'code' => 'ERROR-INIT-MONGO-COMMAND',
            'rs_name' => $rs_name,
            'init_replset' => false,
            'message' => 'Connect mongoDB failed.',
            'data' => array(
                "$host_port_key" => array(
                    '_id'  => 0, 
                    'host' => $host, 
                    'port' => $port, 
                    'check_name' => $host . ':' . $port,
                    'rs_status' => 'Unknown', 
                    'code' => 'ERROR-INIT-MONGO-COMMAND',
                    'conn_status' => 'Success',
                    'success' => false,
                    'is_conflict' => false,
                    'message' => 'Connect mongoDB failed.'
                )
            ),
        );
        if (empty($host) || empty($port)) {
            // code...
            $result['code'] = 'ERROR-EMPTY-HOST-PORT';
            return $result;
        }

        $status = $this->callWindowsMongoCmd($host, $port, 'rs.status()');
        CakeLog::info("rs.status raw data:" . $status);
        $result['detail'] = $status;
        $parse_status = false;

        if (empty($status)) {
            $result['code'] = 'ERROR-CALL-WIN-MONGO-CMD';
            $result['data']["$host_port_key"]['conn_status'] = 'Failed';
            return $result;

        } else if(strpos($status, 'connection attempt failed') !== false) {
            $result['code'] = 'ERROR-CONNECTION-FAILED';
            $result['data'][$host_port_key]['message'] = $result['message'] = 'Couldn\'t connect to server ' . $host . ':' . $port . '.';
            $result['data']["$host_port_key"]['conn_status'] = 'Failed';
            return $result;

        } else if(strpos($status, 'not running with --replSet') !== false) {
            $result['code'] = 'ERROR-NO-RUNNING-WITH-REPLSET';
            $result['data'][$host_port_key]['message'] = $result['message'] = 'Does not running with --replSet.';
            $result['data'][$host_port_key]['rs_status'] = 'no option --replSet.';
            return $result;

        } 

        if (empty($rs_name)) {
            $rs_name = $this->getMongoReplsetName($host, $port);
            $result['rs_name'] = $rs_name;
            if (empty($rs_name)) {
                $result['code'] = 'ERROR-NO-REPLSET-NAME';
                $result['message'] = 'Does not get replica set name.';
            }
        }

        if(strpos($status, 'run rs.initiate(') !== false) {
            $result['code'] = 'ERROR-NO-REPLSET-CONFIG';
            $result['message'] = 'Does not have a vallid replica set config.';
            $result['init_replset'] = true;

        } else if(strpos($status, '"set"') !== false && strpos($status, '"set" : "'.$rs_name.'"') === false) {
            $result['code'] = 'ERROR-REPLSET-NAME-NOT-MATCH';
            $result['message'] = 'Couldn\'t connect to replset name: ' . $rs_name;

        } else if(strpos($status, '"stateStr" : "PRIMARY"') !== false) {
            $result['success'] = true;
            $result['code'] = 'SUCCESS';
            $result['message'] = 'OK!';
            $parse_status = true;

        } else if(strpos($status, '"members"') !== false) {
            $parse_status = true;
            $result['code'] = 'ERROR-NO-PRIMARY';

        } else if(strpos($status, '"ok"') !== false) {
            $parse_status = true;
            $result['code'] = 'ERROR-OTHERS';

        } else {
            $result['code'] = 'ERROR-UNKNOWN';
        }

        $result['data'][$host_port_key]['message'] = $result['message'];

        if ($parse_status) {
            $resp = $this->parseMongoCmdResp($status);
            if (!empty($resp) && isset($resp['members'])) {
                $result['data'] = array(); // reset to empty array.
                foreach ($resp['members'] as $m) {
                    list($host, $port) = explode(':', $m['name']);
                    $result['data'][$m['name']] = array(
                        '_id' => $m['_id'],
                        'host' => $host,
                        'port' => $port,
                        'check_name' => $m['name'],
                        'code' => $result['code'],
                        'rs_status' => $m['stateStr'],
                        'conn_status' => 'Success',
                        'success' => ($m['health'] === 1) ? true : false,
                        'message' => $m['stateStr']
                    );
                }
            } else if(!empty($resp)) {
                isset($resp['stateStr']) && ($result['data'][$host_port_key]['rs_status'] = $resp['stateStr']);
                isset($resp['errmsg']) && ($result['data'][$host_port_key]['message'] = $resp['errmsg']);
                $result['data'][$host_port_key]['code'] = $result['code'];
            } 
        }
        CakeLog::info("check cmd rs.status hostport result:" . json_encode($result));
        return $result;
    }

    public function getMongoReplsetName($host, $port) {
        $resp = $this->callWindowsMongoCmd($host, $port, 'db.serverCmdLineOpts().parsed.replication');
        if (empty($resp)) {
            return false;
        }
        if (strpos($resp, '"replSetName"') === false) {
            return false;
        }
        $arr = $this->parseMongoCmdResp($resp);
        return empty($arr['replSetName']) ? false : $arr['replSetName'];
    }

    private function parseMongoCmdResp($resp) {
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

    public function pingServer($host) {
        $begin = microtime(true);
        $timeout = 2; // micro-seconds
        exec(sprintf('ping -n 1 -w ' . $timeout . ' %s', escapeshellarg($host)), $res, $rval);
        $end = microtime(true);
        CakeLog::info( "pingServer($host) rval[$rval] running offset time:" . ($end - $begin));
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

    public function callWindowsMongoCmd($host, $port, $shell_command, $parsed = false)
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
        $result = shell_exec($commandLine); 
        if ($parsed) {
            $result_parsed = $this->parseMongoCmdResp($result);
            if (!empty($result_parsed)) {
                $result = $result_parsed;
            }
        }
        return $result;
    }

    public function saveOrUpdateReplset($data){
        $id = empty($data['_id']) ? 0 : $data['_id'];
        $id = empty($id) ? (empty($data['id']) ? 0 : $data['id']) : $id;
        $saveData = array(
            'rs_name' => $data['rs_name']
        );
        foreach ($data['data'] as $m) {
            $saveData['members'][] = array(
                '_id' => $m['_id'],
                'host' => $m['host'],
                'port' => $m['port'],
                'status' => empty($m['success']) ? false : true
            );
        }
        if (empty($saveData['members'])) {
            return false;
        }
        if (empty($id)) {
            $this->create();
        } else {
            $saveData['_id'] = $id;
        } 
        return $this->save($saveData);
    }

    public function saveMultiReplset($rses) {
        if (empty($rses)) {
            return false;
        }
        foreach ($rses as $r) {
            if (empty($r['data'])) {
                continue;
            }
            $members = array();
            foreach ($r['data'] as $m) {
                $members[] = array(
                    '_id' => $m['_id'], 
                    'host' => $m['host'], 
                    'port' => $m['port'],
                    'status' => $m['success'],
                );
            }
            $this->create();
            $data = array(
                'rs_name' => $r['rs_name'],
                'members' => $members
            );
            $this->save($data);
        }
    }

    public function saveRsMembers($rses) {
        if (empty($rses)) {
            return false;
        }
        foreach ($rses as $r) {
            if (empty($r) || empty($r['data'])) {
                return false;
            }
            $members = array();
            foreach ($r['data'] as $m) {
                $members[] = array(
                    'host' => $m['host'], 
                    'port' => $m['port'],
                    '_id'  => $m['_id'],
                    'status' => empty($m['success']) ? false : true
                );
            }

            //$data = array(
                //'rs_name' => $r['rs_name'],
                //'members' => $members
            //);
            //$this->MongoReplSet->create();
            //$this->MongoReplSet->save($data);
            $dbSource = $this->getDataSource();
            $collection = $dbSource->getMongoCollection($this);
            CakeLog::info("members:" . json_encode($members));
            $status = $collection->update(
                array(
                    'rs_name' => $r['rs_name'],
                    'members' => array(
                        '$elemMatch' => array(
                            'host' => $members[0]['host'],
                            'port' => $members[0]['port']
                        )
                    )
                ), 
                array(
                    '$addToSet' => array(
                        'members' => array(
                            '$each' => $members
                        )
                    )
                ), 
                array(
                    'multiple' => true, 
                    'upsert' => true
                )
            );
            CakeLog::info("update status:" . json_encode($status));
        }
    }
}
