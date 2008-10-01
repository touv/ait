<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 encoding=utf-8 fdm=marker :

require_once 'Table.php';

class Table_ItemType extends Table
{
    protected $_params = array('href');
    protected $href    = null;

    public $selected;

    function __construct($params = array(), $filename = __FILE__)
    {
        parent::__construct($params, $filename);
        $this->selected = $this->getPersistentVar('it');
    }

    function index()
    {
        $this->View->rows = AIT_ItemType::getAll($this->db);
    }

}
