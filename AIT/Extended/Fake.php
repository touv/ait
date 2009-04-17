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
require_once 'Text/Normalize.php';


/**
 * Classe permettant d'illustrer les capacité d'un plugin
 *
 * @category  AIT
 * @package   AIT
 * @author    Nicolas Thouvenin <nthouvenin@gmail.com>
 * @copyright 2009 Nicolas Thouvenin
 * @license   http://opensource.org/licenses/lgpl-license.php LGPL
 * @link      http://ait.touv.fr/
 */
class AIT_Extended_Fake extends AIT_Extended
{
    // {{{ __construct
    /**
     * Constructeur
     *
     */
    function __construct()
    {
        parent::__construct(array(
            'callbacks' => array(
                'ItemType'   => array(
                    'maMethod' => array($this, 'maMethod'),
                ),
                'Item'       =>  array(
                    'maMethod' => array($this, 'maMethod'),
                ),
                'TagType'    =>  array(
                    'maMethod' => array($this, 'maMethod'),
                ),
                'Tag'        =>  array(
                    'maMethod' => array($this, 'maMethod'),
                ),
            ),
        ));
    }
    // }}}

    /**
    * Exemple de méthode
    * @param AIT $o
    * @param integer $p
    *
    * @return string
    */
    function maMethod($o, $p)
    {
        return str_replace('AIT_', '', get_class($o)).' / '.$p;
    }

}




