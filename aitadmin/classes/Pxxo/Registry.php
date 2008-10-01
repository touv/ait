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
*
* @category  Pxxo
* @package   Pxxo
* @author    Nicolas Thouvenin <nthouvenin@gmail.com>
* @copyright 2008 Nicolas Thouvenin
* @license   http://opensource.org/licenses/bsd-license.php BSD Licence
* @version   SVN: $Id: Pxxo.php,v 1.17 2008/03/27 08:31:53 thouveni Exp $
* @link      http://www.pxxo.net/
*/

require_once 'Pxxo/Zend/Locale.php';
require_once 'Pxxo/Zend/Registry.php';
class Pxxo_Registry extends Pxxo_Zend_Registry 
{

    public function __construct($a = array(), $f = ArrayObject::ARRAY_AS_PROPS)
    {
        parent::__construct($a, $f);
        $this->initRegistry();
    }
   
    // {{{ initRegistry
    /**
     * Used to initialize the pxxo registry
     */
    private function initRegistry()
    {
        // Chemin de référence
        if (empty($this->path) && isset($_SERVER['SCRIPT_FILENAME']))
            $this->path = dirname($_SERVER['SCRIPT_FILENAME']);
        if (empty($this->path) && isset($_SERVER['PHP_SELF']))
            $this->path = dirname($_SERVER['PHP_SELF']);
        if (empty($this->path))
            $this->path = '.';
        $this->path = rtrim($this->path, '/').'/';
        $this->path = strtr($this->path, DIRECTORY_SEPARATOR, '/');
        $this->name = basename($this->path);

        // Chemin vers un répertoire temporaire 
        if (is_dir($this->path.'tmp'))
            $this->temp_path = $this->path.'tmp';
        elseif (isset($_ENV['TMP'])) 
            $this->temp_path = $_ENV['TMP'];
        elseif (isset($_ENV['TMPDIR'])) 
            $this->temp_path = $_ENV['TMPDIR'];
        elseif (isset($_ENV['TEMP'])) 
            $this->temp_path = $_ENV['TEMP'];
        elseif (is_dir('/tmp')) 
            $this->temp_path = '/tmp';
        else 
            $this->temp_path = '.';
        $this->temp_path = rtrim($this->temp_path, '/').'/';
        $this->temp_path = strtr($this->temp_path, DIRECTORY_SEPARATOR, '/');

        // Chemin de stockage par défaut
        if (is_dir($this->path.'templates')) 
            $this->templates_path = $this->path.'templates/';
        else 
            $this->templates_path = $this->path;

        if (is_dir($this->path.'classes')) {
            $include_path = ini_get('include_path');
            $this->class_path = $this->path.'classes/';
            if (
                (strpos($include_path, $this->class_path.PATH_SEPARATOR) === false) &&
                (strpos($include_path, PATH_SEPARATOR.$this->class_path) === false)
            ) 
            ini_set('include_path',$this->class_path.PATH_SEPARATOR.$include_path);
        }
        $this->cache_path = $this->temp_path;

        // Detection et décomposition des éléments de l'URI
        if (!isset($this->url_root))
            $this->url_root = dirname($_SERVER['PHP_SELF']);
        $this->url_root = strtr($this->url_root, DIRECTORY_SEPARATOR, '/');
        if (strpos($this->url_root, '/') === false)
            $this->url_root = '/'.$this->url_root;
        $this->url_root = preg_replace('/\/$/', '', $this->url_root);

        if (!isset($this->url_host) && isset($_SERVER['HTTP_HOST']))
            $this->url_host = 'http'.(!isset($_SERVER['HTTPS'])||strtolower($_SERVER['HTTPS'])!= 'on'?'':'s').'://'.(isset($_SERVER["HTTP_X_FORWARDED_HOST"]) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : $_SERVER['HTTP_HOST']);
        elseif (!isset($this->url_host) && !isset($_SERVER['HTTP_HOST']))
            $this->url_host = '';
        $this->url_host = preg_replace('/\/$/', '', $this->url_host);

        if (!isset($this->url_base))
            $this->url_base = $this->url_host.$this->url_root;
        $this->url_base = preg_replace('/\/$/', '', $this->url_base);

        if (!isset($this->url_script))
            $this->url_script = basename($_SERVER['PHP_SELF']);
        $this->url_script = preg_replace('/\//', '', $this->url_script);

        if (!isset($this->url_query))
            $this->url_query = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';

        // Filtrage des paramètres de l'url
        if (isset($this->url_query_filter)) {
            $url_query_tmp = array();
            parse_str($this->url_query, $url_query_tmp);
            foreach(array_keys($url_query_tmp) as $key)
                if (!preg_match($this->url_query_filter,$key))
                    unset($url_query_tmp[$key]);
            // rebuild the modified url
            $this->url_query = http_build_query($url_query_tmp);
            $this->url_query = preg_replace('/&/', '&amp;', $this->url_query);
        }
        else {
            $this->url_query = ''; // when no url_query_filter is defined, do not forward any GET parameters
        }

        if (!isset($this->url))
            $this->url = $this->url_base .'/'. $this->url_script . ($this->url_query != '' ? '?'.$this->url_query : '');

        // Url des ressources par défaut

        if (is_dir($this->path.'rsc')) { 
            $this->resources_path = $this->path.'rsc/';
            $this->resources_url  = $this->url_root == '/' ? '/rsc' : $this->url_root.'/rsc';
        }
        else  {
            $this->resources_path = $this->temp_path;
            $this->resources_url  = $this->url_root;
        }

        // Locale
        $this->locale = new Pxxo_Zend_Locale(Pxxo_Zend_Locale::BROWSER);

        // Language préféré pour l'utilisateur
        if (!isset($this->language) or is_null($this->language) or empty($this->language)) {
            $this->language = $this->locale->getLanguage();
        }

        // Chemin pour les sessions
        $this->session_path = $this->temp_path;
        $this->session_flag = session_id() === '' ? false : true;

        $this->debug_level = 0;
    }
    // }}}
    // {{{ checkRegistry
    /**
     * Used to check the pxxo registry parameters validities
     */
    public static function checkRegistry()
    {
        // Get the registry instance
        $Registry = self::getInstance();
        if (isset($Registry->checked)) return;
        $Registry->checked = true; 

        // Controle des niveaux de debug
        if (!is_numeric($Registry->debug_level))
            return Pxxo::triggerError('P_E_0017', E_USER_NOTICE,  __METHOD__,
                                      'Invalid parameter', 
                                      '$Registry->debug_level '."(`$Registry->debug_level`) is not a numeric");
        if ($Registry->debug_level < 0)
            $Registry->debug_level = 0;
        if ($Registry->debug_level > 4)
            $Registry->debug_level = 4;

        // Réglage du mode Debug
        if ($Registry->debug_level > 0) {
            error_reporting(E_ALL);
        }

        // Les chemins déduits sont-ils des répertoires ? Et pour certain peut-on écrire dedans ?
        if (!is_dir($Registry->path)) {
            return Pxxo::triggerError('P_E_0017', E_USER_NOTICE,  __METHOD__,
                'Invalid parameter', 
                "`$Registry->path` is not a directory");
        }
        if (!is_dir($Registry->temp_path)) {
            return Pxxo::triggerError('P_E_0017', E_USER_NOTICE,  __METHOD__,
                'Invalid parameter', 
                "`$Registry->temp_path` is not a directory");
        }
        if (!is_writable($Registry->temp_path)) {
            return Pxxo::triggerError('P_E_0017', E_USER_ERROR,  __METHOD__,
                'Invalid parameter', 
                "`$Registry->temp_path` is not writable by the web server");
        }
        if (!is_dir($Registry->cache_path)) {
            return Pxxo::triggerError('P_E_0017', E_USER_ERROR,  __METHOD__,
                'Invalid parameter', 
                "`$Registry->cache_path` is not a directory");
        }
        if (!is_writable($Registry->cache_path)) {
            return Pxxo::triggerError('P_E_0017', E_USER_ERROR,  __METHOD__,
                'Invalid parameter', 
                "`$Registry->cache_path` is not writable by the web server");
        }
        if (!is_dir($Registry->resources_path)) {
            return Pxxo::triggerError('P_E_0017', E_USER_ERROR,  __METHOD__,
                'Invalid parameter', 
                "`$Registry->resources_path` is not a directory");
        }
        if (!is_writable($Registry->resources_path)) {
            return Pxxo::triggerError('P_E_0017', E_USER_ERROR,  __METHOD__,
                'Invalid parameter', 
                "`$Registry->resources_path` is not writable by the web server");
        }
        if (!is_dir($Registry->templates_path)) {
            return Pxxo::triggerError('P_E_0017', E_USER_ERROR,  __METHOD__,
                'Invalid parameter', 
                "`$Registry->templates_path` is not a directory");
        }
        if ($Registry->resources_url === '') {
            return Pxxo::triggerError('P_E_0017', E_USER_ERROR,  __METHOD__,
                'Invalid parameter', 
                "`$Registry->resources_url` is empty");
        }

        // Démarrage de la session
        if ($Registry->session_flag === true) {
            if (!is_dir($Registry->session_path)) {
                return Pxxo::triggerError('P_E_0017', E_USER_ERROR,  __METHOD__,
                    'Invalid parameter', 
                    "`$Registry->session_path` is not a directory");
            }
            if (!is_writable($Registry->session_path)) {
                return Pxxo::triggerError('P_E_0017', E_USER_ERROR,  __METHOD__,
                    'Invalid parameter', 
                    "`$Registry->session_path` is not writable by the web server");
            }
            session_save_path($Registry->session_path);
            if (isset($Registry->session_auto) and 
                $Registry->session_auto === true and 
                !isset($_SESSION)) {
                session_start();
            }
        }
    }
    // }}}
}
Pxxo_Zend_Registry::setClassName('Pxxo_Registry');
