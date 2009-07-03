<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 encoding=utf-8 fdm=marker :

ini_set('include_path', dirname(__FILE__).PATH_SEPARATOR.ini_get('include_path'));

require_once 'PHPUnit/Framework.php';
require_once 'AIT.php';

function normalize($s, $l) {
    static $tn = null;
    if (is_null($tn)) {
        include_once 'Text/Normalize.php';
        $tn = new Text_Normalize('', 'fr');
    }
    $tn->set($s, $l);
    return $tn->get(Text_Normalize::Uppercase);
}

function check($o) {
    return $o->getTagType()->get() === 'TypeTagPrefix';
}




class AITExtendedTest extends PHPUnit_Framework_TestCase
{
    var $cnxstr;
    var $db;
    function setUp()
    {
        $this->cnxstr = 'mysql:host=localhost;dbname=ait';
//        $this->cnxstr = 'mysql:host=localhost;dbname=allistag';
//        $this->cnxstr = 'mysql:host=thouveni.ads.intra.inist.fr;dbname=allistag';
        $this->db = AIT::connect($this->cnxstr, 'root');
    }
    function tearDown()
    {
        $this->db->exec("TRUNCATE ".$this->db->tag());
        $this->db->exec("TRUNCATE ".$this->db->tagged());
        $this->db = null;
    }
    function test_search()
    {
        require_once 'AIT/Extended/Searching.php';
//        $this->db = AIT::connect($this->cnxstr, 'root');
        $this->db->setOption('prefix', 'search_');
        $this->db->checkup();
        $this->db->extendWith(new AIT_Extended_Searching('normalize'));

        $it = new AIT_ItemType('itemtype', $this->db);
        $i1 = $it->addItem('A');
        $i2 = $it->addItem('B');
        $i3 = $it->addItem('C');
        $tt = $it->addTag('tagtype');
        $t1 = $tt->addTag('abcd efgh ijkl');
        $t2 = $tt->addTag('efgh ijkl mnop');
        $t3 = $tt->addTag('ijkl mnop qrst');


        $i1->attach($t1)->attach($t2);
        $i2->attach($t1)->attach($t3);
        $i3->attach($t3);

//        $it->debugging = true;
        $items = $it->searchItems('abc*'); // t1 => i1, i2
        $this->assertEquals($items->count(), 2);

        $items = $it->searchItems('efg*'); // t1,t2 => i1, i2
        $this->assertEquals($items->count(), 2);

        $items = $it->searchItems('^efgh'); // t2 => i1
        $this->assertEquals($items->count(), 1);

        $items = $it->searchItems('mno*'); // t2,t3 => i1, i2, i3
        $this->assertEquals($items->count(), 3);

        $items = $it->searchItems('qrs*'); // t3 => i2, i3
        $this->assertEquals($items->count(), 2);

        $items = $it->searchItems('abc* OR qrs*'); // t3,t1 => i1, i2, i3
        $this->assertEquals($items->count(), 3);

        $items = $it->searchItems('*rst');
        $this->assertEquals($items->count(), 2);

        $items = $it->searchItems('"efgh ijkl"');
        $this->assertEquals($items->count(), 2);

        $items = $it->searchItems('*"fgh ijkl"');
        $this->assertEquals($items->count(), 2);

        $items = $it->searchItems('"efgh ijk"*');
        $this->assertEquals($items->count(), 2);
    }

     function test_fake()
    {
        require_once 'AIT/Extended/Fake.php';
        $this->db->setOption('prefix', 'search_');
        $this->db->checkup();
        $this->db->extendWith(new AIT_Extended_Fake());

        $it = new AIT_ItemType('A', $this->db);
        $i1 = $it->addItem('B');
        $tt = $it->addTag('C');
        $t1 = $tt->addTag('D');

        $this->assertEquals($it->maMethod('W'), 'ItemType / W');
        $this->assertEquals($i1->maMethod('X'), 'Item / X');
        $this->assertEquals($tt->maMethod('Y'), 'TagType / Y');
        $this->assertEquals($t1->maMethod('Z'), 'Tag / Z');
    }

