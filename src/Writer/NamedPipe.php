<?php
namespace Phasty\Stream\Writer {
    use \Phasty\Log\File as log;
    class NamedPipe extends \Phasty\Stream\Reader {
        const MESSAGE_LEN_FIELD_LENGTH_BYTES = 4;

        public static function send($fifo, $message) {
            $message = serialize($message);
            $messageLen = hex2bin(str_pad(dechex(strlen($message)), self::MESSAGE_LEN_FIELD_LENGTH_BYTES * 2, "0", STR_PAD_LEFT));
            $writeStream = fopen($fifo, "c+");
            if (!$writeStream) {
                throw new \Exception("Could not open fifo $fifo for writing: ".var_export(error_get_last(), 1));
            }
            if (!fwrite($writeStream, $messageLen . $message)) {
                log::warning("Could not write to fifo $fifo");
                throw new \Exception("Could not write to fifo $fifo");
            } else {
                log::debug("Written message to $fifo");
            }
            fclose($writeStream);
        }
    }
}
