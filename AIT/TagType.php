<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 encoding=utf-8 fdm=marker :
// {{{ Licence
// +--------------------------------------------------------------------------+
// | AIT - All is Tag                                                         |
// +--------------------------------------------------------------------------+
// | Copyright (C) 2008 Nicolas Thouvenin                                     |
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
 * @copyright 2008 Nicolas Thouvenin
 * @license   http://opensource.org/licenses/lgpl-license.php LGPL
 * @version   SVN: $Id$
 * @link      http://www.pxxo.net/
 */

/**
 * Dépendances
 */
require_once 'AIT.php';


/**
 * Représente un TYPE de TAG
 *
 * @category  AIT
 * @package   AIT
 * @author    Nicolas Thouvenin <nthouvenin@gmail.com>
 * @copyright 2008 Nicolas Thouvenin
 * @license   http://opensource.org/licenses/lgpl-license.php LGPL
 * @link      http://www.pxxo.net/fr/ait
 */
class AIT_TagType extends AIT
{
    protected $_item_id;
    // {{{ __construct
    /**
     * Constructeur
     *
     * @param string $l nom du nouveau type de tag  (label)
     * @param integer $i identifiant du type d'item
     * @param PDO $pdo objet de connexion à la base
     * @param integer $id identifiant physique de l'élement (si déjà connu)
     */
    function __construct($l, $i, PDO $pdo, $id = false)
    {
        parent::__construct($pdo, 'TagType');

        if (!is_string($l) and !is_null($l) and $id !== false)
            trigger_error('Argument 1 passed to '.__METHOD__.' must be a string, '.gettype($l).' given', E_USER_ERROR);
        if (!is_null($i) && !is_int($i))
            trigger_error('Argument 2 passed to '.__METHOD__.' must be a integer, '.gettype($i).' given', E_USER_ERROR);
        if ($id !== false && !is_int($id))
            trigger_error('Argument 4 passed to '.__METHOD__.' must be a Integer, '.gettype($id).' given', E_USER_ERROR);

        $this->_label   = $l;
        $this->_type    = 2;
        $this->_item_id = $i;

        if ($id === false) {
            if (! $this->_checkTag($this->_item_id, 1)) {
                trigger_error('Argument 2 passed to '.__METHOD__.' not describe a "tagtype"', E_USER_ERROR);
            }
            $this->_id = $this->_addTag($this->_label, 2);
            $this->_addTagged($this->_id, $this->_item_id);
        }
        else {
            $this->_id = (int) $id;
            if (is_null($this->_label)) {
                $r = $this->_getTagBySystemID($id);
                $this->_label = $r['label'];
            }
        }
    }
    // }}}

   // {{{ newTag
    /**
     * Crée un tag, son label étant calculé automatiquement
     *
     * @return AIT_Tag
     */
    function newTag()
    {
        $o = new AIT_Tag('NEW', $this->_id, null, $this->_pdo);
        $o->ren('#'.$o->getSystemID());
        return $o;
    }
    // }}}

    // {{{ addTag
    /**
     * Ajout d'un tag au type de tag courant
     *
     * @param string $l nom du nouveau item
     *
     * @return AIT_Item
     */
    function addTag($l)
    {
        if (!is_string($l))
            trigger_error('Argument 1 passed to '.__METHOD__.' must be a string, '.gettype($l).' given', E_USER_ERROR);

        return new AIT_Tag($l, $this->_id, null, $this->_pdo);
    }
    // }}}

    // {{{ getTag
    /**
    * Récupère un tag
     *
     * @param string $l nom du tag
     *
     * @return AIT_Item
     */
    function getTag($l)
    {
        if (!is_string($l))
            trigger_error('Argument 1 passed to '.__METHOD__.' must be a string, '.gettype($l).' given', E_USER_ERROR);


        $sql = sprintf("
            SELECT id
            FROM %s
            WHERE label = ? AND type = ?
            LIMIT 0,1
            ",
            $this->_pdo->tag()
        );
        $this->debug($sql, $l, $this->_id);
        $stmt = $this->_pdo->prepare($sql);
        $stmt->bindParam(1, $l, PDO::PARAM_STR);
        $stmt->bindParam(2, $this->_id, PDO::PARAM_INT);
        $stmt->execute();
        settype($this->_id, 'integer');
        $id = (int)$stmt->fetchColumn(0);
        $stmt->closeCursor();

        if ($id > 0) {
            return new AIT_Tag($l, $this->_id, null, $this->_pdo, $id);
        }
    }
    // }}}

    // {{{ getTags
    /**
     * Récupére tous les tags du type de tag courant
     *
     * @param integer $offset décalage à parir du premier enregistrement
     * @param integer $lines nombre de lignes à retourner
     * @param integer $ordering flag permettant le tri
     *
     * @return AITResult
     */
    function getTags($offset = null, $lines = null, $ordering = null)
    {
        if (!is_null($offset) && !is_int($offset))
            trigger_error('Argument 1 passed to '.__METHOD__.' must be a integer, '.gettype($offset).' given', E_USER_ERROR);
        if (!is_null($lines) && !is_int($lines))
            trigger_error('Argument 2 passed to '.__METHOD__.' must be a integer, '.gettype($offset).' given', E_USER_ERROR);
        if (!is_null($ordering) && !is_int($ordering))
            trigger_error('Argument 3 passed to '.__METHOD__.' must be a integer, '.gettype($ordering).' given', E_USER_ERROR);

        $sql = sprintf("
            SELECT DISTINCT SQL_CALC_FOUND_ROWS id, label
            FROM %s
            WHERE type = ?
            ",
            $this->_pdo->tag()
        );
        $this->sqler($sql, $offset, $lines, $ordering);
        $this->debug($sql, $this->_id);
        $stmt = $this->_pdo->prepare($sql);
        $stmt->bindParam(1, $this->_id, PDO::PARAM_INT);
        $stmt->execute();
        settype($this->_id, 'integer');
        $ret = array();
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
            settype($row['id'], 'integer');
            $ret[] = new AIT_Tag($row['label'], $this->_id, null, $this->_pdo, $row['id']);
        }

        $r = new AITResult($ret);
        $r->setTotal($this->getFoundRows());
        return $r;
    }
    // }}}


    // {{{ getTagBySystemID
    /**
    * Récupère un tag
     *
     * @param intger $i
     *
     * @return AIT_Item
     */
     function getTagBySystemID($i)
    {
        if (!is_integer($i))
            trigger_error('Argument 1 passed to '.__METHOD__.' must be a integer, '.gettype($i).' given', E_USER_ERROR);


        $row = $this->_getTagBySystemID($i);

        if (is_array($row)) {
            return new AIT_Tag($row['label'], $row['type'], null, $this->_pdo, $i);
        }
    }
    // }}}

}