    function test_full()
    {
        require_once 'AIT.php';
        require_once 'AIT/Extended/Searching.php';

        // Connexion à la base
//        $this->db = AIT::connect( $this->cnxstr, 'root');
        $this->db->checkup();

        // Ajout d'un plugin
        $this->db->extendWith(new AIT_Extended_Searching('normalize'));


        // Définition d'un schéma de données
        $sm = $this->db->registerSchema('Disques', array('titre', 'artiste', 'style'));


        // Ajout de quelques Tags génériques
        $jazz      = $sm->style->addTag('Jazz');
        $blues     = $sm->style->addTag('Blues');
        $electro   = $sm->style->addTag('Electro');
        $classique = $sm->style->addTag('Musique Classique');

        // Ajout de quelques items
        $d1 = $sm->disques->addItem('a2407474');
        $d1->addTag('Bossa nova stories', $sm->titre);
        $d1->addTag('Eliane Elias',       $sm->artiste);
        $d1->attach($jazz)->attach($blues);

        $d2 = $sm->disques->addItem('a2274830');
        $d2->addTag('Fuck me I\'m famous Ibiza mix 08', $sm->titre);
        $d2->addTag('David Guetta',                     $sm->artiste);
        $d2->attach($electro);

        $d3 = $sm->disques->addItem('a1902425');
        $d3->addTag('Heroes',             $sm->titre);
        $d3->addTag('Antonio Vivaldi',    $sm->artiste);
        $d3->addTag('Philippe Jaroussky', $sm->artiste);
        $d3->attach($classique);


        // Recherche Full Text
        $result = $sm->disques->searchItems('*es');
        $this->assertEquals($result->count(), 2);

        // Recherche Tags Search
        $result = $sm->disques->fetchItems(new ArrayObject(array($electro)));
        $this->assertEquals($result->count(), 1);

        $sm->disques->del();

    }

    function test_basic_cache()
    {
        require_once 'AIT/Extended/Caching/Basic.php';
        $this->db->checkup();
        $this->db->extendWith(new AIT_Extended_Caching_Basic());

        $it = new AIT_ItemType('A', $this->db);
        $i1 = $it->addItem('B');
        $i2 = $it->addItem('C');


        $r1 = $it->getItems();
        $this->assertEquals($r1->count(), 2);

        $i3 = $it->addItem('D');

        $r1 = $it->getItems();

        $this->assertEquals($r1->count(), 2);

    }


    function test_prefix1()
    {
        require_once 'AIT/Extended/Prefix.php';
        $this->db->checkup();
        $this->db->extendWith(new AIT_Extended_Prefix());

        $it = new AIT_ItemType('_1', $this->db);
        $i1 = $it->addItem('_2');
        $i2 = $it->addItem('_3');

        $tt = $it->addTag('_4');

        $t1 = $tt->addTag('X', array('prefix' => 'P1_'));
        $t2 = $tt->addTag('X', array('prefix' => 'P2_'));
        
        $i1->attach($t1);
        $i2->attach($t2);

        $tags = $i1->getTags();
        $t1a = $tags->offsetGet(0);

        $this->assertEquals($t1a->get(), 'X');

        $tags = $i2->getTags();
        $t2a = $tags->offsetGet(0);
        $this->assertEquals($t2a->get(), 'X');


        $this->assertEquals($t1->getSystemID(), $t1a->getSystemID());
        $this->assertEquals($t2->getSystemID(), $t2a->getSystemID());

        $this->assertNotEquals($t1->getSystemID(), $t2->getSystemID());
        $this->assertNotEquals($t1a->getSystemID(), $t2a->getSystemID());

        $it->del();
    }

    function test_prefix2()
    {
        require_once 'AIT/Extended/Prefix.php';
        $this->db->checkup();
        $this->db->extendWith(new AIT_Extended_Prefix('check'));

        $it = new AIT_ItemType('A', $this->db);
        $i1 = $it->addItem('B');
        $i2 = $it->addItem('C');
        $i3 = $it->addItem('D');
        $i4 = $it->addItem('E');

        $tt = $it->addTagType('TypeTagPrefix');
        $ty = $it->addTagType('TypeTagNormal');

        $t1 = $tt->addTag('X', array('prefix' => 'P3_'));
        $t2 = $tt->addTag('X', array('prefix' => 'P4_'));
        
        $t3 = $ty->addTag('Y', array('prefix' => 'P5_'));
        $t4 = $ty->addTag('Y', array('prefix' => 'P6_'));

        $i1->attach($t1);
        $i2->attach($t2);
        $i3->attach($t3);
        $i4->attach($t4);

        $tags = $i1->getTags();
        $t1a = $tags->offsetGet(0);

        $this->assertEquals($t1a->get(), 'X');

        $tags = $i2->getTags();
        $t2a = $tags->offsetGet(0);
        $this->assertEquals($t2a->get(), 'X');

        $tags = $i3->getTags();
        $t3a = $tags->offsetGet(0);
        $this->assertEquals($t3a->get(), 'Y');

        $tags = $i4->getTags();
        $t4a = $tags->offsetGet(0);
        $this->assertEquals($t4a->get(), 'Y');

        $this->assertEquals($t1->getSystemID(), $t1a->getSystemID());
        $this->assertEquals($t2->getSystemID(), $t2a->getSystemID());

        $this->assertNotEquals($t1->getSystemID(), $t2->getSystemID());
        $this->assertNotEquals($t1a->getSystemID(), $t2a->getSystemID());

        $this->assertEquals($t3->getSystemID(), $t4->getSystemID());
        $this->assertEquals($t3a->getSystemID(), $t4a->getSystemID());
        $this->assertEquals($t3->getSystemID(), $t3a->getSystemID());
        $this->assertEquals($t4->getSystemID(), $t4a->getSystemID());

    }

}
