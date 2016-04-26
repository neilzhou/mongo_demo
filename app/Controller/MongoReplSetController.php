<?php
App::uses('Ping', 'Lib');
App::uses('MongoShell', 'Lib');
App::uses('MongoReplsetMonitor', 'Lib');
App::uses('MongoReplsetConnection', 'Lib');

class MongoReplSetController extends AppController {
    public $components = array('RequestHandler', 'ScanMongoInstances');
    public $uses = array('MongoReplSet', 'TimingMongoInstance');
    //public $uses = array('ReplSetModel')

/**
 * Action for plugin documentation home page
 *
 * @return void
 */
	public function index() {
        if ($this->request->is('post') && $this->request->data) {
            // code...
            ini_set('max_execution_time', 0);
            $start_time = microtime(true);
            CakeLog::info("San start time: $start_time");

            $rs_name = $this->request->data('MongoReplSet.rs_name');
            $scanInLan = $this->request->data('MongoReplSet.scan_all_lan');
            $port = $this->request->data('MongoReplSet.port');

            if ($scanInLan) {
                $status = $this->ScanMongoInstances->scanAllLan($rs_name);
            } else {
                $range = array();
                $range['from'] = $this->request->data('MongoReplSet.from_host');
                $range['to'] = $this->request->data('MongoReplSet.to_host');

                if ($range['from'] > $range['to']) {
                    $this->renderJsonWithError('From ip address is larger than to-ip address.');
                }
                list($fAIp, $fBIp) = explode('.', $range['from']);
                list($tAIp, $tBIp) = explode('.', $range['to']);
                if ($fAIp < $tAIp || $fBIp < $tBIp) {
                    $this->renderJsonWithError('IP range is too huge.');
                }
                $status = $this->ScanMongoInstances->scanRange($range, $port, $rs_name);
            }

//$us32str = sprintf("%u", $s32int);

            $end_time = microtime(true);
            CakeLog::info("San multi request offset[".($end_time - $start_time)."] status:" . json_encode($status));

            $this->renderJsonWithSuccess($status);
        } else {
            $list = $this->MongoReplSet->find('all');
            $rs_list = array();
            if (!empty($list)) {
                foreach($list as $rs) {
                    $rs_db = $rs['MongoReplSet'];
                    $rs_status = MongoReplsetMonitor::getMembersStatus($rs_db['rs_name'], $rs_db["members"]);
                    $rs_status['id'] = $rs_db['_id'];

                    CakeLog::info('demo index rs db:' . json_encode($rs_db));
                    CakeLog::info('demo index rs status:' . json_encode($rs_status));
                    $rs_list[] = $rs_status;
                }
            }
            $this->set('rs_list', $rs_list);
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
        $data = empty($rs['MongoReplSet']) ? array() : $rs['MongoReplSet'];
        $status = array();
        if (!empty($data)) {
            $status = MongoReplsetMonitor::getMembersStatus($data['rs_name'], $data['members']);
            $status['id'] = $this->MongoReplSet->id;
        }

        $this->set('replset_status', $status);
        $this->render('/Elements/replset_panel');
    }

    public function add() {
        $rs_name = $this->request->data('MongoReplSet.rs_name') ? '' : $this->request->data('MongoReplSet.rs_name');
        $members = $this->request->data('MongoReplSet.members');

        $success_arr = array();
        $error_arr = array();
        foreach ($members as $m) {
            $this->ScanMongoInstances->checkEachMember($m['host'], $m['port'], $rs_name, $success_arr, $error_arr, $checked_arr, false);
        }

        $this->MongoReplSet->saveMultiReplset($success_arr);
        $this->MongoReplSet->saveMultiReplset($error_arr);
        $this->renderJsonWithSuccess();
    }


    public function clear_data($id) {
        $host = $this->request->data('MongoReplSet.host');
        $port = $this->request->data('MongoReplSet.port');

        $ch = curl_init();
        $url = Router::url('/', true) . "api_clear_mongo_data.php?host=". urlencode($host) . "&port=$port";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $resp = curl_exec($ch);
        $err = false;
        if (curl_errno($ch)) {
            $err = curl_error($ch);
        }
        curl_close($ch);
        if ($err) {
            $this->renderJsonWithError($err, 'ERROR-CURL');
        }
        $result = json_decode($resp, true);
        if (empty($result)) {
            $this->renderJsonWithError($resp, 'ERROR-JSON-EMPTY');
        } elseif(empty($result['success'])) {
            $result['message'] = empty($result['message']) ? 'Some error occur!' : $result['message'];
            $result['code'] = empty($result['code']) ? 'ERROR-UNKNOWN' : $result['code'];
            $this->renderJsonWithError($result['message'], $result['code'], $result);
        }
        
        $this->renderJsonWithSuccess($result);

    }

    public function reconfig($id) {
        if ($this->request->isPost() && $this->request->data) {
            $this->MongoReplSet->id = $id;
            $rs = $this->MongoReplSet->read();
            $rs = $rs['MongoReplSet'];

            $members = array();
            $i = 0;
            $existed_ids = array();
            $max_i = 0;
            foreach ($this->request->data('MongoReplSet.Members') as $key => $m) {
                $max_i = max(intval($m['_id']), $max_i);
            }
            foreach ($this->request->data('MongoReplSet.Members') as $key => $m) {
                $m_id = intval($m['_id']);
                $members[$i]['_id'] = isset($existed_ids[$m_id]) ? ++$max_i : $m_id;
                $members[$i]['host'] = $m['host'] . ':' . $m['port'];
                $existed_ids[$m_id] = $members[$i]['_id'];
                $i ++;
            }

            if ($this->request->data('MongoReplSet.force')) {
                $command_line = 'var conf = rs.conf(); conf.members=' . json_encode($members) . ';rs.reconfig(conf, {force: true});';
                CakeLog::info("reconfig command line forced:" . $command_line);
                $host_port = MongoReplsetMonitor::getReplsetMemberName($rs['rs_name'], $rs['members']);
                if (empty($host_port)) {
                    $host_port = MongoReplsetMonitor::getReplsetMemberName($rs['rs_name'], $this->request->data('MongoReplSet.Members'));
                }
                if (empty($host_port)) {
                    $this->renderJsonWithError('Not found valid mongo replset member.', 'ERROR-NO-VALID-MEMBER');
                }
                list($host, $port) = explode(':', $host_port);
                $resp = MongoShell::callWindowsCmd($host, $port, $command_line, true);
                if (empty($resp['ok'])) {
                    $this->renderJsonWithError($resp['errmsg'], $resp['code'], $resp);
                }
                sleep(15);

            } else {
                $command_line = 'var conf = rs.conf(); conf.members=' . json_encode($members) . ';rs.reconfig(conf);';
                CakeLog::info("reconfig command line:" . $command_line);
                try{
                    $manager = new MongoReplsetConnection($rs['rs_name'], $rs['members']);
                    $status = $manager->connect(false);
                    if (empty($status['success'])) {
                        $this->renderJsonWithError($status['message'], $status['code'], $status['data']);
                    }
                    $resp = $manager->getConnection()->execute($command_line);
                    if (empty($resp['ok'])) {
                        $this->renderJsonWithError($resp['errmsg'], 'ERROR-RECONFIG', $resp);
                    }
                } catch(Exception $e) {
                    $this->renderJsonWithError($e->getMessage(), 'EXCEPTION-reconfig');
                }
            }
            $rs['data'] = $this->request->data('MongoReplSet.Members');
            CakeLog::info("saveOrUpdateReplset rs:" . json_encode($rs));
            $this->MongoReplSet->saveOrUpdateReplset($rs);
            $this->renderJsonWithSuccess($resp);

        }

        // show reconfig popup UI.
        $this->MongoReplSet->id = $id;
        $rs = $this->MongoReplSet->read();

        // if current replica set works well, then not check 'force'
        $manager = new MongoReplsetConnection($rs['MongoReplSet']['rs_name'], $rs['MongoReplSet']['members']);
        $status = $manager->connect(true);
        CakeLog::info("reconfig connect status:" . json_encode($status));

        foreach($rs['MongoReplSet']['members'] as $key => $m) {
            $instance = $this->TimingMongoInstance->findByHost($m['pc_host']);
            CakeLog::info("TimingMongoInstance, found:" . json_encode($instance));
            CakeLog::info("TimingMongoInstance, check member:" . json_encode($m));
            if(!empty($instance) && ($instance['TimingMongoInstance']['ip'] != $m['host'] || $instance['TimingMongoInstance']['port'] != $m['port']))
                $rs['MongoReplSet']['members'][$key] = array_merge($m, array('changed_ip' => $instance['TimingMongoInstance']['ip'], 'changed_port' => $instance['TimingMongoInstance']['port']));
        }
        $this->set('replset', $rs);
        $this->set('replset_status', $status['success']);

    }

}
