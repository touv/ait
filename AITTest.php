<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 encoding=utf-8 fdm=marker :

error_reporting(E_ALL);
ini_set('include_path', dirname(__FILE__).PATH_SEPARATOR.ini_get('include_path'));


require_once 'AIT.php';
require_once 'PHPUnit/Framework.php';

function cb1($a, $e) { return 'cb1'; }
function cb2($a, $e) { return 'cb2'; }
function cb3($a, $e) { return 'cb3'; }
function cb4($a, $e) { return 'cb4'; }

class AITTest extends PHPUnit_Framework_TestCase
{
    var $db;
    function setUp()
    {
        $cnxstr = 'mysql:host=localhost;dbname=ait';
//        $cnxstr = 'mysql:host=thouveni.ads.intra.inist.fr;dbname=allistag';
        $options = array(
            'prefix'         => 'test_',
            'space_callback' => array(
                'ItemType'   => 'cb1',
                'Item'       => 'cb2',
                'TagType'    => 'cb3',
                'Tag'        => 'cb4',
            ),
        );
        $this->db = AIT::connect($cnxstr, 'root');
        $this->db->setOptions($options);
        $this->db->checkup();
    }
    function tearDown()
    {
        $this->db->exec("TRUNCATE ".$this->db->tag());
        $this->db->exec("TRUNCATE ".$this->db->tagged());
        $this->db = null;
    }
    function test_itemtype()
    {
        $it = new AIT_ItemType('itemtype', $this->db);
        $this->assertEquals($this->_d(), 3);
        $this->assertEquals($this->_q(), 0);
        $it->del();
        $this->assertEquals($this->_d(), 2);
    }
    function test_tagtype()
    {
        $it = new AIT_ItemType('itemtype', $this->db);
        $this->assertEquals($this->_d(), 3);
        $tt = $it->addTag('tagtype');
        $this->assertEquals($this->_d(), 4);
        $this->assertEquals($this->_q(), 1);
        $tt->del();
        $this->assertEquals($this->_d(), 3);
        $this->assertEquals($this->_q(), 0);
        $it->del();
        $this->assertEquals($this->_d(), 2);
    }
    function test_item()
    {
        $it = new AIT_ItemType('itemtype', $this->db);
        $this->assertEquals($this->_d(), 3);
        $i1 = $it->addItem('#1');
        $this->assertEquals($this->_d(), 4);
        $i2 = $it->addItem('#2');
        $this->assertEquals($this->_d(), 5);
        $it->del();
        $this->assertEquals($this->_d(), 2);
        $this->assertEquals($this->_q(), 0);
    }
    function test_tag()
    {
        $it = new AIT_ItemType('itemtype', $this->db);
        $this->assertEquals($this->_d(), 3);
        $tt = $it->addTag('tagtype');
        $this->assertEquals($this->_d(), 4);
        $t1 = $tt->addTag('@1');
        $this->assertEquals($this->_d(), 5);
        $t2 = $tt->addTag('@2');
        $this->assertEquals($this->_d(), 6);
        $tt->del();
        $this->assertEquals($this->_d(), 3);
        $it->del();
        $this->assertEquals($this->_d(), 2);
    }
    function test_tagging()
    {
        $it = new AIT_ItemType('itemtype', $this->db);
        $i1 = $it->addItem('#1');
        $i2 = $it->addItem('#2');
        $this->assertEquals($it->countItems(), 2);
        $this->assertEquals($this->_d(), 5);

        $tt = $it->addTag('tagtype');
        $t1 = $tt->addTag('@1');
        $t2 = $tt->addTag('@2');

        $this->assertEquals($it->countTags(), 1);
        $this->assertEquals($tt->countTags(), 2);
        $this->assertEquals($this->_d(), 8);

        $this->assertEquals($this->_q(), 1);
        $i1->attach($t1)->attach($t2);
        $i2->attach($t1);

        $t1b = $i2->getTag('@1', $tt);
        $t2b = $i1->getTag('@2', $tt);
        $t3  = $i1->getTag('@3', $tt);

        $this->assertEquals($t1, $t1b);
        $this->assertEquals($t2, $t2b);
        $this->assertNull($t3);

        $this->assertEquals($t1->countItems(), 2);
        $this->assertEquals($t2->countItems(), 1);

        $this->assertEquals($this->_q(), 4);

        $it->del();
        $this->assertEquals($this->_d(), 2);
    }
    function test_tagging_shared()
    {
        $it = new AIT_ItemType('itemtype', $this->db);
        $i1 = $it->addItem('#1');
        $i2 = $it->addItem('#2');
        $tt = $it->addTag('tagtype');
        $t1 = $tt->addTag('shared');
        $i1->attach($t1);
        $i2->attach($t1);

        $this->assertEquals($it->countItems(), 2);
        $this->assertEquals($tt->countItems(), 2);
        
        $this->assertEquals($tt->countTags(),  1);
        
        $this->assertEquals($t1->countItems(), 2);
        
        $this->assertEquals($this->_d(), 7);
        $this->assertEquals($this->_q(), 3);

        $i2->del();
        $this->assertEquals($it->countItems(), 2);
        $this->assertEquals($it->countItems(true), 1);

        $this->assertEquals($tt->countItems(), 2);
        $this->assertEquals($tt->countItems(true), 1);

        $this->assertEquals($t1->countItems(), 2);
        $this->assertEquals($t1->countItems(true), 1);
        
        $this->assertEquals($this->_d(), 6);
        $this->assertEquals($this->_q(), 2);

        $i1->del();
        $this->assertEquals($this->_d(), 5);
        $this->assertEquals($this->_q(), 1);

        $it->del();
        $this->assertEquals($this->_d(), 2);
        $this->assertEquals($this->_q(), 0);
    }
    function test_gettimestamps()
    {
        $it = new AIT_ItemType('itemtype', $this->db);
        $ts = $it->getTimestamps();
        $this->assertEquals($ts->count(), 2);
        $this->assertTrue(isset($ts['created']));
        $this->assertTrue(isset($ts['updated']));
        $this->assertEquals($ts['created'], $ts['updated']);
        $this->assertEquals($ts['created'], $ts['updated']);
        sleep(1);
        $it->ren('typeitem');
        $ts = $it->getTimestamps();
        $this->assertNotEquals($ts['created'], $ts['updated']);
        $it->del();
        $this->assertEquals($this->_d(), 2);
    }

