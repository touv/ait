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
 * @version    $Id: Resource.php,v 1.5 2008/03/05 15:17:49 thouveni Exp $
 */
require_once 'Pxxo/Buffer.php';

/**
 * Classe permet de gérer l'accès à des flux de données comme des ressources WEB
 *
 * Un flux de type ressource renvoit une url vers un fichier contenant le flux.
 *
 * @package    Pxxo
 * @copyright  Copyright (c) 2008 Nicolas Thouvenin 
 * @license    http://opensource.org/licenses/bsd-license.php
 */
class Pxxo_Buffer_Resource extends Pxxo_Buffer
{
    /**
     * @var     string type du contenu du flux
     */
    protected $type = 'rsc';
    /**
     * @var		string url d'accès à la ressource
     */
    public $url = './';
    /**
     * @var		string chemin physique correspondant à l'url
     */
    public $path = '.';
    /**
     * @var	array chemin physique et chemin logique de la resource
     */
    public $file = array();
    /**
     * @var	    string  file or inline : sous fomre de fichier ou de lignes de code inserées
     */
    protected $disposition = 'file';
    /**
     * @var	    string  le nom du fichier qui est a l'origine de la ressource (ex: prototype.js)
     */
    public $filename = '';

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
     * On récupere le contenu de la ressource 
     *
     * @return	mixed	 Description
     */
    public function getContent() 
    {
        return parent::get();
    }
    /**
     * On stocke le contenu du flux dans un fichier accessible via $this->url
     *
     * @return	string l'url d'accès au contenu du flux
     */
    public function get() 
    {
        // Calcul du nom et de l'emplacement du fichier
        $id = $this->getID();
        if (empty($id)) return null;
        $filename = $id.'.'.$this->type;
        $filepath = $this->path.DIRECTORY_SEPARATOR;
        $file = $filepath.$filename;
        $url = $this->url.'/'.str_replace(DIRECTORY_SEPARATOR, '/', $filename);

        if (! $this->isCaching() || ! $this->testCacheLevel(P_C_RESOURCE) || ($this->isCaching() and $this->testCacheLevel(P_C_RESOURCE) and !file_exists($file))) {
            $this->traceDebug("Buffer/RESOURCE\t[executed]\tlocalization(URI:".$this->uri.",\tRSC:".$url.')');
            if (!is_dir($filepath)) {
                if (!mkdir($filepath)) {
                    $this->triggerError('P_E_0016', E_USER_ERROR,  __METHOD__, 'PHP function failed', "mkdir $filepath");
                }
                if (chmod($filepath,0777) === FALSE) {
                    $this->triggerError('P_E_0016', E_USER_ERROR,  __METHOD__, 'PHP function failed', "chmod $filepath");
                }
            }
            if (!$handle = fopen($file, 'w')) {
                $this->triggerError('P_E_0016', E_USER_ERROR,  __METHOD__, 'PHP function failed', "fopen $file");
            }
            if (fwrite($handle, parent::get()) === FALSE) {
                $this->triggerError('P_E_0016', E_USER_ERROR,  __METHOD__, 'PHP function failed', "fwrite $file");
            }
            fclose($handle);
            if (chmod($file,0777) === FALSE) {
                $this->triggerError('P_E_0016', E_USER_ERROR,  __METHOD__, 'PHP function failed', "chmod $file");
            }
        } else {
            $this->traceDebug("Buffer/RESOURCE\t[cached]\tlocalization(URI:".$this->uri.",\tRSC:".$url.')');
        }
        $this->file = array($file, $url);
        return $url;
    }
    /**
     * Merge un buffer avec le buffer courant
     *
     * @param   Pxxo_Buffer
     * @param boolean ajout ou non un commentaire
     * @return  Pxxo_Buffer
     */
    public function merge($o, $c = true) 
    {
        if ($c === true)
            $com = $this->comment(
                ' ID('.$o->getID().')'.                
                ' URI('.$o->uri.')'.
                ' SCHEME('.$o->scheme.')'.
                ' WEIGHT('.$o->weight.')'.
                ' TYPE('.$o->getExtendedType().')');
        else $com = '';

        if (method_exists($o,'getContent')) {
            $this->set($this->getContent().$com.$o->getContent());
            return $this;
        }
    }
    /**
     * Filtre permettant de minimiser une flux
     *
     * @param   string chaine de caractère
     * @return	string chaine de caractère compressées
     * @abstract
     */
    public function compress($s) 
    {
        return $s;
    }
    /**
     * Fixe comme on utilisera le code Javascript
     *
     * @param    string 
     */
    public function setDisposition($s) 
    {
        if ($s == 'inline') $this->weight -= 50;
        $this->disposition = $s;
    }
    /**
     * Retourne le mode d'utilisation du code Javascript
     *
     * @return	string
     */
    public function getDisposition() 
    {
        return $this->disposition;
    }
    /**
     * Retourne une chaine en HTML permettant de charger la ressource
     *
     * @return	string chaine de caractère en HTML
     * @abstract
     */
    public function getHTML() 
    {
        // ...
    }
    /**
     * Retourne un tableau php décrivant la ressource
     *
     * @return	string tableau php decrivant la ressource
     * @abstract
     */
    public function getArray() 
    {
        // ...
    }
    /**
     * Renvoi le type étendu du Buffer
     *
     * @return	string
     */
    public function getExtendedType() 
    {
        return $this->type.':'.$this->disposition;
    }
    /**
     * fabrique un commentaire à partir de la chaine donnée en paramètre
     *
     * @param string 
     * @return string
     */
    public function comment($s)
    {
        return '/* '.$s." */\n";
    }    

    /**
     * retourne le nom du fichier qui a permis de generer la ressource (ex: 'prototype.js')
     *
     * @param string 
     * @return string
     */
    public function getFilename()
    {
      return $this->filename;
    }
}



