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
 * Cette classe permet de manipuler un TAG
 *
 * @category  AIT
 * @package   AIT
 * @author    Nicolas Thouvenin <nthouvenin@gmail.com>
 * @copyright 2008 Nicolas Thouvenin
 * @license   http://opensource.org/licenses/lgpl-license.php LGPL
 * @link      http://www.pxxo.net/fr/ait
 */
class AIT_Tag extends AIT
{
    protected $_item_id;
    // {{{ __construct
    /**
     * Le constructeur de la classe.
     *
     * @param string $l Label du TAG
     * @param integer $t Identifiant système du TYPE de TAG associé.
     * @param integer $i Identifiant système de l'ITEM (si connu).
     * @param PDOAIT $pdo Instance de base AIT que l'on souhaite utiliser.
     * @param integer $id Identifiant système de l'élement (si déjà connu).
     */
    function __construct($l, $t, $i, PDOAIT $pdo, $id = false)
    {
        parent::__construct($pdo, 'Tag');

        if (!is_string($l))
            trigger_error('Argument 1 passed to '.__METHOD__.' must be a string, '.gettype($l).' given', E_USER_ERROR);
        if (!is_null($t) && !is_int($t))
            trigger_error('Argument 2 passed to '.__METHOD__.' must be a integer, '.gettype($t).' given', E_USER_ERROR);
        if (!is_null($i) && !is_int($i))
            trigger_error('Argument 3 passed to '.__METHOD__.' must be a integer, '.gettype($i).' given', E_USER_ERROR);
        if ($id !== false && !is_int($id))
            trigger_error('Argument 4 passed to '.__METHOD__.' must be a integer, '.gettype($id).' given', E_USER_ERROR);

        $this->_label    = $l;
        $this->_type     = $t;
        $this->_item_id  = $i;

        if (!is_null($this->_item_id) && $id === false ) {
            try {
                $sql = sprintf(
                    "SELECT item_id FROM %s WHERE tag_id=? LIMIT 0,1",
                    $this->_pdo->tagged(),
                    $this->_pdo->tag()
                );
                $this->debug($sql, $t);
                $stmt = $this->_pdo->prepare($sql);
                $stmt->bindParam(1, $t, PDO::PARAM_INT);
                $stmt->execute();
                $it = (int)$stmt->fetchColumn(0);
                $stmt->closeCursor();
            }
            catch (PDOException $e) {
                self::catchError($e);
            }
            if (! $this->_checkTag($this->_type, 2)) {
                trigger_error('Argument 2 passed to '.__METHOD__.' not describe a "tag"', E_USER_NOTICE);
            }
            if (! $this->_checkTag($this->_item_id, $it)) {
                trigger_error('Argument 3 passed to '.__METHOD__.' not describe a "item" joined with "tag"', E_USER_NOTICE);
            }
            $this->_id = $this->_addTag($this->_label, $this->_type);
            $this->_addTagged($this->_id, $this->_item_id);
        }
        else {
            if ($id === false) {
                $this->_id = $this->_addTag($this->_label, $this->_type);
            }
            else {
                $this->_id = (int) $id;
            }
        }
    }
    // }}}

    // {{{ detach()
    /**
     * Supprime l'association entre le tag son item
     *
     * @return AIT_Tag Retourne l'objet en lui-même. Ce qui permet d'enchainer les méthodes.
     */
    function detach()
    {
        $sql = sprintf(
            "DELETE FROM %s WHERE tag_id=? AND item_id=?",
            $this->_pdo->tagged()
        );
        $stmt = $this->_pdo->prepare($sql);
        $stmt->bindParam(1, $this->_id, PDO::PARAM_INT);
        $stmt->bindParam(2, $this->_item_id, PDO::PARAM_INT);
        $stmt->execute();
        settype($this->_id, 'integer');
        settype($this->_item_id, 'integer');
        $this->_item_id = null;

        return $this;
    }
    // }}}

