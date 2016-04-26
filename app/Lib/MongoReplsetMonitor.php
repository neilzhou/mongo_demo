<?php
App::uses('MongoShell', 'Lib');
App::uses('Ping', 'Lib');
App::uses('MongoReplsetStatus', 'Lib');

class MongoReplsetMonitor {

    /**
     * @desc get specified members status, given replset name in case of multiple replset names of members.
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
     */
    public static function getMembersStatus($rs_name, $members) {
    
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
            $mStatus = self::getStatus($m['host'], $m['port'], $rs_name);
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
        //CakeLog::info("checkReplSetConn rsname[$rs_name] offset:" .($time3 - $time1). ", cmd check members offset:" . ($time3 - $time1));
        return $status;
    }

    /**
     * @desc get specified mongo replset status array.
     * @return array
     */
    public static function getStatus($host, $port, $rs_name = 'rs0') {
        
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

        $status = MongoShell::callWindowsCmd($host, $port, 'rs.status()');
        CakeLog::info("rs.status raw data:" . $status);
        $result['detail'] = $status;
        $parse_status = false;
        if (empty($rs_name)) {
            $rs_name = self::getReplsetName($host, $port);
            $result['rs_name'] = $rs_name;
        }
        $status_code = MongoReplsetStatus::statusCode($status, $rs_name);
        $error_message = MongoReplsetStatus::getMessage($status_code, $result['rs_name']);
        $parse_status = MongoReplsetStatus::canBeParse($status_code);
        $result['success'] = MongoReplsetStatus::isSuccess($status_code);

        $result['data'][$host_port_key]['message'] = $result['message'];

        if ($parse_status) {
            $resp = MongoShell::parse($status);
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
                $error_message && isset($resp['errmsg']) && ($error_message = $resp['errmsg']);
                $result['data'][$host_port_key]['code'] = $status_code;
                $error_message && $result['data'][$host_port_key]['message'] = $error_message;
                //CakeLog::info("error message hostportkey[$host_port_key]: ". $error_message);
            } 
        }
        //CakeLog::info("check cmd rs.status hostport result:" . json_encode($result));
        if(empty($error_message)) $error_message = $status;
        $result['message'] = $error_message;
        $result['code'] = $status_code;
        $result['init_replset'] = MongoReplsetStatus::canBeInit($status_code);
        $result['can_be_added'] = MongoReplsetStatus::canBeAdded($status_code);
        //CakeLog::info("check result parse_status:[$parse_status] code:[$status_code], message:[$error_message], return result:". json_encode($result));
        return $result;
    }

    /**
     * @desc get replica set name of specified mongoDB instance
     */
    public static function getReplsetName($host, $port) {
        $resp = MongoShell::callWindowsCmd($host, $port, 'db.serverCmdLineOpts().parsed.replication');
        if (empty($resp)) {
            return false;
        }
        if (strpos($resp, '"replSetName"') === false) {
            return false;
        }
        $arr = MongoShell::parse($resp);
        return empty($arr['replSetName']) ? false : $arr['replSetName'];
    }

    /**
     * @desc get one replset member name using mongo shell, eg. '127.0.0.1:27017', which is used when reconfig by force.
     */
    public function getReplsetMemberName($rs_name, $members){
        $status = self::getMembersStatus($rs_name, $members);
        CakeLog::info("_getHostPortForCmd status:" . json_encode($status));
        if (empty($status) || empty($status['data'])) {
            return false;
        }
        $host_port = '';
        foreach ($status['data'] as $m) {
            if ($m['success']) {
                $host_port = $m['check_name'];
                break;
            } elseif(MongoReplsetStatus::isMember($m['code'])) {
                $host_port = $m['check_name'];
                break;
            }
        }
        CakeLog::info("_getHostPortForCmd hostport:" . $host_port);
        return $host_port;
    }
}
