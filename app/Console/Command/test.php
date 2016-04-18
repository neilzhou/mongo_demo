<?php

/*
 *     Simple php udp socket client
 *     */

//Reduce errors
error_reporting(~E_WARNING);

//$server = '127.0.0.1';
$server = '255.255.255.255';
$port = 9999;

if(!($sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP)))
{
    $errorcode = socket_last_error();
    $errormsg = socket_strerror($errorcode);

    die("Couldn't create socket: [$errorcode] $errormsg \n");
}

$setopt = socket_set_option($sock, SOL_SOCKET, SO_BROADCAST, 1); 
if ($setopt == false) { die(socket_strerror(socket_last_error())); } 

echo "Socket created \n";

//Communication loop
while(1)
{
    //Take some input to send
    echo 'Enter a message to send : ';
    $input = fgets(STDIN);

    //Send the message to the server
    if( ! socket_sendto($sock, $input , strlen($input) , 0 , $server , $port))
        //if( ! socket_sendto($sock, $input , strlen($input) , MSG_DONTROUTE , $server , $port))
    {
        $errorcode = socket_last_error();
        $errormsg = socket_strerror($errorcode);

        die("Could not send data: [$errorcode] $errormsg \n");
    }

    //Now receive reply from server and print it
    //if(socket_recv ( $sock , $reply , 2045 , MSG_WAITALL ) === FALSE)
    //if(socket_recv ( $sock , $reply , 2045 , MSG_PEEK) === FALSE)
    //{
    //$errorcode = socket_last_error();
    //$errormsg = socket_strerror($errorcode);

    //die("Could not receive data: [$errorcode] $errormsg \n");
    //}

    //echo "Reply : $reply";
    echo "finished!";
}
