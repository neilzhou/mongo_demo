<?php
class MongoReplsetConnection {
    private $_conn = false;
    private $rs_name = 'rs0';
    private $members = array();

    public function __construct($rs_name, $members) {
        $this->rs_name = $rs_name;
        $this->members = $members;
    }

    public function connect($check_status = false) {
    
        $rs_name = $this->rs_name;
        $members = $this->members;

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
                    $result = $this->getRsStatus($rs_name, $members);
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


    private function getRsStatus($rs_name, $members) {
        $conn = $this->_conn;
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
}
