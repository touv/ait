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
require_once 'AIT/Item.php';
require_once 'AIT/TagType.php';


/**
 * Représente un TYPE d'ITEM
 *
 * @category  AIT
 * @package   AIT
 * @author    Nicolas Thouvenin <nthouvenin@gmail.com>
 * @copyright 2008 Nicolas Thouvenin
 * @license   http://opensource.org/licenses/lgpl-license.php LGPL
 * @link      http://www.pxxo.net/fr/ait
 */
class AIT_ItemType extends AIT
{
    // {{{ __construct
    /**
     * Constructeur
     *
     * @param string $label nom du tupe d'item
     * @param PDOAIT $pdo objet de connexion à la base
     */
    function __construct($l, PDOAIT $pdo)
    {
        parent::__construct($pdo, 'ItemType');

        if (!is_string($l))
            trigger_error('Argument 1 passed to '.__METHOD__.' must be a string, '.gettype($l).' given', E_USER_ERROR);

        $this->_label = $l;
        $this->_type  = 1;
        $this->_id    = $this->_addTag($this->_label, $this->_type);
    }
    // }}}

    // {{{ addTag
    /**
     * Ajout d'un type de tag au type d'item courant
     *
     * @param string $l label
     *
     * @return AIT_TagType
     */
    function addTag($l)
    {
        if (!is_string($l))
            trigger_error('Argument 1 passed to '.__METHOD__.' must be a String, '.gettype($l).' given', E_USER_ERROR);

        return new AIT_TagType($l, $this->_id, $this->_pdo);
    }

    // }}}

    // {{{ getTag
    /**
     * Récupére un type de tag du type d'item courant
     *
     * @param string $l label
     *
     * @todo NE PAS CREER D'OBJET SI L'ELEMENT N'EXISTE PAS
     *
     * @return AIT_TagType
     */
    function getTag($l)
    {
        if (!is_string($l))
            trigger_error('Argument 1 passed to '.__METHOD__.' must be a String, '.gettype($l).' given', E_USER_ERROR);

        return new AIT_TagType($l, $this->_id, $this->_pdo);
    }
    // }}}

    // {{{ newItem
    /**
     * Crée un item son label étant calculé automatiquement
     *
     * @return AIT_Item
     */
    function newItem()
    {
        $o = new AIT_Item('NEW', $this->_id, $this->_pdo);
        $o->ren('#'.$o->getSystemID());
        return $o;
    }
    // }}}

    // {{{ addItem
    /**
     * Ajout d'un item au type d'item courant
     *
     * @param string $l nom du nouveau item
     *
     * @return AIT_Item
     */
    function addItem($l)
    {
        if (!is_string($l))
            trigger_error('Argument 1 passed to '.__METHOD__.' must be a String, '.gettype($l).' given', E_USER_ERROR);

        return new AIT_Item($l, $this->_id, $this->_pdo);
    }
    // }}}

