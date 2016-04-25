<?php
App::uses('Component', 'Controller');
App::uses('MongoReplsetMonitor', 'Lib');
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
     * This function is to scan the ip range in LAN
     * @return void
     * @author Neil.zhou
     **/
    public function scanRange($from_ip, $to_ip, $port = 27017, $rs_name = '') {
    
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
