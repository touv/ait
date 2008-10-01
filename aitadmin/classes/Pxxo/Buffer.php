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
 * Classe permet de gérer l'accès à des flux de données
 * Un flux est par exemple une chaine de caractère, un fichier, 
 * mais cela pourait être une base de données, une zone mémoire, peu importe...
 *
 * @package    Pxxo
 * @copyright  Copyright (c) 2008 Nicolas Thouvenin 
 * @license    http://opensource.org/licenses/bsd-license.php
 */
class Pxxo_Buffer extends Pxxo
{
    /**
     * @var		string identifiant unique du Buffer
     */
    protected $id = '';
    /**
     * @var		string localisation du flux
     */
    public $uri = '';
    /**
     * @var		string 
     */
    protected $content = '';
    /**
     * @var		array list de callback à appliquer en sortie du flux
     */
    protected $_func = array();
    /**
     * @var     string type du contenu du flux
     */
    protected $type = 'txt';
    /**
     * @var		string  support pour le flux (url/file/buffer/...)
     */
     public $scheme = 'buffer';

     /**
     * @var integer importance du Buffer
     */
     protected $weight = 0;
    /**
     * Constructeur PHP5
     *
     * @param	string localisation du flux
     */
    function __construct($a = null)
    {
        parent::__construct();
        $this->set($a);
    }
    /**
     * Création d'un objet pour un type de flux spécifique
     *
     * @param   string type du fichier
     * @return	object
     * @static
     */
    public static function factory($type, $uri)
    {
        $type = strtolower($type);
        switch ($type) {
        case 'php':
            include_once('Pxxo/Buffer/PHP.php');
            $o = new Pxxo_Buffer_PHP($uri);
            break;
        case 'html':
            include_once('Pxxo/Buffer/HTML.php');
            $o = new Pxxo_Buffer_HTML($uri);
            break;
        case 'css':
        case 'style':
            include_once('Pxxo/Buffer/Resource/CSS.php');
            $o = new Pxxo_Buffer_Resource_CSS($uri);
            break;
        case 'js':
        case 'javascript':
            include_once('Pxxo/Buffer/Resource/Javascript.php');
            $o = new Pxxo_Buffer_Resource_Javascript($uri);
            break;
        case 'title':
            include_once('Pxxo/Buffer/Header/Title.php');
            $o = new Pxxo_Buffer_Header_Title($uri);
            break;
        case 'meta':
            include_once('Pxxo/Buffer/Header/Meta.php');
            $o = new Pxxo_Buffer_Header_Meta($uri);
            break;
        case 'header':
            include_once('Pxxo/Buffer/Header.php');
            $o = new Pxxo_Buffer_Header($uri);
            break;
        case 'png':
        case 'gif':
        case 'jpg':
        case 'svg':
        case 'cur':
            include_once('Pxxo/Buffer/Resource.php');
            $o = new Pxxo_Buffer_Resource($uri);
            $o->type = $type;
            break;
        case 'tpl':
        default:
            $o = new Pxxo_Buffer($uri);
        }
        return $o;
    }
    /**
     * On récupère le contenu du flux
     *
     * @return	string le contenu du flux
     */
    public function get() 
    {
        if (!is_null($this->content) and $this->content === '' and $this->scheme == 'file') {
            $this->content = file_get_contents($this->uri);
        }
        $this->filter();
        return $this->content;
    }
    /**
     * On rempli soit même le flux
     *
     * @param   mixed  
     */
    public function set($p) 
    {
        if (!is_null($p)) {
            if (strpos($p, "\n") === false and strpos($p, '<') === false and strlen($p < 256) and is_file($p)) {
                // Reference vers un contenu
                $this->uri = $p;
                $this->scheme = 'file';
                $this->setID(md5(''.filemtime($this->uri).$this->uri));
            }
            else {
                // Le contenu direct
                $this->uri = md5(''.$p); // Localization fictive, est-ce vraiment utile ????
                $this->content = $p;
                $this->scheme = 'buffer';
                $this->setID($this->uri);
            }
        }
    }
    /**
     * Merge un buffer avec le buffer courant
     *
     * @param   Pxxo_Buffer
     * @return  Pxxo_Buffer
     */
    public function merge($o) 
    {
        $this->set($this->get().$o->get());
        return $this;
    }
    /**
     * Ajoute une fonction callback en sortie du flux
     *
     * @param    string nom d'une function ou d'une mÃ©thode de l'objet Buffer (protected)
     * @return   boolean le filtre est-il actif
     */
    public function addFilter($s) 
    {
        if (is_string($s) and method_exists($this, $s)) {
            $this->_func[] = array('method', $s);
            return true;
        }
        elseif (is_string($s) and function_exists($s)) {
            $this->_func[] = $s;
            return true;
        }
        else return false;
    }
    /**
     * Application des Filtres
     *
     */
    public function filter() 
    {
        foreach($this->_func as $func) {
            if (is_array($func) and $func[0] === 'method') {
                $this->content = call_user_func(array($this, $func[1]), $this->content);
            }
            else {
                $this->content = call_user_func($func, $this->content);
            }
        }
    }
    /**
     * Fixe l'identifiant unique du Buffer
     *
     * @param string
     */
    public function setID($s) 
    {
        if (isset($s[0]) and $s[0] == '-') $s[0] = 'z';
        $this->id = $s;
        if ($this->isCaching())
            $this->addCacheID($this->id);
    }
    /**
     * Renvoit un identifiant unique du Buffer
     *
     * @return	string
     */
    public function getID() 
    {
        return $this->id;
    }
    /**
     * Renvoi le type du Buffer
     *
     * @return	string
     */
    public function getType() 
    {
        return $this->type;
    }
    /**
     * Renvoi le type étendu du Buffer
     *
     * @return	string
     */
    public function getExtendedType() 
    {
        return $this->type;
    }

    /**
     * Teste le type du Buffer
     *
     * @return	boolean
     */
    public function isType($type) 
    {
        return $this->type === $type ? true : false;
    }

    /**
     * Donne un poid au Buffer
     *
     * @param integer
     */
    public function setWeight($w)
    {
        $this->weight = $w;
    }

    /**
    * returne le poid duBuffer
     *
     * @return integer
     */
    public function getWeight()
    {
        return $this->weight;
    }

}
