<?php
namespace Phasty\Stream {
    use \Phasty\Log\File as log;
    /*
     * Обертка для различных потоковых ресурсов
     *
     * Позволяет объединять множество потоков для прослушивания в наборе потоков
     * StreamSet. При появлении данных на потоке генерируется событие "data"
     * на обертке, которое может быть обработано
     */
    class Stream extends \Phasty\Events\Eventable {
        protected $resource    = null;
        protected $streamSet   = null;
        protected $writeBuffer = "";

        /*
         * В конструктор можно передавать прослушиваемый поток
         */
        public function __construct($resource = null) {
            $this->on("rawdata",    [$this, "onRawData"]);
            $this->on("writeready", [$this, "onWriteReady"]);
            if ($resource) {
                $this->open($resource);
            }
        }

        /*
         * Запоминает поток, если он не был передан в конструктор
         */
        public function open($resource) {
            if ($this->isOpened()) {
                throw new \Exception("В этой обертке открыт другой поток");
            }
            $this->resource = $resource;
            // stream_set_read_buffer($this->resource, 0);
            // stream_set_write_buffer($this->resource, 0);
            if ($this->isOpened()) {
                $this->trigger("open");
                return true;
            }
            return false;
        }

        /*
         * Закрывает поток
         */
        public function close() {
            if (!$this->isOpened()) {
                $this->resource = null;
                return false;
            }
            $this->trigger("before-close");
            fclose($this->resource);
            $this->resource = null;
            $this->trigger("close");
            return true;
        }

        /*
         * Возвращает, открыт ли поток
         */
        public function isOpened() {
            return is_resource($this->resource) && get_resource_type($this->resource) == 'stream';
        }

        /*
         * Возвращает поток
         *
         * @return resource stream
         */
        public function stream() {
            return $this->resource;
        }

        /*
         * Устанавливает, блокируется ли поток
         */
        public function setBlocking($mode) {
            return stream_set_blocking($this->resource, $mode);
        }

        /*
         * Устанавливает таймаут потока
         */
        public function setTimeout($seconds, $microseconds = 0) {
            stream_set_timeout($this->resource, $seconds, $microseconds);
        }

        /*
         * Читает все содержимое потока
         */
        public function getContents() {
            $this->setBlocking(false);
            $data = stream_get_contents($this->resource);
            return $data;
        }

        public function onRawData() {
            if (!\feof($this->resource)) {
                $this->trigger("data", $this->getContents());
            } else {
                $this->close();
            }
        }

        protected function realWrite($data) {
            return fwrite($this->resource, $data);
        }

        public function onWriteReady() {
            $this->streamSet->removeWriteStream($this->resource);
            $written = $this->realWrite($this->writeBuffer);
            if ($written) {
                $this->writeBuffer = substr($this->writeBuffer, $written);
                $this->trigger("written");
            }
        }

        public function setStreamSet(\Phasty\Stream\StreamSet $streamSet = null) {
            $this->streamSet = $streamSet;
        }

        public function getMetaData() {
            return stream_get_meta_data($this->resource);
        }

        public function write($data) {
            if ($this->streamSet && $this->streamSet->isRunning()) {
                $beforeLen = strlen($this->writeBuffer);
                $this->writeBuffer .= $data;
                $this->streamSet->addWriteStream($this);
            } else {
                $this->realWrite($data);
            }
        }
    }
}
