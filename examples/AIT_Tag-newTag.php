<?php
require_once 'AIT.php';

// Connexion à la base 
$db = AIT::connect(
        'mysql:host=localhost;dbname=test', 
        'root'
);

// Définition d'un schéma de données
$sm = $db->registerSchema('Disques', array('titre', 'artiste', 'style'));  


// Ajout d'un nouveau tag 
$tag = $sm->style->newTag();

echo $tag->get(); 

