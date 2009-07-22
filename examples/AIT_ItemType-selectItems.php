<?php
require_once 'prepend.php';

$query = "item.label = 'a762424' OR item.label LIKE 'a2%' ";

$items = $schema->disques->searchItems($query, 0, 10);  

foreach($items as $item) {  
    echo $item->get()."\n";  
}

echo 'Showed : '.$items->count(). ' / '.$items->total()."\n";
?>
