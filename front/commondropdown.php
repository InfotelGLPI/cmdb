<?php

include "../../../inc/includes.php";
$path = PLUGINCMDB_FRONT_PATH . '/' . $_REQUEST['ddtype'] . '.php';
$realpath = str_replace( "\\", "/", realpath($path));
$frontpath = str_replace( "\\", "/", PLUGINCMDB_FRONT_PATH );
if (strpos($realpath, $frontpath) === 0) {
    include_once $path;
} else {
    throw new \RuntimeException('Attempt to load unsecure or missing ' . $path .'!');
}
