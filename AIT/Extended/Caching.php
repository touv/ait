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
 * Classe permettant de faire de mettre en cache les résultats 
 *
 * @category  AIT
 * @package   AIT
 * @author    Nicolas Thouvenin <nthouvenin@gmail.com>
 * @copyright 2009 Nicolas Thouvenin
 * @license   http://opensource.org/licenses/lgpl-license.php LGPL
 * @link      http://ait.touv.fr/
 */
class AIT_Extended_Caching extends AIT_Extended
{
    // {{{ __construct
    /**
     * Constructeur
     *
     */
    function __construct()
    {
        parent::__construct(
            array(
                'callbacks' => array(
                    'ItemType' => array(
                        'getItemsCache'    => array($this, 'cacher'),
                        'fetchItemsCache'  => array($this, 'cacher'),
                        'searchItemsCache' => array($this, 'cacher'),
                        'queryItemsCache'  => array($this, 'cacher'),
                        'getTagTypesCache' => array($this, 'cacher'),
                    ),
                    'Item' => array(
                        'fetchTagsCache'       => array($this, 'cacher'),
                        'getItemType'          => array($this, 'cacher'),
                    ),
                    'TagType' => array(
                        'getTagsCache'       => array($this, 'cacher'),
                    ),
                    'Tag' => array(
                        'getTagTypeCache'       => array($this, 'cacher'),
                        'fetchRelatedTagsCache' => array($this, 'cacher'),
                        'getItems'              => array($this, 'cacher'),
                    ),
                )
            )
        );
    }
    // }}}

    // {{{ cacher
    /**
     * fetch or store ?
     *
     * @param $id string  clé de cache
     * @param $data string données à stocker
     * @return mixed
     */
    function cacher($id, $data = null)
    {
        if (is_null($data)) 
            return $this->fetch($id);
        else 
            return $this->store($id, $data);
    }

    // }}}

    // {{{ fetch
    /**
     * fetch 
     * @param $id string  clé de cache
     * @return mixed
     */
    function fetch($id)
    {
        return false;
    }
    // }}}

    // {{{ store
    /**
     * store
     *
     * @param $id string  clé de cache
     * @param $data string données à stocker
     * @return mixed
     */
    function store($id, $data)
    {
        return false;
    }
    // }}}

    // {{{ encode
    /**
     * encode
     *
     * @param $data string données à stocker
     * @return string
     */
    function encode($data)
    {
        return gzcompress(serialize($data));
    }
    // }}}

    // {{{ decode
    /**
     * decode
     *
     * @param $data string données à stocker
     * @return string
     */
    function decode($data)
    {
        return unserialize(gzuncompress($data));
    }
    // }}}

}







