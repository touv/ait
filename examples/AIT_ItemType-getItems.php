<?php
require_once 'prepend.php';

$items = $schema->disques->getItems();  


foreach ($items as $item) {
    echo "- ".$item->get()."\n";
}
echo 'Showed : '.$items->count(). ' / '.$items->total()."\n";

// Affichera : 
//
// - a2407474
// - a2274830
// - a1902425
// - a762424
// - a1095444
// (...)
