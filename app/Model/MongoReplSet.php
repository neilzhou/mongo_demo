<?php
class MongoReplSet extends AppModel{
	public $primaryKey = '_id';

	//var $useDbConfig = 'mongo';

    var $mongoSchema = array(
            'rs_name' => array('type'=>'string'),
            'members'=>array(
                array(
                    '_id'  => array('type' => 'integer'),
                    'host' => array('type' => 'string'),
                    'port' => array('type' => 'string'),
                    'status' => array('type' => 'boolean'),
                )
            ),
            'created'=>array('type'=>'datetime'),
            'modified'=>array('type'=>'datetime'),
    );

    public function saveOrUpdateReplset($data){
        $id = empty($data['_id']) ? 0 : $data['_id'];
        $id = empty($id) ? (empty($data['id']) ? 0 : $data['id']) : $id;
        $saveData = array(
            'rs_name' => $data['rs_name']
        );
        $hosts = array();
        if(! empty($id)) {
            $this->id = $id;
            $exists = $this->read();
            $members = $exists[$this->alias]['members'];
            foreach($members as $m) {
                $hosts[$m['host'] . ':' . $m['port']] = empty($m['pc_host']) ? '' : $m['pc_host'];
            }
        }
        foreach ($data['data'] as $m) {
            $pc_host = empty($hosts[$m['host'] . ':' . $m['port']]) ? '' : $hosts[$m['host'] . ':' . $m['port']];
            if(empty($pc_host)) {
                $pc_host = gethostbyaddr($m['host']);
            }
            CakeLog::info("saveOrupdateReplset pc_host:[$pc_host]");
            $saveData['members'][] = array(
                '_id' => $m['_id'],
                'host' => $m['host'],
                'port' => $m['port'],
                'pc_host' => $pc_host,
                'status' => empty($m['success']) ? false : true
            );
        }
        if (empty($saveData['members'])) {
            return false;
        }
        if (empty($id)) {
            $this->create();
        } else {
            $saveData['_id'] = $id;
        } 
        return $this->save($saveData);
    }

    public function saveMultiReplset($rses) {
        if (empty($rses)) {
            return false;
        }
        foreach ($rses as $r) {
            if (empty($r['data'])) {
                continue;
            }
            $members = array();
            foreach ($r['data'] as $m) {
                $members[] = array(
                    '_id' => $m['_id'], 
                    'host' => $m['host'], 
                    'port' => $m['port'],
                    'pc_host' => gethostbyaddr($m['host']),
                    'status' => $m['success'],
                );
            }
            $this->create();
            $data = array(
                'rs_name' => $r['rs_name'],
                'members' => $members
            );
            $this->save($data);
        }
    }

    public function saveRsMembers($rses) {
        if (empty($rses)) {
            return false;
        }
        //$timingModel = ClassRegistry::init('TimingMongoInstance');
        foreach ($rses as $r) {
            if (empty($r) || empty($r['data'])) {
                return false;
            }
            $members = array();
            foreach ($r['data'] as $m) {
                //$host = $timingModel->field('host', array(
                    //'ip' => $m['host'],
                    //'port' => $m['port']
                //));
                $host = gethostbyaddr($m['host']);
                $members[] = array(
                    'host' => $m['host'], 
                    'port' => $m['port'],
                    '_id'  => $m['_id'],
                    'pc_host'  => $host,
                    'status' => empty($m['success']) ? false : true
                );
            }

            //$data = array(
                //'rs_name' => $r['rs_name'],
                //'members' => $members
            //);
            //$this->MongoReplSet->create();
            //$this->MongoReplSet->save($data);
            $dbSource = $this->getDataSource();
            $collection = $dbSource->getMongoCollection($this);
            CakeLog::info("members:" . json_encode($members));
            $status = $collection->update(
                array(
                    'rs_name' => $r['rs_name'],
                    'members' => array(
                        '$elemMatch' => array(
                            'host' => $members[0]['host'],
                            'port' => $members[0]['port']
                        )
                    )
                ), 
                array(
                    '$addToSet' => array(
                        'members' => array(
                            '$each' => $members
                        )
                    )
                ), 
                array(
                    'multiple' => true, 
                    'upsert' => true
                )
            );
            CakeLog::info("update status:" . json_encode($status));
        }
    }
}
