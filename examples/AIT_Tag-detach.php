<?php
require_once 'prepend.php';

$item = $schema->disques->getItem('a1902425');  

$tags = $item->getTypedTags($schema->style);

foreach($tags as $tag) {
    $tag->detach();
}
// Supprime tous les tags "style" asscoié à a1902425
