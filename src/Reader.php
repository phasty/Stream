<?php
namespace Phasty\Stream {
    /*
     * Обертка для различных потоковых ресурсов
     *
     * Позволяет объединять множество потоков для прослушивания в наборе потоков
     * StreamSet. При появлении данных на потоке генерируется событие "data"
     * на обертке, которое может быть обработано
     */
    class Reader extends \Phasty\Stream\Stream {
        /*
         * В конструктор можно передавать прослушиваемый поток
         */
        public function __construct($resource = null) {
            parent::__construct($resource);
            $this->on("rawdata", [$this, "onRawData"]);
        }
    }
}
