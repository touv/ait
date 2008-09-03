<?php
require_once 'prepend.php';

$itemA = $schema->disques->getItem('a1902425');  
$id = $itemA->getSystemID();

$itemB = $schema->disques->getItemBySystemID($id);  

if (!is_null($itemB)) {
    echo $itemB->get();
}
// Affichera :  a1902425
