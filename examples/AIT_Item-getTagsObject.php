<?php
require_once 'prepend.php';

$item = $schema->disques->getItem('a1902425');  

$tct = $item->getTagsObject();

echo $tct->titre->offsetGet(0)->get() . "\n";
# Affichera : Heroes

foreach($tct->artiste as $tag) 
    echo ' * ' .$tag->get();

# Affichera : * Antonio Vivaldi * Philippe Jaroussky