    function test_getItems()
    {
        $s1 = '#1';
        $s2 = '#2';
        $it = new AIT_ItemType('itemtype', $this->db);
        $i1 = $it->addItem($s1);
        $i2 = $it->addItem($s2);
        $items = $it->getItems();
        $this->assertEquals($items[0]->get(), $s1);
        $this->assertEquals($items[1]->get(), $s2);
        $it->del();
        $this->assertEquals($this->_d(), 2);
        $this->assertEquals($this->_q(), 0);
    }
    function test_ren()
    {
        $s1 = '#1';
        $s2 = '#2';
        $it = new AIT_ItemType('itemtype', $this->db);
        $i1 = $it->addItem("XXXX");
        $i2 = $it->addItem("YYYY");
        $i1->ren($s1);
        $i2->ren($s2);
        $items = $it->getItems();
        $this->assertEquals($items[0]->get(), $s1);
        $this->assertEquals($items[1]->get(), $s2);
        $it->del();
        $this->assertEquals($this->_d(), 2);
        $this->assertEquals($this->_q(), 0);
    }
    function test_gettyped()
    {
        $it = new AIT_ItemType('itemtype', $this->db);
        $i1 = $it->addItem('A');
        $i2 = $it->addItem('B');
        $y1 = $it->addTag('tagtype');
        $y2 = $it->addTag('typetag');
        $t1 = $y1->addTag('C');
        $t2 = $y1->addTag('D');
        $t3 = $y2->addTag('E');
        $t4 = $y2->addTag('F');
        $i1->attach($t1)->attach($t3);
        $i2->attach($t2)->attach($t4);

        $tags = $i1->getTypedTags($y1);
        $this->assertEquals($tags->count(), 1);
        $this->assertEquals($tags[0]->get(), 'C');
        $this->assertTrue($tags[0]->exists());
        $tags = $i2->getTypedTags($y2);
        $this->assertEquals($tags->count(), 1);
        $this->assertEquals($tags[0]->get(), 'F');
        $this->assertTrue($tags[0]->exists());

        $it->del();
        $this->assertEquals($this->_d(), 2);
        $this->assertEquals($this->_q(), 0);
    }
    function test_tags()
    {
        $it = new AIT_ItemType('itemtype', $this->db);
        $i1 = $it->addItem('A');
        $i2 = $it->addItem('B');
        $tt = $it->addTag('tagtype');
        $t1 = $tt->addTag('C');
        $t2 = $tt->addTag('D');
        $t3 = $tt->addTag('E');
        $t4 = $tt->addTag('F');
        $i1->attach($t1)->attach($t3);
        $i2->attach($t2)->attach($t4);

        $tags = $i1->getTags(null, null, AIT::ORDER_BY_LABEL | AIT::ORDER_ASC);
        $this->assertEquals($tags->count(), 2);
        $this->assertTrue($tags[0]->exists());
        $this->assertEquals($tags[0]->get(), 'C');
        $this->assertTrue($tags[1]->exists());
        $this->assertEquals($tags[1]->get(), 'E');
        $tags = $i2->getTags(null, null, AIT::ORDER_BY_LABEL | AIT::ORDER_ASC);
        $this->assertEquals($tags->count(), 2);
        $this->assertEquals($tags[0]->get(), 'D');
        $this->assertTrue($tags[0]->exists());
        $this->assertEquals($tags[1]->get(), 'F');
        $this->assertTrue($tags[1]->exists());

        $it->del();
        $this->assertEquals($this->_d(), 2);
        $this->assertEquals($this->_q(), 0);
    }
    function test_related()
    {
        $it = new AIT_ItemType('itemtype', $this->db);
        $i1 = $it->addItem('A');
        $i2 = $it->addItem('B');
        $tt = $it->addTag('tagtype');
        $t1 = $tt->addTag('C');
        $t2 = $tt->addTag('D');
        $t3 = $tt->addTag('E');
        $t4 = $tt->addTag('F');
        $i1->attach($t1)->attach($t3);
        $i2->attach($t2)->attach($t4);

        $tags = $t1->getRelatedTags();
        $this->assertEquals($tags->count(), 1);
        $this->assertEquals($tags[0]->get(), $t3->get());

        $tags = $t2->getRelatedTags();
        $this->assertEquals($tags->count(), 1);
        $this->assertEquals($tags[0]->get(), $t4->get());

        $it->del();
        $this->assertEquals($this->_d(), 2);
        $this->assertEquals($this->_q(), 0);
    }
    function test_attachement()
    {
        $it = new AIT_ItemType('itemtype', $this->db);
        $i1 = $it->addItem('A');
        $i2 = $it->addItem('B');
        $tt = $it->addTag('tagtype');
        $t1 = $tt->addTag('C');
        $t2 = $tt->addTag('D');
        $t3 = $tt->addTag('E');
        $t4 = $tt->addTag('F');
        $i1->attach($t1)->attach($t3);
        $i2->attach($t1)->attach($t2)->attach($t4);

        $tags= $tt->getTags();
        $this->assertEquals($tags->count(), 4);
        $this->assertEquals($tags[0]->get(), $t1->get());
        $this->assertEquals($tags[1]->get(), $t2->get());
        $this->assertEquals($tags[2]->get(), $t3->get());
        $this->assertEquals($tags[3]->get(), $t4->get());

        $this->assertEquals($tags[0]->countItems(), 2);
        $this->assertEquals($tags[1]->countItems(), 1);
        $this->assertEquals($tags[2]->countItems(), 1);
        $this->assertEquals($tags[3]->countItems(), 1);

        $it->del();
        $this->assertEquals($this->_d(), 2);
        $this->assertEquals($this->_q(), 0);
    }

