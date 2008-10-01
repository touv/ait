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
require_once 'Pxxo/View.php';
require_once 'Pxxo/Widgets.php';
require_once 'Pxxo/Buffers.php';
require_once 'Pxxo/Registry.php';
require_once 'Pxxo/Zend/Translate.php';
require_once 'Pxxo/Zend/Locale.php';

define('P_M_COMBINE',   4);
define('P_M_OUTPUT',    8);
define('P_M_RESOURCE', 16);

/**
 * Classe mère de tous les widgets
 *
 * @package    Pxxo
 * @copyright  Copyright (c) 2008 Nicolas Thouvenin
 * @license    http://opensource.org/licenses/bsd-license.php
 */
class Pxxo_Widget extends Pxxo
{
    // {{{ Attributs
    /**
     * Pointeur vers l'objet parent de l'objet courant
     * @var		Pxxo_Widget
     */
    public $Parent = null;

    /**
     * Pointeur vers l'objet Racine de l'application
     * @var		Pxxo_Widget
     */
    public $Root = null;

    /**
     * pointeur sur l'objet de stockage des variables de configuration
     * @var Pxxo_Registry      
     * */
    public $Registry = NULL;

    /**
     * Tableau d'objet Pxxo_Widget contenant tous fils de l'objet courant
     * @var		arrayobject
     */
    public $Widgets;

    /**
     * Tableau d'objet Pxxo_Buffer contenant toutes les ressources de l'objet courant
     * @var		Pxxo_Buffers
     */
    public $Resources;

    /**
     * @var		Pxxo_Buffers Tableau contenant des informations destinées à l'entete HTML de la page terminale
     */
    public $Headers;

    /**
     * @var		Pxxo_Response Gestion de l'entete HTTP
     */
    public $Response = null;

    /**
     * Object View
     * @var object Pxxo_View
     */
    public $View = null;

    /**
     * Liste des variables utilisées pas l'objet
     * @var array
     */
    private $_vars = array();

    /**
     * Liste des variables publique de la classe (calculées automatiquement)
     * @var array
     */
    private $_vars_public = null;

    /**
     * Liste des variables privées de l'objet en plus des variables préfixées par _
     * @var		mixed
     */
    private $_vars_privates = array('Registry', 'Root', 'View', 'Parent', 'Templates', 'Widgets', 'Resources', 'widget', 'Response', 'Translate', 'TranslateData', 'TranslateAdapter', 'TranslateOptions');

    /**
     * Liste des variables devant être mise en cache en plus des variables non privées
     * @var		mixed
     */
    private $_vars_protected = array('Resources', 'CurrentMode');

    /**
     * Liste des modes "OneTime"
     * @var		array
     */
    private $_onetime = array();

    /**
     * Mode (ou action) en cours de traitment
     * @var		string
     */
    public $CurrentMode = null;

    /**
     * Mode (ou action) à exécuter par défaut
     * @var		string
     */
    public $DefaultMode = 'index';

    /**
     * Type de contenu produit en sortie
     * @var		string
     */
    public $OutputMode  = 'html';

    /**
     * Niveau de filtre appliqué sur la sortie
     * @var		integer
     */
    public $MinifyLevel  = null;

    /**
     * mode persistant ou non (cad : le mode reste actif tant que l'on n'en change pas explicitement)
     * @var		boolean
     */
    public $PersistentMode = false;

    /**
     * Cet objet va produire une page terminale
     * @var		string
     */
    public $StandaloneMode = false;

    /**
     * @var		boolean active le chargement automatiques des ressources
     */
    public $LoadingMode = true;

    /**
     * Active le mode caché ! Attention incompatible avec le controller...
     * Ne peut être modidfié uniquement un surchargant cette déclaration
     * @var		string
     */
    public $HiddenMode = false;

    /**
     * @var		boolean  vient-on de changer de mode ?
     */
    public $ModeChanged = false;

    /**
     * @var		boolean  le mode actuel vient-il d'être séléctionné volontairement
     */
    public $ModeSelected = false;

    /**
     * @var		boolean  le mode actuel ne pourra produire de résultat car un mode fils à déclencher le mode Standalone
     */
    public $ModeCaptived = false;

    /**
     * @var		boolean  le Mode précendent
     */
    public $PreviousMode = '';

    /**
     * Permet d'ajouter automatiquement un prefix aux variables accessible avec les méthodes getXxxxVar() et setXxxxVar()
     * @var		boolean
     */
    public $PrependVar = false;

    /**
     * @var		boolean Active ou non le mode cache
     */
    public $CacheMode = null;

    /**
     * @var		string lieu de stockage du cache
     */
    public $CachePath = '';

    /**
     * @var		string temps de stockage dans le cache
     */
    public $CacheTime = 4000;

    /**
     * Indique l'état dans lequel se trouve l'objet
     * @var		mixed
     */
    public $State = false;

    /**
     * Nom de la variable permettant de choisir le mode (ou l'action) à traiter
     * @var		mixed
     */
    public $varnamemode = null;

    /**
     * Langue active
     * @var		string null par défaut ce qui indique que cette valeur prendra la même que son objet parent
     */
    public $Lang = 'fr';

    /**
     * Donnée permettant les traductions
     * @var		mixed
     */
    public $TranslateData = null;

    /**
     * Adapter de traduction
     * @var		string
     */
    public $TranslateAdapter = 'Array';

    /**
     * Options du Module de Traduction
     * @var		array
     */
    public $TranslateOptions = array();

    /**
     * Module de Traduction
     * @var		Pxxo_Zend_Translate
     */
    public $Translate = null;


    /**
     * Nom du thème actif
     * @var		string
     */
    public $Theme = null;

    /**
     * Liste de chemin physique de répertoire contenant des themes
     * Exemple :
     *  ./themes:/var/httpd/www/themes
     * @var		string des chemins séparer par PATH_SEPARATOR
     */
    public $ThemePaths = '';

    /**
     * Chemin physique par défaut des templates de l'objet
     * @var		string
     */
    public $TemplatePath = '';

    /**
     * Tableau associant un mode à un objet Pxxo_Buffer
     * @var		array
     */
    public $Templates = array();

    /**
     * URL d'accès aux ressources du composant
     * @var		string
     */
    public $ResourceURL = '';

    /**
     * Chemin physique correspondant à l'URL d'accès aux ressources du composant
     * @var		string
     */
    public $ResourcePath = '';

    /**
     * Nom UNIQUE de définition de la classe
     * @var		mixed
     */
    public $ClassName = null;

    /**
     * Identifiant UNIQUE pour chaque instance de la classe
     * @var		mixed
     */
    public $ClassID = null;

    /**
     * Nom donné par un Composant Parent
     * @var		mixed
     */
    public $ClassAlias = null;

    /**
     * Contenu du cache
     * @var		string
     */
    private $_cached = false;

    /**
     * @var		mixed  Contient la valeur retournée par la méthode du dernier mode executé...
     */
    public $Return = null;

    /**
     * @var		boolean la première méthode correspondant à un mode qui retourne une valeur à gagner...
     */
    private $_returned = false;


    /**
     * @var		array Tableau contenant les variables de classes modifiables à la construction de l'objet
     */
    private $_options = array(
        //  Variable de classe => son equivalent dans Pxxo::config
        'Theme'           => 'theme_name',
        'ThemePaths'      => 'theme_path',
        'TemplatePath'    => 'templates_path',
        'ResourcePath'    => 'resources_path',
        'ResourceURL'     => 'resources_url',
        'CachePath'       => 'cache_path',
        'CacheMode'       => 'cache_flag',
        'CacheTime'       => 'cache_time',
        'Lang'            => 'language',
        'PrependVar'      => 'undefined',
        'MinifyLevel'     => 'undefined',
        'StandaloneMode'  => 'undefined',
        'PersistentMode'  => 'undefined',
        'DefaultMode'     => 'undefined',
        'CurrentMode'     => 'undefined',
        'ClassName'       => 'undefined',
        'ClassID'         => 'undefined',
        'TranslateData'   => 'undefined',
        'TranslateAdapter'=> 'undefined',
        'TranslateOptions'=> 'undefined',
        'LoadingMode'     => 'undefined',
    );

    /**
     * @var		array liste des options du widgets
     */
    protected $_params = array();

    /**
     * @var Pxxo_Widget_Decorator instance de l'éventuel décorateur
     */
    protected $_decorator = NULL;

    /**
     * @var Pxxo_Widget instance de ce que l'on décore (peut pointer sur un décorateur)
     */
    protected $_decorated = NULL;

    /**
     * @var Pxxo_Widget instance du widget que l'on décore (ne peut pas pointer sur un decorateur)
     */
    protected $_decorated_leaf = NULL;

