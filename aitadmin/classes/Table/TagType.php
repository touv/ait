<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 encoding=utf-8 fdm=marker :

require_once 'Table.php';

class Table_TagType extends Table
{
    protected $_params = array('id', 'href');
    protected $id      = null;

    public $selected;

    function __construct($params = array(), $filename = __FILE__)
    {
        parent::__construct($params, $filename);
        $this->selected = $this->getPersistentVar('tt');
    }

    function index()
    {
        $it = new AIT_ItemType(null, $this->db, (int) $this->id);
        $this->View->rows = $it->getTags();
    }

}