    function test_ordering()
    {
        $it = new AIT_ItemType('itemtype', $this->db);
        $i1 = $it->addItem('A');
        $i2 = $it->addItem('B');

        $items = $it->getItems(null,null, AIT::ORDER_BY_LABEL|AIT::ORDER_ASC);
        $this->assertEquals($items->count(), 2);
        $this->assertEquals($items[0]->get(), 'A');
        $items = $it->getItems(null, null, AIT::ORDER_BY_LABEL|AIT::ORDER_DESC);
        $this->assertEquals($items->count(), 2);
        $this->assertEquals($items[0]->get(), 'B');

        $tt = $it->addTag('tagtype');
        $t1 = $tt->addTag('C');
        $t2 = $tt->addTag('D');
        $t3 = $tt->addTag('E');

        $tags = $tt->getTags(null, null, AIT::ORDER_BY_LABEL|AIT::ORDER_ASC);
        $this->assertEquals($tags->count(), 3);
        $this->assertEquals($tags[0]->get(), 'C');
        $tags = $tt->getTags(null, null, AIT::ORDER_BY_LABEL|AIT::ORDER_DESC);
        $this->assertEquals($tags[0]->get(), 'E');

        $i1->attach($t1)->attach($t2);
        $i2->attach($t1)->attach($t2)->attach($t3);

        $tags = $i1->getTags(null, null, AIT::ORDER_BY_LABEL|AIT::ORDER_ASC);
        $this->assertEquals($tags->count(), 2);
        $this->assertEquals($tags[0]->get(), 'C');
        $tags = $i1->getTags(null, null, AIT::ORDER_BY_LABEL|AIT::ORDER_DESC);
        $this->assertEquals($tags[0]->get(), 'D');

        $tags = $t2->getRelatedTags(null, null, AIT::ORDER_BY_LABEL|AIT::ORDER_ASC);
        $this->assertEquals($tags->count(), 2);
        $this->assertEquals($tags[0]->get(), 'C');
        $tags = $t2->getRelatedTags(null, null, AIT::ORDER_BY_LABEL|AIT::ORDER_DESC);
        $this->assertEquals($tags->count(), 2);
        $this->assertEquals($tags[0]->get(), 'E');

        // méthode non implenté
        //        $items = $t2->getItems(null, null, AIT::ORDER_ASC);
        //        $this->assertEquals($items->count(), 2);
        //        $this->assertEquals($items[0]->get(), 'A');
        //        $items = $t2->getItems(null, null, AIT::ORDER_DESC);
        //        $this->assertEquals($items[0]->get(), 'B');

        $it->del();
        $this->assertEquals($this->_d(), 2);
        $this->assertEquals($this->_q(), 0);
    }

