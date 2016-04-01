<?php
class MongoReplSetController extends AppController {
    public $components = array('RequestHandler');
    //public $uses = array('ReplSetModel')

/**
 * Action for plugin documentation home page
 *
 * @return void
 */
	public function index() {
        if ($this->request->is('post') && $this->request->data) {
            // code...
            ini_set('max_execution_time', 30 * 60);
            $data = $this->request->data['MongoReplSet'];
            $rs_name = ''; //$data['rs_name'];
            $from_ip = $data['from_host'];
            $to_ip = $data['to_host'];
            $port = $data['port'];

//$us32str = sprintf("%u", $s32int);
            $from_ip32_int = ip2long($from_ip);
            $to_ip32_int = ip2long($to_ip);
            $result = array();

            $start_time = microtime(true);
            CakeLog::info("San start time: $start_time");
            $checked_members = array();
            $result = array();
            $errors = array(
                //'success' => false,
                //'code' => 'ERROR-INIT',
                //'message' => 'Connect mongoDB failed.',
                //'rs_name' => '',
                //'data' => array(),
            );

            for ($i = $from_ip32_int; $i <= $to_ip32_int; $i++) {
                // code...
                $check_ip = long2ip($i);
                if (isset($checked_members[$check_ip . ':' . $port])) {
                    CakeLog::info("Scan skip $check_ip:$port for existed.");
                    continue;
                }

                $time1 = microtime(true);
                if($this->MongoReplSet->pingServer($check_ip) && $this->MongoReplSet->pingServerPort($check_ip, $port)) {
                    $check_member = array(
                        array(
                            'host' => $check_ip,
                            'port' => $port,
                        )
                    );
                    $check_status = $this->MongoReplSet->checkReplSetConn($rs_name, $check_member);
                    CakeLog::info("San host[$check_ip], port[$port] status:" . json_encode($check_status));
                    if (empty($check_status['success']) && $check_status['code'] == 'ERROR-NO-RUNNING-WITH-REPLSET') {
                        continue;
                    }
                    foreach ($check_status['data'] as $member) {
                        $checked_members[$member['check_name']] = 1;
                    }
                    if ($check_status['success']) {
                        $result[] = $check_status;
                    } else {
                        $rs_name = emtpy($check_status['rs_name']) ? 'No replset name' : $check_status['rs_name'];
                        if (empty($errors[$rs_name]['init_replset'])) {
                            $check_status['data'] = array_merge($errors[$rs_name]['data'], $check_status['data']);
                            $errors[$rs_name] = array_merge($errors[$rs_name], $check_status);
                        } else {
                            $errors[$rs_name]['data'] = array_merge($errors[$rs_name]['data'], $check_status['data']);
                        }
                    }
                }
                $time2 = microtime(true);
                CakeLog::info("San host[$check_ip], port[$port] offset: " . ($time2 - $time1));
            }
            $this->_saveRsMembers($result);
            $this->_saveRsMembers($errors);
            $end_time = microtime(true);
            CakeLog::info("Scan end, scan total num:[" . ($to_ip32_int - $from_ip32_int) . "] time:[$end_time], offset:" . ($end_time - $start_time));
            CakeLog::info("Scan end, scan result: " . json_encode($result));
            CakeLog::info("Scan end, scan checked members:" . json_encode($checked_members));
            CakeLog::info("Scan end, scan errors members:" . json_encode($errors));
            //$this->MongoReplSet->create();
            //$this->MongoReplSet->save(array(
                //'rs_name' => $rs_name,
                //'members' => $members
            //));

            $this->renderJsonWithSuccess($result);
            //$this->render('scan');
        } else {
            $list = $this->MongoReplSet->find('all');
            $rs_list = array();
            if (!empty($list)) {
                // code...
                foreach($list as $rs) {
                    $rs_db = $rs['MongoReplSet'];
                    $rs_status = $this->MongoReplSet->checkReplSetConn($rs_db['rs_name'], $rs_db["members"]);
                    $rs_status['id'] = $rs_db['_id'];

                    CakeLog::info('demo index rs db:' . json_encode($rs_db));
                    CakeLog::info('demo index rs status:' . json_encode($rs_status));
                    $rs_list[] = $rs_status;
                }
            }
            $this->set('rs_list', $rs_list);
        }
	}

    private function _saveRsMembers($rses) {
        foreach ($rses as $r) {
            $members = array();
            foreach ($r['data'] as $m) {
                $members[] = array('host' => $m['host'], 'port' => $m['port']);
            }
            $this->MongoReplSet->create();
            $data = array(
                'rs_name' => $r['rs_name'],
                'members' => $members
            );
            $this->MongoReplSet->save($data);
        }
    }

    public function delete($id) {
        $this->MongoReplSet->id = $id;
        $this->MongoReplSet->delete();
        $this->renderJsonWithSuccess(array(), 'Delete replica set successfully!');
    }

    public function view($id) {
    
        $this->MongoReplSet->id = $id;
        $rs = $this->MongoReplSet->read();
        $data = $rs['MongoReplSet'];
        $status = $this->MongoReplSet->checkReplSetConn($data['rs_name'], $data['members']);
        $status['id'] = $this->MongoReplSet->id;

        $this->set('replset_status', $status);
        $this->render('/Elements/replset_panel');
    }

    public function add() {
        $data = $this->request->data['MongoReplSet'];
        $members = array(
            array('host' => $data['host'], 'port' => $data['port'])
        );

        $saveData = array(
            'rs_name' => $data['rs_name'],
            'members' => $members,
        );

        $this->MongoReplSet->create();
        $this->MongoReplSet->save($saveData);
        $status = $this->MongoReplSet->checkReplSetConn($data['rs_name'], $members);
        $status['id'] = $this->MongoReplSet->id;

        $this->set('replset_status', $status);
        /*if ($status['success']) {
            // code...
            $this->renderJsonWithSuccess($status['data'], $status['message'], $status['code']);
        } else {
            $this->renderJsonWithError($status['message'], $status['code'], $status['data']);
        }*/
        
        /*try{
        
            $conn = new MongoClient("mongodb://{$data['ip_address']}:{$data['port']}", array('replicaSet' => $data['rs_name']));
            //$conn = new MongoClient("mongodb://{$data['ip_address']}:{$data['port']}");

            //if (method_exists($conn, 'setSlaveOkay')) {
                //$conn->setSlaveOkay(true);
            //} else {
                //$conn->setReadPreference(true ? MongoClient::RP_SECONDARY_PREFERRED : MongoClient::RP_PRIMARY);
            //}
            $db = $conn->selectDB("test");

            $result = $db->execute("db.runCommand('ping')");
            if($result['ok']){
                $this->renderJsonWithSuccess($result['retval']);
            }
            $this->renderJsonWithError('Failed', 'ERROR', $result);

        } catch(MongoConnectionException $e){
            $this->renderJsonWithError($e->getMessage());
        } catch(Exception $e) {
            $this->renderJsonWithError($e->getMessage());
        }
        //$result = $source->getMongoDb()->execute("rs.status()");
        $this->renderJsonWithSuccess($result);*/

    }

}
