<?php

    //App::uses('AppShell', 'Console');
    /**
     * BroadcastShell Class
     *
     * @package default
     * @subpackage default
     * @author Neil.zhou
     */
    class BroadcastShell extends AppShell
    {
        const PORT = 8888;
        const SLEEP_SEC = 5;
        public $uses = array('TimingMongoInstance');

        function main()
        {
            $server = '255.255.255.255';
            $port = self::PORT;
            if (!($sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP))) {
                $errorcode = socket_last_error();
                $errormsg = socket_strerror($errorcode);
                $error = "Send broadcast, couldn't create socket: [$errorcode], $errormsg";
                CakeLog::error($error);
                die($error);
            }

            if(!socket_set_option($sock, SOL_SOCKET, SO_BROADCAST, 1)) {
            
                $errorcode = socket_last_error();
                $errormsg = socket_strerror($errorcode);
                $error = "Send broadcast, couldn't set broadcast option: [$errorcode], $errormsg";
                CakeLog::error($error);
                die($error);
            }

            $local_ip = getHostByName(getHostName());
            while (true) {
                $message = json_encode(array("ip" => $local_ip, "port" => 27017));
                if (!socket_sendto($sock, $message, strlen($message), 0, $server, $port)) {
                    $errorcode = socket_last_error();
                    $errormsg = socket_strerror($errorcode);
                    $error = "Send broadcast, couldn't send to: [$errorcode], $errormsg";
                    CakeLog::error($error);
                    die($error);
                }

                CakeLog::info($message);
                //echo "\n Send success:" . $message;
                sleep(self::SLEEP_SEC); // sleep 5 seconds.
            }
        }

        /**
         * listen broadcast message and save into db.
         *
         * @return void
         */
        public function listen()
        {
            $server = "0.0.0.0";
            $port = self::PORT;

            if (!($sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP))) {

                $errorcode = socket_last_error();
                $errormsg = socket_strerror($errorcode);
                $error = "Listen broadcast, couldn't create socket: [$errorcode], $errormsg";
                CakeLog::error($error);
                die($error);
            }

            // Bind the source address
            if (!socket_bind($sock, $server, $port)) {
                $errorcode = socket_last_error();
                $errormsg = socket_strerror($errorcode);
                $error = "Listen broadcast, couldn't bind socket: [$errorcode], $errormsg";
                CakeLog::error($error);
                die($error);
            }

            while (true) {
                // Receive some data
                if(($len = socket_recvfrom($sock, $buf, 512, 0, $remote_ip, $remote_port)) === false) {
                    $errorcode = socket_last_error();
                    $errormsg = socket_strerror($errorcode);
                    $error = "Listen broadcast, couldn't receive from: [$errorcode], $errormsg";
                    CakeLog::error($error);
                } else {
                    $message = "Listen broadcast, buf: $buf, remote ip: [$remote_ip], remote port: [$remote_port]";
                    CakeLog::info($message);

                    $json = json_decode($buf, true);
                    if (!$json) {
                        CakeLog::info("Listen broadcast, decode json false.");
                        continue;
                    }

                    $this->TimingMongoInstance->create();
                    $id = $this->TimingMongoInstance->saveOrUpdate($json['ip'], $json['port']);
                    CakeLog::info("Listen broadcast, saveOrUpdate TimingMongoInstance id: [$id]");
                    //echo "\n" . $message;
                }

            }
        }
        
    } // END class 
