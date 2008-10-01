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

require_once 'Pxxo/Zend/Cache.php';

if (!function_exists('array_md5')) {
    /**
    * Caclul le md5 d'un tableau de chaines
    *
    * @param array $array le tableau à traiter
    *
    * @return   string
    * @package    Pxxo
    */
    function array_md5(Array $array) 
    {
        return md5(implode('', $array));
    }
}

define('P_C_BASIC', 4);
define('P_C_TEMPLATE', 8);
define('P_C_RESOURCE', 16);
define('P_C_XSLT', 32);
define('P_C_WIDGET', 64);
define('P_C_USER', 128);
define('P_C_WIDGET2', 256);
define('P_C_HTTP', 512);

/**
* Classe principale :
* Elle donne à tous les objets la notion de cache, de trace et de bench.
*
* @category  Pxxo
* @package   Pxxo
* @author    Nicolas Thouvenin <nthouvenin@gmail.com>
* @copyright 2008 Nicolas Thouvenin
* @license   http://opensource.org/licenses/bsd-license.php BSD Licence
* @link      http://www.pxxo.net/
*/
class Pxxo
{
    // {{{ Variables
    /**
    * Cache actif ou non
    * @var		boolean
    */
    private $_caching = null;
    /**
    * Niveau de cache
    * @var		integer
    */
    private $_levelcache;
    /**
    * Identifiant interne pour gérer le cache
    * @var		integer
    */
    private $_idcache = 0;
    /**
    * @var		array tableau permettant de calculer un identifiant 
    *                 unique pour chaque action
    */
    private $_ids = array();
    /**
    * Objet cache
    * @var		Pxxo_Zend_Cache
    */
    private $_cache = null;
    /**
    * Options du cache
    * @var		array
    */
    private $_cache_options = array();
    /**
    * Options du cache par défaut
    * @var		array
    */
    private $_cache_options_default = array(
        // Pxxo
        'convertNumericType'        => 'strval',
        'convertArrayType'          => 'array_md5',
        'max_length'                => 30000,
        'stats'                     => false,
        'hits'                      => 'cache.hits',
        'misses'                    => 'cache.misses',
        'entries'                   => 'cache.entries',
        // Mode : normal | refresh | force | ignore
        'mode'                      => 'normal',  
        // backend : auto | File | APC
        'backend'                   => 'auto',     
        'frontendCore'              => array(
            'lifeTime'                  => 3600,
            'logging'                   => false,
            'write_control'             => false,
            'automatic_serialization'   => false,
            'automatic_cleaning_factor' => 100,
        ),
        'backendFile'               => array(
            'cache_dir'                 => '/tmp',
            'file_locking'              => false,
            'read_control'              => true,
            'read_control_type'         => 'strlen',
            'hashed_directory_level'    => 2,
            'file_name_prefix'          => 'pxxo_cache',
        ),
        'backendAPC'               => array(),
    );
    /**
    * Objet bench
    * @var		Benchmark_Timer
    */
    protected $_bench = array('timer'=>null, 'profiler'=>null);
    /**
    * mode debug actif ou non
    * @var		string
    */
    private $_debugging = false;
    /**
    * Options pour le mode debug
    * @var		array
    */
    private $_debug_options = array();
    /**
    * Options du cache par défaut
    * @var		array
    */
    private $_debug_options_default = array(
        //  Permet d'obtenir une regex prédéfinie : all, cpnt, none
        'filter' => 'cpnt',
        // Expression régulière permettant de filtrer les messages de trace
        'regex'  => '',  
        // type d'affichage : tree, plain
        'show'   => 'tree',   
        // type de sortie : html, text, firebug
        'output' => 'html',  
        // Pour pxxo seulement
        'level'  => 0,   
    );
    /**
    * L'objet a t il etait copié ? (par copyto)
    * @var		boolean
    */
    private $_copyed = false;
    // }}}
    // {{{ Constructeur
    /**
    * Constructeur
    */
    function __construct()
    {
        if (strcasecmp($this->getCacheOption('backend'), 'auto') == 0) {
            if (extension_loaded('apc') && ini_get('apc.cache_by_default')) $backend = 'APC';
            else $backend = 'File';
            $this->setCacheOption('backend', $backend);
        }
    }
    // }}}
    // {{{ enableDebug
    /**
    * active le mode debug
    *
    * @return null
    */
    public function enableDebug()
    {
        $this->_debugging = true;
    }
    // }}}
    // {{{ disableDebug
    /**
    * désactive le mode debug
    *
    * @return null
    */
    public function disableDebug()
    {
        $this->_debugging = false;
    }
    // }}}
    // {{{ traceDebug
    /**
    * Affichage d'un message de trace de Debug
    *
    * @param string $msg un message
    *
    * @return null
    */
    public function traceDebug($msg)
    {
        if ($this->isDebugging()) {
            $p = '';
            $l = $this->getDebugOption('level');
            $s = '';
            for ($i = 0; $i < $l; $i++) {
                if ($i > 0) $s .= '|  ';
            }
            if ($l > 0) $p = $s.'|_ '.$p;
            $msg .= sprintf("\tcache(State:%d, Level:%d, Id:%s, ttl:%d)", 
            $this->isCaching(), 
            $this->getCacheLevel(), 
            $this->getCacheID(), 
            $this->getCacheOption('frontendCore', 'lifeTime'));
            if ($this->getDebugOption('filter') == 'all') 
            $this->setDebugOption('regex', ',.*,');
            if ($this->getDebugOption('filter') == 'cpnt')
            $this->setDebugOption('regex', ',COMPONENT,');
            if ($this->getDebugOption('filter') == 'none') 
            $this->setDebugOption('regex', '');
            if ( (strpos(PHP_SAPI, 'cli') !== false)) 
            $this->setDebugOption('output', 'text');
            $regex = $this->getDebugOption('regex');
            if (empty($regex) or is_null($regex)) return;
            if (!preg_match($regex, $msg)) return;

            $output = $this->getDebugOption('output');
            $show   = $this->getDebugOption('show');
            if ($output == 'html') {
                if ($show == 'tree') $msg = $p.$msg;
                echo str_replace(' ', ' ', '<pre style="display:inline">'.$msg."</pre><br/>\n");
            } elseif ($output == 'firebug') {
                $charlist = "'\\";
                $msgtab = explode("\t", preg_replace('/,\s/', ',', $msg));
                if (is_array($msgtab) and count($msgtab) > 2) {
                    printf("<"."script type=\"text/javascript\">\n");
                    printf("var l = $l;\nvar lll = ll-l;\n if (ll >= l) {\nfor(i=0; i <= lll; i++) {\nconsole.groupEnd();\n}\n}\n");
                    $groupname = array_shift($msgtab)."\t".array_shift($msgtab);
                    printf("console.group('%s');\n", addcslashes($groupname, $charlist));
                    foreach ($msgtab as $item) {
                        if (preg_match(',(\w+)\((.*)\),', $item, $match)) {
                            if (!isset($match[1]) or !isset($match[2])) continue;
                            $section  = $match[1];
                            $values   = explode(',', $match[2]);
                            if ($section == 'mode' and preg_match('/[^:]+:(.*),[^:]+:(.*)/', $match[2], $match)) {
                                if (isset($match[1]) and !isset($match[2])) continue;
                                printf("console.log('%s\t%s');\n", addcslashes($match[1], $charlist), addcslashes($match[2], $charlist));
                            } else {
                                printf("a = new Array();\n");
                                printf("a['%s'] = new Array();\n", addcslashes($section, $charlist));
                                foreach ($values as $value) {
                                    $pos = strpos($value, ':');
                                    if ($pos === false) continue;
                                    $n = substr($value, 0, $pos);
                                    $v = substr($value, $pos+1);
                                    printf("a['%s']['%s'] = '%s';\n", addcslashes($section, $charlist), addcslashes($n, $charlist), addcslashes($v, $charlist));
                                }
                                printf("console.dir(a);\n");
                            }
                        }
                    }
                    //                    printf("console.groupEnd();\n");
                    printf("var ll=%s;", addcslashes($l, $charlist));
                    printf("<"."/script>\n");
                }
            } else {
                if ($show == 'tree') $msg = $p.$msg;
                echo preg_replace(',\s+,', ' ', $msg). "\n";
            }
        }
    }
    // }}}
    // {{{ enableCache
    /**
    * mise en route du cache
    *
    * @return null
    * @uses Pxxo_Zend_Cache
    */
    public function enableCache()
    {
        $this->_caching = true;
    }
    // }}}
    // {{{ disableCache
    /**
    * désactive la mise en cache
    *
    * @return null
    */
    public function disableCache()
    {
        $this->_caching = false;
        unset($this->_cache);
        $this->_cache = null;
    }
    // }}}
    // {{{ isCaching
    /**
    * Le cache est-il actif
    *
    * true : le cache a été activé
    * false : le cache a été désactivé
    * null : le cache n' apas été réglé
    *
    * @return   boolean
    */
    public function isCaching()
    {
        return $this->_caching;
    }
    // }}}
    // {{{ isDebugging
    /**
    * Le mode debug est-il actif
    *
    * @return   boolean
    */
    public function isDebugging()
    {
        return $this->_debugging;
    }
    // }}}
    // {{{ setCacheLevel
    /**
    * Changer le niveau du cache
    *
    * @param integer $level niveau de cache
    *
    * @return null
    */
    public function setCacheLevel($level)
    {
        if ($level === 0) { 
            $this->setCacheLevel(P_C_BASIC | P_C_RESOURCE | P_C_TEMPLATE | P_C_USER);
            return;
        }
        if ($level === 1) {
            $this->setCacheLevel(P_C_BASIC | P_C_RESOURCE | P_C_TEMPLATE | P_C_USER | P_C_WIDGET); 
            return;
        }

        $this->_levelcache = $level;
    }
    // }}}
    // {{{ setCacheLevel
    /**
    * ajout un niveau du cache
    *
    * @param integer $level niveau de cache
    *
    * @return null
    */
    public function addCacheLevel($level)
    {
        if ( ! $this->testCacheLevel($level)) 
        $this->setCacheLevel($this->getCacheLevel() | $level);
    }
    // }}}
    // {{{ getCacheLevel
    /**
    * retourne le niveau  du cache
    *
    * @return integer
    */
    public function getCacheLevel()
    {
        return $this->_levelcache;
    }
    // }}}
    //  {{{ testCacheLevel
    /**
    * permet de savoir si un niveau de cache est actif
    *
    * @param integer $level niveau de cache
    *
    * @return string
    */
    function testCacheLevel($level)
    {
        return (($this->_levelcache & $level) == $level);
    }
    // }}}
    //  {{{ setCacheID
    /**
    * Choisir un identifiant de cache
    *
    * @param string $i identifiant de cache
    *
    * @return null
    */
    public function setCacheID($i)
    {
        $this->_idcache = $i;
    }
    // }}}
    //  {{{ resetCacheID
    /**
    * RAZ de l'identifiant de cache
    *
    * @return null
    */
    public function resetCacheID()
    {
        $this->_idcache = 0;
    }
    // }}}
    //  {{{ initCache
    /**
    * Création de l'objet de getsion du cache
    *
    * @return null
    */
    protected function initCache()
    {
        $frontend = 'Core';
        $backend = $this->getCacheOption('backend');
        $frontendOptions = $this->getCacheOption('frontend'.$frontend);
        $backendOptions = $this->getCacheOption('backend'.$backend);
        $this->_cache = Pxxo_Zend_Cache::factory($frontend, $backend, $frontendOptions, $backendOptions);
        if ($backend == 'File' && isset($this->_cache_options['backendFile']['cache_dir'])) {
            $p = rtrim($this->_cache_options['backendFile']['cache_dir'], DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
            if (!isset($this->_cache_options['hits'])) $this->_cache_options['hits'] = $p.'pxxo_cache.hits';
            if (!isset($this->_cache_options['misses'])) $this->_cache_options['misses'] = $p.'pxxo_cache.misses';
            if (!isset($this->_cache_options['entries'])) $this->_cache_options['entries'] = $p.'pxxo_cache.entries';
        }
    }
    // }}}
    //  {{{ resetCache
    /**
    * Réinitilaise le cache
    * (utile quand on a modifié en cours de route le paramètrage)
    *
    * @return null
    */
    public function resetCache()
    {
        unset($this->_cache);
        $this->_cache = null;
    }
    // {{{ cleanCache
    /**
    * Supprime le cache courant
    *
    * @return null
    */
    public function cleanCache()
    {
        if ($this->isCaching()) {
            if (is_null($this->_cache)) $this->initCache();
            return $this->_cache->clean();
        } else return false;
    }
    // }}}
    // {{{ getCacheID
    /**
    * Retroune l'identifiant de cache courant
    * Au passage on le calcul
    *
    * @return   string
    */
    public function getCacheID()
    {
        $func = $this->getCacheOption('convertArrayType');

        if ($this->_idcache === 0) {
            $this->_idcache = $func($this->_ids);
            if (is_int($this->_idcache) and $this->_idcache < 0) $this->_idcache = strtr($this->_idcache, '-', '3');
        }
        if (isset($this->_idcache[0]) and $this->_idcache[0] == '-') $this->_idcache[0] = 'z';
        return $this->_idcache;
    }
    // }}}
    // {{{ getinCache
    /**
    * recupere les données stockés dans le cache
    *
    * @param string $id    identifiant
    * @param string $level niveau de cache
    * 
    * @return	string
    */
    public function getinCache($id, $level = P_C_USER)
    {
        if ($this->isCaching() && $this->testCacheLevel($level)) {
            if (is_null($this->_cache)) $this->initCache();
            if (is_null($id)) $id = $this->getCacheID();
            if (strlen($id) !== 32) $id = md5($id);
            if ($this->getCacheOption('stats')) {
                if ($this->_cache->test($id) !== false) {
                    if ($this->getCacheOption('backend') == 'File') file_put_contents($this->getCacheOption('hits'), '.', FILE_APPEND | LOCK_EX);
                    elseif ($this->getCacheOption('backend') == 'APC') apc_store($this->getCacheOption('hits'), apc_fetch($this->getCacheOption('hits')) + 1);
                } else {
                    if ($this->getCacheOption('backend') == 'File') file_put_contents($this->getCacheOption('misses'), '.', FILE_APPEND | LOCK_EX);
                    elseif ($this->getCacheOption('backend') == 'APC') apc_store($this->getCacheOption('misses'), apc_fetch($this->getCacheOption('misses')) + 1);
                }
            }
            return $this->_cache->get($id);
        } else return false;
    }
    // }}}
    // {{{ setinCache
    /**
    * stocke des données dans le cache
    *
    * @param string $dta   contenu à stocker
    * @param string $id    identifiant 
    * @param string $level niveau de cache
    * 
    * @return   mixed
    */
    public function setinCache($dta, $id, $level)
    {
        if ($this->isCaching() && $this->testCacheLevel($level)) {
            if (is_null($this->_cache)) $this->initCache();
            if (is_null($id)) $id = $this->getCacheID();
            if ($this->getCacheOption('backend') == 'APC' && strlen($dta) >= $this->getCacheOption('max_length')) return false; 

            if (strlen($id) !== 32) $id = md5($id);
            $ret = $this->_cache->save($dta, $id, array(), $this->getCacheOption('frontendCore','lifeTime'));

            if ($this->getCacheOption('stats') and $ret === true) {
                if ($this->getCacheOption('backend') == 'File') file_put_contents($this->getCacheOption('entries'), '.', FILE_APPEND | LOCK_EX);
                elseif ($this->getCacheOption('backend') == 'APC') apc_store($this->getCacheOption('entries'), apc_fetch($this->getCacheOption('entries')) + 1);
            }
            return $ret;
        } else return false;
    }
    // }}}
    //  {{{ delinCache
    /**
    * Supprime le cache courant relatif à un id
    * 
    * @param string $id identifiant de cache
    *
    * @return null
    */
    public function delinCache($id = null)
    {
        if ($this->isCaching()) {
            if (is_null($id)) $id = $this->getCacheID();
            if (is_null($this->_cache)) $this->initCache();

            $id = md5($id);
            return $this->_cache->remove($id);
        } else return false;
    }
    // }}}
    //  {{{ testinCache
    /**
    * Test la présence dans le cache d'un id
    *
    * @param string $id identifiant de cache
    *
    * @return null
    */
    public function testinCache($id = null)
    {
        if ($this->isCaching()) {
            if (is_null($id)) $id = $this->getCacheID();
            if (is_null($this->_cache)) $this->initCache();

            $id = md5($id);
            return $this->_cache->test($id);
        } else return false;
    }
    // }}}
    //  {{{ addCacheID
    /**
    * Ajoute une ou plusierus valeurs discrimiantes
    * pour le caclul de l'identifiant de cache
    *
    * @param variable
    *
    * @return null
    */
    public function addCacheID()
    {
        $func2 = $this->getCacheOption('convertNumericType');
        $argc = func_num_args();
        for ($i = 0; $i < $argc; $i++) {
            $value = func_get_arg($i);
            if (is_string($value)) {
                $value = md5($value);
            } elseif (is_numeric($value)) {
                $value = md5($func2($value));
            } elseif (is_array($value) && !is_callable($value)) {
                $value = md5(serialize($value));
            } elseif (is_array($value) && is_callable($value)) { 
                continue;
            } elseif (is_object($value) and method_exists($value, 'getCacheID')) {
                $value = $value->getCacheID();
            } elseif (is_object($value) and !method_exists($value, 'getCacheID')) {
                $value = md5(serialize($value));
            } elseif (is_resource($value)) { 
                continue;
            } else {
                $value = md5($value);
            }

            if (is_string($value)) {
                $this->_ids[] = $value;
            }
        }
    }
    // }}}
    // {{{ setCacheObject
    /**
    * Fixe une objet de cache spécifique
    *
    * @param Pxxo_Zend_Cache $obj Gestionnaire du système de cache
    *
    * @return null
    */
    public function setCacheObject($obj)
    {
        $this->_cache = $obj;
    }
    // }}}
    // {{{ getCacheObject
    /**
    * retourne l'objet cache utilisé
    *
    * @return Pxxo_Zend_Cache
    */
    public function getCacheObject()
    {
        return $this->_cache;
    }
    // }}}
    // {{{ getCacheOption
    /**
    * retourne la valeur d'une option du cache
    *
    * @param string $n nom de l'option
    *
    * @return mixed
    */
    public function getCacheOption($n, $x = null)
    {
        if (!is_null($x)) {
            if (isset($this->_cache_options[$n][$x])) {
                return $this->_cache_options[$n][$x];
            }
            elseif (isset($this->_cache_options_default[$n][$x])) {
                return $this->_cache_options_default[$n][$x];
            }
        }
        elseif (isset($this->_cache_options[$n])) {
            if (is_array($this->_cache_options[$n])) {
                return array_merge($this->_cache_options_default[$n], $this->_cache_options[$n]);
            } else {
                return $this->_cache_options[$n];
            }
        } elseif (isset($this->_cache_options_default[$n])) {
            return $this->_cache_options_default[$n];
        }
    }
    // }}}
    // {{{ setCacheOption
    /**
    * fixe une option du cache
    *
    * @param string $n nom
    * @param string $v valeur
    *
    * @return null
    */
    public function setCacheOption($n, $v, $x = null)
    {
        if (!is_null($x)) {
            if (!isset($this->_cache_options_default[$n][$v])) {
                $this->triggerError('P_E_0011', E_USER_NOTICE,  __METHOD__, 'Unknown option', "OptionName = `$n`");
            }
            $this->_cache_options[$n][$v] = $x;
        } else {
            if (!isset($this->_cache_options_default[$n])) {
                $this->triggerError('P_E_0011', E_USER_NOTICE,  __METHOD__, 'Unknown option', "OptionName = `$n`");
            }
            $this->_cache_options[$n] = $v;
        }
    }
    // }}}
    // {{{ setCacheOptions
    /**
    * fixe les options du cache
    * si le cache a déjà été activé on en recrée un
    *
    * @param array $a tableau d'options
    *
    * @return null
    */
    public function setCacheOptions(array $a)
    {
        if (is_array($a)) foreach ($a as $k => $v) $this->setCacheOption($k, $v);
    }
    // }}}
    // {{{ getDebugOption
    /**
    * retourne la valeur d'une option du debugging
    *
    * @param string $n nom de l'option
    *
    * @return   mixed
    */
    public function getDebugOption($n)
    {
        if (isset($this->_debug_options[$n])) {
            return $this->_debug_options[$n];
        } elseif (isset($this->_debug_options_default[$n])) {
            return $this->_debug_options_default[$n];
        }
    }
    // }}}
    // {{{ setDebugOption
    /**
    * fixe une option du debugging
    *
    * @param string $n nom
    * @param string $v valeur
    *
    * @return null
    */
    public function setDebugOption($n, $v)
    {
        if (!isset($this->_debug_options_default[$n])){
            $this->triggerError('P_E_0011', E_USER_NOTICE,  __METHOD__, 'Unknown option', "OptionName = `$n`");
        }
        $this->_debug_options[$n] = $v;
    }
    // }}}
    // {{{ setBenchmarkTimer
    /**
    * affection du timer
    *
    * @param Benchmark_Timer $o objet timer
    *
    * @return null
    */
    public function setBenchmarkTimer(Benchmark_Timer $o)
    {
        $this->_bench['timer'] = $o;
    }
    // }}}
    // {{{ setBenchmarkProfiler
    /**
    * affection du profiler
    *
    * @param Benchmark_Profiler $o objet profiler 
    *
    * @return null
    */
    public function setBenchmarkProfiler(Benchmark_Profiler $o)
    {
        $this->_bench['profiler'] = $o;
    }
    // }}}
    // {{{ getBenchmarkProfiler
    /**
    * 
    *
    * @return Benchmark_Profiler $o objet profiler 
    */
    public function getBenchmarkProfiler()
    {
        return $this->_bench['profiler'];
    }
    // }}}    
    // {{{ copyto
    /**
    * Methode permettant de copier les variables internes de l'objet 
    * vers un  une autre instance du même type
    *
    * @param Pxxo $obj objet pxxo de destination
    *
    * @return null
    */
    public function copyto(Pxxo $obj)
    {
        // {{{ Activation descendante
        if (is_null($obj->isCaching())) {
            if ($this->isCaching() === true) {
                if (!is_null($this->getCacheObject()) and is_null($obj->getCacheObject())) {
                    $obj->setCacheObject($this->_cache);
                }
                if (!is_null($this->getCacheLevel()) and is_null($obj->getCacheLevel())) {
                    $obj->setCacheLevel($this->getCacheLevel());
                }
                $obj->enableCache();
            } elseif ($this->isCaching() === false) {
                $obj->disableCache();
            }
        }

        if ($this->isDebugging()) {
            $obj->enableDebug();
        } else {
            $obj->disableDebug();
        }
        // }}}
        // {{{ Transfere descendant des options sauf pour ce qui est déja fixé
        if ($this->_cache_options !== $obj->_cache_options) {
            foreach ($this->_cache_options as $k => $v) {
                if (is_array($this->_cache_options[$k])) {
                    foreach ($this->_cache_options[$k] as $k1 => $v1) {
                        if ( isset($this->_cache_options[$k][$k1]) && $obj->getCacheOption($k, $k1) !== $this->_cache_options_default[$k][$k1] ) $obj->setCacheOption($k, $k1, $v1);
                    }
                } elseif ( isset($this->_cache_options[$k]) && $obj->getCacheOption($k) !== $this->_cache_options_default[$k]  ) $obj->setCacheOption($k, $v);
            }
        }
        if ($this->_debug_options !== $obj->_debug_options) {
            foreach ($this->_debug_options as $k => $v) {
                if ( isset($this->_debug_options[$k]) && $obj->getDebugOption($k) === $this->_debug_options_default[$k]  ) $obj->setDebugOption($k, $v);
            }
        }
        // }}}
        $obj->setDebugOption('level', $obj->getDebugOption('level') + 1);

        // {{{ Bench et cie
        if (!is_null($this->_bench['timer']))
            $obj->setBenchmarkTimer($this->_bench['timer']);
        if (!is_null($this->_bench['profiler']))
            $obj->setBenchmarkProfiler($this->_bench['profiler']);
        // }}}
        
        $obj->copyfrom($this);
    }
    // }}}
    // {{{ errorlog
    /**
    * Methode traitant toutes les Pxxo
    *
    * @param string $n numero de l'erreur
    * @param string $s court libellé de l'erreur
    * @param string $d détails
    * @param integer $l niveau d'erreur
    * @param string $m nom de la méthode ayant déclanché l'erreur
    *
    * @return boolean
    */
    static function triggerError($n, $l, $m, $s, $d = '')
    {
        $url = 'http://www.pxxo.net/fr/error';
        if (!empty($d)) $d = ' <i>('.$d.')</i>';
        return trigger_error(sprintf(
            '%s - <a href="%s#%s">[%s] %s</a>%s', $m, $url, strtolower($n), $n, $s, $d), $l);
    }
    // }}}
    // {{{ copyfrom
    /**
     * Cette méthode indique pour le moment le fait qu'un objet vient d'être copié par copyto
     * A l'avenir on peut utiliser d'autre usage...
     *
     * @param Pxxo $obj objet pxxo source
     */
    public function copyfrom(Pxxo $obj)
    {
        $this->_copyed = true;
    }
    // }}}
     // {{{ isCopyed
    /**
     * L'objet a t il est copiÃ© ?
     *
     * @return boolean
     */
    public function isCopyed()
    {
        return $this->_copyed;
    }
    // }}}
}
