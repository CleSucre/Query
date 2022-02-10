<?php

const DEBUG = 0;

if (!function_exists('readline')) {
    function readline($question) {
        $fh = fopen('php://stdin', 'r');
        echo $question;
        $userInput = trim(fgets($fh));
        fclose($fh);
        return $userInput;
    }
}

/*
 * from: https://github.com/jasonwynn10/libpmquery
 */
function query(string $host, int $port, int $timeout = 4) : bool|array{
    $socket = @fsockopen('udp://'.$host, $port, $errno, $errstr, $timeout);
    if($errno) {
        fclose($socket);
        if (DEBUG > 0) {
            echo $errstr . " " . $errno;
        }
        return false;
    } else if($socket === false) {
        if (DEBUG > 0) {
            echo $errstr . " " . $errno;
        }
        return false;
    }
    stream_Set_Timeout($socket, $timeout);
    stream_Set_Blocking($socket, true);
    $OFFLINE_MESSAGE_DATA_ID = \pack('c*', 0x00, 0xFF, 0xFF, 0x00, 0xFE, 0xFE, 0xFE, 0xFE, 0xFD, 0xFD, 0xFD, 0xFD, 0x12, 0x34, 0x56, 0x78);
    $command = \pack('cQ', 0x01, time());
    $command .= $OFFLINE_MESSAGE_DATA_ID;
    $command .= \pack('Q', 2);
    $length = \strlen($command);
    if($length !== fwrite($socket, $command, $length)) {
        if (DEBUG > 0) {
            echo "Failed to write on socket. " . E_WARNING;
        }
        return false;
    }
    $data = fread($socket, 4096);
    fclose($socket);
    if(empty($data) or $data === false) {
        if (DEBUG > 0) {
            echo "Server failed to respond " . E_WARNING;
        }
        return false;
    }
    if(substr($data, 0, 1) !== "\x1C") {
        if (DEBUG > 0) {
            echo "First byte is not ID_UNCONNECTED_PONG. " . E_WARNING;
        }
        return false;
    }
    if(substr($data, 17, 16) !== $OFFLINE_MESSAGE_DATA_ID) {
        if (DEBUG > 0) {
            echo "Magic bytes do not match.";
        }
        return false;
    }
    $data = \substr($data, 35);
    $data = \explode(';', $data);
    //plugins are somewhere in here, but I have no idea how to read it :/
    return [
        'GameName' => $data[0],
        'MOTD' => $data[1],
        'Protocol' => $data[2],
        'Version' => $data[3],
        'Players' => $data[4],
        'MaxPlayers' => $data[5],
        'Unknown2' => $data[6], // TODO: What is this?
        'Software' => $data[7],
        'GameMode' => $data[8],
        'Unknown3' => $data[9] // TODO: What is this?
    ];
}

$address = readline('Adresse (127.0.0.1): ');
if ($address == "") {
    $address = "127.0.0.1";
}

$port = readline('Port (19132): ');
if ($port == "") {
    $port = 19132;
}

$timeout = readline('Timeout (4): ');
if ($timeout == "") {
    $timeout = 4;
}

echo "Starting to query server " . $address . ":" . $port . " with a time out of " . $timeout . " secondes ...\n";
$result = query($address, $port, $timeout);
if (!$result) {
    echo "No server found\n";
    exit(0);
}

foreach ($result as $key => $item) {
    echo $key . ": " . $item . "\n";
}
exit(0);
