<?php
require_once 'prepend.php';

$jazz = $schema->style->getTag('Jazz');

if (!is_null($jazz)) 
    echo $jazz->countItems();

// Affichera : 2

