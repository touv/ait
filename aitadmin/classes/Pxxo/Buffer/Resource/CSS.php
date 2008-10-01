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
require_once 'Pxxo/Buffer/Resource.php';

/**
 * Classe permet de gérer l'accès à des flux de données comme des ressources CSS
 *
 * @package    Pxxo
 * @copyright  Copyright (c) 2008 Nicolas Thouvenin 
 * @license    http://opensource.org/licenses/bsd-license.php
 */
class Pxxo_Buffer_Resource_CSS extends Pxxo_Buffer_Resource
{
    /**
     * @var     string type du contenu du flux
     */
    protected $type = 'css';
    /**
     * @var		string  media pour lequel est destiné le CSS
     */
    protected $media = 'all';
    /**
     * @var		string condition d'application de la feuille CSS  
     */
    public $condition = '';
    /**
     * @var integer importance du Buffer
     */
    protected $weight = 1000;
    /**
     * Constructeur PHP5
     *
     * @param	string localisation du flux
     */
    function __construct($p)
    {
        parent::__construct($p);
    }
    /**
     * Filtre permettant de minimiser du Code CSS
     *
     * @param   string chaine de caractère
     * @return	string chaine de caractère compressée
     */
    public function compress($s) 
    {
        $s = preg_replace('/\s+/', ' ', $s);
        $s = preg_replace('/\/\*.*?\*\//', '', $s);
        return trim($s);
    }
    /**
     * Fixe le type de média qui utilisera le css 
     *
     * @param    string 
     */
    public function setMedia($s) 
    {
        $this->media = $s;
    }
    /**
     * Retourne le media qui utilisera le css
     *
     * @return	string
     */
    public function getMedia() 
    {
        return $this->media;
    }
    /**
     * Retourne une chaine en HTML permettant de charger la ressource
     *
     * @return	string chaine de caractère en HTML
     */
    public function getHTML() 
    {
        $htmlstring = '';
        if (!empty($this->condition)) $htmlstring .= '<!--['.$this->condition.']>'."\n";;
        if ($this->disposition != 'inline') {
            $htmlstring .= '<link rel="stylesheet" type="text/css" href="'.$this->get().'" media="'.strtolower($this->media).'" />'."\n";
        }
        else {
            $htmlstring .= '<style type="text/css" media="'.strtolower($this->media).'">'.$this->getContent().'</style>'."\n";
        }
        if (!empty($this->condition)) $htmlstring .= '<![endif]-->'."\n";;
        return $htmlstring;
    }
    /**
     * Retourne un tableau PHP décrivant la ressource
     *
     * @return	string tableau php décrivant la ressource
     */
    public function getArray()
    {
        $data = array();
        $data['type']        = $this->type;
        $data['disposition'] = $this->disposition;
        $data['condition']   = $this->condition;
        if ($this->disposition != 'inline')
        {
            $data['attributes']  = array('rel' => 'stylesheet',
                                         'type' => 'text/css',
                                         'href' => $this->get(),
                                         'media' => strtolower($this->media));
        }
        else
        {
            $data['attributes']  = array('type' => 'text/css',
                                         'media' => strtolower($this->media));
            $data['content']     = $this->getContent();
        }
        return $data;
    }
    /**
     * Renvoi le type étendu du Buffer
     *
     * @return	string
     */
    public function getExtendedType() 
    {
        return $this->type.':'.$this->disposition.':'.$this->media;
    }
}
