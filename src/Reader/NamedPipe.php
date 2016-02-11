<?php
namespace Phasty\Stream\Reader {
    use \Phasty\Log\File as log;
    class NamedPipe extends \Phasty\Stream\Reader {
        const MESSAGE_LEN_FIELD_LENGTH_BYTES = 4;

        protected $valid = false;
        protected $fifoFile = null;
        protected $selfCreated = false;

        public function __construct($fifoFile = null) {
            parent::__construct();
            if ($fifoFile) {
                $this->open($fifoFile);
            }
        }

        public function __destruct() {
            if ($this->isOpened()) {
                $this->close();
            }
        }

        public function close() {
            parent::close();
            if ($this->fifoFile) {
                //Если кто-нибудь его увидит и захочет в него написать,
                //то заблокируется, так что лучше удалить
                if (file_exists($this->fifoFile) && $this->selfCreated) {
                    unlink($this->fifoFile);
                    set_error_handler(function() {});
                    // Удаляем ТОЛЬКО если директория пуста
                    @rmdir(dirname($this->fifoFile));
                    restore_error_handler();
                }
                $this->fifoFile = null;
            }
        }

        public function open($fifoFile) {
            $this->fifoFile = $fifoFile;
            $dir = pathinfo($this->fifoFile, PATHINFO_DIRNAME);
            umask(0);
            $this->selfCreated = false;
            if (!is_dir($dir)) {
                if (!mkdir($dir, 0777, true)) {
                    if (!is_dir($dir)) {
                        throw new \Exception("Could not create directory $dir");
                    }
                }
            }
            // If pipe was not create on another side
            if (!file_exists($this->fifoFile)) {
                if (!posix_mkfifo($this->fifoFile, 0777)) {
                    throw new \Exception("Could not create $this->fifoFile: " . posix_strerror(posix_get_last_error()));
                } else {
                    $this->selfCreated = true;
                }
            }
            log::debug("Creating stream for {$this->fifoFile}");
            $stream = fopen($this->fifoFile, "c+");
            log::debug("Stream $stream = {$this->fifoFile}");
            $this->valid = (bool)$stream;
            parent::open($stream);

            $this->setBlocking(false);
        }

        public static function isListened($fifo) {
            return file_exists($fifo);
        }
        public function write($data) {
            log::debug("Writing data to $this->fifoFile");
            parent::write($data);
        }
    }
}
