<?php

use thread\CustomThread;

require __DIR__ . "/QueryInfo.php";
require __DIR__ . "/thread/CustomThread.php";

if (!function_exists('readline')) {
	function readline($question): string
    {
		$fh = fopen('php://stdin', 'r');
		echo $question;
		$userInput = trim(fgets($fh));
		fclose($fh);

		return $userInput;
	}
}

$address = readline('Adresse (mcpe.plutonium.best): ');
if ($address == "") {
	$address = "mcpe.plutonium.best";
}

$port = readline('Port (19132): ');
if ($port == "") {
	$port = 19132;
}

echo "Starting to query server " . $address . ":" . $port . " at date " . date("Y-m-d H:i:s") . " timezone " . date_default_timezone_get() . "...\n\n";

$thread = new CustomThread(0, $address, $port);
$thread->start();
$thread->join();

if (!$thread->result) {
    echo "Unable to query server " . $address . ":" . $port . " at date " . date("Y-m-d H:i:s") . " timezone " . date_default_timezone_get() . "...\n";
    exit(1);
}

$result = new QueryInfo(json_decode($thread->result, true));

$results = [
	'motd' => $result->getMotd(),
	'gametype' => $result->getGametype(),
	'map' => $result->getMap(),
	'numPlayers' => $result->getNumPlayers(),
	'maxPlayers' => $result->getMaxPlayers(),
	'hostIp' => $result->getHostIp(),
	'hostPort' => $result->getHostPort(),
	'gameId' => $result->getGameId(),
	'version' => $result->getVersion(),
	'serverEngine' => $result->getServerEngine(),
	'plugins' => $result->getPlugins(),
	'players' => $result->getPlayers(),
	'whitelist' => $result->getWhitelist(),
	'hostName' => $result->getHostname(),
];

foreach ($results as $key => $value) {
	$message = ucfirst(str_replace(['_', 'Id'], [' ', 'ID'], $key)) . ': ';

	if (is_array($value) && count($value) > 0) {
		$message .= implode(', ', $value);
	} elseif (!empty($value)) {
		$message .= $value;
	} else {
		$message .= 'Unknown or server refused to send ' . $key;
	}

	echo $message . PHP_EOL;
}

exit(0);
