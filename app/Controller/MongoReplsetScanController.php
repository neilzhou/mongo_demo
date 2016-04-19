<?php
class MongoReplsetScanController extends AppController {
    public $components = array('RequestHandler', 'ScanMongoInstances');
    public $uses = array('MongoReplSet');
    //public $uses = array('ReplSetModel')

    /**
     * Action for plugin documentation home page
     *
     * @return void
     */
    public function index() {
        $from_ip = $this->request->param("named.from");
        $to_ip = $this->request->param("named.to");
        $port = $this->request->param("named.port");
        $rs_name = $this->request->param("named.rs_name");

        $from_ip32_int = ip2long($from_ip);
        $to_ip32_int = ip2long($to_ip);
        $result = array();

        $start_time = microtime(true);

        $checked_members = array();
        $result = array();
        $errors = array();
        for ($i = $from_ip32_int; $i <= $to_ip32_int; $i++) {
            $check_ip = long2ip($i);
            $check_status = array();

            if (isset($checked_members[$check_ip . ':' . $port])) {
                CakeLog::info("Scan skip $check_ip:$port for existed.");
                continue;
            }
            $time1 = microtime(true);
            if(
                $this->MongoReplSet->pingServer($check_ip) 
                && $this->MongoReplSet->pingServerPort($check_ip, $port)
            ) {
                $this->ScanMongoInstances->checkEachMember($check_ip, $port, $rs_name, $result, $errors, $checked_members);
            }

            $time2 = microtime(true);
            CakeLog::info("San host[$check_ip], port[$port] offset: " . ($time2 - $time1));
        }

        $this->MongoReplSet->saveRsMembers($result);
        $this->MongoReplSet->saveRsMembers($errors);
        $end_time = microtime(true);
        CakeLog::info("Scan end, fromip[$from_ip], toip[$to_ip], port[$port], rs_name[$rs_name] scan total num:[" . ($to_ip32_int - $from_ip32_int) . "] time:[$end_time], offset:" . ($end_time - $start_time));
        CakeLog::info("Scan end, scan result: " . json_encode($result));
        CakeLog::info("Scan end, scan checked members:" . json_encode($checked_members));
        CakeLog::info("Scan end, scan errors members:" . json_encode($errors));

        $this->renderJsonWithSuccess($result);
    }
}
