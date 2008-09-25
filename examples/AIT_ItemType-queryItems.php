<?php
require_once 'prepend.php';

$jazz    = $schema->style->getTag('Jazz');
$blues   = $schema->style->getTag('Blues');
$electro = $schema->style->getTag('Electro');

$query = new AITQuery($db);

// les disques marqués avec Jazz et Blues 
$query->all(new ArrayObject(array($jazz, $blues))); 

// Ou bien
$query->eitheror();

// Les disques marqués avec Jazz et Electro
$query->all(new ArrayObject(array($jazz, $electro)));

$items = $schema->disques->queryItems($query, 0, 10, AIT::ORDER_BY_SCORE|AIT::ORDER_ASC);
foreach($items as $item) {  
    echo $item->get();  
}
echo 'Showed : '.$items->count(). ' / '.$items->total()."\n";
?>