    function test_scoring()
    {
        $it = new AIT_ItemType('itemtype', $this->db);
        $i1 = $it->addItem('A');
        $i1->setScore(6);
        $this->assertEquals($i1->getScore(), 6);

        $i2 = $it->addItem('B');
        $i2->setScore(5);
        $this->assertEquals($i2->getScore(), 5);

        $items = $it->getItems(null,null, AIT::ORDER_BY_SCORE|AIT::ORDER_ASC);
        $this->assertEquals($items->count(), 2);
        $this->assertEquals($items[0]->get(), 'B');
        $items = $it->getItems(null, null, AIT::ORDER_BY_SCORE|AIT::ORDER_DESC);
        $this->assertEquals($items->count(), 2);
        $this->assertEquals($items[0]->get(), 'A');


        $tt = $it->addTag('tagtype');
        $tt->setScore(4);
        $this->assertEquals($tt->getScore(), 4);
        $t1 = $tt->addTag('C');
        $t1->setScore(3);
        $this->assertEquals($t1->getScore(), 3);
        $t2 = $tt->addTag('D');
        $t2->setScore(2);
        $this->assertEquals($t2->getScore(), 2);
        $t3 = $tt->addTag('E');
        $t3->setScore(1);
        $this->assertEquals($t3->getScore(), 1);

        $tags = $tt->getTags(null, null, AIT::ORDER_BY_SCORE|AIT::ORDER_ASC);
        $this->assertEquals($tags->count(), 3);
        $this->assertEquals($tags[0]->get(), 'E');
        $tags = $tt->getTags(null, null, AIT::ORDER_BY_SCORE|AIT::ORDER_DESC);
        $this->assertEquals($tags[0]->get(), 'C');

        $i1->attach($t1)->attach($t2);
        $i2->attach($t1)->attach($t2)->attach($t3);

        $tags = $i1->getTags(null, null, AIT::ORDER_BY_SCORE|AIT::ORDER_ASC);
        $this->assertEquals($tags->count(), 2);
        $this->assertEquals($tags[0]->get(), 'D');
        $tags = $i1->getTags(null, null, AIT::ORDER_BY_SCORE|AIT::ORDER_DESC);
        $this->assertEquals($tags[0]->get(), 'C');

        $tags = $t2->getRelatedTags(null, null, AIT::ORDER_BY_SCORE|AIT::ORDER_ASC);
        $this->assertEquals($tags->count(), 2);
        $this->assertEquals($tags[0]->get(), 'E');
        $tags = $t2->getRelatedTags(null, null, AIT::ORDER_BY_SCORE|AIT::ORDER_DESC);
        $this->assertEquals($tags->count(), 2);
        $this->assertEquals($tags[0]->get(), 'C');

        $it->del();
        $this->assertEquals($this->_d(), 2);
        $this->assertEquals($this->_q(), 0);
    }
    function test_getbysystemid()
    {
        $it = new AIT_ItemType('A', $this->db);
        $i1 = $it->addItem('B');
        $this->assertEquals($i1, $it->getItemBySystemID($i1->getSystemID()));

        $tt = $it->addTag('C');
        $t1 = $tt->addTag('D');
        $this->assertEquals($t1, $tt->getTagBySystemID($t1->getSystemID()));

        $it->del();
        $this->assertEquals($this->_d(), 2);
        $this->assertEquals($this->_q(), 0);
    }

