<?php

/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Pxxo_Zend_Cache
 * @subpackage Frontend
 * @copyright  Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */


/**
 * Pxxo_Zend_Cache_Core
 */
require_once 'Pxxo/Zend/Cache/Core.php';


/**
 * @package    Pxxo_Zend_Cache
 * @subpackage Frontend
 * @copyright  Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Pxxo_Zend_Cache_Frontend_Function extends Pxxo_Zend_Cache_Core
{

    // ------------------
    // --- Properties ---
    // ------------------

    /**
     * This frontend specific options
     *
     * ====> (boolean) cache_by_default :
     * - if true, function calls will be cached by default
     *
     * ====> (array) cached_functions :
     * - an array of function names which will be cached (even if cache_by_default = false)
     *
     * ====> (array) non_cached_functions :
     * - an array of function names which won't be cached (even if cache_by_default = true)
     *
     * @var array options
     */
    protected $_specificOptions = array(
        'cache_by_default' => true,
        'cached_functions' => array(),
        'non_cached_functions' => array()
    );


    // ----------------------
    // --- Public methods ---
    // ----------------------

    /**
     * Constructor
     *
     * @param array $options associative array of options
     */
    public function __construct($options = array())
    {
        while (list($name, $value) = each($options)) {
            $this->setOption($name, $value);
        }
        $this->setOption('automatic_serialization', true);
    }

    /**
     * Main method : call the specified function or get the result from cache
     *
     * @param string $name function name
     * @param array $parameters function parameters
     * @param array $tags cache tags
     * @param int $specificLifetime if != false, set a specific lifetime for this cache record (null => infinite lifetime)
     * @return mixed result
     */
    public function call($name, $parameters = array(), $tags = array(), $specificLifetime = false)
    {
        $cacheBool1 = $this->_specificOptions['cache_by_default'];
        $cacheBool2 = in_array($name, $this->_specificOptions['cached_functions']);
        $cacheBool3 = in_array($name, $this->_specificOptions['non_cached_functions']);
        $cache = (($cacheBool1 || $cacheBool2) && (!$cacheBool3));
        if (!$cache) {
            // We do not have not cache
            return call_user_func_array($name, $parameters);
        }
        $id = $this->_makeId($name, $parameters);
        if ($this->test($id)) {
            // A cache is available
            $result = $this->load($id);
            $output = $result[0];
            $return = $result[1];
        } else {
            // A cache is not available
            ob_start();
            ob_implicit_flush(false);
            $return = call_user_func_array($name, $parameters);
            $output = ob_get_contents();
            ob_end_clean();
            $data = array($output, $return);
            $this->save($data, $id, $tags, $specificLifetime);
        }
        echo $output;
        return $return;
    }


    // ------------------------------------
    // --- Private or protected methods ---
    // ------------------------------------

    /**
     * Make a cache id from the function name and parameters
     *
     * @param string $name function name
     * @param array $parameters function parameters
     * @return string cache id
     */
    private function _makeId($name, $parameters)
    {
        if (!is_string($name)) {
            Pxxo_Zend_Cache::throwException('Incorrect function name');
        }
        if (!is_array($parameters)) {
            Pxxo_Zend_Cache::throwException('parameters argument must be an array');
        }
        return md5($name . serialize($parameters));
    }

}