<?php
class ReplsetInitModalController extends AppController {
    public $components = array('RequestHandler');
    public $uses = array('MongoReplSet');

    public function view($id){
        $this->MongoReplSet->id = $id;
        $rs = $this->MongoReplSet->read();
        $members = array_map(function($m){ return $m['host'] . ':' . $m['port'];}, $rs['MongoReplSet']['members']);
        $members = array_combine($members, $members);
        $this->set('id', $id);
        $this->set('members', $members);
    }

    public function add($id) {
        $host_port = $this->request->data('MongoReplSet.host');
        list($host, $port) = split(':', $host_port);

        $this->MongoReplSet->id = $id;
        $rs = $this->MongoReplSet->read();
        $members = array("_id" => $rs['MongoReplSet']['rs_name']);

        foreach ($rs['MongoReplSet']['members'] as $i=>$m) {
            $members['members'][] = array("_id" => $i, "host" => $m['host'] . ':' . $m['port']); 
        }

        $command_line = 'var conf = ' . json_encode($members) . ';rs.initiate(conf);';

        $resp = $this->MongoReplSet->callWindowsMongoCmd($host, $port, $command_line);
        if (strpos($resp, '"ok" : 1') !== false){
            $this->renderJsonWithSuccess(array(), "Initialize replica set configuration successfully!");
        }
        $this->renderJsonWithError("Failed to initiate replica set configuration.", 'ERROR-CALL-INITIATE', array($resp));
    }
}
