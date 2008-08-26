<?php
require_once 'prepend.php';

// Ajout d'un nouveau tag 
$tag = $schema->style->addTag('disco');

echo $tag->get();

// Affichera : disco