    // }}}
    // {{{ Constructeur
    /**
     * Constructeur
     *
     * @param string tableau des  paramètres du composants
     * @param string chemin complet du fichier de définition du widget
     */
    function __construct($options, $filename)
    {
        parent::__construct();

        $this->Registry  = Pxxo_Registry::getInstance();
        $this->Headers   = new Pxxo_Buffers;
        $this->Resources = new Pxxo_Buffers;
        $this->View      = new Pxxo_View($this);
        $this->Widgets   = new Pxxo_Widgets($this);

        // {{{ Assignation automtiques des attributs de l'objet
        // Paramètres Génériques
        foreach($this->_options as $opt => $cfg) {
            if (is_array($options) and array_key_exists($opt, $options)) {
                $this->$opt = $options[$opt];
            }
            elseif (is_array($options) and array_key_exists(strtolower($opt), $options)) {
                $this->$opt = $options[strtolower($opt)];
            }
            elseif ($cfg !== 'undefined' && $this->Registry->offsetExists($cfg)) {
                $this->{$opt} = $this->Registry->get($cfg);
            }
        }        
        // Paramètres Spécifiques
        foreach($this->_params as $p) {
            if (isset($options[$p])) {
                $this->$p = $options[$p];
            }
        }
        // }}}

        // {{{ Caclul pseudo automatique d'un identifiant unique pour le composant
        if (!is_null($this->varnamemode)) {
            // Retro compatibilité 3.6 et précedente
            $this->ClassID = $this->ClassName = $this->varnamemode;
        }
        else {
            if ($this->ClassName === null)
                $this->ClassName = get_class($this);
            if ($this->ClassID === null)
            {
                if (!isset($GLOBALS['PXXO_GLOBALS'][$this->ClassName])) $GLOBALS['PXXO_GLOBALS'][$this->ClassName] = 0;
                
                if ($GLOBALS['PXXO_GLOBALS'][$this->ClassName] !== 0)
                    $this->ClassID = $this->ClassName.$GLOBALS['PXXO_GLOBALS'][$this->ClassName];
                else
                    $this->ClassID = $this->ClassName;
                
                if ($this->HiddenMode)
                    $this->ClassID = 'x'.strtoupper(base_convert(sprintf("%u", crc32($this->ClassID)), 10, 36));
                
                ++$GLOBALS['PXXO_GLOBALS'][$this->ClassName];
            }
        }
        // }}}



        // {{{ Réglage du cache
        if (!empty($this->CachePath)) {
            $this->CachePath = rtrim($this->CachePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
            $this->setCacheOption('backendFile', 'cache_dir', $this->CachePath);
        }
        if (!is_null($this->CacheMode))  {
            if ($this->CacheMode)
                $this->enableCache();
            else
                $this->disableCache();
        }
        if (!is_null($this->CacheTime))  {
            $this->setLifetime($this->CacheTime);
        }
        // }}}


        // {{{ Detection des variables publique du widget
        $tab = get_object_vars($this);
        $res = array();
        foreach($tab as $k => $v) {
            if (!$this->isPrivateVar($k)) $this->_vars_public[] = $k;
        }
        // }}}



        // {{{ gestion du ThemePaths
        $filepath = dirname($filename).DIRECTORY_SEPARATOR.basename($filename, '.php');
        if (isset($options['themepath'])) $this->addThemePath($options['themepath']);
        if (!empty($this->TemplatePath)) {
            // Dans cette classe, On considère le TemplatePath comme commun à tous les widgets
            $tplpath = rtrim($this->TemplatePath,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$this->ClassName;
            if (! $this->addThemePath($tplpath)) {
                $tplpath = rtrim($this->TemplatePath,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.strtolower($this->ClassName); // retro compatibilté PHP4
                if (! $this->addThemePath($tplpath)) {
                    $tplpath = $filepath;
                }
            }
            $this->TemplatePath = $tplpath;
        }
        else {
            $this->TemplatePath = $filepath;
        }
        $this->addThemePath($filepath);
        // }}}


        $this->State += 256;
    }
    // }}}
    // {{{ putWidget
    /**
     * Ajout d'un widget sous forme de composant
     *
     * si l'identifiant est null on attache un widget au Widget courant
     * sans pour autant le mettre à disposition du template
     * mais renvoyant simplement le rendu sous forme d'une chaine de caractères
     *
     * @param string identifiant du widget dans les templates
     * @param Pxxo_Widget  instance d'un widget
     * @return mixed
     */
    protected function putWidget($id, Pxxo_Widget $o)
    {
        if (($this->State & 8) !== 8) 
            $this->triggerError('P_E_0001', E_USER_NOTICE,  __METHOD__, 'Called before initMode');

        if (is_null($o)) 
            return $this->triggerError('P_E_0004', E_USER_ERROR,  __METHOD__, 'Widget is NULL');
        $o->ClassAlias = $id;
        $obj = $this->stackWidget($o);
        if (is_null($id) or $id === false) {
            return $obj->get();
        }
        else {
            if ($obj)
                $this->View->assign($id, $obj->get());
        }
    }
    // }}}
    // {{{ putFile
    /**
     * Ajout le contenu d'un template dans une zone
     *
     * @param string identifiant dans les templates
     * @param string fichier
     */
    protected function putFile($id, $filename)
    {
        if (($this->State & 8) !== 8) 
            $this->triggerError('P_E_0001', E_USER_NOTICE,  __METHOD__, 'Called before initMode');

        $themefile = $this->getThemeFile($filename);
        $stm = Pxxo_Buffer::factory('tpl', $themefile);
        $this->copyto($stm);
        $this->View->setInputBuffer($stm);
        $this->View->assign($this->getPublicVars());
//        $this->View->assign('Self', $this);
        $this->View->transform($typ_rsc);
        $out = $this->View->getOutputBuffer();
        $this->View->assign($id, $out->get());
    }
    // }}}
    // {{{ putOnceStyle
    /**
     * Ajout de CSS
     *
     * le CSS est contenu dans un fichier. Ce fichier fonctionne comme un template.
     *
     * @param string nom du fichier (son emplacement sera déduit en fonction du theme)
     * @param string media destibation des templates
     * @return string identifiant de la ressources dans $this->Resources
     */
    private function putOnceStyle($filename, $media = 'screen')
    {
        if (isset($this->_memory_cache[__METHOD__][$filename][$media.'X'])) return $this->_memory_cache[__METHOD__][$filename][$media.'X'];

        $id_rsc = $this->dynResource($filename, 'css');
        /* IE : pour gerer les conditions */
        if (substr($media, 0, 3) === 'if ') {
            $this->Resources->get($id_rsc)->condition = $media;
            $media = 'screen';
        }
        $this->Resources->get($id_rsc)->setMedia($media);

        $this->_memory_cache[__METHOD__][$filename][$media.'X'] = $id_rsc;
        return $id_rsc;
    }
    // }}}
    // {{{ putStyle
    /**
     * @see putOnceStyle
     * @param string nom du fichier (son emplacement sera déduit en fonction du theme)
     * @param string media destibation des templates
     */
    protected function putStyle($filename, $media = 'all')
    {
        if (($this->State & 8) !== 8) 
            $this->triggerError('P_E_0001', E_USER_NOTICE,  __METHOD__, 'Called before initMode');

        $this->loadResource($filename, 'Style');
        return $this->putOnceStyle($filename, $media);
    }
    // }}}
    // {{{ putOnceScript
    /**
     * Ajout de Javascript
     *
     * le javascript est contenu dans un fichier. Ce fichier fonctionne comme un template.
     *
     * @param string nom du fichier (son emplacement sera déduit en fonction du theme)
     * @param string disposition du code (dans un fichier externe ou à l'intérieur de la page)
     * @return string identifiant de la ressources dans $this->Resources
     */
     private function putOnceScript($filename, $disposition = 'file')
    {
        if (isset($this->_memory_cache[__METHOD__][$filename][$disposition.'X'])) return $this->_memory_cache[__METHOD__][$filename][$disposition.'X'];

        $id_rsc = $this->dynResource($filename, 'js');
        $this->Resources->get($id_rsc)->setDisposition($disposition);

        $this->_memory_cache[__METHOD__][$filename][$disposition.'X'] = $id_rsc;
        return $id_rsc;
    }
    // }}}
    // {{{ putScript
    /**
     * @see putOnceScript
     * @param string nom du fichier (son emplacement sera déduit en fonction du theme)
     * @param string disposition du code (dans un fichier externe ou à l'intérieur de la page)
     */
    protected function putScript($filename, $disposition = 'file')
    {
        if (($this->State & 8) !== 8) 
            $this->triggerError('P_E_0001', E_USER_NOTICE,  __METHOD__, 'Called before initMode');

        $this->loadResource($filename, 'Script');
        return $this->putOnceScript($filename, $disposition);
    }
    // }}}
    // {{{ putOnceImage
    /**
     * Ajout d'une image (statique)
     * Si l'identifiant n'est pas choisit, celui ci sera automatiquement égale
     * au nom (en majuscule) du fichier sans son suffixe
     *
     * @param string nom du fichier (son emplacement sera déduit en fonction du theme)
     * @param string identifiant dans les templates
     */
     private function putOnceImage($filename, $id = null)
    {
        if (isset($this->_memory_cache[__METHOD__][$filename][$id.'X'])) return $this->_memory_cache[__METHOD__][$filename][$id.'X'];
        $themefile = $this->getThemeFile($filename);
        $ext       = substr(strrchr($themefile, '.'), 1);
        $id1       = (is_null($id) or empty($id)) ? strtoupper(str_replace(array('%', '-', '.'),'_',basename($themefile, '.'.$ext))) : $id;
        $id2       = str_replace(array('%', '-', '.'),'_', basename($themefile));
        $rsc       = Pxxo_Buffer::factory($ext, $themefile);
        $this->copyto($rsc);
        //        $rsc->addCacheID($id_rsc);
        $rsc->url = $this->ResourceURL;
        $rsc->path = $this->ResourcePath;
        $this->Resources->add($rsc);
        $url = $rsc->get();
        $this->View->assign($id1, $url);
        $this->View->assign($id2, $url);
        $this->_memory_cache[__METHOD__][$filename][$id.'X'] = $url;
        return $url;
    }
    // }}}
    // {{{ putImage
    /**
     * @see putOnceImage
     * @param string nom du fichier (son emplacement sera déduit en fonction du theme)
     * @param string identifiant dans les templates
     */
    public function putImage($filename, $id = null)
    {
        if (($this->State & 8) !== 8) 
            $this->triggerError('P_E_0001', E_USER_NOTICE,  __METHOD__, 'Called before initMode');

        $this->loadResource($filename, 'Image');
        return $this->putOnceImage($filename, $id);
    }
    // }}}
    // {{{ putMedia
    /**
     * Ajout d'un media Dynamique (calculé comme un template)
     *
     * Un média est une ressource externe à la page référencé dans celle-ci par une URL
     * Exemple : un fichier svg, swf, png, etc ...
     *
     * @param string nom du fichier (son emplacement sera déduit en fonction du theme)
     * @param string identifiant du widget dans les templates
     */
    protected function putMedia($filename, $id = null)
    {
        if (isset($this->_memory_cache[__METHOD__][$filename][$id.'X'])) return $this->_memory_cache[__METHOD__][$filename][$id.'X'];

        $ext       = substr(strrchr($filename, '.'), 1);
        $id        = (is_null($id) or empty($id)) ? strtoupper(substr($filename, 0, strpos($filename, '.'))) : $id;
        $id_rsc    = $this->dynResource($filename, $ext);
        $url = $this->Resources->get($id_rsc)->get();
        $this->View->assign($id, $url);

        $this->_memory_cache[__METHOD__][$filename][$id.'X'] = $url;
        return $url;
    }
    // }}}
    // {{{ putTitle
    /**
     * Ajout d'un titre
     *
     */
    protected function putTitle($s)
    {
        $b = Pxxo_Buffer::factory('title', $s);
        $this->copyto($b);
        return $this->Headers->add($b);
    }
    // }}}
    // {{{ putXSLT
    /**
     * Ajout d'une transformation XSL
     *
     * @param string  identifiant dans les templates
     * @param string  fichier xsl présent dans ThemePaths ou chaine de caractères
     * @param string  fichier xml présent dans ThemePaths ou chaine de caractères
     * @param array   paramètres pour la transformation
     * @param boolean Booléen permettant d'activer l'exécution de code php éventuellement générer par la transformation (par défaut: false)
     * @param string  nom du parseur XSLT à utiliser. Les valeurs possibles sont : domxslt, xsltproc (par défaut automatique)
     */
    protected function putXSLT($id, $xsl, $xml, $par = array(), $php = false, $engine = 'auto')
    {
        if (($this->State & 8) !== 8) 
            $this->triggerError('P_E_0001', E_USER_NOTICE,  __METHOD__, 'Called before initMode');

        include_once 'Pxxo/Xslt.php';
        $x = Pxxo_Xslt::factory($engine);
        $this->copyto($x);

        $xslfile = $this->getThemeFile($xsl, 'xxx');
        if ($xslfile !== 'xxx') $x->setXSL($xslfile);
        else $x->setXSL($xsl);

        $xmlfile = $this->getThemeFile($xml, 'xxx');
        if ($xmlfile !== 'xxx') $x->setXML($xmlfile);
        else $x->setXML($xml);

        if (is_array($par) and count($par) > 0) $x->setPAR($par);
        if ($php == true) $x->enablePHP();

        $ret = $x->transform();
        $this->View->assign($id, $x->get());
        return $ret;
    }
    // }}}
    // {{{ initMode
    /**
     * Cette méthode est toujours exéctuée avant le mode courant
     *
     */
    protected function initMode()
    {
        // ...
    }
    // }}}
    // {{{ execMode
    /**
     * On exécute la méthode correpondant à un mode donné
     *
     * @param    string  nom du mode à exécuter
     */
    protected function execMode($m)
    {

        $ret = $this->$m();
        if ($this->_returned === false) {
            $this->Return = $ret;
            $this->_returned = true;
        }

        $tpl = $this->getTemplate($m);
        if ($tpl->scheme == 'file') {
            $this->loadResource($tpl->uri, 'Html');
        }
    }
    // }}}
    // {{{ chooseDefaultMode
    /**
     * retourne le mode par défaut
     * (utile surtout pour gérer la rétro compatibilité avec l'ancien mode 'defaut')
     *
     * @return    string
     */
    protected function chooseDefaultMode()
    {
        if (method_exists($this,$this->DefaultMode))
            return $this->DefaultMode;

        // Retro compatibilité
        if (method_exists($this,'defaut'))
        {
            $this->DefaultMode = 'defaut';
            return $this->DefaultMode;
        }
    }
    // }}}
    // {{{ chooseMode
    /**
     * quel mode doit être executé
     *
     * @param    string  nom du mode à exécuter
     */
    protected function chooseMode()
    {
        $m = $this->getMode();
        if (method_exists($this, $m)) {
            if (in_array($m, $this->_onetime) ) {
                if ($this->PreviousMode == $m)  {
                    $this->setMode($m = $this->DefaultMode);
                }
            }
        }
        else $m = $this->DefaultMode;
        if ($this->PersistentMode) {
            $this->checkSession();
            $_SESSION[$this->ClassID] = $m;
        }
        return $m;
    }
    // }}}
    // {{{ main
    /**
     * Execution du widget
     *
     */
    public function main()
    {
        if (!isset($this->Root) or $this->Root->ClassID === $this->ClassID) {
            Pxxo_Registry::checkRegistry();
        }

        if (($this->State & 256) !== 256 ) {
            $this->triggerError('P_E_0020', E_USER_ERROR,  __METHOD__, 'Construct not called: '.$this->ClassID);
        }
        
        // Gestion de l'insertion d'un eventuel décorateur
        if ($this->_decorator) {
            $deco = $this->_decorator;
            $this->_decorator = NULL;
            if (!is_null($this->Parent)) {
                $this->Parent->stackWidget($deco);
            }
            else {
                $this->stackWidget($deco,false);
            }
            $this->View = $deco->View;
            $this->View->Self = $this;
            return;
        }

        // Initialisation
        $this->chooseDefaultMode();
        $this->initTemplate();


        // Gestion de l'entete HTTP
        if (is_null($this->Response)) {
            include_once 'Pxxo/Response.php';
            $this->Response = new Pxxo_Response;
        }




        // Déclenche le traitement d'une action dans l'objet
        $this->State += 8;


        // {{{ mise en cache
        $this->resetCacheID();

        if ($this->isCaching() and $this->testCacheLevel(P_C_WIDGET)) {
            if ( $this->computeCacheID() !== 0) {
                // Que faut-il faire avec le cache
                $cache_mode = $this->getCacheOption('mode');
                if ($cache_mode == 'force') $this->resetCache();
                elseif ($cache_mode == 'refresh') $this->cleanCache();
                elseif ($cache_mode != 'ignore') {

                    // Déclare les objets avant de les restaurer avec unserialize
                    include_once('Pxxo/Buffer/Resource/CSS.php');
                    include_once('Pxxo/Buffer/Resource/Javascript.php');

                    // on récupére la sortie
                    $this->_cached = $this->getinCache($this->getCacheID(), P_C_WIDGET);
                    if (is_string($this->_cached)) {
                        $this->traceDebug(sprintf("COMPONENT\t[cached]\tmode(Class:%s, Method:%s)\toptions(Persistent:%d, MinifyLevel:%d, Standalone:%d, OneTime:%d)",$this->ClassID, $this->getMode(), $this->PersistentMode, $this->MinifyLevel, $this->StandaloneMode, in_array($this->getMode(), $this->_onetime)));
                        $vars = $this->getinCache($this->getCacheID().'ProtectedVars', P_C_WIDGET);
                        if (is_string($vars)) {
                            $vars = unserialize($vars);
                            if ($vars !== false) {
                                $this->setProtectedVars($vars);
                                $cl = $this->getinCache($this->getCacheID().'CacheLevel', P_C_WIDGET);
                                if (is_string($cl)) {
                                    $cl = unserialize($cl);
                                    if ($cl !== false) {
                                        $this->setCacheLevel($cl);
                                        return;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        $this->_cached = false;
        // }}}

        $this->initTranslate();


        // {{{ Trace
        $this->traceDebug(sprintf("COMPONENT\t[executed]\tmode(Class:%s, Method:%s)\toptions(Persistent:%d, MinifyLevel:%d, Standalone:%d, OneTime:%d)",$this->ClassID, $this->getMode(), $this->PersistentMode, $this->MinifyLevel, $this->StandaloneMode, in_array($this->getMode(), $this->_onetime)));
        if (!is_null($this->_bench['profiler']))
            $this->_bench['profiler']->enterSection($this->ClassID.'('.$m_save.')');
        // }}}

        $this->initMode();

        $this->State += 4;

        $m  = $this->chooseMode();
        $m_save = $m;

        $this->execMode($m);

        // {{{ Production de la sortie correspondant au mode executé
        $this->State += 16;


        // Parcours des composants pour
        $standalone = null;
        foreach($this->Widgets as $cle => $wdgt) {
            // ... ???
            if (isset($wdgt->_vars) and is_array($wdgt->_vars) ) {
                $this->_vars = array_merge($this->_vars, $wdgt->_vars);
            }
            // ... remonter les ressources vers le sommet
            if (isset($wdgt->Resources)) {
                $this->Resources->merge($wdgt->Resources);
            }
            // ... remonter les headers vers le sommet
            if (isset($wdgt->Headers)) {
                $this->Headers->merge($wdgt->Headers);
                $wdgt->Headers = $this->Headers;// utile pour les décorateurs
            }
            // ... vérifier qu'il n'y a pas un objet que veut produire une page terminale
            if (isset($wdgt->StandaloneMode) and $wdgt->StandaloneMode) {
                $this->ModeCaptived = true;
                $this->StandaloneMode = true;
                $standalone = $cle;
            }
            if (isset($wdgt->ModeCaptived) and $wdgt->ModeCaptived) {
                $this->ModeCaptived = true;
            }
        }
        // Re-Parcours des composants pour mettre tous les composants fils
        // à égalité de connaissance
        foreach($this->Widgets as $cle => $wdgt) {
            // ... ???
            if (isset($wdgt->_vars) and is_array($wdgt->_vars) ) {
                $wdgt->_vars = $this->_vars;
            }
            // ... des ressources
            if (isset($wdgt->Resources)) {
                $wdgt->setResources($this->Resources);
            }
            // ... des entetes
            if (isset($wdgt->Headers)) {
                $wdgt->setHeaders($this->Headers);
            }
        }


        // Groupement de ressources ( CSS, Javascript)
        if (is_null($this->MinifyLevel)) $this->MinifyLevel = P_M_COMBINE;

        if ((($this->MinifyLevel & P_M_COMBINE) === P_M_COMBINE) and ($this->StandaloneMode or is_null($this->Parent))) {
            $this->Resources->concat();
        }
        if (!is_null($standalone)) {
            // Un objet veut prendre la main, on supprime tout les autres
            foreach($this->Widgets as $cle => $wdgt) {
                if ($cle != $standalone) {
                    unset($this->Widgets->$cle);
                }
            }
            // On est choisit comme template le résultat du composant qui veut prendre la main
            $this->View->setInputBuffer($this->Widgets->$standalone->getBuffer());
        }
        else {
            $this->View->setInputBuffer($this->getTemplate());
            $this->View->assign($this->getPublicVars());
//            $this->View->assign('Self', $this);
        }

        $this->View->transform($this->OutputMode);

        // }}}


        // {{{ On mémorise quelques informations pour la prochaine fois
        if ($this->testCacheLevel(P_C_WIDGET2)) {
            $this->checkSession();
            if (isset($_SESSION['PXXO_VARS'][$this->ClassID][$m_save]))
                $_SESSION['PXXO_VARS'][$this->ClassID][$m_save] = @array_merge($_SESSION['PXXO_VARS'][$this->ClassID][$m_save], $this->_vars);
            else
                $_SESSION['PXXO_VARS'][$this->ClassID][$m_save] = $this->_vars;
        }
        // }}}


        // {{{ Caclul des temps de traitments
        if (!is_null($this->_bench['timer']))
            $this->_bench['timer']->setMarker($this->ClassID.'('.$m_save.')');
        if (!is_null($this->_bench['profiler']))
            $this->_bench['profiler']->leaveSection($this->ClassID.'('.$m_save.')');
        // }}}

        $this->State += 32;

        if (session_id() !== '')
            $_SESSION[$this->ClassID] = $this->CurrentMode;
        else
            $this->Response->addCookie($this->ClassID, $this->CurrentMode);

    }
    // }}}
    // {{{ addDecorator
    /**
     * Permet de décorer le widget courant avec un widget particulier
     * Les widgets décorator dérivent de Pxxo_Widget_Decorator et permettent
     * de modifier l'apparence ou le comportement du widget courant
     * Exemples : rajouter des bords, ajaxiser, ajouter un corps html
     *
     * @param   mixed  nom ou instance du décorateur
     * @param   array  paramètres du décorateur si ce n'est pas une isnstance
     */
    public function addDecorator($deco, $decoparams = array())
    {
        if ( $this->_decorator )
        {
            // ajoute le decorateur a la chaine
            return $this->_decorator->addDecorator($deco, $decoparams);
        }
        else
        {
            // instanciation du decorateur
            if (!is_object($deco)) {
                // avec son nom
                $this->loadWidget($deco);
                if (!class_exists($deco)) {
                    $this->triggerError('P_E_0018', E_USER_ERROR,  __METHOD__, 'Unknown widget', "WidgetName = `$deco`");
                }
                // Gives the real decorated widget to the decorator so that
                // the decoracor can setup, if needed, some parameters of
                // the decorated widget from its constructor
                // (example: used in Pxxo_Translator constructor)
                $decoparams['decorated'] = $this->_decorated_leaf ? $this->_decorated_leaf : $this;
                $obj = new $deco($decoparams);
            }
            else {
                // avec son instance
                $obj = $deco;
            }
            // on fournit au nouveau décorateur ($obj)
            // une reference vers ce que l'on décore ($this)
            $obj->setDecoratedWidget($this);

            // on fournit au widget décoré ($this)
            // une reference vers son premier décorateur ($obj)
            $this->_decorator = $obj;

            // on fournit au nouveau décorateur ET a ses eventuels propres decorateurs
            // une référence directe vers le widget "feuille" décoré
            // remarque : on peut décorer en cascade sans connaitre a prioris
            //   le nombre de décoration successives cette variable permet
            //   de connaitre dans toute la cascade des décorateurs
            //   le widget feuille qui lui n'est pas un décorateur
            if ($this->_decorated_leaf)
                $leaf = $this->_decorated_leaf;
            else
                $leaf = $this;
            $dtmp = $this;
            while($dtmp = $dtmp->_decorator)
            {
                $dtmp->_decorated_leaf = $leaf;
            }

            return $this->_decorator;
        }
    }
    // }}}
    // {{{ includeDecoratedWidget
    /**
     * On insert (et on exécute) l'objet décoré
     *
     * @param   string nom de la variable représentant l'objet décoré
     * @return boolean true si nous sommes un décorateur donc qu'un widget fils doit être affiché
     */
    protected function includeDecoratedWidget($tplvarname = 'WIDGET')
    {
        if ($this->_decorated) {
            if (!isset($this->Templates[$this->getDefaultMode()])) {
                $this->setTemplateRaw($this->getDefaultMode(), '<?php echo $this->WIDGET ?>');
            }
            $this->putWidget($tplvarname, $this->_decorated);
            return true;
        }
        else return false;
    }
    // }}}
    // {{{ getDecoratedWidget
    /**
     * Retourne l'instance du premier widget décoré (peut-être un décorateur)
     *
     * @return Pxxo_Widget
     */
    public function getDecoratedWidget()
    {
        return $this->_decorated;
    }
    // }}}
      // {{{ setDecoratedWidget
    /**
     * On demande au widget de décoré un autre widget
     *
     * @param   Pxxo_Widget
     */
    public function setDecoratedWidget(Pxxo_Widget $w)
    {
        $this->_decorated = $w;
    }
    // }}}
    // {{{ getDecorated
    /**
     * Permet de récuperer l'instance du widget décoré feuille
     * (ce widget n'est donc pas un décorateur lui même)
     *
     * @return   Pxxo_Widget  l'instance du widget décoré ou null si cette methode n'est pas appelée depuis un décorateur
     */
    public function getDecorated()
    {
        return $this->_decorated_leaf;
    }
    // }}}
    // {{{ findParentWidget
    /**
     * Permet de recuperer les instances des widgets parents identifiés par leur noms de classe
     * Par défaut cette methode retourne le premier widget trouvé dans la hiérarchie des parents.
     * Dans le cas où $onlyfirst est à false, un tableau sera retournée avec tous les
     * widgets parents trouvés et un identifiant de profondeur permet de connaitre de combien
     * de niveau hierarchique sont eloignés les widgets trouvés.
     * Par exemple : La profondeur 0 indique que l'on s'est cherché soit même,
     *               et une profondeur de 5 signifie qu'il y a 4 autres widgets insérés entre
     *               le widget courant et le widget recherché
     *
     * @param   string   nom de la classe du/des widgets parents recherchés
     * @param   boolean  pour retourner le premier widget trouvé ou pas
     * @return  array    la liste des widgets dont le nom de classe vaut $classname indexé par un identifiant de profondeur
     */
    public function findParentWidget($classname, $onlyfirst = true)
    {
        $widgets = array();
        $depth = 0;
        $wp = $this;
        while($wp)
        {
            if ($wp->ClassName == $classname)
                if ($onlyfirst)
                    return $wp;
                else
                    $widgets[$depth] = $wp;
            $wp = $wp->Parent;
            $depth++;
        }
        return $widgets;
    }
    // }}}
    // {{{ loadResource
    /**
     * On recherche automatiquement les ressources liées un fichier de ressource
     *
     * Exemples :
     * pour un fichier HTML, on incluera les Javascript, les CSS et les images
     * pour un fichier Javascript, on incluera les CSS et les images
     * pour un fichier CSS, on incluera les images
     *
     * Les inclusion se font dans l'ordre inverse.
     * D'abord les images, puis les CSS, puis les scripts, puis les
     *
     * @param   string   nom d'un fichier
     * @param   string   type du fichier  (Image|Style|Script|Html)
     * @return	array	 Description
     */
    private function loadResource($filename, $type = 'Html')
    {
        if (! $this->LoadingMode) return;
        $types     = array('Image' => 1, 'Style' => 2, 'Script' => 3, 'Html' => 4);
        $type      = ucwords($type);
        $type      = (isset($types[$type])) ? $types[$type] : 4;
        $themefile = $this->getThemeFile($filename);
        $themedir  = rtrim(dirname($themefile), DIRECTORY_SEPARATOR);
        $this->addThemePath($themedir);

        static $themefiles = null;
        static $cache = array();

        $id_cache = $themedir.$this->ClassID.__METHOD__;

        // On parcourt une seule fois chaque répertoire.
        if (is_null($themefiles) or !isset($themefiles[$themedir])) {
            if (($ret = $this->getinCache($id_cache, P_C_BASIC)) !== false) $themefiles[$themedir] = unserialize($ret);
            else {
                $themefiles[$themedir] = array();
                $d = dir($themedir);
                while (false !== ($e = $d->read())) {
                    $f = $themedir.DIRECTORY_SEPARATOR.$e;
                    if (!is_file($f)) continue;
                    if (preg_match('/\.css$/i',$e))
                        $themefiles[$themedir]['Style'][] = $f;
                    elseif (preg_match('/\.js$/i',$e)) {
                        $themefiles[$themedir]['Script'][] = $f;
                    }
                    elseif (preg_match('/\.(jpe?g|gif|png)$/i',$e)) {
                        $themefiles[$themedir]['Image'][] = $f;
                    }
                }
                $d->close();
                $this->setinCache(serialize($themefiles[$themedir]), $id_cache, P_C_BASIC);
            }
        }
        // Suivant un ordre précis, on inclus une seule fois chaque fichier
        foreach($types as $t => $v) {
            if ($type > $v and
                isset($themefiles[$themedir]) and
                isset($themefiles[$themedir][$t])
            ) foreach($themefiles[$themedir][$t] as $f) {
                if (isset($cache[$this->ClassID][$f])) continue;
                $func = 'put'.$t;
                $ret = $this->$func(basename($f));
                $cache[$this->ClassID][$f] = true;
            }
        }
    }
    // }}}
    // {{{ getMode
    /**
     * Le mode correspond à la méthode "Action" à déclencher
     *
     * Par défaut cette méthode va chercher le "mode" dans les
     * variables _POST ou _GET.
     *
     * @return string  Le mode trouvé ou sinon null
     */
    public function getMode()
    {
        if (!is_null($this->varnamemode)) {
            // Retro compatibilité 3.6 et précedente
            $this->ClassID = $this->ClassName = $this->varnamemode;
        }
        if ($this->CurrentMode != null) return $this->CurrentMode;

        if (isset($_POST[$this->ClassID])) {
            $this->CurrentMode = $_POST[$this->ClassID];
            $this->ModeSelected = true;
        }
        elseif (isset($_GET[$this->ClassID])) {
            $this->CurrentMode  = $_GET[$this->ClassID];
            $this->ModeSelected = true;
        }
        elseif (isset($_POST[strtolower($this->ClassID)])) {
            $this->CurrentMode = $_POST[strtolower($this->ClassID)];
            $this->ModeSelected = true;
        }
        elseif (isset($_GET[strtolower($this->ClassID)])) {
            $this->CurrentMode  = $_GET[strtolower($this->ClassID)];
            $this->ModeSelected = true;
        }
        elseif (isset($_GET[$this->ClassID])) {
            $this->CurrentMode  = $_GET[$this->ClassID];
            $this->ModeSelected = true;
        }
        elseif ($this->PersistentMode) {
            $this->checkSession();
            if (isset($_SESSION[$this->ClassID])) {
                $this->CurrentMode = $_SESSION[$this->ClassID];
            }
            else {
                $this->CurrentMode = $this->DefaultMode;
            }
        }
        else {
            $this->CurrentMode = $this->DefaultMode;
        }

        // On vérifie si il y a un mode pour le cache
        $t = explode(',',$this->CurrentMode, 2);
        $this->CurrentMode = $t[0];
        if (isset($t[1])) $this->setCacheOptions(array('mode'=>$t[1]));

        // On détècte le changement de mode et on mémorise le dernier mode choisit
        // Si et seulement si on peut poser un cookie !
        if (session_id() !== '')
            $this->PreviousMode = isset($_SESSION[$this->ClassID]) ? $_SESSION[$this->ClassID] : $this->CurrentMode;
        else
            $this->PreviousMode = isset($_COOKIE[$this->ClassID]) ? $_COOKIE[$this->ClassID] : $this->CurrentMode;
        $this->ModeChanged = ($this->CurrentMode != $this->PreviousMode);

        return $this->CurrentMode;
    }
    // }}}
    // {{{ setMode
    /**
     * On effectue en changement de mode
     *
     * @param    string
     */
    public function setMode($s)
    {
        if (!empty($s)) $this->CurrentMode = $s;
    }
    // }}}
    // {{{ switchMode
    /**
     * Change le mode courant et exécute le nouveau mode
     * Si le mode n'existe pas Pxxo sort en erreur. 
     * Ceci pour éviter de boucler sur le mode par défaut.
     *
     * @param string $s nom du nouveau mode
     */
    public function switchMode($s)
    {
        $this->setMode($s);
        $this->initTemplate();

        if (($this->State & 4) === 4) {
            if (!method_exists($this, $s)) {
                return $this->triggerError('P_E_0019', E_USER_ERROR,  __METHOD__, 'Unknown mode', 
                    sprintf('ClassID = `%s` Mode = `%s`', $this->ClassID, $s)
                );
            }
            return call_user_func(array($this, $s));
        }
    }
    // }}}
    // {{{ getDefaultMode
    /**
     * on renvoit le mode qui sera exécuté par défaut
     *
     * @return    string
     */
    public function getDefaultMode()
    {
        return $this->DefaultMode;
    }
    // }}}
    // {{{ setDefaultMode
    /**
     * On fixe le mode, l'action, la méthode qui sera exécuté par défaut.
     *
     * @param    string
     */
    public function setDefaultMode($s)
    {
        if (!empty($s)) $this->DefaultMode = $s;
    }
    // }}}
    // {{{ setOneTimeMode
    /**
     * Fixe un mode commme ne pouvant pas être executé deux fois de suite.
     *
     * Cette méthode active automatiquement le historisation des modes.
     * Si on tente d'executer un mode alors que le mode précédent était le même
     * le mode est avorté et on execute automatiqument le mode DefaultMode.
     *
     * @param string nom du mode à usage unique
     */
    public function setOneTimeMode($s)
    {
        array_push($this->_onetime, $s);
    }
    // }}}
    // {{{ getBuffer
    /**
     * Sortie du résultat sous forme de flux
     *
     * @return	 Pxxo_Buffer
     */
    public function getBuffer()
    {
        $this->State += 64;

        if ($this->getCacheID() !== 0 and $this->isCaching() and $this->testCacheLevel(P_C_WIDGET)) {
            if ($this->_cached === false) {
                $ret = $this->View->getOutputBuffer();

                // Les attributs de l'objet Pxxo pour le moment seulement le cache level (notamment si on a utilise addCacheLevel)
                $this->setinCache(serialize($this->getCacheLevel()), $this->getCacheID().'CacheLevel', P_C_WIDGET);

                // Les attributs suceptibles d'être utilisé à l'exterieur de l'objet 
                $this->setinCache(serialize($this->getProtectedVars()), $this->getCacheID().'ProtectedVars', P_C_WIDGET);

                // Le HTML
                $this->setinCache($ret->get(), $this->getCacheID(), P_C_TEMPLATE);
            }
            else {
                $ret = Pxxo_Buffer::factory($this->OutputMode, $this->_cached);
            }
        }
        else {
            $ret = $this->View->getOutputBuffer();
        }
        if ($this->testCacheLevel(P_C_HTTP)) {
            $this->Response->setEtag($ret->getId());
            $this->Response->freshFor($this->getCacheOption('frontendCore', 'lifeTime'));
        }
        return $ret;
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
        $o = $this->getBuffer();
        if (($this->MinifyLevel & P_M_OUTPUT) === P_M_OUTPUT) $o->addFilter('compress');
        return $o->get();
    }
    // }}}
    // {{{ dump
    /**
     * Affiche le résultat
     *
     */
    public function dump()
    {
        $r = $this->get();
        if (!is_null($this->Response)) {
            if (!headers_sent()) {
                $this->Response->setCacheDirective('must-revalidate', true);
                $this->Response->setCacheDirective('public', false);
                $this->Response->setCacheDirective('private', true);
                if ($this->Response->getDuration('max-age') === -1) {
                    $this->Response->setCacheDirective('no-cache', true);
                    $this->Response->setCacheDirective('no-store', true);
                }
                $this->Response->sendStatusAndHeaders(true);
            }
        }
        $this->State += 128;
        echo $r;
    }
    // }}}
    // {{{ getSessionVar
    /**
     * Récupère une variable de session si elle n'existe pas on la crée on on l'affecte avec
     * une valeur par défaut
     *
     * @param string nom
     * @param string valeur par défaut si il n'existe pas
     * @return	mixed	 Valeur du parametre
     */
    public function getSessionVar($n, $v = null)
    {
        $this->checkSession();

        if ($this->PrependVar === true) $n = $this->ClassID.$n;
        $this->_vars[$n] = 'SESSION';
        if (isset($_SESSION[$n]))
            return $_SESSION[$n];
        else
            return $this->setSessionVar($n, $v);
    }
    // }}}
    // {{{ setSessionVar
    /**
     * Positionne une variable de session
     * @param	string nom
     * @param    string valeur
     */
    public function setSessionVar($n, $v)
    {
        $this->checkSession();

        if ($this->PrependVar === true) $n = $this->ClassID.$n;
        $_SESSION[$n] = $v;       
        return $v;
    }
    // }}}
    // {{{ delSessionVar
    /**
     * Supprime une variable de session
     * @param	string nom de la variable
     * @return   null
     */
    public function delSessionVar($n)
    {
        $this->checkSession();

        if ($this->PrependVar === true) $n = $this->ClassID.$n;
        unset($_SESSION[$n]);
        return null;
    }
    // }}}
    // {{{ getInputVar
    /**
     * Methode permettant de récupèrer une variable en provenance du client
     * Si on le trouve pas, on renvoit la valeur par défaut ou null
     *
     * @param string nom
     * @param string valeur par défaut si il n'existe pas
     * @return	mixed	 Valeur du parametre
     */
    public function getInputVar($n, $v = null)
    {
        if ($this->PrependVar === true) $n = $this->ClassID.$n;
        $this->_vars[$n] = 'INPUT';
        if (isset($_POST[$n]))
            return $_POST[$n];
        elseif (isset($_GET[$n]))
            return $_GET[$n];
        else
            return $v;
    }
    // }}}
    // {{{ setInputVar
    /**
     * Methode permettant de forcer une variable en provenance du client
     *
     * @param    string  nom
     * @param    string  valeur
     * @return	mixed   Valeur du parametre
     */
    public function setInputVar($n, $v)
    {
        if ($this->PrependVar === true) $n = $this->ClassID.$n;
        $_POST[$n] = $v;
        $_GET[$n] = $v;
        return $v;
    }
    // }}}
    // {{{ getPersistentVar
    /**
     * Methode permettant de récupérer une variable persistante
     * Une variable persistante est une variable généralement en provenance du client
     * dont la dernière affectation persistante dans le temps (même si le client ne la positionne plus)
     * Si on le trouve pas on la fixe à une valeur par défaut
     *
     * @param    string nom
     * @param    string valeur par défaut si il n'existe pas
     * @return	mixed
     */
    public function getPersistentVar($n, $v = '')
    {
        $this->checkSession();

        if ($this->PrependVar === true and strpos($n, $this->ClassID) !== 0 ) $n = $this->ClassID.$n;
        $this->_vars[$n] = 'PERSISTENT';

        if (isset($_POST[$n]))
            $_SESSION[$n] = $_POST[$n];
        elseif (isset($_GET[$n]))
            $_SESSION[$n] = $_GET[$n];
        elseif (!isset($_SESSION[$n]))
            $this->setPersistentVar($n, $v);
        return $_SESSION[$n];
    }
    // }}}
    // {{{ setPersistentVar
    /**
     * On fixe une variable persistante
     *
     * @param	string nom
     * @param    string valeur
     * @return   string la valeur affectée
     */
    public function setPersistentVar($n, $v)
    {
        $this->checkSession();

        if ($this->PrependVar === true and strpos($n, $this->ClassID) !== 0 ) $n = $this->ClassID.$n;
        $_POST[$n] = $_GET[$n] = $_SESSION[$n] = $v;
        return $v;
    }
    // }}}
    // {{{ delPersistentVar
    /**
     * Supprime toute apparition d'une variable persistante
     *
     * @param	string
     * @return   null
     */
    public function delPersistentVar($n)
    {
        $this->checkSession();

        if ($this->PrependVar === true) $n = $this->ClassID.$n;
        unset($_POST[$n]);
        unset($_GET[$n]);
        unset($_SESSION[$n]);
        return null;
    }
    // }}}
    // {{{ computeCacheID
    /**
     * Calcul un identifiant de cache
     *
     * @return	integer
     */
    private function computeCacheID()
    {
        if ($this->testCacheLevel(P_C_WIDGET2)) {
            $this->checkSession();
            if (isset($_SESSION['PXXO_VARS'][$this->ClassID][$this->getMode()])) foreach($_SESSION['PXXO_VARS'][$this->ClassID][$this->getMode()] as $key => $type) {
                if ($type == 'PERSISTENT')
                    $this->addCacheID($key.serialize($this->getPersistentVar($key)));
                if ($type == 'INPUT')
                    $this->addCacheID($key.serialize($this->getInputVar($key)));
                if ($type == 'SESSION')
                    $this->addCacheID($key.serialize($this->getSessionVar($key)));
            }
            $this->addCacheID($this->getPublicVars());
        }
        if ($this->testCacheLevel(P_C_WIDGET)) {
            $this->addCacheID($this->getMode());
            $this->addCacheID($this->ClassID);
            $this->addCacheID($this->Theme);
            $this->addCacheID($this->Lang);
        }
        return $this->getCacheID();
    }
    // }}}
    // {{{ getProtectedVars
    /**
     * Renvoit un tableau contenant les variables protegées de la classe
     *
     * @return	array
     */
    private function getProtectedVars()
    {
        $res = array();
        foreach($this->_vars_public as $v) {
            if ($this->isProtectedVar($v)) $res[$v] = $this->{$v};
        }
        return $res;
    }
    // }}}
    // {{{ getPublicVars
    /**
     * Renvoit un tableau contenant les variables publiques de la classe
     *
     * @return	array
     */
    private function getPublicVars()
    {
        $res = array();
        foreach($this->_vars_public as $v) $res[$v] = $this->{$v};
        return $res;
    }
    // }}}
    // {{{ setPublicVars
    /**
     * Positionne à l'aide d'un tableau toutes variables publiques de la classe
     *
     * @param array $tab
     */
    private function setPublicVars(array $tab)
    {
        if (is_array($tab)) {
            foreach($tab as $k => $v) {
                if (!$this->isPrivateVar($k)) $this->{$k} = $tab[$k];
            }
        }
    }
    // }}}
    // {{{ setProtectedVars
    /**
     * Positionne à l'aide d'un tableau toutes les variables protegées de la classe
     *
     * @param array $tab
     */
    private function setProtectedVars(array $tab)
    {
        if (is_array($tab)) {
            foreach($tab as $k => $v) {
                if ($this->isProtectedVar($k)) $this->{$k} = $tab[$k];
            }
        }
    }
    // }}}
    // {{{ isPrivateVar
    /**
     * On teste si le nom de la variable donnée en argument
     * correspond à une variable qui ne doit pas être envoyée à la VIEW
     *
     * @param   string   nom du variable
     * @return	boolean	 Description
     */
    private function isPrivateVar($k) 
    {
        if ($k[0] === '_' || in_array($k, $this->_vars_privates)) return true;
        else return false;
    }
    // }}}
    // {{{ isProtectedVar
    /**
     * On teste si le nom de la variable donnée en argument
     * correspond à une variable qui peut éventuellment être envoyée à la vue
     *
     * @param   string   nom du variable
     * @return	boolean	 Description
     */
    private function isProtectedVar($k)
    {
        if (!$this->isPrivateVar($k) || in_array($k, $this->_vars_protected)) return true;
        else return false;
    }
    // }}}
    // {{{ connect
    /**
     * Méthode permettant de "connecter" les modes de 2 objets
     * Si l'objet est dans le mode $event, alors on postionne
     * dans l'objet $objet le mode $mode
     * si on fournit un tableau comme quatrième argument
     * on lancera l'éxécution de la méthode correspondant au mode récèpteur
     * chaque valeur du tableau sera passée en paramètre à cette méthode
     * auquel on ajoutera comme dernier paramètre la valeur du retour de l'exécution de la méthode déclencheur
     * Cette dernière valeur peut-être null si
     *
     * @param    string  nom du mode déclencheur
     * @param    object  récepteur
     * @param    string  nom du mode récepteur
     * @param    mixed   paramètres envoyé au mode récepteur
     * @return	boolean vrai si la connection a été faite faux sinon
     */
    public function connect($event, Pxxo_Widget $objet, $mode, $param = null)
    {
        if ($this->getMode() == $event) {
            if (is_array($param)) {
                if (($objet->State & 4) === 4 && ($objet->State & 16) !== 16 ) {
                    // La méthode main est en cours d'execution et on demande l'exécution
                    // donc on synchronise le mode avec la méthode en cours d'exécution
                    $objet->setMode($mode);
                }
                array_push($param, $this->Return);
                $ret = call_user_func_array(array($objet, $mode), $param); // je ne sais plus pourquoi j'avais mis $param ...
                if (isset($objet->_returned) and $objet->_returned === false and !is_null($ret)) {
                    $objet->Return = $ret;
                    $objet->_returned = true;
                }
            }
            else {
                $objet->setMode($mode);
            }
            return true;
        }
        else return false;
    }
    // }}}
    // {{{ getThemeFile
    /**
     * Recherche un fichier interne à la classe
     * en fonction de ThemePaths et de ThemeName
     * et renvoit son emplacement et son nom
     *
     * @param    string un nom fichier
     * @param    string une valeur de retour si on trouve pas le fichier (on évite une erreur)
     * @return	string la référence physique du fichier donnée en paramètre
     */
    public function getThemeFile($s, $r = null)
    {
        $id_cache = $s.$this->ThemePaths.$this->Theme.$this->ClassID.__METHOD__;
        if (($ret = $this->getinCache($id_cache, P_C_BASIC)) !== false) return $ret;

        $filename = $s;
        if (is_file($filename)) {
            $this->setinCache($filename, $id_cache, P_C_BASIC);
            return $filename;
        }

        $tabpath = explode(PATH_SEPARATOR, $this->ThemePaths);
        if (!empty($this->TemplatePath)) {
            $tabpath[] = $this->TemplatePath;
        }
        foreach($tabpath as $path) {
            $path = rtrim($path,DIRECTORY_SEPARATOR);
            // {{{ on cherche en fichier dans divers répertoire
            $t = ($this->Theme !== '' and !is_null($this->Theme)) ? $this->Theme : 'default';
            $filename = $path.DIRECTORY_SEPARATOR.$t.DIRECTORY_SEPARATOR.$s;
            if (is_file($filename)) {
                $this->setinCache($filename, $id_cache, P_C_BASIC);
                return $filename;
            }

            $filename = $path.DIRECTORY_SEPARATOR.'default'.DIRECTORY_SEPARATOR.$s;
            if (is_file($filename)) {
                $this->setinCache($filename, $id_cache, P_C_BASIC);
                return $filename;
            }

            // {{{ Retro compatible
            $t = ($this->Theme !== '' and !is_null($this->Theme)) ? $this->Theme : 'defaut';
            $filename = $path.DIRECTORY_SEPARATOR.$t.DIRECTORY_SEPARATOR.$s;
            if (is_file($filename)) {
                $this->setinCache($filename, $id_cache, P_C_BASIC);
                return $filename;
            }
            $filename = $path.DIRECTORY_SEPARATOR.'defaut'.DIRECTORY_SEPARATOR.$s;
            if (is_file($filename)) {
                $this->setinCache($filename, $id_cache, P_C_BASIC);
                return $filename;
            }
            // }}}

            $filename = $path.DIRECTORY_SEPARATOR.$this->ClassName.DIRECTORY_SEPARATOR.$s;
            if (is_file($filename)) {
                $this->setinCache($filename, $id_cache, P_C_BASIC);
                return $filename;
            }

            $filename = $path.DIRECTORY_SEPARATOR.$s;
            if (is_file($filename)) {
                $this->setinCache($filename, $id_cache, P_C_BASIC);
                return $filename;
            }
            // }}}
        }
        if (is_null($r)) 
            $this->triggerError('P_E_0002', E_USER_WARNING,  __METHOD__, 'Unknown theme file', 
                sprintf('FileName = `%s`, ThemePaths = `%s`, Theme = `%s`', $s, $this->ThemePaths, $this->Theme)
            );
        $this->setinCache($r, $id_cache, P_C_BASIC);
        return $r;
    }
    // }}}
    // {{{ addThemePath
    /**
     * Ajout d'un chemin à la liste des chemins de thème
     *
     * @param   string
     * @param   boolean en entete
     * @return  boolean ajoute oui/non
     */
    protected function addThemePath($s, $e = false)
    {
        if (!empty($s) and strpos($this->ThemePaths.PATH_SEPARATOR, $s.PATH_SEPARATOR) === false and is_dir($s)) {
            if (!$e)
                $this->ThemePaths = trim($this->ThemePaths,PATH_SEPARATOR).PATH_SEPARATOR.trim($s,PATH_SEPARATOR);
            else
                $this->ThemePaths = trim($s,PATH_SEPARATOR).PATH_SEPARATOR.trim($this->ThemePaths,PATH_SEPARATOR);
            return true;
        }
        return false;
    }
    // }}}
    // {{{ checkSession
    /**
     * Vérifie qu'une session est initialisée avant de placer de fixer des variables de sessions
     *
     * @return
     */
    private function checkSession()
    {
        $id = session_id();
        if ($id === '' and PHP_SAPI !== 'cli') {            
            session_cache_limiter('public');
            session_start();
        }
    }
    // }}}
    // {{{ setTemplate
    /**
     * Choix d'un template pour une action
     *
     * si on ne donne qu'un seul paramètre à cette methode
     * on considère que l'action concerné est l'action par défaut
     *
     * @param string action concernée (ou fichier si un seul paramètre)
     * @param string fichier associé
     */
    protected function setTemplate($a, $t = null)
    {
        if (is_null($t)) {
            $t = $a;
            $a = $this->DefaultMode;
        }
        $filename = $this->getThemeFile($t);
        $this->Templates[$a] = Pxxo_Buffer::factory('tpl', $filename);
        $this->copyto($this->Templates[$a]);
    }
    // }}}
    // {{{ initTemplate
    /**
     * Devine le nom des templates associés à l'action courant ou à l'action par défaut
     */
    protected function initTemplate()
    {
        $def = $this->getDefaultMode();
        if (!isset($this->Templates[$def])) {
            $deftpl = $this->getThemeFile($def.'.php.html', 'xxx');
            if ($deftpl !== 'xxx') {
                $this->Templates[$def] = Pxxo_Buffer::factory('tpl', $deftpl);
                $this->copyto($this->Templates[$def]);
            }
        }
        $mode = $this->getMode();
        if (!isset($this->Templates[$mode]) || empty($this->Templates[$mode])) {
            $modetpl = $this->getThemeFile($mode.'.php.html', 'xxx');
            if ($modetpl !== 'xxx') {
                $this->Templates[$mode] = Pxxo_Buffer::factory('tpl', $modetpl);
                $this->copyto($this->Templates[$mode]);
            }
        }
    }
    // }}}
    // {{{ getTemplate
    /**
     * Retourne le fichier de Template à utiliser
     *
     * @param    string on peut forcer un mode
     * @return	string
     */
    public function getTemplate($s = null)
    {
        if (is_null($s)) {
            $s = $this->getMode();
        }
        $def = $this->getDefaultMode();
        if ( $s != $def and ! isset($this->Templates[$s]) ) {
            if (isset($this->Templates[$def])) {
                $this->Templates[$s] = $this->Templates[$def];
            }
            else {
                return $this->triggerError('P_E_0006', E_USER_WARNING,  __METHOD__,
                    'No default template', 
                    sprintf('ClassID = `%s` Mode = `%s`', $this->ClassID, $s));
            }
        }
        if (!isset($this->Templates[$s]) || empty($this->Templates[$s])) {
            return $this->triggerError('P_E_0007', E_USER_WARNING,  __METHOD__, 
                'No template',
                sprintf('ClassID = `%s` Mode = `%s`', $this->ClassID, $s));
        }
        return $this->Templates[$s];
    }
    // }}}
    // {{{ setTemplateRaw
    /**
     * Choix d'un template sous forme d'une chaine de caractère et non d'un fichier
     *
     * @param string action concernée
     * @param string contenu du template
     */
    protected function setTemplateRaw($a, $t)
    {
        $this->Templates[$a] = Pxxo_Buffer::factory('tpl', $t);
        $this->copyto($this->Templates[$a]);
    }
    // }}}
    // {{{ setTemplatePath
    /**
     * Position le chemin des templates
     * @param string le chemin
     */
    public function setTemplatePath($s)
    {
        $this->TemplatePath  = $s;
    }
    // }}}
    // {{{ setResourcePath
    /**
     * Position le chemin physique des resources de l'objet
     * ce chemin est transmis automatiquement aux fils de l'objets...
     * @param string le chemin
     */
    public function setResourcePath($s)
    {
        $this->ResourcePath = $s;
    }
    // }}}
    // {{{ setResourceURL
    /**
     * Position l'url d'accès aux resources de l'objet
     * cette url est transmise automatiquement aux fils de l'objets...
     * @param string URL
     */
    public function setResourceURL($s)
    {
        $this->ResourceURL = $s;
    }
    // }}}
    // {{{ setResources
    /**
     * On fixe toutes ressources de l'objet d'un seul coup
     * @param Pxxo_Buffers
     */
    public function setResources(Pxxo_Buffers $b)
    {
        $this->Resources = $b;
    }
    // }}}
    // {{{ setHeaders
    /**
     * On fixe toutes entetes de l'objet d'un seul coup
     * @param Pxxo_Buffers
     */
    public function setHeaders(Pxxo_Buffers $h)
    {
        $this->Headers = $h;
    }
    // }}}
    // {{{ dynResource
    /**
     * construction d'une ressource dynamique comme un template HTML
     *
     * @param    string   nom du fichier (son emplacement sera déduit en fonction du theme)
     * @param    string   type du fichier
     * @return	string   identifiant de la ressource
     */
    private function dynResource($filename, $typ_rsc)
    {
        $themefile = $this->getThemeFile($filename);
        $stm = Pxxo_Buffer::factory($typ_rsc, $themefile);
        $this->copyto($stm);
        $this->View->setInputBuffer($stm);
        $this->View->assign($this->getPublicVars());
//        $this->View->assign('Self', $this);
        $this->View->transform($typ_rsc);
        $rsc = $this->View->getOutputBuffer();
        $this->copyto($rsc);
        //        $rsc->addCacheID($id);
        $rsc->url      = $this->ResourceURL;
        $rsc->path     = $this->ResourcePath;
        $rsc->filename = basename($filename);
        return $this->Resources->add($rsc);
    }
    // }}}
    // {{{ stackWidget
    /**
     * On emboite un composant dans le composant courant
     *
     * @param  Pxxo_Widget
     * @param  bool permet d'ignorer l'enregistrement du widget parent dans l'attribut du fils (utilisé pour l'éventuel décorateur racine)
     * @return Pxxo_Buffer
     */
    public function stackWidget(Pxxo_Widget $son, $register_son_parent = true)
    {
        // L'objet est prisonnier, inutile d'ajouter quoi que ce soit
        if ($this->ModeCaptived) return null;

        // On vérifie que l'on a bien à faire avec un objet de type Widget
        if (is_null($son))
            return $this->triggerError('P_E_0004', E_USER_ERROR,  __METHOD__, 'Widget is NULL');

        // Transmission descendante de paramètres dans la hiérachie
        if (is_null($son->Lang))        $son->Lang = $this->Lang;
        if (is_null($son->Theme))       $son->Theme = $this->Theme;
        if (is_null($son->MinifyLevel)) $son->MinifyLevel = $this->MinifyLevel;
        $son->ResourcePath = $this->ResourcePath;
        $son->ResourceURL = $this->ResourceURL;

        // Le fils garde un accès à l'objet Response
        $son->Response = $this->Response;
        // Le fils garde un lien vers son père
        if ($register_son_parent)
            $son->Parent = $this;
        // Le père garde un lien vers son fils
        $alias = $son->ClassAlias;
        if (is_null($alias) or $alias === false) {
            $alias = 'X'.rand();
            while ($this->Widgets->exists($alias)) $alias = 'X'.rand();
        }
        $this->Widgets->add($alias, $son);

        // Thèmes hiérarchiques
        $paths = explode(PATH_SEPARATOR, $this->ThemePaths);
        $dirname = null;
        foreach($paths as $p) {
            $p = trim($p);
            if ($p === '' or !is_dir($p)) continue;
            // {{{ On recherche un répertoire
            $t = ($this->Theme !== '' and !is_null($this->Theme)) ? $this->Theme : 'default';
            $dirname = rtrim($p, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$t.DIRECTORY_SEPARATOR.$son->ClassName;
            if ($son->addThemePath($dirname, true)) break;

            $t = ($this->Theme !== '' and !is_null($this->Theme)) ? $this->Theme : 'defaut';
            $dirname = rtrim($p, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$t.DIRECTORY_SEPARATOR.$son->ClassName;
            if ($son->addThemePath($dirname, true)) break;

            $dirname = rtrim($p, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . $son->ClassName;
            if ($son->addThemePath($dirname, true)) break;

            $dirname = rtrim($p, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'defaut' . DIRECTORY_SEPARATOR . $son->ClassName;
            if ($son->addThemePath($dirname, true)) break;

            $dirname = rtrim($p, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $son->ClassName;
            if ($son->addThemePath($dirname, true)) break;

            // On  Recherche en minuscule (car en PHP4 le ClassName est en minuscule) donc on est obligé de mettre le nom de répertoire en minuscule...
            $t = ($this->Theme !== '' and !is_null($this->Theme)) ? $this->Theme : 'default';
            $dirname = rtrim($p, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$t.DIRECTORY_SEPARATOR.strtolower($son->ClassName);
            if ($son->addThemePath($dirname, true)) break;

            $t = ($this->Theme != '' and !is_null($this->Theme)) ? $this->Theme : 'defaut';
            $dirname = rtrim($p, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR.$t.DIRECTORY_SEPARATOR . strtolower($son->ClassName);
            if ($son->addThemePath($dirname, true)) break;

            $dirname = rtrim($p, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . strtolower($son->ClassName);
            if ($son->addThemePath($dirname, true)) break;

            $dirname = rtrim($p, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'defaut' . DIRECTORY_SEPARATOR . strtolower($son->ClassName);
            if ($son->addThemePath($dirname, true)) break;

            $dirname = rtrim($p, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . strtolower($son->ClassName);
            if ($son->addThemePath($dirname, true)) break;
            // }}}
        }
        $this->copyto($son);
        $son->main();

        // Le fils veut prendre le pouvoir, on est prisonnié !
        if (isset($son->StandaloneMode) and $son->StandaloneMode) {
            $this->ModeCaptived = true;
        }
        return $son->getBuffer();
    }
    // }}}
    // {{{ dumphead
    /**
     * Affiche en html toutes les informations destinées à l'entete de la page : Headers et Resources
     */
    public function dumphead()
    {
        $data = '';
        foreach($this->Resources as $k => $o) {
            if (($this->MinifyLevel & P_M_RESOURCE) === P_M_RESOURCE) $o->addFilter('compress');
            $data .= $o->getHTML();
        }
        echo $data;
    }
    // }}}
    // {{{ gethead
    /**
     * Retourne les header html du widget sous forme de chaine de caractere ou de tableau
     * @param    string $type le type de retour que l'on veut : du HTML ('html') ou un tableau php ('array')
     */
    public function gethead($type = 'html')
    {
        $type = strtolower($type);
        $data = ($type == 'html') ? '' : array();
        foreach($this->Resources as $k => $o) {
            if (($this->MinifyLevel & P_M_RESOURCE) === P_M_RESOURCE) $o->addFilter('compress');
            if ($type == 'html')
                $data .= $o->getHTML();
            else
                $data[] = $o->getArray();
        }
        return $data;
    }
    // }}}
    // {{{ loadWidget
    public function loadComponent($name) {return $this->loadWidget($name);}
    /**
     * Charge le fichier de définition d'une classe
     *
     * On vérifie que le nom de la classe donnée en paramètre est correct
     * On recherche un fichier de définition/déclaration de la cette classe
     * On le charge en mémoire (on fait un include_once)
     *
     * @param    string nom d'une classe
     * @return	boolean true la classe existe et on put l'utiliser la classe est inutilisable...
     */
    public function loadWidget($name)
    {
        if (version_compare(phpversion(), '5', '>=')) {
            if (class_exists($name, false)) return true;
        } else {
            if (class_exists($name)) return true;
        }
        if (!preg_match(',\w+,', $name) ) return false;
        $name2 = str_replace('_', DIRECTORY_SEPARATOR, $name);
        $filenames = array($name2.'.php', $name.'.php', $name.'.class.php', $name2.'.class.php', $name.'.inc', $name2.'.inc', ucfirst($name2).'.php', substr($name2, 0, 1).ucfirst(substr($name2, 1)).'.php' );
        $paths = explode(PATH_SEPARATOR, ini_get('include_path'));
        foreach($filenames as $filename) {
            foreach ($paths as $path) {
                $fullpath = $path . DIRECTORY_SEPARATOR . $filename;
                if (file_exists($fullpath)) {
                    include_once($fullpath);
                    return true;
                }
            }
        }
        return false;
    }
    // }}}
    // {{{ initTranslate
    /**
     * Initialise le moteur de traduction
     *
     */
    protected function initTranslate()
    {
        if(!is_null($this->Translate)) return;

        if (empty($this->Lang)) {
            $this->triggerError('P_E_0003', E_USER_WARNING,  __METHOD__, 'Lang is empty');
            $p = debug_backtrace();
            foreach($p as $k => $v) {
                unset($p[$k]['object']);
                unset($p[$k]['args']);
                echo "\t at $k  ".$p[$k]['file']." (line {$p[$k]['line']}) -> {$p[$k]['function']} \n";
            }
            exit;
        }


        // Liste des nom de fichiers de langue possibles
        $filenames = array(
            $this->Lang.'.%s',
            $this->Lang.'_'.strtoupper($this->Lang).'.%s',
            $this->Lang,
            substr($this->Lang,0,2).'.%s',
            'fr.%s',
            'en.%s',
            'fr_FR.%s',
            'en_GB.%s',
        );
        // Liste des extentions possibles en fonction de l'Adapter choisi
        $extentions = array(
            'Array'   => 'php',
            'Csv'     => 'csv',
            'Tmx'     => 'tmx',
            'Qt'      => 'ts',
            'Xliff'   => 'xliff',
            'XmlTm'   => 'xml',
            'Tbx'     => 'tbx',
            'Gettext' => 'mo',
        );
        $fullname = 'xxx';
        foreach($filenames as $filename) {
            if (!isset($extentions[$this->TranslateAdapter])) continue;
            $name = 'i18n'.DIRECTORY_SEPARATOR.sprintf($filename,$extentions[$this->TranslateAdapter]);
            $fullname = $this->getThemeFile($name, 'xxx');
            if ($fullname !== 'xxx') break;
        }
        if ($fullname === 'xxx' && is_null($this->TranslateData)) return;

        if ($this->TranslateAdapter == 'Array' && $fullname !== 'xxx') {
            $save = $this->TranslateData;
            include $fullname;
            if (is_array($this->TranslateData) and is_array($save))
                $this->TranslateData = array_merge($this->TranslateData, $save);
            elseif (!is_array($this->TranslateData) and is_array($save))
                $this->TranslateData = $save;
            elseif (!is_array($this->TranslateData) and !is_array($save))
                $this->TranslateData = array();
        }
        if ($this->TranslateAdapter == 'Array' and !is_array($this->TranslateData)) $this->TranslateData = array();
        $this->Translate = new Pxxo_Zend_Translate($this->TranslateAdapter, $this->TranslateData, $this->Lang, $this->TranslateOptions);
    }
    // }}}
    // {{{ _
    /**
     * Methode permettant la traduction
     *
     * @param   string clé
     * @return	string traduction
     */
    public function _()
    {
        $this->initTranslate();
        $args = func_get_args();
        if (is_null($this->Translate) or !$this->Translate->isTranslated($args[0])) {
            if ($this->isDebugging()) $args[0] = '_'.$args[0].'_';
        }
        else $args[0] = $this->Translate->_($args[0]);
        return call_user_func_array('sprintf', $args);
    }
    // }}}
    // {{{ getViewObject
    /**
     * retourne l'objet view utilisé
     *
     * @return Pxxo_View
     */
    public function getViewObject()
    {
        return $this->View;
    }
    // }}}
    // {{{ enableCache
    /**
     * Quand on démarre le cache on fixe (si et seulement si ce n'est pas déjà fait)
     * un niveau de cache standard
     *
     */
    public function enableCache()
    {
        if (($this->State & 32) === 32) {
            $this->triggerError('P_E_0009', E_USER_NOTICE,  __METHOD__, 'Called too late');
        }
        if (is_null($this->getCacheLevel())) {
            $this->setCacheLevel(P_C_BASIC | P_C_RESOURCE | P_C_TEMPLATE | P_C_USER);
        }
        parent::enableCache();
    }
    // }}}
    // {{{ setLifetime
    /**
     * Fixe la durée de vie du cache (de toutes les caches possibles)
     * @param integer new lifetime (in seconds)
     */
    public function setLifetime($lt)
    {
        if (($this->State & 16) === 16) 
            $this->triggerError('P_E_0008', E_USER_NOTICE,  __METHOD__, 'Called after execMode');
        
        $this->setCacheOption('frontendCore', 'lifeTime', $lt);
        if (!is_null($this->Response)) {
            $this->Response->freshFor($lt);
        }
    }
    // }}}
    // {{{ excludeVars 
    /**
     * Evite qu'une ou plusieurs variables de classe soit envoyée(s) au Template
     *
     * @param string ...
     */
    public function excludeVars()
    {
        $argc = func_num_args();
        for ($i = 0; $i < $argc; $i++) {
            $v = func_get_arg($i);
            if ($k = array_search($v, $this->_vars_public)) { 
                unset($this->_vars_public[$k]);
                $this->_vars_privates[] = $v;
            }
        }
    }
    // }}}
    // {{{ getParamsArray 
    /**
     * Retourne la liste des paramètres du widget (clé = nom du paramètre, valeur = valeur du paramètre)
     *
     * @return array
     */
    public function getParamsArray()
    {
        $params = array();
        foreach($this->_params as $p)
            $params[$p] = $this->$p;
        return $params;
    }
    // }}}   
}
