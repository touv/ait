<?php
require_once 'prepend.php';

$item = $schema->disques->getItem('a1902425');  
if (!is_null($item)) {
    echo $item->get();
}
// Affichera :  a1902425
