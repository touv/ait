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


/**
 * Classe de Gestion de l'entete HTTP 
 *
 * Cette classe reprend la classe de Hugo Haas (http://larve.net/2006/08/php-http-caching/)
 * et lui ajoute quelques bricoles
 *
 * @package    Pxxo
 * @copyright  Copyright (c) 2008 Nicolas Thouvenin 
 * @license    http://opensource.org/licenses/bsd-license.php
 */
class Pxxo_Response
{
    /**
     * Array containing the list of Cache-Control directives, except max-age and s-maxage
     * @var array 
     */
    private $_CacheDirectives = array();

    /**
     * allowed Cache Control Directives
     * @var array 
     */
    private $CacheDirectives = array(
        'public',
        'private',
        'no-cache',
        'no-store',
        'must-revalidate',
        'proxy-revalidate',
    );

    /**
     * Array containing the list of Cache-Control directives, except max-age and s-maxage
     * @var array 
     */
    private $_ContentDirectives = array();

    /**
     * allowed Content Directives
     * @var array 
     */
    private $ContentDirectives = array(
        'type',
        'language',
        'disposition',
        'transfer-encoding',
        'length',
    );


    /**
     * var array Ages: max-age and s-maxage
     */
    private $ages = array(
        'max-age' => -1, 
        's-maxage' => -1
    );

    /**
     * @var string Last-Modified and ETags
     */
    private $lastModified;

    /**
     * @var string 
     */
    private $eTag;

    /**
     * @var array URL et Status Code 
     */
    private $redirection  =array();

    /**
     * @var array cookies will be send
     */
    private $cookies = array();

    /**
     * Send HTTP caching headers (Expires, Cache-Control, ETags and
     * Last-Modified), and returns a 304 if the client has a cached version.
     *
     * This function returns a 304 if the client already has the latest version
     * of the resource's representation. The PHP
     * script will subsequently quit if $die is set to true. 
     *
     * @param $die if set to true, the program terminates if a 304 is being sent, and if set to false, the call will return and the execution will continue
     */
    function sendStatusAndHeaders($die) 
    {
        if ($this->isRedirect()) {
            header('HTTP/1.1 ' . $this->redirection[1]);
            $this->sendHeaders();
            if ($die == true) exit();
        }

        $isFresh = $_SERVER['REQUEST_METHOD'] == "GET" ? $this->isFresh() : false;
        // Send back a 304?
        if ($isFresh == true) {
            header('HTTP/1.1 304 Not Modified');
        }
        $this->sendHeaders();
        // Die if 304?
        if ($isFresh == true && $die == true) exit();
    }

    /**
     * Send HTTP caching headers (Expires, Cache-Control, ETags and  Last-Modified)
     *
     * Expires and Cache-Control are sent as set.
     *
     * ETag is sent if set. Last-Modified is sent if set or if ETag isn't set,
     * defaulting to the current time.
     * Indeed, at least you of the two needs to be present for the response
     * to be cacheable.
     */
    function sendHeaders() 
    {
        // Signature Pxxo
        header('X-Powered-By: Pxxo/5.x', false);


        if ($this->isRedirect()) {
            header('Location: ' . $this->redirection[0], true);
            header('HTTP/1.1 ' . $this->redirection[1]);
            return;
        }

        // Cookies
        foreach($this->cookies as $key => $value) {
            header('Set-Cookie: '.$value, false);
        }

        // Expires corresponds to max-age
        if ($this->ages['max-age'] >= 0) {
            header('Expires: ' . self::formatDate(time() + $this->ages['max-age']), 1);
        }
        // Cache-Control
        if (!is_array($this->_CacheDirectives)) {
            $this->_CacheDirectives = array();
        }
        foreach($this->ages as $dir => $value) {
            if ($value >= 0) {
                array_push($this->_CacheDirectives, "$dir=$value");
            }
        }
        if (count($this->_CacheDirectives) > 0) {
            header('Cache-Control: ' .
                implode(', ', $this->_CacheDirectives), true);
        }
        // At least one of ETags of Last-Modified will be sent for cacheability
        if ($this->eTag) {
            header('ETag: ' . $this->eTag);
        }
        if ($this->lastModified) {
            $lm = $this->lastModified;
        } else if (!$this->eTag) {
            $lm = time();
        }
        if (isset($lm)) {
            header('Last-Modified: ' . self::formatDate($lm));
        }

        // Content-*
        foreach($this->_ContentDirectives as $key => $value) {
            header('Content-'.ucfirst($key).': ' . $value, true);
        }
    }