    // {{{ attach()
    /**
     * Ajoute une association entre le tag et un item
     *
     * @param AIT_Item $o Un objet contenant un item.
     *
     * @return AIT_Tag Retourne l'objet en lui-même. Ce qui permet d'enchainer les méthodes.
     */
    function attach(AIT_Item $o)
    {
        $this->_item_id = $o->getSystemID();
        $this->_addTagged($this->_id, $this->_item_id);

        return $this;
    }
    // }}}

    // {{{ getRelatedTags
    /**
     * Récupére tout les tags associé au même item que le tag courant
     *
     * @param integer $offset décalage à parir du premier enregistrement
     * @param integer $lines nombre de lignes à retourner
     * @param integer $ordering flag permettant le tri.
     *
     * @return ArrayObject
     */
    function getRelatedTags($offset = null, $lines = null, $ordering = null)
    {
        return $this->fetchRelatedTags(new ArrayObject(), $offset, $lines, $ordering);
    }
    // }}}

    // {{{ fetchRelatedTags
    /**
    * Récupére les tags associé au même item que le tag courant
    * mais en filtrant sur une certain nombre de type de tag
    *
    * @param ArrayObject $tags Tableau de type de tag
    * @param integer $offset décalage à parir du premier enregistrement
    * @param integer $lines nombre de lignes à retourner
    * @param integer $ordering flag permettant le tri
    *
    * @return	ArrayObject
    */
    function fetchRelatedTags(ArrayObject $tags, $offset = null, $lines = null, $ordering = null)
    {
        if (!is_null($offset) && !is_int($offset))
            trigger_error('Argument 2 passed to '.__METHOD__.' must be a integer, '.gettype($offset).' given', E_USER_ERROR);
        if (!is_null($lines) && !is_int($lines))
            trigger_error('Argument 3 passed to '.__METHOD__.' must be a integer, '.gettype($lines).' given', E_USER_ERROR);
        if (!is_null($ordering) && !is_int($ordering))
            trigger_error('Argument 4 passed to '.__METHOD__.' must be a integer, '.gettype($ordering).' given', E_USER_ERROR);

        $n = 0;
        $w  = '';
        if ($tags->count() != 0) {
            foreach($tags as $tag) {
                if (! $tag instanceof AIT_TagType) {
                    trigger_error('Line 3 of Argument 1 passed to '.__METHOD__.' must be a instance of AIT_Tag, '.gettype($tag).' given and ignored', E_USER_NOTICE);
                    continue;
                }
                if (!empty($w)) $w .= ' OR ';
                $w = 'type = '. $tag->getSystemID();
                $n++;
            }
            if ($n === 0) return new ArrayObject(array());
            else $w = ' AND ('.$w.')';
        }
        $sql = sprintf("
            SELECT DISTINCT id, label, type
            FROM %s a
            LEFT JOIN %s b ON a.item_id=b.item_id
            LEFT JOIN %s c ON b.tag_id=c.id
            WHERE a.tag_id = ? $w
            ",
            $this->_pdo->tagged(),
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
            if ($row['id'] == $this->_id) continue;
            settype($row['type'], 'integer');
            settype($row['id'], 'integer');
            $ret[] = new AIT_Tag($row['label'], $row['type'], $this->_item_id, $this->_pdo, $row['id']);
        }
        return new ArrayObject($ret);

    }
    // }}}

    // {{{ getFrequency
    /**
     * Donne la frequence d'utilisation du tag courrant
     *
     * @return	integer
     */
    function getFrequency()
    {
        try {
            $sql = sprintf("
                SELECT count(*) n
                FROM %s
                WHERE tag_id = ?
                LIMIT 0,1
                ",
                $this->_pdo->tagged()
            );
            $this->debug($sql, $this->_id);
            $stmt = $this->_pdo->prepare($sql);
            $stmt->bindParam(1, $this->_id, PDO::PARAM_INT);
            $stmt->execute();
            settype($this->_id, 'integer');
            $c = (int)$stmt->fetchColumn(0);
            $stmt->closeCursor();
            return $c;
        }
        catch (PDOException $e) {
            self::catchError($e);
        }
    }
    // }}}

}





