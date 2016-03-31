<?php
if (!empty($rs_list)) {
    // code...
    foreach ($rs_list as $rs) {
        echo $this->element('../MongoReplSet/add', array('replset_status' => $rs));
    }
}
