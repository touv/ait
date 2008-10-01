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
 * @version    $Id: Date.php 2498 2006-12-23 22:13:38Z thomas $
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */


/** Pxxo_Zend_Locale */
require_once 'Pxxo/Zend/Locale.php';

/**
 * @category   Zend
 * @package    Pxxo_Zend_Translate
 * @copyright  Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
abstract class Pxxo_Zend_Translate_Adapter {

    private          $_automatic = true;
    protected static $_cache     = null;

    // Scan options
    const LOCALE_DIRECTORY = 1;
    const LOCALE_FILENAME  = 2;

    /**
     * Array with all options, each adapter can have own additional options
     *
     * @var array
     */
    protected $_options = array(
        'clear'  => false, // clear previous loaded translation data
        'scan'   => null,  // where to find the locale
        'locale' => null   // actual set locale/language 
    );

    /**
     * Translation table
     *
     * @var array
     */
    protected $_translate = array();


    /**
     * Generates the adapter
     *
     * @param  string|array        $data      Translation data for this adapter
     * @param  string|Pxxo_Zend_Locale  $locale    OPTIONAL Locale/Language to set, identical with Locale identifiers
     *                                        see Pxxo_Zend_Locale for more information
     * @param  string|array        $options   Options for the adaptor
     * @throws Pxxo_Zend_Translate_Exception
     */
    public function __construct($data, $locale = null, array $options = array())
    {
        if (isset(self::$_cache)) {
            $id = 'Pxxo_Zend_Translate_' . $this->toString();
            if ($result = self::$_cache->load($id)) {
                $this->_translate = unserialize($result);
                return true;
            }
        }

        if ($locale === null) {
            $locale = new Pxxo_Zend_Locale();
        }
        if ($locale instanceof Pxxo_Zend_Locale) {
            $locale = $locale->toString();
        }
        $originate = $locale;

        $options = array_merge($this->_options, $options);
        if (is_string($data) and is_dir($data)) {
            foreach (new RecursiveIteratorIterator(
                     new RecursiveDirectoryIterator($data, RecursiveDirectoryIterator::KEY_AS_PATHNAME), 
                     RecursiveIteratorIterator::SELF_FIRST) as $file => $info) {
                if ($info->isDir()) {

                    $directory = $info->getPath();
                    // pathname as locale
                    if (($options['scan'] === self::LOCALE_DIRECTORY) and (Pxxo_Zend_Locale::isLocale((string) $info))) {
                        $locale = (string) $info;
                    }

                } else if ($info->isFile()) {

                    // filename as locale
                    if ($options['scan'] === self::LOCALE_FILENAME) {
                        $filename = explode('.', (string) $info);
                        array_pop($filename);
                        $filename = implode('.', $filename);
                        if (Pxxo_Zend_Locale::isLocale($filename)) {
                            $locale = (string) $filename;
                        } else {
                            $found = false;
                            $parts = explode('.', $filename);
                            foreach($parts as $token) {
                                $parts = array_merge(explode('_', $token), $parts);
                            }
                            foreach($parts as $token) {
                                $parts = array_merge(explode('-', $token), $parts);
                            }
                            $parts = array_unique($parts);
                            foreach($parts as $token) {
                                if (Pxxo_Zend_Locale::isLocale($token)) {
                                    $locale = $token;
                                }
                            }
                        }
                    }
                    try {
                        $this->addTranslation((string) $info->getPathname(), $locale, $options);
                        if ((array_key_exists($locale, $this->_translate)) and (count($this->_translate[$locale]) > 0)) {
                            $this->setLocale($locale);
                        }
                    } catch (Pxxo_Zend_Translate_Exception $e) {
                        // ignore failed sources while scanning
                    }
                }
            }
        } else {
            $this->addTranslation($data, $locale, $options);
            if ((array_key_exists($locale, $this->_translate)) and (count($this->_translate[$locale]) > 0)) {
                $this->setLocale($locale);
            }
        }
        if ((array_key_exists($originate, $this->_translate)) and (count($this->_translate[$originate]) > 0)) {
            $this->setLocale($originate);
        }
        $this->_automatic = true;
    }


    /**
     * Sets new adapter options
     *
     * @param  array  $options  Adapter options
     * @throws Pxxo_Zend_Translate_Exception
     */
    public function setOptions(array $options = array())
    {
        foreach ($options as $key => $option) {
            $this->_options[strtolower($key)] = $option;
        }
    }

    /**
     * Returns the adapters name and it's options
     *
     * @param  string|null  $optionKey  String returns this option
     *                                  null returns all options
     * @return integer|string|array
     */
    public function getOptions($optionKey = null)
    {
        if ($optionKey === null) {
            return $this->_options;
        }
        if (array_key_exists(strtolower($optionKey), $this->_options)) {
            return $this->_options[strtolower($optionKey)];
        }
        return null;
    }


    /**
     * Gets locale
     *
     * @return Pxxo_Zend_Locale|null
     */
    public function getLocale()
    {
        return $this->_options['locale'];
    }


    /**
     * Sets locale
     *
     * @param  string|Pxxo_Zend_Locale  $locale  Locale to set
     * @throws Pxxo_Zend_Translate_Exception
     */
    public function setLocale($locale)
    {
        if ($locale instanceof Pxxo_Zend_Locale) {
            $locale = $locale->toString();
        } else if (!$locale = Pxxo_Zend_Locale::isLocale($locale)) {
            require_once 'Pxxo/Zend/Translate/Exception.php';
            throw new Pxxo_Zend_Translate_Exception("The given Language ({$locale}) does not exist");
        }

        if (!array_key_exists($locale, $this->_translate) and empty($this->_translate[$locale])) {
            $temp = explode('_', $locale);
            if (!array_key_exists($temp[0], $this->_translate)) {
                require_once 'Pxxo/Zend/Translate/Exception.php';
                throw new Pxxo_Zend_Translate_Exception("Language ({$locale}) has to be added before it can be used.");
            }
            $locale = $temp[0];
        }

        $this->_options['locale'] = $locale;
        if ($locale == "auto") {
            $this->_automatic = true;
        } else {
            $this->_automatic = false;
        }
    }


    /**
     * Returns the avaiable languages from this adapter
     *
     * @return array
     */
    public function getList()
    {
        $list = array_keys($this->_translate);
        $result = null;
        foreach($list as $key => $value) {
            if (!empty($this->_translate[$value])) {
                $result[$value] = $value;
            }
        }
        return $result;
    }


    /**
     * Returns all avaiable message ids from this adapter
     * If no locale is given, the actual language will be used
     *
     * @param  $locale  String|Pxxo_Zend_Locale  Language to return the message ids from
     * @return array
     */
    public function getMessageIds($locale = null)
    {
        if (empty($locale) or !$this->isAvailable($locale)) {
            $locale = $this->_options['locale'];
        }
        return array_keys($this->_translate[$locale]);
    }


    /**
     * Returns all avaiable translations from this adapter
     * If no locale is given, the actual language will be used
     * If 'all' is given the complete translation dictionary will be returned
     *
     * @param  $locale  String|Pxxo_Zend_Locale  Language to return the messages from
     * @return array
     */
    public function getMessages($locale = null)
    {
        if ($locale == 'all') {
            return $this->_translate;
        }
        if (empty($locale) or !$this->isAvailable($locale)) {
            $locale = $this->_options['locale'];
        }
        return $this->_translate[$locale];
    }


    /**
     * Is the wished language avaiable ?
     *
     * @param  string|Pxxo_Zend_Locale  $locale  Language to search for, identical with locale identifier,
     *                                      see Pxxo_Zend_Locale for more information
     * @return boolean
     */
    public function isAvailable($locale)
    {
        if ($locale instanceof Pxxo_Zend_Locale) {
            $locale = $locale->toString();
        }

        return array_key_exists($locale, $this->_translate);
    }

    /**
     * Load translation data
     *
     * @param  mixed               $data
     * @param  string|Pxxo_Zend_Locale  $locale
     * @param  array               $options
     */
    abstract protected function _loadTranslationData($data, $locale, array $options = array());

    /**
     * Add translation data
     *
     * It may be a new language or additional data for existing language
     * If $clear parameter is true, then translation data for specified
     * language is replaced and added otherwise
     *
     * @param  array|string          $data    Translation data
     * @param  string|Pxxo_Zend_Locale    $locale  Locale/Language to add data for, identical with locale identifier,
     *                                        see Pxxo_Zend_Locale for more information
     * @param  array                 $options OPTIONAL Option for this Adapter
     * @throws Pxxo_Zend_Translate_Exception
     */
    public function addTranslation($data, $locale, array $options = array())
    {
        if (!$locale = Pxxo_Zend_Locale::isLocale($locale)) {
            require_once 'Pxxo/Zend/Translate/Exception.php';
            throw new Pxxo_Zend_Translate_Exception("The given Language ({$locale}) does not exist");
        }

        if (!array_key_exists($locale, $this->_translate)) {
            $this->_translate[$locale] = array();
        }

        $this->_loadTranslationData($data, $locale, $options);
        if ($this->_automatic === true) {
            $find = new Pxxo_Zend_Locale($locale);
            $browser = $find->getBrowser() + $find->getEnvironment();
            arsort($browser);
            foreach($browser as $language => $quality) {
                if (array_key_exists($language, $this->_translate)) {
                    $this->_options['locale'] = $language;
                    break;
                }
            }
        }

        if (isset(self::$_cache)) {
            $id = 'Pxxo_Zend_Translate_' . $this->toString();
            self::$_cache->save( serialize($this->_translate), $id);
        }
    }


    /**
     * Translates the given string
     * returns the translation
     *
     * @param  string              $messageId  Translation string
     * @param  string|Pxxo_Zend_Locale  $locale     OPTIONAL Locale/Language to use, identical with locale identifier,
     *                                         see Pxxo_Zend_Locale for more information
     * @return string
     */
    public function translate($messageId, $locale = null)
    {
        if ($locale === null) {
            $locale = $this->_options['locale'];
        } else {
            if (!$locale = Pxxo_Zend_Locale::isLocale($locale)) {
                // language does not exist, return original string
                return $messageId;
            }
        }

        if ((array_key_exists($locale, $this->_translate)) and
            (array_key_exists($messageId, $this->_translate[$locale]))) {
            // return original translation
            return $this->_translate[$locale][$messageId];
        } else if (strlen($locale) != 2) {
            // faster than creating a new locale and separate the leading part
            $locale = substr($locale, 0, -strlen(strrchr($locale, '_')));

            if ((array_key_exists($locale, $this->_translate)) and
                (array_key_exists($messageId, $this->_translate[$locale]))) {
                // return regionless translation (en_US -> en)
                return $this->_translate[$locale][$messageId];
            }
        }

        // no translation found, return original
        return $messageId;
    }


    /**
     * Translates the given string
     * returns the translation
     *
     * @param  string              $messageId  Translation string
     * @param  string|Pxxo_Zend_Locale  $locale     OPTIONAL Locale/Language to use, identical with locale identifier,
     *                                         see Pxxo_Zend_Locale for more information
     * @return string
     */
    public function _($messageId, $locale = null)
    {
        return $this->translate($messageId, $locale);
    }


    /**
     * Checks if a string is translated within the source or not
     * returns boolean
     *
     * @param  string              $messageId  Translation string
     * @param  boolean             $original   OPTIONAL Allow translation only for original language
     *                                         when true, a translation for 'en_US' would give false when it can
     *                                         be translated with 'en' only
     * @param  string|Pxxo_Zend_Locale  $locale     OPTIONAL Locale/Language to use, identical with locale identifier,
     *                                         see Pxxo_Zend_Locale for more information
     * @return boolean
     */
    public function isTranslated($messageId, $original = false, $locale = null)
    {
        if (($original !== false) and ($original !== true)) {
            $locale = $original;
            $original = false;
        }
        if ($locale === null) {
            $locale = $this->_options['locale'];
        } else {
            if (!$locale = Pxxo_Zend_Locale::isLocale($locale)) {
                // language does not exist, return original string
                return false;
            }
        }

        if ((array_key_exists($locale, $this->_translate)) and
            (array_key_exists($messageId, $this->_translate[$locale]))) {
            // return original translation
            return true;
        } else if ((strlen($locale) != 2) and ($original === false)) {
            // faster than creating a new locale and separate the leading part
            $locale = substr($locale, 0, -strlen(strrchr($locale, '_')));

            if ((array_key_exists($locale, $this->_translate)) and
                (array_key_exists($messageId, $this->_translate[$locale]))) {
                // return regionless translation (en_US -> en)
                return true;
            }
        }

        // no translation found, return original
        return false;
    }


    /**
     * Sets a cache for all Pxxo_Zend_Translate_Adapters
     *
     * @param Pxxo_Zend_Cache_Core $cache Cache to store to
     */
    public static function setCache(Pxxo_Zend_Cache_Core $cache)
    {
        self::$_cache = $cache;
    }


    /**
     * Returns the adapter name
     *
     * @return string
     */
    abstract public function toString();
}
