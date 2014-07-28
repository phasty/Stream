<?php
namespace Phasty\Stream\Timer {
    use \Phasty\Stream\Timer;
    class TimerSet extends \SplPriorityQueue {
        protected $timers = null;
        protected $lastExtracted = null;

        public function __construct() {
            $this->setExtractFlags(self::EXTR_BOTH);
            $this->timers = new \SplObjectStorage();
        }
        public function extract() {
            $data = parent::extract();
            $return = [
                "timer"  => $data[ "data" ],
                "time"   => round(abs($data[ "priority" ]) - microtime(true), 6),
                "tickAt" => abs($data[ "priority" ])
            ];
            $return[ "time" ] = $return[ "time" ] >= 0 ? $return[ "time" ] : 0;
            return (object)$return;
        }

        public function onTimerTick($event, $timer) {
            $this->lastExtracted = null;
            $this->add($timer);
        }
        public function add(Timer $timer, $firstTickAfter = null) {
            $time = microtime(true);
            $timer->off("tick", [ $this, "onTimerTick" ]);
            $timer->on("tick", [ $this, "onTimerTick" ]);
            $firstTickAfter = $firstTickAfter ? $firstTickAfter : $timer->getTime();
            $this->insert($timer, - ($time + $firstTickAfter));
            $this->timers[ $timer ] = true;
        }

        public function getNearest() {
            if ($this->lastExtracted) {
                $firstTickAfter = $this->lastExtracted->tickAt - microtime(true);
                $this->add($this->lastExtracted->timer, $firstTickAfter);
            }
            $skipped = [];
            do {
                if (!$this->valid()) {
                    $timerInfo = null;
                    break;
                }
                $timerInfo = $this->extract();
                if ($timerInfo->timer->isCanceled()) {
                    // simply forget timer, ignore it
                } elseif ($timerInfo->timer->isStopped()) {
                    $skipped []= $timerInfo->timer;
                } elseif (isset($this->timers[ $timerInfo->timer ])) {
                    $this->lastExtracted = $timerInfo;
                    break;
                }
            } while (true);

            foreach ($skipped as $timer) {
                $this->add($timer);
            }
            if (!$timerInfo) {
                return null;
            }
            return (object)$timerInfo;
        }

        public function remove($timer) {
            unset($this->timers[ $timer ]);
        }
    }
}
