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
     * @param array $row Propriétés de l'élément (si déja connu)
     */
    function __construct($l, $t, $i, PDOAIT $pdo, $id = false, $row = false)
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
        if ($row !== false && !is_array($row))
            trigger_error('Argument 5 passed to '.__METHOD__.' must be a Array, '.gettype($row).' given', E_USER_ERROR);

        $this->_label    = $l;
        $this->_type     = $t;
        $this->_item_id  = $i;

        if (!is_null($this->_item_id) && $id === false ) {
            try {
                $sql = sprintf(
                    "SELECT type FROM %s WHERE id=? LIMIT 0,1",
                    $this->_pdo->tag()
                );
                self::timer();
                $stmt = $this->_pdo->prepare($sql);
                $stmt->bindParam(1,  $i, PDO::PARAM_INT);
                $stmt->execute();
                $it = (int)$stmt->fetchColumn(0);
                $stmt->closeCursor();
                self::debug(self::timer(true), $sql, $i);

                if (! $this->_checkTag($this->_type, 2)) {
                    trigger_error('Argument 2 passed to '.__METHOD__.' not describe a "tag"', E_USER_ERROR);
                }
                if (! $this->_checkTagged($t, $it)) {
                    trigger_error('Argument 3 passed to '.__METHOD__.' not describe a "item" joined with "tag"', E_USER_ERROR);
                }
                if (! $this->_checkType($t)) {
                    trigger_error('Argument 2 passed to '.__METHOD__.' not describe a "type" that doesn\' exist', E_USER_ERROR);
                }
                $this->_id = $this->_addTag($this->_label, $this->_type);
                if ($this->_checkTagged($this->_id, $this->_item_id) === false) {
                    $this->_addTagged($this->_id, $this->_item_id);
                    $this->_increaseFrequency($this->_id);
                    $this->_increaseFrequency($this->_type);
                }
            }
            catch (PDOException $e) {
                self::catchError($e);
            }

        }
        else {
            if ($id === false) {
                if (! $this->_checkType($t)) {
                    trigger_error('Argument 2 passed to '.__METHOD__.' not describe a "type" that doesn\' exist', E_USER_ERROR);
                }
                $this->_id = $this->_addTag($this->_label, $this->_type);
            }
            else {
                if ($row !== false) $this->_fill($row);
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
        try {
            $sql = sprintf(
                "DELETE FROM %s WHERE tag_id=? AND item_id=?",
                $this->_pdo->tagged(true)
            );
            self::timer();
            $stmt = $this->_pdo->prepare($sql);
            $stmt->bindParam(1, $this->_id, PDO::PARAM_INT);
            $stmt->bindParam(2, $this->_item_id, PDO::PARAM_INT);
            $stmt->execute();
            settype($this->_id, 'integer');
            $stmt->closeCursor();
            self::debug(self::timer(true), $sql, $this->_id);

            $this->_item_id = null;

            $this->_decreaseFrequency($this->_id);
            $this->_decreaseFrequency($this->_type);

            if ($this->countItems() <= 0) $this->del();

            return $this;
        }
        catch (PDOException $e) {
            self::catchError($e);
        }
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
        if ($this->_checkTagged($this->_id, $this->_item_id) === false) {
            $this->_addTagged($this->_id, $this->_item_id);
            $this->_increaseFrequency($this->_id);
            $this->_increaseFrequency($this->_type);
        }
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
     * @return AITResult
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
    * @return AITResult
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
            if ($n === 0) return new AITResult(array());
            else $w = ' AND ('.$w.')';
        }
        $sql1 = 'SELECT DISTINCT id, label, space, score, frequency, type ';
        $sql2 = sprintf("
            FROM %s a
            LEFT JOIN %s b ON a.item_id=b.item_id
            LEFT JOIN %s c ON b.tag_id=c.id
            WHERE a.tag_id = ? $w
            ",
            $this->_pdo->tagged(),
            $this->_pdo->tagged(),
            $this->_pdo->tag()
        );
        $sql = $sql1.$sql2;
        self::sqler($sql, $offset, $lines, $ordering);
        self::timer();
        $stmt = $this->_pdo->prepare($sql);
        $stmt->bindParam(1, $this->_id, PDO::PARAM_INT);
        $stmt->execute();
        settype($this->_id, 'integer');
        $ret = array();
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
            if ($row['id'] == $this->_id) continue;
            settype($row['type'], 'integer');
            settype($row['id'], 'integer');
            $ret[] = new AIT_Tag($row['label'], $row['type'], $this->_item_id, $this->_pdo, $row['id'], $row);
        }
        $stmt->closeCursor();
        self::debug(self::timer(true), $sql, $this->_id);

        $sql = 'SELECT COUNT(DISTINCT id) '.$sql2;
        $r = new AITResult($ret);
        $r->setQueryForTotal($sql, array($this->_id => PDO::PARAM_INT,), $this->_pdo); 
        return $r;
    }
    // }}}

    // {{{ countItems
    /**
     * Compte le nombre de tags du type d'item courant
     *
     * @return integer
     */
    function countItems()
    {
        return (int) $this->_get('frequency', true);
    }
    // }}}

    // {{{ del
    /**
    * Suppression de l'élement courrant
    */
    public function del($cascade = false)
    {
        if ($cascade) {
            // Le mode cascade devrait également supprimer les items attachés au TAG
        }
        $this->_rmTagged($this->_id, null);
        $this->_rmTag($this->_id);
    }
    // }}}

    // {{{ getTagType
    /**
    * Retourne le type de tag associé
    */
    public function getTagType()
    {
        $row = $this->_getTagBySystemID($this->_type);

        if (is_array($row)) {
            return new AIT_TagType($row['label'], null, $this->_pdo, $this->_type, $row);
        }
    }
    // }}}

}





