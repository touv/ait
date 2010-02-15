<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 encoding=utf-8 fdm=marker :

error_reporting(E_ALL);
ini_set('include_path', dirname(__FILE__).PATH_SEPARATOR.ini_get('include_path'));


require_once 'AIT.php';
require_once 'PHPUnit/Framework.php';


class AITTest extends PHPUnit_Framework_TestCase
{
    var $db;
    function setUp()
    {
        $cnxstr = 'mysql:host=127.0.0.1;port=330600;dbname=ait';
        //        $cnxstr = 'mysql:host=thouveni.ads.intra.inist.fr;dbname=allistag';
        $options = array(
            'prefix'         => 'test_',
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
    function test_fix()
    {
        $it = new AIT_ItemType('itemtype', $this->db, false, array(
            'prefix' => 'A',
            'suffix' => 'B',
            'buffer' => 'AB',
        ));

        $itt = AIT::getBySystemID($this->db, $it->getSystemID());
        $this->assertEquals($itt->get('prefix'), 'A');
        $this->assertEquals($itt->get('suffix'), 'B');
        $this->assertEquals($itt->get('buffer'), 'AB');

        $tt = $it->addTag('tagtype', array(
            'prefix' => 'C',
            'suffix' => 'D',
            'buffer' => 'CD',
        ));

        $tags  = $it->getTags(null, null, null, array('prefix'=>'C'));
        $this->assertEquals($tags->count(), 1);
        $this->assertTrue($tags[0]->exists());
        $this->assertEquals($tags[0]->get(), 'tagtype');

        $ttt = AIT::getBySystemID($this->db, $tt->getSystemID());
        $this->assertEquals($ttt->get('prefix'), 'C');
        $this->assertEquals($ttt->get('suffix'), 'D');
        $this->assertEquals($ttt->get('buffer'), 'CD');

        $c1 = array(
            'prefix' => 'E',
            'suffix' => 'F',
            'buffer' => 'EF',
        );
        $t1 = $tt->addTag('@1', $c1);
        array_shift($c1);
        $tags  = $tt->getTags(null, null, null, $c1);
        $this->assertEquals($tags->count(), 1);
        $this->assertTrue($tags[0]->exists());
        $this->assertEquals($tags[0]->get(), '@1');

        $t1t = AIT::getBySystemID($this->db, $t1->getSystemID());
        $this->assertEquals($t1t->get('prefix'), 'E');
        $this->assertEquals($t1t->get('suffix'), 'F');
        $this->assertEquals($t1t->get('buffer'), 'EF');


        $c2 = array(
            'prefix' => 'G',
            'suffix' => 'H',
            'buffer' => 'GH',
        );
        $t2 = $tt->addTag('@2', $c2);

        $tags  = $tt->getTags(null, null, null, $c2);
        $this->assertEquals($tags->count(), 1);
        $this->assertTrue($tags[0]->exists());
        $this->assertEquals($tags[0]->get(), '@2');

        $t2t = AIT::getBySystemID($this->db, $t2->getSystemID());
        $this->assertEquals($t2t->get('prefix'), 'G');
        $this->assertEquals($t2t->get('suffix'), 'H');
        $this->assertEquals($t2t->get('buffer'), 'GH');

        $i1 = $it->addItem('#1', array(
            'prefix' => 'I',
            'suffix' => 'J',
            'buffer' => 'IJ',
        ));
        $i1->attach($t1)->attach($t2);

        $i1t = AIT::getBySystemID($this->db, $i1->getSystemID());
        $this->assertEquals($i1t->get('prefix'), 'I');
        $this->assertEquals($i1t->get('suffix'), 'J');
        $this->assertEquals($i1t->get('buffer'), 'IJ');


        $i2 = $it->addItem('#2', array(
            'prefix' => 'K',
            'suffix' => 'L',
            'buffer' => 'KL',
        ));
        $i2->attach($t2);

        $i2t = AIT::getBySystemID($this->db, $i2->getSystemID());
        $this->assertEquals($i2t->get('prefix'), 'K');
        $this->assertEquals($i2t->get('suffix'), 'L');
        $this->assertEquals($i2t->get('buffer'), 'KL');

        $tags = $it->getItems(null, null, null, array('suffix'=>'L'));
        $this->assertEquals($tags->count(), 1);
        $this->assertTrue($tags[0]->exists());
        $this->assertEquals($tags[0]->get(), '#2');


        $tags = $i1->getTags(null, null, null, array('suffix'=>'H'));
        $this->assertEquals($tags->count(), 1);
        $this->assertTrue($tags[0]->exists());
        $this->assertEquals($tags[0]->get(), '@2');

        $q = new ArrayObject(array($t2));
        $tags = $it->fetchItems($q, null, null, null, array('buffer'=>'KL'));
        $this->assertEquals($tags->count(), 1);
        $this->assertTrue($tags[0]->exists());
        $this->assertEquals($tags[0]->get(), '#2');

        $q =  new AITQuery($this->db);
        $q->one(new ArrayObject(array($t2)));
        $tags = $it->queryItems($q, null, null, null, array('buffer'=>'KL'));
        $this->assertEquals($tags->count(), 1);
        $this->assertTrue($tags[0]->exists());
        $this->assertEquals($tags[0]->get(), '#2');

        $items = $t2->getItems(null, null, null, array('suffix'=>'KL'));
        $this->assertEquals($tags->count(), 1);
        $this->assertTrue($tags[0]->exists());
        $this->assertEquals($tags[0]->get(), '#2');



        $tt->del();
        $this->assertEquals($this->_d(), 5);
        $it->del();
        $this->assertEquals($this->_d(), 2);
    }

    function test_define()
    {
        $it = new AIT_ItemType('A', $this->db);
        $t1 = $it->defTag('C')->defTag('D');
        $t1->attach($it->defItem('B'));
        $t2 = $it->defItem('B')->defTag('D', $it->defTag('C'));

        $this->assertEquals($t2, $t1);

        $it->del();
        $this->assertEquals($this->_d(), 2);
        $this->assertEquals($this->_q(), 0);
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
        $this->assertEquals($it->countItems(), 1);

        $this->assertEquals($tt->countItems(), 1);

        $this->assertEquals($t1->countItems(), 1);

        $this->assertEquals($this->_d(), 6);
        $this->assertEquals($this->_q(), 2);

        $i1->del();
        $this->assertEquals($this->_d(), 4);
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
    function test_related2()
    {
        $it = new AIT_ItemType('itemtype', $this->db);
        $i1 = $it->addItem('1');
        $i2 = $it->addItem('2');
        $i3 = $it->addItem('3');           //        | 1 | 2 | 3 | 4 |
        $i4 = $it->addItem('4');           //        +---------------+
        $y1 = $it->addTag('Y1');            //   y1  | A |   | A |   |
        $t1 = $y1->addTag('A');            //        | B | B | B |   |
        $t2 = $y1->addTag('B');            //        | C | C |   | C |
        $t3 = $y1->addTag('C');            //        |   | D | D | D |
        $t4 = $y1->addTag('D');            //        |   |   | E | E |
        $t5 = $y1->addTag('E');            //    .....................
        $y2 = $it->addTag('y2');            //    Y2 | W | X | Y | Z |
        $t6 = $y2->addTag('W');            //
        $t7 = $y2->addTag('X');
        $t8 = $y2->addTag('Y');
        $t9 = $y2->addTag('Z');

        $i1->attach($t1)->attach($t2)->attach($t3)->attach($t6);
        $i2->attach($t2)->attach($t3)->attach($t4)->attach($t7);
        $i3->attach($t1)->attach($t2)->attach($t4)->attach($t5)->attach($t8);
        $i4->attach($t3)->attach($t4)->attach($t5)->attach($t9);

        $tags = $t2->fetchRelatedTags(new ArrayObject());
        $str = ''; foreach($tags as $tag) $str .= $tag->get();
        $this->assertEquals($tags->count(), 7);
        $this->assertEquals($str, 'ACWDXEY');

        $tags = $t2->fetchRelatedTags(new ArrayObject(array($y2)));
        $str = ''; foreach($tags as $tag) $str .= $tag->get();
        $this->assertEquals($tags->count(), 3);
        $this->assertEquals($str, 'WXY');

        $tags = $t2->fetchRelatedTags(new ArrayObject(array($y2, $t4)));
        $str = ''; foreach($tags as $tag) $str .= $tag->get();
        $this->assertEquals($tags->count(), 2);
        $this->assertEquals($str, 'XY');

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

    function test_search()
    {
        $it = new AIT_ItemType('itemtype', $this->db);
        $i1 = $it->addItem('a');
        $i2 = $it->addItem('b');
        $i3 = $it->addItem('c');
        $i4 = $it->addItem('d');
        $i5 = $it->addItem('e');
        $tt = $it->addTag('tagtype');
        $t1 = $tt->addTag('abcde');
        $t2 = $tt->addTag('bcdef');

        $i1->attach($t1)->attach($t2);
        $i2->attach($t2);
        $i3->attach($t1);

        $items = $it->searchItems('');  //  --> a, b, c
        $this->assertEquals($items->count(), 3);

        $items = $it->selectItems(''); // --> a, b, c, d, e
        $this->assertEquals($items->count(), 5);

        $items = $it->searchItems('item.label=\'a\'');  // --> a
        $this->assertEquals($items->count(), 1);

        $items = $it->selectItems('item.label=\'a\''); // --> a
        $this->assertEquals($items->count(), 1);

        $items = $it->searchItems('item.label=\'e\''); // --> NULL
        $this->assertEquals($items->count(), 0);

        $items = $it->selectItems('item.label=\'e\''); // --> e
        $this->assertEquals($items->count(), 1);

        $items = $it->searchItems('tag.label=\'abcde\''); // --> a, c
        $this->assertEquals($items->count(), 2);

        $misc = $it->search('tag.label=\'abcde\'');
        $this->assertEquals($misc->count(), 1);

        $misc = $it->search('tag.label like \'a%\''); // --> a, c
        $this->assertEquals($misc->count(), 2);

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
        $ar = $it->getItems(1, 10);

        $this->assertEquals($this->_d(), 5);

        $si1 = unserialize(serialize($i1));
        $sar = unserialize(serialize($ar));

        $this->assertEquals($i1->get(), $si1->get());
        $this->assertEquals($i1->getSystemID(), $si1->getSystemID());
        $this->assertEquals($ar->count(), $sar->count());
        $this->assertEquals($ar[0]->get(), $sar[0]->get());
        $this->assertEquals($ar->total(), $sar->total());

        $it->del();
        $this->assertEquals($this->_d(), 2);

    }

    function test_static()
    {
        $it1 = new AIT_ItemType('A', $this->db);
        $it2 = new AIT_ItemType('B', $this->db);

        $this->assertEquals($this->_d(), 4);


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
        $this->assertEquals($a->countItems(), 1);

        $this->assertEquals($e->countItems(), 0);
        $this->assertFalse($e->exists());

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

    function test_detach()
    {
        $it = new AIT_ItemType('A', $this->db);
        $tt = $it->addTag('tagtype1');

        $it->defItem('B')->addTag('C', $tt);

        $tags = $it->defItem('B')->getTypedTags($tt);

        $this->assertEquals($tags->count(), 1);
        $this->assertEquals($tags->offsetGet(0)->countItems(), 1);

        $tags->offsetGet(0)->detach();

        $this->assertFalse($tags->offsetGet(0)->exists());

        $it->del();
        $this->assertEquals($this->_d(), 2);
        $this->assertEquals($this->_q(), 0);
    }

    function test_tagorder()
    {
        $it = new AIT_ItemType('A', $this->db);
        $i1 = $it->addItem('B');
        $tt = $it->addTag('C');
        $t1 = $tt->addTag('1');
        $t2 = $tt->addTag('2');

        $i1->attach($t1);
        $i1->attach($t2);
        $i1->addTag('3', $tt);
        $i1->addTag('4', $tt);

        $tags = $i1->getTags();

        $c = '';
        foreach($tags as $tag)
            $c .= $tag->get();

        $this->assertEquals($tags->count(), 4);
        $this->assertEquals($c, '1234');

        $i2 = $it->addItem('D');

        $t3 = $tt->addTag('3');
        $t4 = $tt->addTag('4');

        $i2->attach($t4);
        $i2->attach($t1, AIT::INSERT_FIRST);
        $i2->attach($t3, $t1);
        $i2->attach($t2, $t1);

        $tags = $i2->getTags(null, null, AIT::ORDER_BY_RANK);

        $c = '';
        foreach($tags as $tag)
            $c .= $tag->get();

        $this->assertEquals($tags->count(), 4);
        // 4132 => 1234
        $this->assertEquals($c, '1234');

        $it->del();
        $this->assertEquals($this->_d(), 2);
        $this->assertEquals($this->_q(), 0);
    }
    function test_attach_polymorph()
    {
        $it = new AIT_ItemType('A', $this->db);
        $i1 = $it->addItem('B');
        $i2 = $it->addItem('C');
        $tt = $it->addTag('D');
        $t1 = $tt->addTag('E');
        $t2 = $tt->addTag('F');

        $i1->attach($t1)->attach($t2);
        $i2->attach($t1);

        $i1->attach($i2);

        $tags = $i1->getTags();
        $this->assertEquals($tags->count(), 2);

        $tags = $i1->getTypedTags($tt);
        $this->assertEquals($tags->count(), 2);

        $tags = $i1->fetchTags(new ArrayObject(array($tt)));
        $this->assertEquals($tags->count(), 2);

        $items = $i1->getItems();
        $this->assertEquals($items->count(), 1);
        $items = $i1->getTypedItems($it);
        $this->assertEquals($items->count(), 1);

        $items = $i1->fetchItems(new ArrayObject(array($it)));
        $this->assertEquals($items->count(), 1);

        $elems = $i1->getElements();
        $this->assertEquals($elems->count(), 3);

        $elems = $i1->getTypedElements($tt);
        $this->assertEquals($elems->count(), 2);

        $elems = $i1->getTypedElements($it);
        $this->assertEquals($elems->count(), 1);

        $elems = $i1->fetchElements(new ArrayObject(array($it, $tt)));
        $this->assertEquals($elems->count(), 3);

        $it->del();
        $this->assertEquals($this->_d(), 2);
        $this->assertEquals($this->_q(), 0);
    }
    function test_attach_directaccess()
    {
        $it = new AIT_ItemType('A', $this->db);
        $i1 = $it->addItem('B');
        $i2 = $it->addItem('C');
        $tt1 = $it->addTag('D');
        $tt2 = $it->addTag('E');
        $t1 = $tt1->addTag('F');
        $t2 = $tt1->addTag('G');
        $t3 = $tt2->addTag('H');

        $i1->attach($t1)->attach($t2)->attach($t3);
        $i2->attach($t3);


        $this->assertEquals($it->D, $tt1);
        $this->assertEquals($it->E, $tt2);

        $this->assertEquals($i1->D->count(), 2);
        $this->assertEquals($i1->E->count(), 1);
        $this->assertEquals($i1->E->offsetGet(0)->getSystemID(), $t3->getSystemID());
        $this->assertEquals($i2->E->count(), 1);
        $this->assertEquals($i2->E->offsetGet(0)->getSystemID(), $t3->getSystemID());

        $it->del();
        $this->assertEquals($this->_d(), 2);
        $this->assertEquals($this->_q(), 0);
    }
    function test_dat()
    {
        $it = new AIT_ItemType('itemtype', $this->db);
        $i1 = $it->addItem('#1', array(
            'content' => 'dièse un',
        ));
        $i2 = $it->addItem('#2', array(
            'content' => 'dièse deux',
        ));

        $tt = $it->addTag('tagtype');
        $t1 = $tt->addTag('@1', array(
            'content' => 'arobase un',
        ));
        $t2 = $tt->addTag('@2', array(
            'content' => 'arobase deux',
        ));

        $i1->attach($t1)->attach($t2);
        $i2->attach($t1);

        $t1b = $i2->getTag('@1', $tt);
        $t2b = $i1->getTag('@2', $tt);
        $i1b = $t2->getItems()->offsetGet(0);


        $this->assertEquals($t1b->get('content'), 'arobase un');
        $this->assertEquals($t2b->get('content'), 'arobase deux');
        $this->assertEquals($i1b->get('content'), 'dièse un');

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
