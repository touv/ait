<?php
require_once 'prepend.php';

$item = $schema->disques->getItem('a1902425');  

$ttags = new ArrayObject();  
$ttags->append($schema->style);  

$tags = $item->fetchTags($ttags);

if ($tags->count() > 0) {
    foreach($tags as $tag) {
        echo "- ".$tag->get()."\n";
    }
}
echo 'Showed : '.$tags->count(). ' / '.$tags->total()."\n";