    function test_fillspace()
    {
        $it = new AIT_ItemType('itemtype', $this->db);
        $this->assertEquals($it->get('space'), 'cb1');
        $i1 = $it->addItem('A');
        $this->assertEquals($i1->get('space'), 'cb2');
        $tt = $it->addTag('tagtype');
        $this->assertEquals($tt->get('space'), 'cb3');
        $t1 = $tt->addTag('C');
        $this->assertEquals($t1->get('space'), 'cb4');

        $it->del();
        $this->assertEquals($this->_d(), 2);
        $this->assertEquals($this->_q(), 0);
    }
    function test_search()
    {
        $it = new AIT_ItemType('itemtype', $this->db);
        $i1 = $it->addItem('A');
        $i2 = $it->addItem('B');
        $i3 = $it->addItem('C');
        $tt = $it->addTag('tagtype');
        $t1 = $tt->addTag('abcde');
        $t2 = $tt->addTag('bcdef');

        $i1->attach($t1)->attach($t2);
        $i2->attach($t2);
        $i3->attach($t1);

        $items = $it->searchItems('');
        $this->assertEquals($items->count(), 3);

        $items = $it->searchItems('item.label=\'A\'');
        $this->assertEquals($items->count(), 1);

        $items = $it->searchItems('tag.label=\'abcde\'');
        $this->assertEquals($items->count(), 2);

        $it->del();
        $this->assertEquals($this->_d(), 2);
        $this->assertEquals($this->_q(), 0);
    }
    function test_schema()
    {
        $schm = $this->db->registerSchema('Voitures', array('couleur', 'marque'));

        $this->assertEquals($schm->couleur->get(), 'couleur');
        $this->assertEquals($schm->marque->get(), 'marque');

        $t1 = $schm->couleur->addTag('rouge');
        $t2 = $schm->couleur->addTag('bleu');
        $t3 = $schm->marque->addTag('renault');
        $t4 = $schm->marque->addTag('peugeot');

        $i1 = $schm->voitures->addItem('Megane');
        $i1->attach($t1)->attach($t3);

        $i2 = $schm->voitures->addItem('206');
        $i2->attach($t2)->attach($t4);

        $schm->voitures->del();
        $this->assertEquals($this->_d(), 2);
        $this->assertEquals($this->_q(), 0);
    }
    function test_query1()
    {
        $it = new AIT_ItemType('itemtype', $this->db);
        $i1 = $it->addItem('I1');
        $i2 = $it->addItem('I2');
        $i3 = $it->addItem('I3');
        $i4 = $it->addItem('I4');
        $tt = $it->addTag('tagtype');
        $t1 = $tt->addTag('T1');
        $t2 = $tt->addTag('T2');
        $t3 = $tt->addTag('T3');
        $t4 = $tt->addTag('T4');
        $t5 = $tt->addTag('T5');

        $i1->attach($t1)->attach($t2)->attach($t4);
        $i2->attach($t1)->attach($t2)->attach($t3);
        $i3->attach($t1)->attach($t3)->attach($t4);
        $i4->attach($t2)->attach($t3)->attach($t4);

        $q =  new AITQuery($this->db);
        $q->all(new ArrayObject(array($t1, $t2)));
        $q->one(new ArrayObject(array($t3, $t4)));

        $items = $it->queryItems($q);
        $this->assertEquals($items->count(), 2);

        $it->del();
        $this->assertEquals($this->_d(), 2);
        $this->assertEquals($this->_q(), 0);
    }