    /**
     * Determines whether the client has a fresh representation of the resource.
     *
     * This function compares the If-Modified-Since and If-None-Match headers
     * provided by the client to the values specified for this resource, and
     * returns true if the resource has been modified or false if the client's
     * version is current.
     *
     * @return true if the version the client has is fresh, false if this is not the case and a new version needs to be returned
     */
    function isFresh() 
    {
        if (!isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) &&
            !isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
                // No information provided by the client
                return false;
            }

        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && ! $this->eTag) {
            if (!$this->lastModified) {
                $this->addCookie('T2', true);
                return false;
            }
            // Split the If-Modified-Since (Netscape < v6 gets this wrong)
            $ifModifiedSince = explode(';', $_SERVER['HTTP_IF_MODIFIED_SINCE']);
            // Turn the client request If-Modified-Since into a timestamp
            $ifModifiedSince = strtotime($ifModifiedSince[0]);
            // Compare timestamps (FIXME: make this test '!='?)
            if ($this->lastModified > $ifModifiedSince) {
                return false;
            }
        }

        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $this->eTag) {
            if ($_SERVER['HTTP_IF_NONE_MATCH'] == '*') {
                return true;
            }
            $etags = preg_split('/,\s*/', $_SERVER['HTTP_IF_NONE_MATCH']);
            foreach($etags as $e) {
                if ($this->etagMatch($e)) {
                    return true;
                }
            }
            return false;
        }

        return true;
    }

    /**
     * Compares an etag with the resource's entity tag.
     *
     * @param $etag Entity tag to compare
     * @return true if they match, false if they don't
     */
    private function etagMatch($etag) 
    {
        if (!$this->eTag) {
            return false;
        }
        if ((self::isEtagWeak($this->eTag) || self::isEtagWeak($etag))
            &&
                ($_SERVER['REQUEST_METHOD'] != "GET" || isset($_SERVER['HTTP_RANGE'])))
        {
            // Weak validation only works for non-subrange GET requests
            return false;
        }
        if (self::etagValidator($this->eTag) == self::etagValidator($etag)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Determines if an entity tag is weak
     *
     * @param $etag Entity tag
     * @return true if the entity tag is weak, false otherwise
     */
    static function isEtagWeak($etag) 
    {
        return (substr_compare($etag, 'W/', 0, 2) == 0);
    }

    /**
     * Returns the validator value of an entity tag
     *
     * @param $etag Entity tag
     * @return The validator value ($etag if the entity tag is strong, or what's after 'W/' otherwise)
     */
    static function etagValidator($etag)
    {
        if (self::isEtagWeak($etag)) {
            return substr($etag, 2);
        } else {
            return $etag;
        }
    }

    /**
     * Sets Location header and response code. Forces replacement of any prior redirects.
     *
     * @param string $url
     * @param int $code
     */
    public function setRedirect($url, $code = 302)
    {
        $this->redirection = array($url, $code);
    }

    /**
     * Is there a redirection
     *
     * @return boolean
     */
    public function isRedirect()
    {
        return (count($this->redirection) === 2);
    }


    /**
     * Get the max-age or s-maxage cache control directive value
     *
     * @param $type "max-age" or "s-maxage"
     * @return The value in seconds; if -1 is returned, no max-age directive will be sent
     */
    function getDuration($type) 
    {
        if ($type != "max-age" && $type != "s-maxage") {
            return $this->triggerError('P_E_0011', E_USER_NOTICE,  __METHOD__, 'Unknown option', "OptionName = `$type`");
        }
        return $this->ages[$type];
    }

    /**
     * Set max-age or s-maxage cache control directive value
     *
     * @param $type "max-age" or "s-maxage"
     * @param $time Duration as a positive number of seconds, a string
     *        for strtotime(), or a negative number to disable this directive
     * @return Updated value
     */
    function setDuration($type, $time) 
    {
        if ($type != "max-age" && $type != "s-maxage") {
            return $this->triggerError('P_E_0011', E_USER_NOTICE,  __METHOD__, 'Unknown option', "OptionName = `$type`");
        }
        if (is_numeric($time)) {
            $time = intval($time);
            if ($time < 0) {
                $time = -1;
            }
        } else {
            if ($time == 'now') {
                $time = 0;
            } else {
                $time = strtotime($time) - time();
            }
            if ($time < 0) {
                return $this->triggerError('P_E_0012', E_USER_NOTICE,  __METHOD__, 'Bad interval', "Value = `$time`");
            }
        }
        return $this->ages[$type] = $time;
    }

    /**
     * Set max-age and the Expires header value
     *
     * @param $time Duration as a positive number of seconds, a string
     *        for strtotime(), or a negative number to disable this directive
     * @return Updated value
     */
    function freshFor($time) 
    {
        return $this->setDuration('max-age', $time);
    }

    /**
     * Set/unset Cache-Control directive
     *
     * @param string $type Cache-Control directive (e.g. "public")
     * @param boolean $set true or false to set or unset the parameter
     */
    function setCacheDirective($type, $set) 
    {
        $type = strtolower($type);
        if (!in_array($type, $this->CacheDirectives)) {
            return $this->triggerError('P_E_0011', E_USER_NOTICE,  __METHOD__, 'Unknown option', "OptionName = `$type`");
        }
        if ($set == true) {
            if (!in_array($type, $this->_CacheDirectives)) {
                array_push($this->_CacheDirectives, $type);
            }
        } else {
            $this->_CacheDirectives = array_diff($this->_CacheDirectives, array($type));
        }
    }

    /**
     * Set/unset Content directive
     *
     * @param string $type Content directive (e.g. "type")
     * @param mixed $set 
     */
    function setContentDirective($type, $set) 
    {
        $type = strtolower($type);
        if (!in_array($type, $this->ContentDirectives)) {
            return $this->triggerError('P_E_0011', E_USER_NOTICE,  __METHOD__, 'Unknown option', "OptionName = `$type`");
        }
        $this->_ContentDirectives[$type] = $set;
    }



    /**
     * Set value of the ETag header
     * The string will be quoted automatically.
     *
     * @param string $value Value (string)
     */
    function setEtag($value) 
    {
        $this->eTag = '"' . $value . '"';
    }

    /**
     * Set value of the ETag header to a weak value
     * The string will be quoted automatically.
     *
     * @param string $value Value (string)
     */
    function setWeakEtag($value) 
    {
        $this->setEtag($value);
        $this->eTag = "W/" . $this->eTag;
    }

    /**
     * Set value of the Last-Modified header
     *
     * @param string $value Unix timestamp
     */
    function setLastModified($value) 
    {
        $this->lastModified = $value;
    }

    /**
     * Set value of the Last-Modified header using the last modification date of a file
     *
     * @param string $file File whose modification time will be used
     */
    function setLastModifiedFromFile($file) 
    {
        $finfo = stat($file);
        if (!$finfo) {
            return;
        }
        $this->setLastModified($finfo['mtime']);
    }

    /**
     * Format a date in RFC 1123 date format
     *
     * @param string $time Time since epoch in seconds
     * @return string Formatted date (e.g. "Sun, 27 Aug 2006 08:32:28 GMT")
     */
    static function formatDate($time) 
    {
        return gmdate("D, d M Y H:i:s", $time) . ' GMT';
    }


    /**
     * setCookie (RFC 2109 compatible) 
     *
     * @param string Name of the cookie
     * @param string Value of the cookie
     * @param int Lifetime of the cookie
     * @param string Path where the cookie can be used
     * @param string Domain which can read the cookie
     * @param bool Secure mode?
     * @param bool Only allow HTTP usage?
     * @return bool True or false whether the method has successfully run
     */
    function addCookie($name, $value='', $maxage=0, $path='', $domain='', $secure=false, $HTTPOnly=false)
    {
        $ob = ini_get('output_buffering');

        // Abort the method if headers have already been sent, except when output buffering has been enabled
        if ( headers_sent() && (bool) $ob === false || strtolower($ob) == 'off' )
            return false;

        if ( !empty($domain) )
        {
            // Fix the domain to accept domains with and without 'www.'.
            if ( strtolower( substr($domain, 0, 4) ) == 'www.' ) $domain = substr($domain, 4);
            // Add the dot prefix to ensure compatibility with subdomains
            if ( substr($domain, 0, 1) != '.' ) $domain = '.'.$domain;

            // Remove port information.
            $port = strpos($domain, ':');

            if ( $port !== false ) $domain = substr($domain, 0, $port);
        }

        $this->cookies[$name] = rawurlencode($name).'='.rawurlencode($value)
        .(empty($domain) ? '' : '; Domain='.$domain)
        .(empty($maxage) ? '' : '; Max-Age='.$maxage)
        .(empty($path) ? '' : '; Path='.$path)
        .(!$secure ? '' : '; Secure')
        .(!$HTTPOnly ? '' : '; HttpOnly');

        return true;
    }

}



