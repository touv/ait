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
 * @package    Pxxo_Zend_Translate
 * @copyright  Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @version    $Id: $
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */


/** Pxxo_Zend_Locale */
require_once 'Pxxo/Zend/Locale.php';

/** Pxxo_Zend_Translate_Adapter */
require_once 'Pxxo/Zend/Translate/Adapter.php';


/**
 * @category   Zend
 * @package    Pxxo_Zend_Translate
 * @copyright  Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Pxxo_Zend_Translate_Adapter_Array extends Pxxo_Zend_Translate_Adapter {
    /**
     * Generates the adapter
     *
     * @param  array               $data     Translation data
     * @param  string|Pxxo_Zend_Locale  $locale   OPTIONAL Locale/Language to set, identical with locale identifier,
     *                                       see Pxxo_Zend_Locale for more information
     * @param  array               $options  OPTIONAL Options to set
     */
    public function __construct($data, $locale = null, array $options = array())
    {
        parent::__construct($data, $locale, $options);
    }

    /**
     * Load translation data
     *
     * @param  string|array  $data
     * @param  string        $locale  Locale/Language to add data for, identical with locale identifier,
     *                                see Pxxo_Zend_Locale for more information
     * @param  array         $options OPTIONAL Options to use
     */
    protected function _loadTranslationData($data, $locale, array $options = array())
    {
        if (!is_array($data)) {
            if (file_exists($data)) {
                ob_start();
                $data = include($data);
                ob_end_clean();
            }
        }
        if (!is_array($data)) {
            require_once 'Pxxo/Zend/Translate/Exception.php';
            throw new Pxxo_Zend_Translate_Exception("Error including array or file '".$data."'");
        }

        $options = array_merge($this->_options, $options);
        if (($options['clear'] == true) ||  !isset($this->_translate[$locale])) {
            $this->_translate[$locale] = array();
        }

        $this->_translate[$locale] = array_merge($this->_translate[$locale], $data);
    }

    /**
     * returns the adapters name
     *
     * @return string
     */
    public function toString()
    {
        return "Array";
    }
}
