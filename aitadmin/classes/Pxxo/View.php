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
require_once 'Pxxo/Buffer.php';

/**
 * Moteur de template pour les différentes "VUE" d'un composants
 *
 * @package    Pxxo
 * @copyright  Copyright (c) 2008 Nicolas Thouvenin
 * @license    http://opensource.org/licenses/bsd-license.php
 */
class Pxxo_View 
{
    /**
     * @var Pxxo_Buffer Input buffer
     */
    private $_input;

    /**
     * @var Pxxo_Buffer Output buffer
     */
    private $_output;

    /**
     * @var Pxxo_Widget
     */
    public $Self;

    /**
     * Pour utiliser les méthodes de l'objet Pxxo
     * sans hérité de l'objet. Ainsi Cette objet est "presque" vierge de toute variable
     * @var Pxxo
     */
    private $_pxxo;

    /**
     * Constructor.
     * @param Pxxo_widget
     */
    public function __construct(Pxxo $widget)
    {
        $this->_pxxo = new Pxxo;
        $this->Self = $widget;
    }

    /**
     * Processes a view script and returns the output.
     *
     * @param string $name The script script name to process.
     * @param string $type The type of the output buffer
     * @return Pxxo_Buffer
     */
    public function render($name, $type = '')
    {
        if ($type === '') {
            if (preg_match(',\.php\.(.+)$,', $name, $m)) {
                $type = $m[1];
            } else {
                return $this->triggerError('P_E_0015', E_USER_NOTICE,  __METHOD__, 'Invalid output type', "Type = `$type` Render = `$name`");
            }
        }
        $this->_input  = Pxxo_Buffer::factory($type, $name);
        $this->_output = Pxxo_Buffer::factory($type, $this->_render());
        return $this->_output;
    }

    /**
     * On récupére le flux de sortie
     *
     * @return	Pxxo_Buffer
     */
    public function getOutputBuffer()
    {
        return $this->_output;
    }

    /**
     * On choisit un flux d'entrée
     *
     * @param Pxxo_Buffer
     * @access   public
     */
    public function setInputBuffer($o)
    {
        $this->_input = $o;
    }

    /**
     * Transformation Du buffer d'entrée en buffer de Sortie
     *
     * @param string $type The type of the output buffer
     */
    public function transform($type)
    {
        $this->_input->copyto($this->_pxxo);

        if ($this->Self->testCacheLevel(P_C_TEMPLATE)) {

            $this->_pxxo->resetCacheID();
            $this->_pxxo->addCacheID($type);
            $this->_pxxo->addCacheID($this->_input->getCacheID());

            $out = $this->_pxxo->getinCache($this->_pxxo->getCacheID(), P_C_TEMPLATE);
        }
        else $out = false;

        if ($out === false) {
            $this->_pxxo->traceDebug("VIEW\t[executed]\tinput(uri:".$this->_input->uri.")\toutput(type:".$type.')');
            $out = $this->_render();
            if (!empty($out) and $this->Self->testCacheLevel(P_C_TEMPLATE)) $this->_pxxo->setinCache($out, $this->_pxxo->getCacheID(), P_C_TEMPLATE);
        }
        else {
            $this->_pxxo->traceDebug("VIEW\t[cached]\tinput(uri:".$this->_input->uri.")\toutput(type:".$type.')');
        }
        $this->_output = Pxxo_Buffer::factory($type, $out);
        $this->_pxxo->copyto($this->_output);
    }

    /**
     * Génération ...
     *
     * @return string
     */
    protected function _render()
    {
        ob_start();
        extract($this->getVars());
        if ($this->_input->scheme == 'file')
            include($this->_input->uri);
        else {
            eval('?'.'>'.$this->_input->get());
        }
        return ob_get_clean();
    }

    /**
     * Effectation d'une variable
     *
     * @param string $key The variable name.
     * @param mixed $val The variable value.
     * @return void
     */
    public function __set($key, $val)
    {
        if (strpos($key, '_')  === 0 and 
            $key !== '_input' and 
            $key !== '_output' and
            $key !== '_pxxo'
        ) {
            return $this->triggerError('P_E_0021', E_USER_ERROR,  __METHOD__, 'Bad name', "Name = `$key` Value = `$val`");
        }
        if ($val instanceof Pxxo_Widget) {
            $obj = $this->Self->stackWidget($val);
            if ($obj) $this->$key = $obj->get();
        }
        else {
            if ($this->Self->testCacheLevel(P_C_TEMPLATE)) {
                $this->_pxxo->addCacheID($val);
            }
            $this->$key = $val;
        }
    }

    /**
     * Allows testing with empty() and isset() to work inside
     * templates.
     *
     * @param  string $key
     * @return boolean
     */
    public function __isset($key)
    {
        if ('_' != substr($key, 0, 1)) {
            return isset($this->$key);
        }

        return false;
    }

    /**
     * Allows unset() on object properties to work
     *
     * @param string $key
     * @return void
     */
    public function __unset($key)
    {
        if ('_' != substr($key, 0, 1) && isset($this->$key)) {
            unset($this->$key);
        }
    }
    /**
     * Assigns variables to the view script 
     *
     * @param  string|array 
     * @param  mixed (Optional) If assigning a named variable, use this
     * as the value.
     */
    public function assign($spec, $value = null)
    {
        // which strategy to use?
        if (is_string($spec)) {
            // assign by name and value
            if ('_' == substr($spec, 0, 1)) {
                return $this->triggerError('P_E_0021', E_USER_ERROR,  __METHOD__, 'Bad name', "Name = `$key` Value = `$val`");
            }
            $this->$spec = $value;
        } elseif (is_array($spec)) {
            // assign from associative array
            $error = false;
            foreach ($spec as $key => $val) {
                if ('_' == substr($key, 0, 1)) {
                    $error = true;
                    break;
                }
                $this->$key = $val;
            }
            if ($error) {
                return $this->triggerError('P_E_0021', E_USER_ERROR,  __METHOD__, 'Bad name', "Name = `$key` Value = `$val`");
            }
        } else {
            return $this->triggerError('P_E_0017', E_USER_ERROR,  __METHOD__, 'Invalid parameter');
        }

        return $this;
    }

    /**
     * Return list of all assigned variables
     *
     * @return array
     */
    public function getVars()
    {
        $vars   = get_object_vars($this);
        unset($vars['_input']);
        unset($vars['_output']);
        unset($vars['_pxxo']);
        return $vars;
    }

    /**
     * Clear all assigned variables
     *
     * @return void
     */
    public function clearVars()
    {
        $vars   = get_object_vars($this);
        foreach ($vars as $key => $value) {
            if ('_' != substr($key, 0, 1)) {
                unset($this->$key);
            }
        }
    }
    // {{{ getCacheID
    /**
    * Retroune l'identifiant de cache courant
    *
    * @return   string
    */
    public function getCacheID()
    {
        return $this->_pxxo->getCacheID();
    }
    // }}}

}

