<?php
require_once 'Table.php';

class Table_Tag extends Table
{
    protected $_params = array('id', 'href', 'itid');
    protected $id      = null;
    protected $href    = null;
    protected $itid    = null;

    function __construct($params = array(), $filename = __FILE__)
    {
        parent::__construct($params, $filename);
    }

    function index()
    {
        if (is_null($this->itid)) {
            $o = new AIT_Item(null, null, $this->db, (int) $this->id);
        }
        else {
            $o = new AIT_TagType(null, (int)$this->itid, $this->db, (int)$this->id);
        }
        $this->View->rows = $o->getTags();
    }
}
