<?php

include __DIR__ . "/../lib/xlog.php";

XLog::setup(array(
    "dir" => "/tmp",
));

for ($var = 0; $var < 3; $var++) {
    usleep(12);
    XLog::debug("hello1");
    XLog::Debug("hello3");
    XLog::debug("hello2", "test2");
    XLog::write("any", "test", "name2");
}
