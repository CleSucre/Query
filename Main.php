<?php

require __DIR__ . "/Query.php";

if (!function_exists('readline')) {
    function readline($question) {
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

echo "Starting to query server " . $address . ":" . $port . "\n";
$result = new Query($address, $port);
if (!$result->isOnline()) {
    echo "Server not fund\n";
    exit(0);
}

foreach ($result->getAll() as $key => $item) {
    if (is_array($item)) {
        $item = implode(", ", $item);
    }
    echo $key . ": " . $item . "\n";
}
exit(0);