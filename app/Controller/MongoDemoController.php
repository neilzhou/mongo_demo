<?php
class MongoDemoController extends AppController {

    public $uses = array('MongoReplSet');

/**
 * Action for plugin documentation home page
 *
 * @return void
 */
	public function index() {
        $list = $this->MongoReplSet->find('all');
        $rs_list = array();

        if (!empty($list)) {
            // code...
            foreach($list as $rs) {
                $rs = $rs['MongoReplSet'];
                $rs_status = $this->MongoReplSet->checkReplSetConn($rs['rs_name'], $rs["members"]);
                $rs_status['id'] = $rs['_id'];
                $rs_list[] = $rs_status;
            }
        }
        $this->set('rs_list', $rs_list);
	}

}
