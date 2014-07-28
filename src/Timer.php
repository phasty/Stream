<?php
namespace Phasty\Stream {
    class Timer extends \Phasty\Events\Eventable {
        protected $time = null;
        protected $isStopped  = false;
        protected $isCanceled = false;

        public function __construct($seconds, $microseconds = 0, $callback = null) {
            $this->time = "$seconds." . str_pad($microseconds, 6, "0", STR_PAD_RIGHT);
            if ($callback) {
                if (!is_callable($callback)) {
                    throw new \InvalidArgumentException("callback should be null or callable");
                } else {
                    $this->on("tick", $callback);
                }
            }
        }

        public function getTime() {
            return $this->time;
        }

        public function isStopped() {
            return $this->isStopped;
        }

        public function isCanceled() {
            return $this->isCanceled;
        }

        public function start() {
            if (!$this->isStopped) {
                return false;
            }
            $this->isStopped = false;
            $this->trigger("start");
            return true;
        }

        public function stop() {
            if ($this->isStopped) {
                return false;
            }
            $this->isStopped = true;
            $this->trigger("stop");
            return true;
        }

        public function cancel() {
            if ($this->isCanceled) {
                return false;
            }
            $this->isCanceled = true;
            $this->trigger("cancel");
            return true;
        }
    }
}
