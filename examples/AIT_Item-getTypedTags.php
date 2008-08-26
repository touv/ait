<?php
require_once 'prepend.php';

$item = $schema->disques->getItem('a1902425');  

$tags = $item->getTypedTags($schema->style);

if ($tags->count() > 0) {
    echo $tags->offsetGet(0)->get();
}  
// Affichera : Musique Classique
