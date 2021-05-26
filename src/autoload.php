<?php
$file_helps = __DIR__.DIRECTORY_SEPARATOR.'helps.php';
if (!file_exists($file_helps)) {
    throw new Exception("Help Class not fount please install again this Library");
}
require_once $file_helps;
use Appkita\SPARK\Helps;
$list_file = Helps::listFiles(__DIR__, 'php');
$help_dir = realpath('helps.php');
for ($i = 0; $i < sizeof($list_file); $i++) {
    $filename = $list_file[$i];
    if ($filename != $help_dir) {
        if (file_exists($filename)) {
          require_once $filename;
        }
    }
}