    function test_query2()
    {
        $it = new AIT_ItemType('itemtype', $this->db);
        $i1 = $it->addItem('1');
        $i2 = $it->addItem('2');
        $i3 = $it->addItem('3');
        $i4 = $it->addItem('4');
        $i5 = $it->addItem('5');
        $tt = $it->addTag('tagtype');
        $a = $tt->addTag('A');
        $b = $tt->addTag('B');
        $c = $tt->addTag('C');
        $d = $tt->addTag('D');
        $e = $tt->addTag('E');
        $f = $tt->addTag('F');

        $i1->attach($a)->attach($b)->attach($d);
        $i2->attach($a)->attach($c)->attach($d);
        $i3->attach($a)->attach($b)->attach($d);
        $i4->attach($a)->attach($e);
        $i5->attach($a)->attach($f);

        $q =  new AITQuery($this->db);
        $q->all(new ArrayObject(array($a, $b, $d)));
        $q->eitheror();
        $q->all(new ArrayObject(array($a, $e)));
        $q->eitheror();
        $q->all(new ArrayObject(array($a, $f)));

        $items = $it->queryItems($q);
        $this->assertEquals($items->count(), 4);

        $it->del();
        $this->assertEquals($this->_d(), 2);
        $this->assertEquals($this->_q(), 0);
    }
    function test_serialize()
    {
        $it = new AIT_ItemType('itemtype', $this->db);
        $i1 = $it->addItem('#1');
        $i2 = $it->addItem('#2');
        $this->assertEquals($this->_d(), 5);

        $si1 = unserialize(serialize($i1));

        $this->assertEquals($i1->get(), $si1->get());
        $this->assertEquals($i1->getSystemID(), $si1->getSystemID());

        $it->del();
        $this->assertEquals($this->_d(), 2);

    }

    function test_static()
    {
        $it1 = new AIT_ItemType('A', $this->db);
        $it2 = new AIT_ItemType('B', $this->db);

        $ret = AIT_ItemType::getAll($this->db);
        $this->assertEquals($ret->count(), 2);
        $this->assertEquals($ret[0]->get(), 'A');
        $this->assertEquals($ret[1]->get(), 'B');

        $a = new AIT_ItemType('ItemType', $this->db);
        $b = $a->addItem('Item');
        $c = $a->addTag('TagType');
        $d = $c->addTag('Tag');

        $ret = AIT::getBySystemID($this->db, $a->getSystemID());
        $this->assertTrue($ret instanceof AIT_ItemType);
        $this->assertEquals($ret->get(), 'ItemType');
        $ret = AIT::getBySystemID($this->db, $b->getSystemID());
        $this->assertTrue($ret instanceof AIT_Item);
        $this->assertEquals($ret->get(), 'Item');
        $ret = AIT::getBySystemID($this->db, $c->getSystemID());
        $this->assertTrue($ret instanceof AIT_TagType);
        $this->assertEquals($ret->get(), 'TagType');
        $ret = AIT::getBySystemID($this->db, $d->getSystemID());
        $this->assertTrue($ret instanceof AIT_Tag);
        $this->assertEquals($ret->get(), 'Tag');

        $d->del();
        $c->del();
        $b->del();
        $a->del();
        $it1->del();
        $it2->del();
        $this->assertEquals($this->_d(), 2);
    }


