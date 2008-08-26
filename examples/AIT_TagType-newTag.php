<?php
require_once 'prepend.php';

// Ajout d'un nouveau tag 
$tag = $schema->style->newTag();

echo $tag->get(); 

