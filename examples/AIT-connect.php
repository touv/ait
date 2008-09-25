<?php

require_once 'AIT.php';

// Paramètres de connexion 
$dsn      = 'mysql:host=localhost;dbname=music';
$user     = 'root';
$password = '';

// Connexion à la base 
$db = AIT::connect(
        $dsn, 
        $user,
        $password
    );

// On purge 
$db->exec("TRUNCATE ".$db->tag());
$db->exec("TRUNCATE ".$db->tagged());


// Contrôle et création de la structure de données  
$db->checkup(); 

