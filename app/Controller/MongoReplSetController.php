<?php
class MongoReplSetController extends AppController {
    public $components = array('RequestHandler', 'ScanMongoInstances');
    public $uses = array('MongoReplSet');
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
                $status = $this->_scanAllLan($port, $rs_name);
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
                $status = $this->_scanMultiRequest($range, $port, $rs_name);
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
    private function _scanAllLan($port, $rs_name) {
        $status = array();
        $client_ip = $this->request->clientIp();
        if(empty($client_ip) || strpos($client_ip, '.') === false) $client_ip = '127.0.0.1';
        list($a_ip, $b_ip) = explode('.', $client_ip);

        $hosts = array(
            //array(
            //'from' => '10.0.0.0',
            //'to' => '10.255.255.255',
            //),
            //array(
            //'from' => '172.16.0.0',
            //'to' => '172.31.255.255',
            //),
            //array(
                //'from' => '192.168.0.0',
                //'to' => '192.168.255.255',
            //),
            array(
            'from' => "$a_ip.$b_ip.0.0",
            'to' => "$a_ip.$b_ip.255.255",
            ),
        );
        CakeLog::info('_scanAllLAN:' . json_encode($hosts) . ', rs_name:' . $rs_name . ', port:' . $port);
        foreach ($hosts as $range) {
            $status[] = $this->_scanMultiRequest($range, $port, $rs_name);
        }
        return $status;
    }

    private function _scanMultiRequest($range, $port, $rs_name) {
        if (empty($range) || empty($range['from']) || empty($range['to'])) {
            return false;
        }

        $mh = curl_multi_init();
        $curl = $data = array();

        $from_ip32_int = ip2long($range['from']);
        $to_ip32_int = ip2long($range['to']);
        if (($from_ip32_int === false) || ($to_ip32_int === false)) {
            return false;
        }

        $offset_int = ceil(($to_ip32_int - $from_ip32_int) / 50);
        $offset_int = $offset_int > 16 ? $offset_int : 16;

        for ($i = $from_ip32_int; $i <= $to_ip32_int; $i++) {
            $from_ip = long2ip($i);
            $i += $offset_int;//255 * 1;
            $end_ip = $i > $to_ip32_int ? long2ip($to_ip32_int) : long2ip($i);

            $url = Router::url(array(
                'controller' => 'mongo_replset_scan',
                'action' => 'index',
                'from' => urlencode($from_ip),
                'to' => urlencode($end_ip),
                'port' => $port,
                'rs_name' => $rs_name
            ), true);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30 * 60);
            curl_multi_add_handle($mh, $ch);
            $curl[] = $ch;
        }

        $active = null;
        $i = $j = $x = 0;
        do {
            $mrc = curl_multi_exec($mh, $active);
            $i ++;
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while($active && $mrc == CURLM_OK) {
            if (curl_multi_select($mh) == -1) {
                $x ++;
                //usleep(100);
            }
            do {
                $j++;
                $mrc = curl_multi_exec($mh, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        }
        
        CakeLog::info('scan multiple count['.count($curl).'] offset_int:['.$offset_int.'], i:' . $i . ', j:' . $j . ', x:' . $x);
        foreach ($curl as $c) {
            $data[] = curl_multi_getcontent($c);
            curl_multi_remove_handle($mh, $c);
        }
        curl_multi_close($mh);
        return $data;
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
            $status = $this->MongoReplSet->checkReplSetConn($data['rs_name'], $data['members']);
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

    private function _getHostPortForCmd($rs_name, $members){
        $status = $this->MongoReplSet->checkReplSetConn($rs_name, $members);
        CakeLog::info("_getHostPortForCmd status:" . json_encode($status));
        if (empty($status) || empty($status['data'])) {
            return false;
        }
        $host_port = '';
        foreach ($status['data'] as $m) {
            if ($m['success']) {
                $host_port = $m['check_name'];
                break;
            } elseif($m['code'] == 'ERROR-NO-PRIMARY' || $m['code'] == 'ERROR-OTHERS') {
                $host_port = $m['check_name'];
                break;
            }
        }
        CakeLog::info("_getHostPortForCmd hostport:" . $host_port);
        return $host_port;
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
                $host_port = $this->_getHostPortForCmd($rs['rs_name'], $rs['members']);
                if (empty($host_port)) {
                    $host_port = $this->_getHostPortForCmd($rs['rs_name'], $this->request->data('MongoReplSet.Members'));
                }
                if (empty($host_port)) {
                    $this->renderJsonWithError('Not found valid mongo replset member.', 'ERROR-NO-VALID-MEMBER');
                }
                list($host, $port) = explode(':', $host_port);
                $resp = $this->MongoReplSet->callWindowsMongoCmd($host, $port, $command_line, true);
                if (empty($resp['ok'])) {
                    $this->renderJsonWithError($resp['errmsg'], $resp['code'], $resp);
                }
                sleep(15);

            } else {
                $command_line = 'var conf = rs.conf(); conf.members=' . json_encode($members) . ';rs.reconfig(conf);';
                CakeLog::info("reconfig command line:" . $command_line);
                try{
                    $status = $this->MongoReplSet->connectRs($rs['rs_name'], $rs['members'], false);
                    if (empty($status['success'])) {
                        $this->renderJsonWithError($status['message'], $status['code'], $status['data']);
                    }
                    $resp = $this->MongoReplSet->getConnection()->execute($command_line);
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
        $this->MongoReplSet->id = $id;
        $rs = $this->MongoReplSet->read();
        $this->set('replset', $rs);

    }

}
