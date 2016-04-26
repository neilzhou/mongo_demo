<?php
class MongoReplsetStatus {

    const NO_ERROR = 'SUCCESS';

    const EMPTY_RESPONSE = 'ERROR-EMPTY';

    const CONNECT_FAILED = 'ERROR-CONNECT-FAILED';

    const REPLSET_NOT_RUNNING = 'ERROR-NOT-RUNNING';
    const REPLSET_NO_CONFIG = 'ERROR-NO-CONFIG';
    const REPLSET_NO_NAME = 'ERROR-NO-NAME';
    const REPLSET_NAME_NOT_MATCH = 'ERROR-NAME-NOT-MATCH';
    const REPLSET_NO_PRIMARY = 'ERROR-NO-PRIMARY';
    const REPLSET_REMOVED = 'ERROR-REMOVED';
    const REPLSET_NO_MEMBERS = 'ERROR-NO-MEMBERS';

    const PARSED_FAILED = 'ERROR-PARSE-FAILED';
    const PARSED_OTHERS = 'ERROR-OTHERS';

    const UNKNOWN = 'ERROR-UNKNOWN';

    public static function isSuccess($code){
        return $code == self::NO_ERROR;
    }

    public static function isMember($code) {
        $array = array(
            self::NO_ERROR => 1,
            self::REPLSET_NO_PRIMARY => 1
        );
        return isset($array[$code]);
    }

    public static function getMessage($code, $rs_name='') {
        $messages = self::mappingMessages($rs_name);
        CakeLog::info("get messages rsname[$rs_name], code[$code]:" . json_encode($messages));
        return empty($messages[$code]) ? '' : $messages[$code];
    }

    /**
     * @desc grep the reponse of mongo shell command to see what status code should be.
     */
    public static function statusCode($resp, $rs_name='', $check_rsname = true){
        if(empty($resp)) return self::EMPTY_RESPONSE;
        $errorsMapping = self::mappingConds($rs_name);
        //CakeLog::info("errorCode resp:" . $resp);
        foreach($errorsMapping as $key => $map) {
            //CakeLog::info("errors mapping: key:[$key], map:" . json_encode($map));
            if(($key == self::REPLSET_NO_NAME) && $check_rsname && empty($rs_name)) return self::REPLSET_NO_NAME;
            if(is_array($map)) {
                $is_matched = false;
                foreach($map as $m_key => $con) {
                    //CakeLog::info("sub map, mkey:[$m_key], cond:".json_encode($con));
                    if($m_key == 'not' && !self::matchError($resp, $con)) {
                        $is_matched = true;
                    } else if (is_int($m_key) && self::matchError($resp, $con)) {
                        $is_matched = true;
                    } else {
                         //if one condition does not match, then break
                        $is_matched = false;
                        break;
                    }
                }
                if($is_matched) return $key;
            } 
            elseif(self::matchError($resp, $map)){
                return $key;
            }
        }
        return self::UNKNOWN;
    }

    /**
     * @desc check if the member can be initiated as a replica set in UI.
     */
    public static function canBeInit($code) {
        $array = array(
            self::REPLSET_NO_CONFIG => 1,
            //self::REPLSET_REMOVED => 1,
        );
        return isset($array[$code]);
    }

    /**
     * @desc check if the member can be added into replica set.
     */
    public static function canBeAdded($code) {
    
        $array = array(
            self::REPLSET_NO_CONFIG => 1,
            self::REPLSET_REMOVED => 1,
        );
        return isset($array[$code]);
    }

    /**
     * @desc check if the response of mongo shell cmd can be parsed as array, so that we can parse it and get members details.
     */
    public static function canBeParse($code){
        $except_array = array(
            self::EMPTY_RESPONSE => 1,
            self::CONNECT_FAILED => 1,
            self::REPLSET_NAME_NOT_MATCH => 1,
            self::REPLSET_NO_NAME => 1
        );
        return ! isset($except_array[$code]);
    }

    private static function matchError($resp, $map_code) {
        return !empty($map_code) && strpos($resp, $map_code) !== false;
    }

    private static function mappingMessages($rs_name = ''){
        return array(
            self::EMPTY_RESPONSE => 'Connect mongoDB failed.',
            self::CONNECT_FAILED => 'Connect mongoDB failed.',
            self::REPLSET_NO_NAME => 'Does not get replica set name.',
            self::REPLSET_NOT_RUNNING => 'Does not running with --replSet.',
            self::REPLSET_NO_CONFIG => 'Does not have a vallid replica set config.',
            self::REPLSET_REMOVED=> 'Does not have a vallid replica set config.',
            self::REPLSET_NO_PRIMARY => 'The replica set has no primary member.',
            self::REPLSET_NAME_NOT_MATCH=> 'Couldn\'t connect to replset name: ' . $rs_name,
            self::NO_ERROR => 'OK!',
            self::UNKNOWN => ''
        );
    }

    private static function mappingConds($rs_name = ''){
        $rs_conds = $rs_name ? array(
            '"set"',
            'not' => '"set" : "'.$rs_name.'"'
        ) : array('not' => '"set"');

        // should not change sequence below, conditions match one by one.
        return array(
            self::CONNECT_FAILED => 'connection attempt failed',
            self::REPLSET_NOT_RUNNING => 'not running with --replSet',
            self::REPLSET_NO_CONFIG => 'run rs.initiate(',
            self::REPLSET_REMOVED => '"stateStr" : "REMOVED"',
            self::REPLSET_NO_NAME=> '',
            self::REPLSET_NAME_NOT_MATCH => $rs_conds,
            self::REPLSET_NO_MEMBERS => array(
                'not' => '"members"'
            ),
            self::NO_ERROR => '"stateStr" : "PRIMARY"' ,
            self::REPLSET_NO_PRIMARY => '"members"',
            self::PARSED_OTHERS => '"ok"'
        );
    }
}
