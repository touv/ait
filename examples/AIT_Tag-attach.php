<?php
require_once 'prepend.php';

$item = $schema->disques->getItem('a1902425');  

$tag = $schema->style->addTag('disco');

// Ajout du tag "disco" à "a1902425"
$tag->attach($item);

