<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 encoding=utf-8 fdm=marker :

require_once 'Pxxo/Widget.php';
require_once 'Table/Item.php';
require_once 'Table/ItemType.php';
require_once 'Table/TagType.php';
require_once 'Table/Tag.php';

class Root extends Pxxo_Widget
{
    protected $_params = array('db');
    protected $db      = null;

    function __construct($params = array())
    {
        parent::__construct($params, __FILE__);
        $this->setCacheLevel(P_C_BASIC | P_C_RESOURCE | P_C_USER);
    }
    function index()
    {
        $p = array(
            'db'           => $this->db,
        );
        $o1 = new Table_ItemType($p);
        $this->putWidget('ITEMTYPE', $o1);

        if (!is_null($o1->selected)) {
            $p = array(
                'db'           => $this->db,
                'id'           => $o1->selected,
            );
            $o2 = new Table_Item($p);
            $this->putWidget('ITEM', $o2);

            if (!is_null($o2->selected)) {
                $p = array(
                    'db'           => $this->db,
                    'id'           => $o2->selected,
                    'link'         => 'AIT_Item',
                );
                $o4 = new Table_Tag($p);
                $this->putWidget('TAG1', $o4);
            }


            $p = array(
                'db'           => $this->db,
                'id'           => $o1->selected,
            );
            $o3 = new Table_TagType($p);
            $this->putWidget('TAGTYPE', $o3);
            if (!is_null($o3->selected)) {
                $p = array(
                    'db'           => $this->db,
                    'id'           => $o3->selected,
                    'itid'         => $o1->selected,
                );
                $o4 = new Table_Tag($p);
                $this->putWidget('TAG2', $o4);
            }
        }
    }
}