    // {{{ getItem
    /**
     * Récupère un item
     *
     * @param string $l nom de l'item
     *
     * @return AIT_Item
     */
    function getItem($l)
    {
        if (!is_string($l))
            trigger_error('Argument 1 passed to '.__METHOD__.' must be a String, '.gettype($l).' given', E_USER_ERROR);

        $sql = sprintf("
            SELECT id
            FROM %s
            WHERE label = ? AND type = ?
            LIMIT 0,1
        ", $this->_pdo->tag());
        $this->debug($sql, $l, $this->_id);
        $stmt = $this->_pdo->prepare($sql);
        $stmt->bindParam(1, $l, PDO::PARAM_STR);
        $stmt->bindParam(2, $this->_id, PDO::PARAM_INT);
        $stmt->execute();
        settype($this->_id, 'integer');
        $id = (int)$stmt->fetchColumn(0);
        $stmt->closeCursor();

        if ($id > 0) {
            return new AIT_Item($l, $this->_id, $this->_pdo, $id);
        }
    }
    // }}}

    // {{{ getItems
    /**
     * Récupére tous les items du type d'item courant
     *
     * @param integer $offset décalage à parir du premier enregistrement
     * @param integer $lines nombre de lignes à retourner
     * @param integer $ordering flag permettant le tri
     *
     * @return ArrayObject
     */
    function getItems($offset = null, $lines = null, $ordering = null)
    {
        if (!is_null($offset) && !is_int($offset))
            trigger_error('Argument 1 passed to '.__METHOD__.' must be a integer, '.gettype($offset).' given', E_USER_ERROR);
        if (!is_null($lines) && !is_int($lines))
            trigger_error('Argument 2 passed to '.__METHOD__.' must be a integer, '.gettype($offset).' given', E_USER_ERROR);
        if (!is_null($ordering) && !is_int($ordering))
            trigger_error('Argument 3 passed to '.__METHOD__.' must be a integer, '.gettype($ordering).' given', E_USER_ERROR);

        $sql = sprintf("
            SELECT id, label
            FROM %s
            WHERE type = ?
            ", $this->_pdo->tag());
        $this->sqler($sql, $offset, $lines, $ordering);
        $this->debug($sql, $this->_id);
        $stmt = $this->_pdo->prepare($sql);
        $stmt->bindParam(1, $this->_id, PDO::PARAM_INT);
        $stmt->execute();
        settype($this->_id, 'integer');
        $ret = array();
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
            settype($row['id'], 'integer');
            $ret[] = new AIT_Item($row['label'], $this->_id, $this->_pdo, $row['id']);
        }
        return new ArrayObject($ret);
    }
    // }}}

    // {{{ fetchItems
    /**
    * Récupére les items possédant un ou plusieurs tags donnée en paramètres
    *
    * @param ArrayObject $tags Tableau de tag
    * @param integer $offset décalage à parir du premier enregistrement
    * @param integer $lines nombre de lignes à retourner
    * @param integer $ordering flag permettant le tri
    *
    * @return	ArrayObject
    */
    function fetchItems(ArrayObject $tags, $offset = null, $lines = null, $ordering = null)
    {
        if (!is_null($offset) && !is_int($offset))
            trigger_error('Argument 2 passed to '.__METHOD__.' must be a integer, '.gettype($offset).' given', E_USER_ERROR);
        if (!is_null($lines) && !is_int($lines))
            trigger_error('Argument 3 passed to '.__METHOD__.' must be a integer, '.gettype($lines).' given', E_USER_ERROR);
        if (!is_null($ordering) && !is_int($ordering))
            trigger_error('Argument 4 passed to '.__METHOD__.' must be a integer, '.gettype($ordering).' given', E_USER_ERROR);

        $n = 0;
        $w  = '';
        if ($tags->count() == 0) return new ArrayObject(array());
        foreach($tags as $tag) {
            if (! $tag instanceof AIT_Tag) {
                trigger_error('Line 3 of Argument 1 passed to '.__METHOD__.' must be a instance of AIT_Tag, '.gettype($tag).' given and ignored', E_USER_NOTICE);
                continue;
            }
            if (!empty($w)) $w .= ' OR ';
            $w .= 'tag_id = '. $tag->getSystemID();
            $n++;
        }
        if ($n === 0) return new ArrayObject(array());
        $sql = sprintf("
            SELECT  id, label, type
            FROM (
            SELECT count(item_id) n, item_id
            FROM %s a
            WHERE %s
            GROUP BY item_id
            ) temp
            LEFT JOIN %s b ON temp.item_id = b.id
            WHERE n = ? AND type = ?
            ",
            $this->_pdo->tagged(),
            $w,
            $this->_pdo->tag()
        );
        $this->sqler($sql, $offset, $lines, $ordering);
        $this->debug($sql, $n, $this->_id);
        $stmt = $this->_pdo->prepare($sql);
        $stmt->bindParam(1, $n, PDO::PARAM_INT);
        $stmt->bindParam(2, $this->_id, PDO::PARAM_INT);
        $stmt->execute();
        settype($this->_id, 'integer');
        $ret = array();
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
            settype($row['type'], 'integer');
            settype($row['id'], 'integer');
            $ret[] = new AIT_Item($row['label'], $row['type'], $this->_pdo, $row['id']);
        }
        return new ArrayObject($ret);
    }
    // }}}

