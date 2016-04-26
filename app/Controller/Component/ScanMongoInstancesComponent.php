<?php
App::uses('Component', 'Controller');
App::uses('MongoReplsetMonitor', 'Lib');
App::uses('Ping', 'Lib');
App::uses('MongoShell', 'Lib');
/**
 * This class is used to scan mongo instances in LAN range.
 *
 * @package app.Component
 * @subpackage default
 * @author Neil.zhou
 */
class ScanMongoInstancesComponent extends Component
{

    /**
     * @desc scan computers which has installed ActiveTiming program in LAN.
     */
    public function scanAllLan($rs_name = '') {

        $success_members= array();
        $checked_members = array();
        $error_members = array();

        $list = $this->loadModel('TimingMongoInstance')->find('all');
        if(empty($list)) return array();
        foreach ($list as $item) {
            $item = current($item);
            $host = $item['ip'];
            $port = $item['port'];
            if (isset($checked_members[$host . ':'. $port])) {
                continue;
            }
            if (Ping::pingServer($host) && Ping::pingServerPort($host, $port)) {
                $this->checkEachMember($host, $port, '', $success_members, $error_members, $checked_members);
            }
        }

        $this->loadModel('MongoReplSet')->saveRsMembers($success_members);
        $this->loadModel('MongoReplSet')->saveRsMembers($error_members);

        return $success_members;
    }

    /**
     * This function is to scan the ip range in LAN
     * @return array
     * @author Neil.zhou
     **/
    public function scanRange($range, $port, $rs_name) {
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


    /**
     * check mongo instance status
     *
     * @param $host: string, ip address
     * @param $port: string, mongo instance listened port
     * @param $success_arr: referenced array, store successful member, the structure is:
     *     array(
     *        0 => array(
     *             'success' => true,
     *             'code' => 'SUCCESS',
     *             'message' => 'xxx',
     *             'data' => array(
     *
     *             ),
     *        )
     *     )
     * @param $error_err: referenced array, store failed member, structure is:
     *     array(
     *        'rs0' => array(
     *             'success' => false,
     *             'code' => 'ERROR-',
     *             'message' => 'xxx',
     *             'data' => array(
     *
     *             ),
     *        )
     *     )
     * @param $checked_arr: referenced array, store checked member, structure is:
     *  array(
     *      '127.0.0.1' => 1
     *      '127.0.0.2' => 1
     *  )
     *  @param $remove_no_repl: boolean, if false, then the member with no replset is added into success_arr or error_arr
     * @return void
     */
    public function checkEachMember($host, $port, $rs_name = '', &$success_arr = array(), &$error_arr=array(), &$checked_arr = array(), $remove_no_repl = true)
    {

        if (isset($checked_arr[$host . ':' . $port])) {
            return;
        }
        $check_member = array(
            array(
                'host' => $host,
                'port' => $port
            )
        );
        $check_status = MongoReplsetMonitor::getMembersStatus($rs_name, $check_member);

        if ($remove_no_repl
            && empty($check_status['success']) 
            && $check_status['code'] == 'ERROR-NO-RUNNING-WITH-REPLSET') 
        {
            return;
        }

        foreach ($check_status['data'] as $member) {
            $checked_arr[$member['check_name']] = 1;
        }

        if ($check_status['success']) {
            $success_arr[] = $check_status;
        } else {
            $rs_name_key = empty($check_status['rs_name']) ? 'No replset name' : $check_status['rs_name'];
            if (!isset($error_arr[$rs_name_key])) {
                $error_arr[$rs_name_key] = $check_status;

            } elseif (empty($error_arr[$rs_name_key]['init_replset'])) {
                $check_status['data'] = array_merge($error_arr[$rs_name_key]['data'], $check_status['data']);
                $error_arr[$rs_name_key] = array_merge($error_arr[$rs_name_key], $check_status);

            } else {

                $error_arr[$rs_name_key]['data'] = array_merge($error_arr[$rs_name_key]['data'], $check_status['data']);
            }
        }
    }

    public function loadModel($name) {
        return $this->_Collection->getController()->$name;
    }
    
} // END class ScanMongoInstancesComponent extends Component
