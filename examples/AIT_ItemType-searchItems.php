<?php
require_once 'prepend.php';

$query = "tag.label = 'Tourist' OR tag.label LIKE 'Heroe%' ";

$items = $schema->disques->searchItems($query, 0, 10);  

foreach($items as $item) {  
    echo $item->get()."\n";  
}

echo 'Showed : '.$items->count(). ' / '.$items->total()."\n";
?>
