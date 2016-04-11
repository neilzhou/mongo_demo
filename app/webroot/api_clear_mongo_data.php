<?php

    $mongoSchema = array(
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
function rrmdir($src, $iteria = false) {
    $dir = opendir($src);
    while(false !== ( $file = readdir($dir)) ) {
        if (( $file != '.' ) && ( $file != '..' )) {
            $full = $src . '/' . $file;
            if ( is_dir($full) ) {
                rrmdir($full, true);
            }
            else {
                @unlink($full);
            }
        }
    }
    closedir($dir);
    $iteria && @rmdir($src);
    return true;
}

function renderJsonWithError($message = 'Invalid Request.', $code="ERROR-INIT", $data = array()) {
    $data = array(
        'success' => false,
        'code' => $code,
        'message' => $message,
        'data' => $data
    );
    renderJson($data);
}
function renderJsonWithSuccess($message = 'OK!', $code="SUCCESS", $data = array()) {
    $data = array(
        'success' => true,
        'code' => $code,
        'message' => $message,
        'data' => $data
    );
    renderJson($data);
}

function renderJson($data) {
    echo json_encode($data);
    exit;
}


$Wshshell= new COM('WScript.Shell');
$installDir= $Wshshell->regRead('HKEY_LOCAL_MACHINE\SOFTWARE\Active Network\ATMongodbService\InstallDir');
if (empty($installDir)) {
    renderJsonWithError('Not found installation directory on host:' . $_GET('host'));
}

//$yaml = $installDir . '\mongod.conf';
//$parsedConf = yaml_parse_file($yaml);
//echo "---";
//echo json_encode($parsedConf);
//exit;

$dirPath = $installDir . '\ATMongoData';
if (! @file_exists($dirPath)) {
    renderJsonWithSuccess();
}
$service_name = 'ATMongodService';
$status = win32_query_service_status($service_name);
if ($status['CurrentState'] != WIN32_SERVICE_STOPPED && $status['CurrentState'] != WIN32_SERVICE_STOP_PENDING) {
    if(($stop_status = win32_stop_service($service_name)) !== WIN32_NO_ERROR) {
        renderJsonWithError('Stop ' . $service_name . ' failed.', $stop_status);
    }
}


$max_i = 300;
$i = 0;
while(true) { 
    $status = win32_query_service_status($service_name);
    if ($status['CurrentState'] == WIN32_SERVICE_STOPPED) {
        break;
    }
    if ($i > $max_i) {
        break;
    }
    $i++;
    sleep(1);
}

$rmdir_status = rrmdir($dirPath);
if (!$rmdir_status) {
    renderJsonWithError('Remove directory failed', 'ERROR-REMOVE-DIR');
}

$start_status = win32_start_service($service_name);
//var_dump($start_status);

if ($start_status === WIN32_NO_ERROR) {
    renderJsonWithSuccess($start_status, $status);
}
renderJsonWithError('Restart service failed', 'ERROR-START-SERVICE');
