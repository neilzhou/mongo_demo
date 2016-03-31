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

            $cmd_line = $this->MongoReplSet->formatSql('rs.add("%s")', $data['host'].':'.$data['port']);
            $result = $conn->execute($cmd_line);
            if ($result['ok']) {
                $new_member = array(
                    'host' => $data['host'], 
                    'port' => $data['port'], 
                );
                if (!in_array($new_member, $rs['members'])) {
                    $rs['members'][] = $new_member;
                    $this->MongoReplSet->save($rs);
                }
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
        $rs = $rs['MongoReplSet'];
        $status = $this->MongoReplSet->connectRs($rs['rs_name'], $rs['members'], false);
        if (empty($status['success'])) {
            $this->renderJsonWithError($status['message'], $status['code'], $status['data']);
        }
        try {
            $conn = $this->MongoReplSet->getConnection();

            $cmd_line = $this->MongoReplSet->formatSql('rs.remove("%s")', $data['host'].':'.$data['port']);
            $result = $conn->execute($cmd_line);
            //echo json_encode($result);exit;
            if (!empty($result['ok'])) {
                $new_member = array(
                    'host' => $data['host'], 
                    'port' => $data['port'], 
                );
                if (($key = array_search($new_member, $rs['members'])) !== false) {
                    unset($rs['members'][$key]);
                    $this->MongoReplSet->save($rs);
                }
                $this->renderJsonWithSuccess($rs);
            }
            $this->renderJsonWithError(array($result), 'ERROR-REMOVE-MEMBER', $result);
        } catch (Exception $e) {
            $this->renderJsonWithError($e->getMessage(), 'ERROR-EXCEPTION');
        }
    }

}
