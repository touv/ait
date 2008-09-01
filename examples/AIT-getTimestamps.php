<?php
require_once 'prepend.php';

$item = $schema->disques->getItem('a1902425');  
$timestamps = $item->getTimestamps();
echo date('Y-m-d\TH:i:s\Z', $timestamps->updated)."\n";
// Affichera : 2008-08-21T17:22:17Z
echo $timestamps['created']."\n";
// Affichera : 1219332137


$jazz = $schema->style->getTag('Jazz');
$timestamps = $jazz->getTimestamps();
var_dump($timestamps);

// Affichera : 
//  object(ArrayObject)#15 (2) {
//     ["updated"]=>
//     int(1219332137)
//     ["created"]=>
//     int(1219332137)
//  }
// 
