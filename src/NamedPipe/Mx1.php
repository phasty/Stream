<?php
namespace Phasty\Stream\NamedPipe {
    use \Phasty\Log\File as log;
    class Mx1 {
        protected static $streams = [];
        /**
         * Send message to all pipes in directory, optionally using StreamSet
         *
         * @param string $fifoPath    Path to directory with pipes
         * @param mixed  $message     Message should be castable to string
         * @param mixed $useStreamSet If \Phasty\Stream\StreamSet use it for streams \
         *                            If false no need using streams \
         *                            If null use default StreamSet instance
         */
        public static function send($fifoPath, $message, $useStreamSet = false) {
            $fifos = glob("$fifoPath/*");
            $message = (string)$message;
            log::debug("Mx1 send to " . count($fifos) . " fifos");
            foreach ($fifos as $fifo) {
                if (!isset(self::$streams[ $fifo ])) {
                    self::$streams[ $fifo ] = new \Phasty\Stream\Reader\NamedPipe($fifo);
                    if ($useStreamSet !== false) {
                        if (is_null($useStreamSet)) {
                            self::$streams[ $fifo ]->setStreamSet(\Phasty\Stream\StreamSet::instance());
                        } else {
                            self::$streams[ $fifo ]->setStreamSet($useStreamSet);
                        }
                    }
                    self::$streams[ $fifo ]->on("close", function() use($fifo) {
                        unset(self::$streams[ $fifo ]);
                    });
                }
                self::$streams[ $fifo ]->write($message);
            }
        }
    }
}
