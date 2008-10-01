<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 encoding=utf-8 fdm=marker :

require_once 'Pxxo/Widget.php';

class Table extends Pxxo_Widget
{
    private $__params = array('db');
    protected $db = null;
        public function __construct($params, $filename)
    {
        $this->_params = array_merge($this->__params, $this->_params);
        parent::__construct($params, $filename);
    }
}
