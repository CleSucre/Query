<?php

namespace thread;

use Thread;

class CustomThread extends Thread {
	const SESSION_ID = 2;
	const TYPE_HANDSHAKE = 0x09;
	const TYPE_STAT = 0x00;
	const TIMEOUT = 3;
	private string $error;
	public string $result;

	public function __construct(
		private int $id,
		public string $host,
		public int $port,
	) {
	}

	public function run() {
		$error = "";
		$socket = fsockopen('udp://' . $this->host, $this->port, $errno, $error, self::TIMEOUT);
		if (!$socket) {
			return;
		}
		stream_set_timeout($socket, self::TIMEOUT);
		stream_set_blocking($socket, true);
		if (!$token = $this->getToken($socket)) {
			return;
		}

		$packet = pack("c3N2", 0xFE, 0xFD, self::TYPE_STAT, self::SESSION_ID, $token);

		//add the full stat thingy.
		$packet = $packet . pack("c4", 0x00, 0x00, 0x00, 0x00);

		//write packet
		if (!fwrite($socket, $packet, strlen($packet))) {
			$this->error = "Unable to write to socket.";

			return;
		}

		//read packet header
		fread($socket, 16);

		//read the rest of the stream.
		$response = fread($socket, 2056);

		//split the response into 2 parts.
		$payload = explode("\x00\x01player_\x00\x00", $response);

		$info_raw = explode("\x00", rtrim($payload[0], "\x00"));
		//extract key->value chunks from info
		if (count($info_raw) % 2) {
			/*
			 * if you query a server multiple times in a row in a short period of time,
			 * some servers (minority) will return a response that doesn't contain
			 * all the expected data. Probably for security reasons?
			 */
			$this->error = "Server is unstable";

			return;
		}
		$info = [];
		foreach (array_chunk($info_raw, 2) as $pair) {
			if (!isset($pair[1])) {
				continue;
			}
			list($key, $value) = $pair;
			//strip possible color format codes from hostname
			if ($key == "hostname") {
				$key = "motd";
				$value = preg_replace('/[\x00-\x1F\x80-\xFF]./', '', $value);
			}
			$info[$key] = $value;
		}

		//set real hostname
		$info['hostname'] = $this->host;

		//get player data
		$info['players'] = [];
		if (isset($payload[1])) { //no players online
			$players_raw = rtrim($payload[1], "\x00");
			$players = [];
			if (!empty($players_raw)) {
				$players = explode("\x00", $players_raw);
			}
			$info['players'] = $players;
		}

		$this->error = "";
		$this->result = json_encode($info);
	}

	private function getToken($socket) : false|string {
		if (!$socket) {
			return false;
		}

		//build packet to get challenge.
		$packet = pack("c3N", 0xFE, 0xFD, self::TYPE_HANDSHAKE, self::SESSION_ID);

		//write packet
		if (fwrite($socket, $packet, strlen($packet)) === FALSE) {
			$this->error = "Unable to write to socket";

			return false;
		}

		//read packet.
		$response = fread($socket, 2056);

		if (empty($response)) {
			$this->error = "Unable to authenticate connection";

			return false;
		}

		$response_data = unpack("c1type/N1id/a*token", $response);

		if (empty($response_data['token'])) {
			$this->error = "Unable to authenticate connection.";

			return false;
		}

		return $response_data['token'];
	}
}
