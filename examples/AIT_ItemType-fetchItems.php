<?php
require_once 'prepend.php';

$jazz = $schema->style->getTag('Jazz');

$tags = new ArrayObject();  
$tags->append($jazz);  

$items = $schema->disques->fetchItems($tags, 0, 10);  

foreach($items as $item) {  
    echo $item->get()."\n";  
}

echo 'Showed : '.$items->count(). ' / '.$items->total()."\n";
?>