    function test_searchtags()
    {
        $it = new AIT_ItemType('itemtype', $this->db);
        $tt = $it->addTag('tagtype');
        $a = $tt->addTag('A');
        $b = $tt->addTag('B');
        $c = $tt->addTag('C');
        $d = $tt->addTag('D1');
        $e = $tt->addTag('D2');
        $f = $tt->addTag('D3');

        $this->assertEquals($tt->countTags(), 6);

        $tags = $tt->searchTags('');
        $this->assertEquals($tags->count(), 6);

        $tags = $tt->searchTags('tag.label=\'A\'');
        $this->assertEquals($tags->count(), 1);

        $tags = $tt->searchTags('label=\'A\'');
        $this->assertEquals($tags->count(), 1);

        $tags = $tt->searchTags('label like \'D%\'');
        $this->assertEquals($tags->count(), 3);

        $it->del();
        $this->assertEquals($this->_d(), 2);
    }



    function test_frequency()
    {
        $it = new AIT_ItemType('itemtype', $this->db);
        $i1 = $it->addItem('1');
        $i2 = $it->addItem('2');
        $i3 = $it->addItem('3');
        $i4 = $it->addItem('4');
        $i5 = $it->addItem('5');
        $tt = $it->addTag('tagtype');
        $a = $tt->addTag('A');
        $b = $tt->addTag('B');
        $c = $tt->addTag('C');
        $d = $tt->addTag('D');
        $e = $tt->addTag('E');
        $f = $tt->addTag('F');

        $i1->attach($a)->attach($b)->attach($d);
        $i2->attach($a)->attach($c)->attach($d);
        $i3->attach($a)->attach($b)->attach($d);
        $i4->attach($a)->attach($e);
        $i5->attach($a)->attach($f);

        $this->assertEquals($a->countItems(), 5);
        $this->assertEquals($b->countItems(), 2);
        $this->assertEquals($c->countItems(), 1);
        $this->assertEquals($d->countItems(), 3);
        $this->assertEquals($e->countItems(), 1);
        $this->assertEquals($f->countItems(), 1);

        $f->setFrequency(2);        
        $this->assertEquals($f->countItems(), 2);

        $it->del();
        $this->assertEquals($this->_d(), 2);
        $this->assertEquals($this->_q(), 0);
    }

    function test_frequency2()
    {
        $it = new AIT_ItemType('itemtype', $this->db);
        $i1 = $it->addItem('1');
        $i2 = $it->addItem('2');

        $tt1 = $it->addTag('tagtype1');
        $tt2 = $it->addTag('tagtype2');

        $a = $tt1->addTag('A');
        $b = $tt1->addTag('B');
        $c = $tt1->addTag('C');
        $d = $tt2->addTag('D');
        $e = $tt2->addTag('E');

        $i1->attach($a)->attach($b)->attach($c);
        $i2->attach($a)->attach($b)->attach($c);

        $i1->attach($d);
        $i2->attach($e);
       
        $this->assertEquals($a->countItems(), 2);
        $this->assertEquals($d->countItems(), 1);

        $i2->del();
        
        $aa = $i1->getTag('A', $tt1);
        $this->assertEquals($aa->countItems(), 1);

        $this->assertEquals($a->countItems(), 2);
//        $a->cleanCache();
//        $this->assertEquals($a->countItems(), 1);

        $this->assertEquals($e->countItems(), 0); // Ne doit plus exister ?

        $it->del();
        $this->assertEquals($this->_d(), 2);
        $this->assertEquals($this->_q(), 0);

        }

