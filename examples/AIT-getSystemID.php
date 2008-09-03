<?php
require_once 'prepend.php';

$item = $schema->disques->getItem('a1902425');  
$id = $item->getSystemID();

var_dump($id);

// Affichera : int(17)

