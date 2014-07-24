Stream
======

This component is wrapper for different kind of php streams

Usage
-----

Classes of this package allow you to deal with such streams as files, sockets, pipes, i/o-streams and so on
with ability to use them in non-blocking manner:

    use Phasty\Stream\Stream;
    use Phasty\Stream\StreamSet;
    use Phasty\Stream\Timer;

    // use wrapper for STDIN
    $readStream = new Stream(STDIN);
    // streamset allows you work with different streams at sames time tih interraptions for timers
    $streamSet = StreamSet::instance();
    $streamSet->addReadStream($readStream);
    $readStream->on("data", function($event) use ($streamSet) {
        $data = $event->getData();
        if (trim($data) === "q") {
            $streamSet->stop();
            return;
        }
        echo "you wrote: " . $event->getData();
    });
    $timer = new Timer(2, 0, function() { 
        echo "Now: " . date("H:i:s"), "\n";
    });
    $streamSet->addTimer($timer);
    $streamSet->listen();
    echo "exiting\n";

You will get something like this:

    Now: 20:52:11
    dawd
    you wrote: dawd
    Now: 20:52:13
    q
    exiting


