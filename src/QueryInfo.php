<?php

class QueryInfo {
	private array $info;

	public function __construct(array $info) {
		$info["hostip"] = gethostbyname($info["hostname"]);
		if (isset($info["plugins"])) {
			// Server give us server engine and plugins in one string, so remove server engine from plugins
			$temp = explode(":", $info["plugins"]);
			if (isset($temp[1]) && $temp[1] !== "") {
				// Server should not give us plugins without server engine, but just in case a server is broken lol
				if (!isset($info["server_engine"])) {
					$info["server_engine"] = $temp[0];
				}

				$info["plugins"] = [];
				foreach (explode(";", $temp[1]) as $plugin) {
					if ($plugin === "") {
						continue;
					}
					if ($plugin[0] === " ") {
						$plugin = substr($plugin, 1);
					}
					$data = explode(" ", $plugin);
					$info["plugins"]["name"] = $data[0];
					$info["plugins"]["version"] = $data[1] ?? "unknown";
				}
			} else {
				$info["plugins"] = [];
			}
		}
		if (isset($info["players"])) {
			$info["players"] = array_map(fn ($player) => $player, $info["players"]);
		}
		$this->info = $info;
	}

	public function getMotd() : string {
		return $this->info["motd"] ?? "";
	}

	public function getHostname() : string {
		return $this->info["hostname"] ?? "";
	}

	public function getGametype() : string {
		return $this->info["gametype"] ?? "";
	}

	public function getGameId() : string {
		return $this->info["game_id"] ?? "";
	}

	public function getVersion() : string {
		return $this->info["version"] ?? "";
	}

	public function getServerEngine() : string {
		return $this->info["server_engine"] ?? "";
	}

	public function getPlugins() : array {
		return $this->info["plugins"] ?? [];
	}

	public function getMap() : string {
		return $this->info["map"] ?? "";
	}

	public function getNumPlayers() : int {
		return (int) ($this->info["numplayers"] ?? 0);
	}

	public function getMaxPlayers() : int {
		return (int) ($this->info["maxplayers"] ?? 0);
	}

	public function getWhitelist() : bool {
		return ($this->info["whitelist"] ?? "") == "on";
	}

	public function getHostIp() : string {
		return $this->info["hostip"] ?? "0.0.0.0";
	}

	public function getHostPort() : int {
		return (int) ($this->info["hostport"] ?? 19132);
	}

	public function getPlayers() : array {
		return $this->info["players"] ?? [];
	}
}
