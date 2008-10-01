<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 fdm=marker encoding=utf8 :
/**
 * Pxxo - build self-supported and interoperable Web graphical components
 * 
 * Copyright (c) 2008, Nicolas Thouvenin
 *
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the author nor the names of its contributors may be 
 *       used to endorse or promote products derived from this software without 
 *       specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE REGENTS AND CONTRIBUTORS ``AS IS'' AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE REGENTS AND CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

 * @package    Pxxo
 * @copyright  Copyright (c) 2008 Nicolas Thouvenin 
 * @license    http://opensource.org/licenses/bsd-license.php
 * @version    $Id$
 */
require_once 'Pxxo.php';
/**
 * Classe permet de stocker des Objets Pxxo_Widget
 *
 * @package    Pxxo
 * @copyright  Copyright (c) 2008 Nicolas Thouvenin 
 * @license    http://opensource.org/licenses/bsd-license.php
 */
class Pxxo_Widgets extends Pxxo implements Countable, Iterator
{
    /**
     * Contains array of configuration data
     *
     * @var array
     */
    protected $_childs;

    /**
     * Parent Object
     *
     * @var Pxxo_Widget
     */
    protected $_parent;

    /**
     * Constructeur
     */
    public function __construct($parent)
    {
        $this->_parent = $parent;
        $this->_childs   = array();
    }
  
    /**
     * Magic function so that $obj->value will work.
     *
     * @param string 
     * @return mixed
     */
    public function __get($key)
    {
        if (!is_string($key)) {
            return Pxxo::triggerError('P_E_0017', E_USER_NOTICE,  __METHOD__,
                'Invalid parameter', 
                "`$key` is not string");
        }
        return $this->_childs[$key];
    }


    /**
     * Ajout d'un widget à la liste
     *
     * @param string $key The variable name.
     * @param Pxxo_Widget $val The variable value.
     * @return void
     */
    public function add($key, Pxxo_Widget $val)
    {
        if (!is_string($key)) {
            return Pxxo::triggerError('P_E_0017', E_USER_NOTICE,  __METHOD__,
                'Invalid parameter', 
                "`$key` is not string");
        }
        if (isset($this->_childs[$key])) {
            unset($this->_childs[$key]);
        }
        $this->_childs[$key] = $val;
    }

     /**
     * Test la présence d'un widget à partir de son alias
     *
     * @param string $key The variable name.
     * @return boolean
     */
    public function exists($key)
    {
        return isset($this->_childs[$key]);
    }



    /**
     * Magic function to set value 
     *
     * @param string $key The variable name.
     * @param mixed $val The variable value.
     * @return void
     */
    public function __set($key, $val)
    {
        if (!is_string($key)) {
            return Pxxo::triggerError('P_E_0017', E_USER_NOTICE,  __METHOD__,
                'Invalid parameter', 
                "`$key` is not string");
        }
        if ( ! $val instanceof Pxxo_Widget) {
            return Pxxo::triggerError('P_E_0017', E_USER_NOTICE,  __METHOD__,
                'Invalid parameter', 
                "Second parameter is not a instance of Pxxo_Widget");
        }
        $val->ClassAlias = $key;
        $obj = $this->_parent->stackWidget($val);
        if ($obj) $this->_parent->View->$key = $val->get();
    }

    /**
     * Magic function to test key
     *
     * @param string $key The variable name.
     * @return boolean
     */
    public function __isset($key) 
    {
        return isset($this->_childs[$key]);
    }

    /**
     * Magic function to test key
     *
     * @param string $key The variable name.
     * @return boolean
     */
    public function __unset($key) 
    {
        unset($this->_childs[$key]);
    }

    /**
     * Defined by Countable interface
     *
     * @return int
     */
    public function count()
    {
        return count($this->_childs);
    }

    /**
     * Defined by Iterator interface
     *
     * @return mixed
     */
    public function current()
    {
        $k = key($this->_childs);
        return($this->_childs[$k]);
    }

    /**
     * Defined by Iterator interface
     *
     * @return mixed
     */
    public function key()
    {
        return key($this->_childs);
    }

    /**
     * Defined by Iterator interface
     *
     */
    public function next()
    {
        return next($this->_childs);
    }

    /**
     * Defined by Iterator interface
     *
     */
    public function rewind()
    {
        return reset($this->_childs);
    }

    /**
     * Defined by Iterator interface
     *
     * @return boolean
     */
    public function valid()
    {
        return array_key_exists(key($this->_childs),$this->_childs);
    }
}
