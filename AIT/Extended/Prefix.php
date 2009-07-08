<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 encoding=utf-8 fdm=marker :
// {{{ Licence
// +--------------------------------------------------------------------------+
// | AIT - All is Tag                                                         |
// +--------------------------------------------------------------------------+
// | Copyright (C) 2009 Nicolas Thouvenin                                     |
// +--------------------------------------------------------------------------+
// | This library is free software; you can redistribute it and/or            |
// | modify it under the terms of the GNU General Public License              |
// | as published by the Free Software Foundation; either  version 2          |
// | of the License, or (at your option) any later version.                   |
// |                                                                          |
// | This program is distributed in the hope that it will be useful,          |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of           |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU         |
// | General Public License for more details.                                 |
// |                                                                          |
// | You should have received a copy of the GNU General Public License        |
// | along with this library; if not, write to the Free Software              |
// | Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA  |
// +--------------------------------------------------------------------------+
// }}}

/**
 * @category  AIT
 * @package   AIT
 * @author    Nicolas Thouvenin <nthouvenin@gmail.com>
 * @copyright 2009 Nicolas Thouvenin
 * @license   http://opensource.org/licenses/lgpl-license.php LGPL
 * @version   SVN: $Id$
 * @link      http://ait.touv.fr/
 */

/**
 * Dépendances
 */
require_once 'AIT.php';
require_once 'AIT/Extended.php';


/**
 * Classe permettant d'ajouter automatiquement les prefix au nom d'un tag
 *
 * @category  AIT
 * @package   AIT
 * @author    Nicolas Thouvenin <nthouvenin@gmail.com>
 * @copyright 2009 Nicolas Thouvenin
 * @license   http://opensource.org/licenses/lgpl-license.php LGPL
 * @link      http://ait.touv.fr/
 */
class AIT_Extended_Prefix extends AIT_Extended
{
    private $callback;

    function __construct($callback = null)
    {
        if (!is_null($callback) and !is_callable($callback))
            die('It\'s not a valid Callback');
        $this->callback = $callback;

        parent::__construct(array(
            'callbacks' => array(
                'Tag'        =>  array(
                    'getHook' => array($this, 'getHook'),
                    'setHook' => array($this, 'setHook'),
                ),
            ),
        ));
    }

    function getHook($n, $v, $o)
    {
        static $c = false; // evite de rééentré dans le hook

        if ($c === true) return $v;
        else $c = true;

        if (!$this->_check($o)) {
            $c = false;
            return $v;
        }
        $prefix = $o->get('prefix');

        if (!is_null($prefix) and $prefix !== '')
            $ret = str_replace($prefix, '', $v);
        else 
            $ret = $v;

        $c = false;
        return $ret;
    }

    function setHook($n, $v, $o)
    {
        if ($n === 'prefix' and !is_null($v) and $v !== '' and $this->_check($o)) {
            $a = $v.$o->get();
            $o->ren($a);
        }
    }

    private function _check($o)
    {
        if (is_null($this->callback)) return true;
        return call_user_func($this->callback, $o);
    }

}




