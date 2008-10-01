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
require_once 'Pxxo/Buffer.php';

/**
 * Classe permet de gérer l'accès à des flux de données comme du HTML
 *
 * @package    Pxxo
 * @copyright  Copyright (c) 2008 Nicolas Thouvenin 
 * @license    http://opensource.org/licenses/bsd-license.php
 */
class Pxxo_Buffer_HTML extends Pxxo_Buffer
{
    /**
     * @var     string type du contenu du flux
     */
    protected $type = 'html';
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
    * Filtre permettant de minimiser du Code HTML
    *
    * @param   string chaine de caractère
    * @return  string chaine de caractère compressée
    */
    public function compress($source) 
    {
        // Pull out the script blocks
        preg_match_all("!<script[^>]+>.*?</script>!is", $source, $match);
        $_script_blocks = $match[0];
        $source = preg_replace("!<script[^>]+>.*?</script>!is",
            '@@@SMARTY:TRIM:SCRIPT@@@', $source);

        // Pull out the pre blocks
        preg_match_all("!<pre>.*?</pre>!is", $source, $match);
        $_pre_blocks = $match[0];
        $source = preg_replace("!<pre>.*?</pre>!is",
            '@@@SMARTY:TRIM:PRE@@@', $source);

        // remove all leading spaces, tabs and carriage returns NOT
        // preceeded by a php close tag.
        $source = preg_replace('/((?<!\?>)\n)[\s]+/m', '\1', $source);

        // replace script blocks
        foreach($_script_blocks as $curr_block) {
            $source = preg_replace("!@@@SMARTY:TRIM:SCRIPT@@@!",$curr_block,$source,1);
        }
        // replace pre blocks
        foreach($_pre_blocks as $curr_block) {
            $source = preg_replace("!@@@SMARTY:TRIM:PRE@@@!",$curr_block,$source,1);
        }

        return trim($source); 
    }
}



