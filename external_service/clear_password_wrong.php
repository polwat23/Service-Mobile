<?php
require_once('../autoloadConnection.php');
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/function_util.php');

use Utility\Library;
use Component\functions;

$lib = new library();
$func = new functions();

$updateWrongCounter = $conoracle->prepare("UPDATE gcmemberaccount SET counter_wrongpass = 0 WHERE counter_wrongpass > 0");
$updateWrongCounter->execute();
?>