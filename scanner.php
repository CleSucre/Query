<?php

if (!function_exists('readline')) {
    function readline($question) {
        $fh = fopen('php://stdin', 'r');
        echo $question;
        $userInput = trim(fgets($fh));
        fclose($fh);
        return $userInput;
    }
}

function query(string $host, int $port, float $timeout = 1) : bool|array{
    $socket = fsockopen('udp://'.$host, $port, $errno, $errstr, $timeout);
    if($errno) {
        fclose($socket);
        return false;
    } else if($socket === false)
        return false;
    stream_set_timeout($socket, $timeout);
    stream_set_blocking($socket, true);
    $OFFLINE_MESSAGE_DATA_ID = pack('c*', 0x00, 0xFF, 0xFF, 0x00, 0xFE, 0xFE, 0xFE, 0xFE, 0xFD, 0xFD, 0xFD, 0xFD, 0x12, 0x34, 0x56, 0x78);
    $command = pack('cQ', 0x01, time()) . $OFFLINE_MESSAGE_DATA_ID . pack('Q', 2);
    $length = strlen($command);
    if($length !== fwrite($socket, $command, $length))
        return false;
    $data = fread($socket, 4096);
    fclose($socket);
    if(empty($data) or $data === false)
        return false;
    if(substr($data, 0, 1) !== "\x1C")
        return false;
    if(substr($data, 17, 16) !== $OFFLINE_MESSAGE_DATA_ID)
        return false;
    $data = substr($data, 35);
    $data = explode(';', $data);
    return [
        'GameName' => $data[0],
        'MOTD' => $data[1],
        'Version' => $data[3],
        'Players' => $data[4],
        'Software' => $data[7]
    ];
}

$address = readline('Server adresse (DNS or IP): ');
$minPort = readline('Minimum Port: ');
$maxPort = readline('Maximum Port: ');
$info = null;
while ($info === null) {
    $info = strtolower(readline('Do you want any informations about servers that you will found? (Y/N): '));
    if ($info == "y")
        $info = true;
    else if ($info == "n")
        $info = false;
    else
        $info = null;
}
$ports = $maxPort - $minPort;
echo "Scanning " . $ports . " ports on server " . gethostbyname($address) . "\nEstimated time: " . gmdate("H:i:s", $ports) . "\n";
$servers = 0;
for ($i=$minPort; $i<$maxPort; $i++) {
    $result = query($address, $i);
    if (!$result)
        continue;
    $servers++;
    echo "Server found on port " . $i . "\n";
    if ($info) {
        foreach ($result as $key => $item)
            echo $key . ": " . $item . "\n";
        echo "\n\n";
    }
}
echo $servers . " servers found.";
exit(0);
?>