    // {{{ getTags
    /**
     * Récupére tout les type de tags de l'item courant
     *
     * @param integer $offset décalage à parir du premier enregistrement
     * @param integer $lines nombre de lignes à retourner
     * @param integer $ordering flag permettant le tri
     *
     * @return ArrayObject
     */
    function getTags($offset = null, $lines = null, $ordering = null)
    {
        if (!is_null($offset) && !is_int($offset))
            trigger_error('Argument 1 passed to '.__METHOD__.' must be a integer, '.gettype($offset).' given', E_USER_ERROR);
        if (!is_null($lines) && !is_int($lines))
            trigger_error('Argument 2 passed to '.__METHOD__.' must be a integer, '.gettype($lines).' given', E_USER_ERROR);
        if (!is_null($ordering) && !is_int($ordering))
            trigger_error('Argument 3 passed to '.__METHOD__.' must be a integer, '.gettype($ordering).' given', E_USER_ERROR);

        $sql = sprintf("
            SELECT DISTINCT id, label, type
            FROM %s a
            LEFT JOIN %s b ON a.tag_id=b.id
            WHERE item_id = ?
            ",
            $this->_pdo->tagged(),
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
            $ret[] = new AIT_TagType($row['label'], $this->_id, $this->_pdo, $row['id']);
        }
        return new ArrayObject($ret);
    }
    // }}}

     // {{{ searchItems
    /**
     * Recherche des items du type courant
     *
     * @param string  $query requete (le format dépend de la query_callback) sans callback c'est du SQL
     * @param integer $offset décalage à parir du premier enregistrement
     * @param integer $lines nombre de lignes à retourner
     * @param integer $ordering flag permettant le tri
     *
     * @return ArrayObject
     */
    function searchItems($query, $offset = null, $lines = null, $ordering = null)
    {
        if (!is_null($offset) && !is_int($offset))
            trigger_error('Argument 2 passed to '.__METHOD__.' must be a integer, '.gettype($offset).' given', E_USER_ERROR);
        if (!is_null($lines) && !is_int($lines))
            trigger_error('Argument 3 passed to '.__METHOD__.' must be a integer, '.gettype($lines).' given', E_USER_ERROR);
        if (!is_null($ordering) && !is_int($ordering))
            trigger_error('Argument 4 passed to '.__METHOD__.' must be a integer, '.gettype($ordering).' given', E_USER_ERROR);

        if (is_callable($this->_queryspace)) {
            $query = call_user_func($this->_queryspace, $query, $this);
        }
        if ($query !== '') $query = 'AND '.$query;
        $sql = sprintf('
            SELECT DISTINCT item.id, item.label
            FROM %1$s tag
            LEFT JOIN %2$s b ON tag.type=b.tag_id
            LEFT JOIN %2$s d ON tag.id=d.tag_id
            LEFT JOIN %1$s item ON d.item_id=item.id
            WHERE b.item_id = ? AND item.type = ? %3$s
            ',
            $this->_pdo->tag(),
            $this->_pdo->tagged(),
            $query
        );

        $this->sqler($sql, $offset, $lines, $ordering);
        $this->debug($sql, $this->_id, $this->_id);

        $stmt = $this->_pdo->prepare($sql);
        $stmt->bindParam(1, $this->_id, PDO::PARAM_INT);
        $stmt->bindParam(2, $this->_id, PDO::PARAM_INT);
        $stmt->execute();
        settype($this->_id, 'integer');
        $ret = array();
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
            settype($row['id'], 'integer');
            $ret[] = new AIT_Item($row['label'], $this->_id, $this->_pdo, $row['id']);
        }
        return new ArrayObject($ret);
    }
    // }}}

    // {{{ getItemBySystemID
    /**
    * Récupère un Item
    *
    * @param integer $i
    *
    * @return AIT_Item
     */
    function getItemBySystemID($i)
    {
        if (!is_integer($i))
            trigger_error('Argument 1 passed to '.__METHOD__.' must be a integer, '.gettype($i).' given', E_USER_ERROR);

        $row = $this->_getTagBySystemID($i);
        if (is_array($row)) {
            return new AIT_Item($row['label'], $this->_id, $this->_pdo, $row['id']);
        }
    }
    // }}}

}



