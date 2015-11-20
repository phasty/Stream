<?php
namespace Phasty\Stream {
    class StreamSet {
        const MESSAGE_LEN_FIELD_LENGTH_BYTES = 4;

        const E_NO_ERROR   = 0x0000;
        const E_NO_STREAMS = 0x0001;
        const E_STREAM_SELECT_ERROR = 0x0002;

        protected $streams = [
            "read"  => [],
            "write" => []
        ];

        protected $streamsObjects = [
            "read"  => [],
            "write" => []
        ];

        protected $running = false;
        protected $timerSet = null;

        public static function instance() {
            static $instance = null;
            if (!$instance instanceof static) {
                $instance = new static();
            }
            return $instance;
        }

        public function listen() {
            $exceptStreams = null;
            $this->running = true;
            while (true) {
                $readStreams  = $this->streams[ "read" ];
                $writeStreams = $this->streams[ "write" ];
                if (empty($readStreams) && empty($writeStreams)) {
                    return self::E_NO_STREAMS;
                }
                $timerInfo = $this->timerSet->getNearest();
                $timeout = ($timerInfo ? $timerInfo->time : 1) * 1000000;
                if (($streamCount = stream_select($readStreams, $writeStreams, $exceptStreams, 0, $timeout)) === false) {
                    return self::E_STREAM_SELECT_ERROR;
                }
                if ($streamCount === 0) {
                    if ($timerInfo) {
                        $timerInfo->timer->trigger("tick");
                    }
                } else {
                    foreach ($readStreams as $readStream) {
                        $streamIndex = array_search($readStream, $this->streams[ "read" ]);
                        if (feof($readStream)) {
                            $this->streamsObjects[ "read" ][ $streamIndex ]->close();
                        } else {
                            $readAgain = $this->streamsObjects[ "read" ][ $streamIndex ]->trigger("rawdata");
                        }
                    }
                    foreach ($writeStreams as $writeStream) {
                        $streamIndex = array_search($writeStream, $this->streams[ "write" ]);
                        // Stream was closed in previous foreach
                        if ($streamIndex === false) {
                            continue;
                        }
                        $this->streamsObjects[ "write" ][ $streamIndex ]->trigger("writeready");
                    }
                }
                if (!$this->running) {
                    return self::E_NO_ERROR;
                }
            }
        }

        public function stop() {
            $this->running = false;
        }

        protected function addStream(Stream $stream, $type) {
            $index = (int)$stream->stream();
            if ($stream->stream()) {
                $stream->setStreamSet($this);
                $this->streams[ $type ] [ intval($stream->stream()) ] = $stream->stream();
                $this->streamsObjects[ $type ][ intval($stream->stream()) ] = $stream;
            } else {
                $stream->on("open", function($event, $stream) use(&$index, $type) {
                    $index = intval($stream->stream());
                    $stream->setStreamSet($this);
                    $this->streams[ $type ][ $index ] = $stream->stream();
                    $this->streamsObjects[ $type ][ $index ] = $stream;
                });
            }
            $stream->on("close", function($event, $stream) use(&$index, $type) {
                $this->removeStream($index, $type);
            });
        }

        public function addReadStream(Stream $stream) {
            $this->addStream($stream, "read");
        }

        public function addWriteStream(Stream $stream) {
            $this->addStream($stream, "write");
        }

        public function removeReadStream($stream) {
            return $this->removeStream($stream, "read");
        }

        public function removeWriteStream($stream) {
            return $this->removeStream($stream, "write");
        }

        public function removeStream($index, $type) {
            $index          = self::isStream($index) ? (int) $index : $index;
            $return         = false;
            if (isset($this->streams[ $type ][ $index ])) {
                $streamObject = $this->streamsObjects[ $type ][ $index ];
                unset($this->streams[ $type ][ $index ]);
                unset($this->streamsObjects[ $type ][ $index ]);

                if (!isset($this->streams[ "read" ][ $index ]) && !isset($this->streams[ "write" ][ $index ])) {
                    $streamObject->setStreamSet(null);
                }
                return true;
            }
            return false;
        }

        public function __construct() {
            $this->timerSet = new Timer\TimerSet();
        }

        public function addTimer(Timer $timer) {
            $this->timerSet->add($timer);
        }

        static public function isStream($var) {
            return is_resource($var) && get_resource_type($var) === 'stream';
        }
        public function isRunning() {
            return $this->running;
        }

        public function getReadStreamsCount() {
            return count($this->streams[ "read" ]);
        }

        public function getWriteStreamsCount() {
            return count($this->streams[ "write" ]);
        }
    }
}
