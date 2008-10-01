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
 * @version    $Id: Xslt.php,v 1.11 2008/02/29 13:35:22 thouveni Exp $
 */
require_once 'Pxxo.php';

/**
 * Encapsule une  transformation XSL
 *
 * Exemple :
 *    $x = new Pxxo_Xslt('exemple01.xml', 'exemple01.xsl');
 *    $x->transform();
 *    $ret = $x->get();
 *
 * @package    Pxxo
 * @copyright  Copyright (c) 2008 Nicolas Thouvenin
 * @license    http://opensource.org/licenses/bsd-license.php
 */
class Pxxo_Xslt extends Pxxo
{
    // {{{ Variables
    /**
     * Fichier ou buffer XML
     * @var		mixed
     */
    private $_XML = '';
    /**
     * Fichier ou buffer XSL
     * @var		mixed
     */
    private $_XSL = '';
    /**
     * Parametres pour la feuille
     * @var	  array
     */
    private $_PAR = array();
    /**
     * buffer de sortie
     * @var		string
     */
    private $_out;
    /**
     * active ou non l'execution du PHP généré par le XSL
     * @var boolean
     */
    public $eval_result = false;
    /**
     * active ou non les fonctions PHP à l'intetrieur du XSL
     * @var boolean
     */
    public $register_functions = true;
    /**
     * active si possible xsltcache 
     * @var boolean
     */
    public $xsl_cache = true;
    // }}}
    // {{{ Constructeur
    /**
     * Constructeur
     *
     * @param	   string      Chemin du fichier xml
     * @param	   string      Chemin du fichier xsl
     * @param	   mixed       Tableau de partametres
     */
    function __construct($a = '', $b = '', $c = array())
    {
        parent::__construct();
        $this->setXML($a);
        $this->setXSL($b);
        $this->setPAR($c);
    }
    // }}}
    // {{{ factory
    /**
     * Construction automatique d'un objet
     *
     * @param    string   which handler you want to use
     * @deprecated
     * @return	Object	 Pxxo_Xslt
     */
    public static function factory($s = '')
    {
        $x = new Pxxo_Xslt();
        return $x;
    }
    // }}}
    // {{{ transform
    /**
     * Effectue la transformation
     *
     * @param array options de tranformation
     * @return
     */
    public function transform($options = array())
    {
        $this->_out = $this->getinCache($this->getCacheID(), P_C_XSLT);

        if ($this->_out === false ) {

            $xmldom = new DOMDocument;

            // assign user's options
            if (isset($options['xmldom'])) foreach($options['xmldom'] as $k => $v) $xmldom->$k = $v;

            $this->_XML = trim($this->_XML);
            if (substr($this->_XML, 0, 1) == '<') {
                if(!$xmldom->loadXML($this->_XML)) {
                    return $this->triggerError('P_E_0013', E_USER_NOTICE,  __METHOD__, 'initialization failed', "XML buffer");
                }
            }
            else {
                if (!$xmldom->load($this->_XML)) {
                    return $this->triggerError('P_E_0013', E_USER_NOTICE,  __METHOD__, 'initialization failed', "XML file");
                }
            }
            $this->_XSL = trim($this->_XSL);

            if (substr($this->_XSL, 0, 1) == '<') {
                $xslfile = false;
                $this->xsl_cache = false;
            }
            else {
                $xslfile = true;
            }

            if ($this->xsl_cache and extension_loaded('xslcache')) {
                $proc = new xsltCache;
                $proc->importStyleSheet($this->_XSL);
            }
            else {
                $xsldom = new DOMDocument;
                if ($xslfile) {
                    if (!$xsldom->load($this->_XSL)) {
                        return $this->triggerError('P_E_0013', E_USER_NOTICE,  __METHOD__, 'initialization failed', "XSL file");
                    }
                }
                else {
                    if(!$xsldom->loadXML($this->_XSL)) {
                        return $this->triggerError('P_E_0013', E_USER_NOTICE,  __METHOD__, 'initialization failed', "XSL buffer");
                    }
                }
                if (isset($options['xsldom'])) foreach($options['xsldom'] as $k => $v) $xsldom->$k = $v;
                $proc = new XSLTProcessor;
                $proc->importStyleSheet($xsldom);
            }

            if ($this->register_functions and method_exists($proc, 'registerPHPFunctions')) {
                $proc->registerPHPFunctions();
            }

            if(is_array($this->_PAR)) {
                foreach($this->_PAR as $k => $v) {
                    $proc->setParameter('', $k, $v);
                }
            }

            $this->_out = $proc->transformToXML($xmldom);
            if(!$this->_out) {
                return $this->triggerError('P_E_0014', E_USER_ERROR,  __METHOD__, 'transform failed', "XSL");
            }

            if ($this->eval_result) {
                ob_start();
                eval('?'.'>'.$this->_out);
                $this->_out = ob_get_contents();
                ob_end_clean();
            }
            $this->setinCache($this->_out, $this->getCacheID(), P_C_XSLT);
        }
        return true;
    }
    // }}}
    // {{{ get
    /**
     * Sortie du résultat
     *
     * @return	string
     */
    public function get()
    {
        return $this->_out;
    }
    // }}}
    // {{{ dump
    /**
     * Affiche le résultat
     *
     * @return	string
     */
    public function dump()
    {
        echo $this->get();
    }
    // }}}
    // {{{ enablePHP
    /**
     * active l'exécution de PHP dans les modèles XSL
     * @deprecated
     */
    public function enablePHP()
    {
        $this->eval_result = true;
    }
    // }}}
    // {{{ disablePHP
    /**
     * désactive l'exécution de PHP dans les modèles XSL
     * @deprecated
     */
    public function disablePHP()
    {
        $this->eval_result = false;
    }
    // }}}
    // {{{ setXML
    /**
     * Fixe une chaine ou un fichier XML
     *
     * @param	string
     */
    public function setXML($s)
    {
        $this->_XML = $s;
        $this->addCacheID($s);
    }
    // }}}
    // {{{ setXSL
    /**
     * Fixe une chaine ou un fichier XSL
     *
     * @param	string
     */
    public function setXSL($s)
    {
        $this->_XSL = $s;
        $this->addCacheID($s);
    }
    // }}}
    // {{{ setPar
    /**
     * Ajout des paramètres
     *
     * Passe des paramètres à Sablotron qui seront intégrés comme variables
     * dans le fichier XSL
     *
     * @param	array
     */
    public function setPAR($t)
    {
        if (is_array($t)) {
            $this->_PAR = array();
            foreach($t as $key => $val) {
                $this->_PAR[$key] = $val;
                $this->addCacheID($val);
            }
        }
    }
    // }}}
}
