<?php

echo STREAM_SERVER_BIND | STREAM_SERVER_LISTEN;
echo PHP_EOL;
echo constant('STREAM_SERVER_BIND') | constant('STREAM_SERVER_LISTEN');
/*
echo constant('STREAM_SERVER_BIND');
echo constant('STREAM_SERVER_LISTEN');
*/