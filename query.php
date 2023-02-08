<?php

class Query {
    /** @var string[]|null|string */
    private array|null|string $server;

    /** @var string[] */
    private array $fetchedData;

    public function __construct($host = "", $port = 19132) {
        $this->server = $this->UT3Query($host, $port);
        if ($this->server === null) {
            return;
        }

        $this->fetchedData = [
            "hostname" => $this->server['hostname'],
            "gametype" => $this->server['gametype'],
            "game_id" => $this->server['game_id'],
            "version" => $this->server['version'],
            "server_engine" => $this->server['server_engine'],
            "plugins" => explode(";", explode(":", $this->server['plugins'])[1]), //remove server software name
            "map" => $this->server['map'],
            "numplayers" => $this->server['numplayers'],
            "maxplayers" => $this->server['maxplayers'],
            "whitelist" => $this->server['whitelist'] == "on",
            "hostip" => $this->server['hostip'],
            "hostport" => $this->server['hostport'],
            "players" => $this->server['players'],
        ];
    }

    public function putServer(string $host = "localhost", int $port = 19132) : self {
        $this->server = $this->UT3Query($host, $port);

        return $this;
    }

    public function isOnline() : bool {
        return $this->server !== null;
    }

    /**
     * @return string[]
     */
    public function getAll() : array {
        return $this->fetchedData;
    }

    public function getHostname() : string {
        return $this->fetchedData['hostname'];
    }

    public function getGametype() : string {
        return $this->fetchedData['gametype'];
    }

    public function getGameId() : string {
        return $this->fetchedData['game_id'];
    }

    public function getVersion() : string {
        return $this->fetchedData['version'];
    }

    public function getServerEngine() : string {
        return $this->fetchedData['server_engine'];
    }

    /**
     * @return string[]
     */
    public function getPlugins() : array {
        return $this->fetchedData['plugins'];
    }

    public function getMap() : string {
        return $this->fetchedData['map'];
    }

    public function getNumplayers() : int {
        return $this->fetchedData['numplayers'];
    }

    public function getMaxplayers() : int {
        return $this->fetchedData['maxplayers'];
    }

    public function getWhitelist() : bool {
        return $this->fetchedData['whitelist'];
    }

    public function getHostip() : string {
        return $this->fetchedData['hostip'];
    }

    public function getHostport() : string {
        return $this->fetchedData['hostport'];
    }

    /**
     * @return string[]
     */
    public function getPlayers() : array {
        return $this->fetchedData['players'];
    }

    private function UT3Query(string $host, int $port) : array|null|string {
        $socket = @fsockopen("udp://" . $host, $port);
        if (!$socket) {
            return null;
        }
        if (!@fwrite($socket, "\xFE\xFD\x09\x10\x20\x30\x40\xFF\xFF\xFF\x01")) {
            return null;
        }
        $challenge = @fread($socket, 1400);
        if (!$challenge) {
            return null;
        }
        $challenge = substr(preg_replace("/[^0-9-]/si", "", $challenge), 1);
        $query = sprintf(
            "\xFE\xFD\x00\x10\x20\x30\x40%c%c%c%c\xFF\xFF\xFF\x01",
            $challenge >> 24,
            $challenge >> 16,
            $challenge >> 8,
            $challenge >> 0
        );
        if (!@fwrite($socket, $query)) {
            return null;
        }
        $response = [];
        $response[] = @fread($socket, 2048);
        $response = implode($response);
        $response = substr($response, 16);
        $response = explode("\0", $response);
        array_pop($response);
        array_pop($response);

        $result = [];
        for ($i = 0; $i < 27; $i++) {
            if ($i % 2) {
                $result[$response[$i - 1]] = $response[$i];
            }
        }
        $result['players'] = array_slice($response, 27);

        return $result;
    }
}