    function test_fetchItems()
    {
        $it = new AIT_ItemType('itemtype', $this->db);
        $i1 = $it->addItem('1');
        $i2 = $it->addItem('2');
        $i3 = $it->addItem('3');
        $i4 = $it->addItem('4');
        $i5 = $it->addItem('5');
        $tt = $it->addTag('tagtype');
        $a = $tt->addTag('A');
        $b = $tt->addTag('B');
        $c = $tt->addTag('C');
        $d = $tt->addTag('D');
        $e = $tt->addTag('E');
        $f = $tt->addTag('F');

        $i1->attach($a)->attach($b)->attach($d);
        $i2->attach($a)->attach($b)->attach($d);
        $i3->attach($a)->attach($b)->attach($d);
        $i4->attach($a)->attach($c)->attach($e);
        $i5->attach($c)->attach($e)->attach($f);

        $q = new ArrayObject(array($a, $b, $d));
        $items = $it->fetchItems($q);
        $this->assertEquals($items->count(), 3);

        $q = new ArrayObject(array($e));
        $items = $it->fetchItems($q);
        $this->assertEquals($items->count(), 2);

        $q = new ArrayObject(array($e, $c));
        $items = $it->fetchItems($q);
        $this->assertEquals($items->count(), 2);

        $q = new ArrayObject(array($f));
        $items = $it->fetchItems($q);
        $this->assertEquals($items->count(), 1);

        $it->del();
        $this->assertEquals($this->_d(), 2);
        $this->assertEquals($this->_q(), 0);
    }

    function test_gettype()
    {
        $itA = new AIT_ItemType('itemtype', $this->db);
        $i = $itA->addItem('1');
        $ttA = $itA->addTag('tagtype');
        $a = $ttA->addTag('A');

        $itB = $i->getItemType();
        $this->assertEquals($itA->getSystemID(), $itB->getSystemID());
        $this->assertEquals($itA->get(), $itB->get());

        $ttB = $a->getTagType();
        $this->assertEquals($ttA->getSystemID(), $ttB->getSystemID());
        $this->assertEquals($ttA->get(), $ttB->get());

        $itA->del();
        $this->assertEquals($this->_d(), 2);
        $this->assertEquals($this->_q(), 0);
    }
    function test_tagobject()
    {
        $it = new AIT_ItemType('itemtype', $this->db);
        $i1 = $it->addItem('1');
//        $i2 = $it->addItem('2');
//        $i3 = $it->addItem('3');
//        $i4 = $it->addItem('4');
//        $i5 = $it->addItem('5');
        $tt1 = $it->addTag('tagtype1');
        $a = $tt1->addTag('A');
        $b = $tt1->addTag('B');
        $tt2 = $it->addTag('tagtype2');
        $c = $tt2->addTag('C');
        $d = $tt2->addTag('D');
//        $tt3 = $it->addTag('tagtype3');
//        $e = $tt3->addTag('E');
//        $f = $tt3->addTag('F');

        $i1->attach($a)->attach($b)->attach($d);
//        $i2->attach($a)->attach($b)->attach($d);
//        $i3->attach($a)->attach($b)->attach($d);
//        $i4->attach($a)->attach($c)->attach($e);
//        $i5->attach($c)->attach($e)->attach($f);

        $to = $i1->getTagsObject();
        $this->assertEquals($to->tagtype1->count(), 2);
        $this->assertEquals($to->tagtype2->count(), 1);

        $it->del();
        $this->assertEquals($this->_d(), 2);
        $this->assertEquals($this->_q(), 0);
    }



    /**/
    private function _d()
    {
        $stmt = $this->db->query(
            sprintf("SELECT count(*) c FROM %s LIMIT 0,1", $this->db->tag())
        );
        $c = (int)$stmt->fetchColumn(0);
        $stmt->closeCursor();
        return $c;
    }
    private function _q()
    {
        $stmt = $this->db->query(
            sprintf("SELECT count(*) c FROM %s LIMIT 0,1", $this->db->tagged())
        );
        $c = (int)$stmt->fetchColumn(0);
        $stmt->closeCursor();
        return $c;
    }

}
