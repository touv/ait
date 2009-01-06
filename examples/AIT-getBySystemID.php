<?php
require_once 'prepend.php';

$o = AIT::getBySystemID($db, 17);
echo $o->get() ." : ";

$o = AIT::getBySystemID($db, 18);
echo $o->get() .", ";

$o = AIT::getBySystemID($db, 19);
echo $o->get() ."\n";

// Affichera : a1902425 : Heroes, Antonio Vivaldi

