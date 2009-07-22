<?php
include_once 'AIT-connect.php';

// Définition d'un schéma de données
$schema = $db->registerSchema('Disques', array('titre', 'artiste', 'style'));

// Ajout de quelques Tags génériques
$jazz      = $schema->style->addTag('Jazz');
$blues     = $schema->style->addTag('Blues');
$electro   = $schema->style->addTag('Electro');
$classique = $schema->style->addTag('Musique Classique');

// Ajout de quelques items
$d1 = $schema->disques->addItem('a2407474');
$d1->addTag('Bossa nova stories', $schema->titre); 
$d1->addTag('Eliane Elias',       $schema->artiste);  
$d1->attach($jazz)->attach($blues);

$d2 = $schema->disques->addItem('a2274830');
$d2->addTag('Fuck me I\'m famous Ibiza mix 08', $schema->titre); 
$d2->addTag('David Guetta',                     $schema->artiste); 
$d2->attach($electro);

$d3 = $schema->disques->addItem('???');
$d3->addTag('Heroes',             $schema->titre); 
$d3->addTag('Antonio Vivaldi',    $schema->artiste); 
$d3->addTag('Philippe Jaroussky', $schema->artiste); 
$d3->attach($classique);
$d3->ren('a1902425');

$d4 = $schema->disques->addItem('a762424');
$d4->addTag('The very best of Louis Armstrong', $schema->titre); 
$d4->addTag('Louis Armstrong',       $schema->artiste);  
$d4->attach($jazz);

$d5 = $schema->disques->addItem('a1095444');
$d5->addTag('Tourist',          $schema->titre); 
$d5->addTag('St Germain',       $schema->artiste);  
$d5->attach($jazz)->attach($electro);

