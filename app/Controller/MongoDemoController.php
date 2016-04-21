<?php
class MongoDemoController extends AppController {

    public $uses = array('MongoReplSet', 'TimingMongoInstance');

/**
 * Action for plugin documentation home page
 *
 * @return void
 */
	public function index() {
        $list = $this->MongoReplSet->find('all');
        $rs_list = array();
        $time1 = microtime(true);
            //App::uses('MongodbSource', 'Mongodb.Model/Datasource');
            //$conf = array(
                //'database' => 'test',
                //'replicaset' => array(
                    //'host' => 'mongodb://10.107.122.183:27017, 10.107.122.184:27017', 
                    //'options' => array('replicaSet' => 'rs0')
                //),
            //);
            //new MongodbSource($conf, true);
        /*try{

        $m = new MongoClient("mongodb://10.107.122.184:27017, 10.107.122.183:27017", array("replicaSet" => "rs0", 'connect'=>true, 'connectTimeoutMS'=>'5000'));
        $db = $m->selectDB('test');
        $result = $db->execute('rs.status()');
        } catch(Exception $e){
        
            $result = array($e->getMessage());
        }*/
            $time2 = microtime(true);
            //CakeLog::info("test offset:" . ($time2 - $time1) . ", result:" . json_encode($result));

        if (!empty($list)) {
            // code...
            foreach($list as $rs) {
                $rs_db = $rs['MongoReplSet'];
                $rs_status = $this->MongoReplSet->checkReplSetConn($rs_db['rs_name'], $rs_db["members"]);
                $rs_status['id'] = $rs_db['_id'];

                CakeLog::info('demo index rs db:' . json_encode($rs_db));
                CakeLog::info('demo index rs status:' . json_encode($rs_status));
                $this->MongoReplSet->saveOrUpdateReplset($rs_status);
                $rs_list[] = $rs_status;
            }
        }
        $lan_hosts = $this->TimingMongoInstance->find('all');
        $select_options = array();
        if(!empty($lan_hosts)){
        
            foreach($lan_hosts as $h) {
                $h = $h['TimingMongoInstance'];
                $key = $h['ip'] . ':' . $h['port'];
                $select_options[$key] = $key;
            }
        }
        $this->set('rs_list', $rs_list);
        $this->set('select_options', $select_options);
	}

}
