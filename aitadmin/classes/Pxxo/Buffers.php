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
* Classe permet de stocker des Objets Pxxo_Buffers
 *
 * @package    Pxxo
 * @copyright  Copyright (c) 2008 Nicolas Thouvenin 
 * @license    http://opensource.org/licenses/bsd-license.php
 */
 class Pxxo_Buffers extends Pxxo implements Countable, Iterator
{
    /**
     * Iteration index
     *
     * @var integer
     */
    protected $_index;


    /**
     * Contains array of configuration data
     *
     * @var array
     */
     protected $_data;

     /**
     * Reprsentation trié de _data
     *
     * @var array
     */
     public $_sort;


    /**
    * Constructeur
    */
    public function __construct()
    {
        parent::__construct();
        $this->_index = 0;
        $this->_data = array();
        $this->_sort = array();
    }

    /**
     * Test si l'objet est un objet de type Pxxo_Buffer
     * 
     * @return  Pxxo_Buffer
     */

    public function isBuffer($o) 
    {
        if (is_null($o) or !is_object($o) ) return false;
        if (! $o instanceof Pxxo) return false;
        if (!isset($o->scheme) or !isset($o->uri)) return false;
        return true;
    }

     /**
     * Recupere un Buffer particulier de la liste
     * 
     * @return  Pxxo_Buffer
     */
    public function get($i) 
    {
        if (isset($this->_data[$i])) return $this->_data[$i];
        else return null;
    }

    /**
     * Magic function so that $obj->value will work.
     *
     * @param string 
     * @return mixed
     */
    public function __get($i)
    {
        return $this->get($i);
    }


    /**
     * Ajout d'un Buffer
     *
     * @param Pxxo_Buffer
     * @return integer indice
     */
    public function add($o)
    {
        if (!$this->isBuffer($o)) return false;

        $i = $o->getID();
        $this->_data[$i] = $o;
        $this->_sort[$i] = $o->getWeight();

        return $i;
    }


    /**
     * Defined by Countable interface
     *
     * @return int
     */
    public function count()
    {
        return count($this->_data);
    }

    /**
     * Defined by Iterator interface
     *
     * @return mixed
     */
    public function current()
    {
        $k = key($this->_sort);
        return($this->_data[$k]);
    }

    /**
     * Defined by Iterator interface
     *
     * @return mixed
     */
    public function key()
    {
        return key($this->_sort);
    }

    /**
     * Defined by Iterator interface
     *
     */
    public function next()
    {
        next($this->_sort);
        $this->_index++;
    }

    /**
     * Defined by Iterator interface
     *
     */
    public function rewind()
    {
        reset($this->_data);
        $this->_index = 0;
        $this->classify();
    }

    /**
     * Defined by Iterator interface
     *
     * @return boolean
     */
    public function valid()
    {
        return $this->_index < $this->count();
    }

    /**
     * Merge une liste de Buffers avec la liste courante, 
     * les entrées en communs sont ignorés.
     *
     * @param Pxxo_Buffers $merge
     * @return Pxxo_Buffers
     */
     public function merge(Pxxo_Buffers $merge)
    {
        foreach($merge as $key => $item) {
            if (!array_key_exists($key, $this->_data)) {
                $this->add($item);
            }
        }
        return $this;
    }

    /**
     * Recherche le Buffer contenant une valeur 
     *
     * @param string
     * @return string
     */
    public function search($val)
    {
        foreach($this->_data as $k => $v) if ($val == $v->get()) return $k;
    }

    /**
     * Concatene les Buffers ayant un type etendu identique
     *
     * @param string
     * @return Pxxo_Buffers
     */
    public function concat()
    {
        $this->classify();
        $a = array();
        foreach($this->_sort as $k => $w) {
            $obj = $this->_data[$k];
            $extyp = $obj->getExtendedType();
            if (!isset($a[$extyp])) $a[$extyp] = $obj;
            else {
                $a[$extyp]->merge($obj);
                unset($this->_data[$k]);
            }
        }
    }


    /**
     * N'en garder qu'un seul, supprime tout les autres...
     *
     * @param string
     * @return Pxxo_Buffer
     */
    public function keepOnlyOne($i) 
    {
        foreach($this->_data as $k => $v) if ($k != $i) unset($this->_data[$k]); 
    }

     /**
      * Triage en fonction du poid
     *
     */
    public function classify()
    {
        $this->_sort = array();
        foreach($this->_data as $k => $o) {
            $this->_sort[$k] = $o->getWeight();
        }
        arsort($this->_sort, SORT_NUMERIC);
    }

}
