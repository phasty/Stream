<?php
namespace Phasty\Stream\Socket {
    class Tcp extends \Phasty\Stream\Stream {
        public function __construct($address = null) {
            parent::__construct();
            // $this->setBlocking(false);
            if (is_null($address)) {
                return;
            }
            if (is_resource($address)) {
                $this->open($address);
            } else {
                // TODO: open socket by address
            }

        }
    }
}
