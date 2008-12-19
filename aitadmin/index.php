<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 encoding=utf-8 fdm=marker :
$d = dirname(__FILE__).DIRECTORY_SEPARATOR;
ini_set('include_path',$d.'classes'.PATH_SEPARATOR.$d.'..');

require_once "AIT.php";
//$cnxstr = 'mysql:host=ida.intra.inist.fr;port=51101;dbname=kloog';
//$cnxstr = 'mysql:host=ida.intra.inist.fr;port=51119;dbname=kloog';
$cnxstr = 'mysql:host=localhost;dbname=notules';

$db = AIT::connect($cnxstr, 'root');
$db->setOptions(array('prefix' => 'notules_'));


require_once 'Root.php';
$o = new Root(array(
    'CacheMode' => true,
    'db' => $db,
));
$o->main();
$o->dump();

?>
