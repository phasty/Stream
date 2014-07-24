<?php
namespace Phasty\Stream\Socket {
    class Server extends \Phasty\Stream\Stream {
        public function __construct($address = null) {
            parent::__construct();
            if ($address) {
                $this->open($address);
            }
        }
        public function __destruct() {
            if ($this->isOpened()) {
                $this->close();
            }
        }
        public function open($address) {
            $address = "tcp://$address";
            $stream = stream_socket_server($address, $errno, $errstr);
            if ($stream === false) {
                throw new Exception("Error ($errno): $errstr");
            }
            parent::open($stream);
            $this->setBlocking(false);
            $this->on("rawdata", "onRawData");
        }

        public function onRawData() {
            $childSocket = stream_socket_accept($this->resource, 0);
            if (!$childSocket) {
                return;
            }
            $childStream = new \Phasty\Stream\Socket\Tcp($childSocket);
            if ($this->streamSet) {
                $this->streamSet->addReadStream($childStream);
            }
            $this->trigger("connected", (object)[ "connection" => $childStream ]);
        }
    }
}
