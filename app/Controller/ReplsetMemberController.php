<?php
class ReplsetMemberController extends AppController {

    public $uses = array('MongoReplSet');

/**
 * Action for plugin documentation home page
 *
 * @return void
 */
	public function add($id) {
        $data = $this->request->data['MongoReplSet'];
        if (empty($data) || empty($data['host']) || empty($data['port'])) {
            $this->renderJsonWithError("The request params is invalid.", 'ERROR-EMPTY-PARAMS', $data);
        }

        $this->MongoReplSet->id = $id;
        $rs = $this->MongoReplSet->read();
        if (empty($rs)) {
            $this->renderJsonWithError('The replica set does not exist, please check.', 'ERROR-EMPTY-RS', $rs);
        }
        $rs = $rs['MongoReplSet'];
        $status = $this->MongoReplSet->connectRs($rs['rs_name'], $rs['members'], false);
        if (empty($status['success'])) {
            $this->renderJsonWithError($status['message'], $status['code'], $status['data']);
        }
        try {
            $conn = $this->MongoReplSet->getConnection();
            $rs_status = $conn->execute('rs.conf()');
            CakeLog::info("config tstatus:" . json_encode($rs_status));
            $votes_threshold = 7;
            $max_i = 0;
            if ($rs_status['members']) {
                foreach ($rs_status['members'] as $m) {
                    if ($m['votes']) {
                        $votes_threshold --;
                    }
                    $max_i = $m['_id'];
                }
            }
            $max_i ++;
            if ($votes_threshold > 0) {
                $cmd_opts = array(
                    'host' => $data['host'] . ':' . $data['port'],
                    '_id' => $max_i,
                );
                $cmd_line = 'rs.add(' . json_encode($cmd_opts) . ')';
            } else {
                $cmd_opts = array(
                    'host' => $data['host'] . ':' . $data['port'],
                    'votes' => 0,
                    'priority' => 0,
                    '_id' => $max_i,
                );
                $cmd_line = 'rs.add(' . json_encode($cmd_opts) . ')';
            }
            $result = $conn->execute($cmd_line);
            if ($result['ok']) {
                $new_member = array(
                    'host' => $data['host'], 
                    'port' => $data['port'], 
                    '_id' => $max_i,
                    'status' => true
                );
                if (!in_array($new_member, $rs['members'])) {
                    $rs['members'][] = $new_member;
                    $this->MongoReplSet->save($rs);
                }
                sleep(5);
                $this->renderJsonWithSuccess($rs);
            }
            $this->renderJsonWithError($result['errmsg'], 'ERROR-ADD-MEMBER', $result);
        } catch (Exception $e) {
            $this->renderJsonWithError($e->getMessage(), 'ERROR-EXCEPTION');
        }
	}

    public function delete($id) {
        $data = $this->request->data['MongoReplSet'];
        if (empty($data) || empty($data['host']) || empty($data['port'])) {
            $this->renderJsonWithError("The request params is invalid.", 'ERROR-EMPTY-PARAMS', $data);
        }

        $this->MongoReplSet->id = $id;
        $rs = $this->MongoReplSet->read();
        if (empty($rs)) {
            $this->renderJsonWithError('The replica set does not exist, please check.', 'ERROR-EMPTY-RS', $rs);
        }
        $rs = $new_rs = $rs['MongoReplSet'];

        $member_status = true;
        foreach ($new_rs['members'] as $key => $m) {
            if ($m['host'] == $data['host'] && $m['port'] == $data['port']) {
                $member_status = empty($m['status']) ? false : true;
                unset($new_rs['members'][$key]);
            }
        }

        $status = $this->MongoReplSet->connectRs($rs['rs_name'], $rs['members'], false);
        CakeLog::info("MongoReplSet status:" . json_encode($status));
        if (empty($status['success'])) {
            // delete only from DB when couldn't'connect to replica set and the member status is false.
            if ($member_status) {
                $this->renderJsonWithError($status['message'], $status['code'], $status['data']);
            }
            if (empty($new_rs['members'])) {
                $this->MongoReplSet->id = $id;
                $this->MongoReplSet->delete();
            } else {
                $this->MongoReplSet->save($new_rs);
            }
            $this->renderJsonWithSuccess($new_rs);

        }
        try {
            $conn = $this->MongoReplSet->getConnection();

            $cmd_line = $this->MongoReplSet->formatSql('rs.remove("%s")', $data['host'].':'.$data['port']);
            $result = $conn->execute($cmd_line);
            CakeLog::info("remove member status:" . json_encode($result));
            //echo json_encode($result);exit;
            if (!empty($result['ok']) || (is_string($result) && strpos($result, 'error: couldn\'t find ' . $data['host'] . ':' . $data['port'] . ' in') !== false )) {
                $this->MongoReplSet->save($new_rs);
                $this->renderJsonWithSuccess($new_rs);
            }
            $errmsg = is_array($result) ? $result['errmsg'] : $result;
            $this->renderJsonWithError($errmsg, 'ERROR-REMOVE-MEMBER', $result);
        } catch (Exception $e) {
            $this->renderJsonWithError($e->getMessage(), 'ERROR-EXCEPTION');
        }
    }

}
