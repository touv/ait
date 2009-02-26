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
 * Représente un ITEM
 *
 * @category  AIT
 * @package   AIT
 * @author    Nicolas Thouvenin <nthouvenin@gmail.com>
 * @copyright 2008 Nicolas Thouvenin
 * @license   http://opensource.org/licenses/lgpl-license.php LGPL
 * @link      http://www.pxxo.net/fr/ait
 */
class AIT_Item extends AIT
{
    // {{{ __construct
    /**
     * Constructeur
     *
     * @param string $l nom du nouveau type de tag
     * @param mixed $t identifiant du type d'item associé
     * @param PDOAIT $pdo objet de connexion à la base
     * @param integer $id identifiant physique du de l'élement (si déjà connu)
     * @param array $row Propriétés de l'élément (si déja connu)
     */
    function __construct($l, $t, PDOAIT $pdo, $id = false, $row = false)
    {
        parent::__construct($pdo, 'Item');

        if (!is_string($l) and !is_null($l) and $id !== false)
            trigger_error('Argument 1 passed to '.__METHOD__.' must be a string, '.gettype($l).' given', E_USER_ERROR);
        if (!is_int($t) and !is_null($t) and $id !== false)
            trigger_error('Argument 2 passed to '.__METHOD__.' must be a integer, '.gettype($t).' given', E_USER_ERROR);
        if ($id !== false && !is_int($id))
            trigger_error('Argument 4 passed to '.__METHOD__.' must be a integer, '.gettype($id).' given', E_USER_ERROR);
        if ($row !== false && !is_array($row))
            trigger_error('Argument 5 passed to '.__METHOD__.' must be a Array, '.gettype($row).' given', E_USER_ERROR);


        $this->_label = $l;
        $this->_type = $t;
        if ($id !== false) {
            if ($row !== false) $this->_fill($row);
            $this->_id = (int) $id;
            $r = null;
            if (is_null($this->_label)) {
                $r = $this->_getTagBySystemID($this->_id);
                $this->_label = $r['label'];
            }
            if (is_null($this->_type)) {
                if (is_null($r)) {
                    $r = $this->_getTagBySystemID($this->_id);
                }
                $this->_label = $r['type'];
            }
        }
        else {
            if (! $this->_checkType($this->_type)) {
                trigger_error('Argument 2 passed to '.__METHOD__.' not describe a "type" that doesn\' exist', E_USER_ERROR);
            }
            if (! $this->_checkTag($t, 1)) {
                trigger_error('Argument 2 passed to '.__METHOD__.' not describe a "typeitem" ', E_USER_ERROR);
            }
            $this->_id = $this->_addTag($this->_label, $this->_type);
            $this->_increaseFrequency($this->_type);
        }
    }
    // }}}

   // {{{ getTag
    /**
     * Retourne un tag associé à l'item courant (si il existe)
     *
     * @param string $l nom du nouveau tag
     * @param AIT_TagType $o Type de Tag
     *
     * @return AIT_Tag or null
     */
    function getTag($l, AIT_TagType $o)
    {
        if (!is_string($l))
            trigger_error('Argument 1 passed to '.__METHOD__.' must be a string, '.gettype($l).' given', E_USER_ERROR);

        $sql = sprintf(
            "SELECT a.id id, label, type
             FROM %s a
             LEFT JOIN %s b ON a.id=b.tag_id
             WHERE label = ? and type = ?  and item_id = ?
             LIMIT 0,1
             ",
             $this->_pdo->tag(),
             $this->_pdo->tagged()
         );
        self::timer();
        $stmt = $this->_pdo->prepare($sql);
        $stmt->bindParam(1,  $l, PDO::PARAM_STR);
        $stmt->bindParam(2,  $o->getSystemID(), PDO::PARAM_INT);
        $stmt->bindParam(3,  $this->_id, PDO::PARAM_INT);
        $stmt->execute();
        settype($this->_id, 'integer');
        if (($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
            if (is_null($row['id'])) continue;
            settype($row['type'], 'integer');
            settype($row['id'], 'integer');
            $ret = new AIT_Tag($row['label'], $row['type'], $this->_id, $this->_pdo, $row['id'], $row);
        }
        else $ret = null;
        $stmt->closeCursor();
        self::debug(self::timer(true), $sql, $l, $o->getSystemID());

        return $ret;
    }
    // }}}

    // {{{ defTag
    /**
     * Récupére un tag associé à l'item courant.
     * Si le tag n'existe pas il est automatiquement créé.
     *
     * @param string $l nom du tag
     * @param AIT_TagType $o Type de Tag
     *
     * @return AIT_Tag
     */
    function defTag($l, AIT_TagType $o)
    {
        $ret = $this->getTag($l, $o);
        if (is_null($ret))
            $ret = new AIT_Tag($l, $o->getSystemID(), $this->_id, $this->_pdo);
        return $ret;
    }
    // }}}

    // {{{ addTag
    /**
     * Ajout d'un tag à l'item courant
     *
     * @param string $l nom du nouveau tag
     * @param AIT_TagType $o Type de Tag
     *
     * @return AIT_Tag
     */
    function addTag($l, AIT_TagType $o)
    {
        return new AIT_Tag($l, $o->getSystemID(), $this->_id, $this->_pdo);
    }
    // }}}

    // {{{ delTag
    /**
     * Supprime un tag à l'item courant
     *
     * @param string $l nom du nouveau tag
     * @param AIT_TagType $o Type de Tag
     */
    function delTag($l, AIT_TagType $o)
    {
        if (!is_string($l))
            trigger_error('Argument 1 passed to '.__METHOD__.' must be a String, '.gettype($l).' given', E_USER_ERROR);

        $o = new AIT_Tag($l, $o->getSystemID(), $this->_id, $this->_pdo);
        $o->del();
    }
    // }}}

    // {{{ attach
    /**
     * Ajoute une association entre le tag et un item
     *
     * @param AIT_Tag $o Tag
     *
     * @return AIT_Item
     */
    function attach(AIT_Tag $o)
    {
        $o->attach($this);
        return $this;
    }
    // }}}

    // {{{ detach
    /**
     * Supprime une association entre le tag et un item
     *
     * @param AIT_Tag $o Tag
     *
     * @return AIT_Item
     */
    function detach(AIT_Tag $o)
    {
        trigger_error('Not yet implemented, sorry.', E_USER_ERROR);
        return $this;
    }
    // }}}

    // {{{ getTags
    /**
     * Récupére tout les tags de l'item courant
     *
     * @param integer $offset décalage à parir du premier enregistrement
     * @param integer $lines nombre de lignes à retourner
     * @param integer $ordering flag permettant le tri
     *
     * @return AITResult
     */
    function getTags($offset = null, $lines = null, $ordering = null)
    {
        return $this->fetchTags(new ArrayObject(), $offset, $lines, $ordering);
    }
    // }}}

    // {{{ getTypedTags
    /**
     * Récupére tout les tags d'un certain type de l'item courant
     *
     * @param AIT_TagType $typetag un type de tag
     * @param integer $offset décalage à parir du premier enregistrement
     * @param integer $lines nombre de lignes à retourner
     * @param integer $ordering flag permettant le tri
     *
     * @return AITResult
     */
    function getTypedTags(AIT_TagType $typetag, $offset = null, $lines = null, $ordering = null)
    {
        return $this->fetchTags(new ArrayObject(array($typetag)), $offset, $lines, $ordering);
    }
    // }}}

    // {{{ fetchTags
    /**
     * Récupére les tags possédant de un ou plusieurs tags donnée
     *
     * @param ArrayObject $tags Tableau de type de tag
     * @param integer $offset décalage à parir du premier enregistrement
     * @param integer $lines nombre de lignes à retourner
     * @param integer $ordering flag permettant le tri
     *
     * @return AITResult
     */
    function fetchTags(ArrayObject $tags, $offset = null, $lines = null, $ordering = null)
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
                    trigger_error('Line '.$n.' of Argument 1 passed to '.__METHOD__.' must be a instance of AIT_TagType, '.gettype($tag).' given and ignored', E_USER_NOTICE);
                    continue;
                }
                if (!empty($w)) $w .= ' OR ';
                $w = 'type = '. $tag->getSystemID();
                $n++;
            }
            if ($n === 0) return new AITResult(array());
            else $w = ' AND ('.$w.')';
        }
        $sql1 = 'SELECT id, label, space, score, frequency, type ';
        $sql2 = sprintf("
            FROM %s a
            LEFT JOIN %s b ON a.tag_id=b.id
            WHERE item_id = ? %s
            ",
            $this->_pdo->tagged(),
            $this->_pdo->tag(),
            $w
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
            if (is_null($row['id'])) continue;
            settype($row['type'], 'integer');
            settype($row['id'], 'integer');
            $ret[] = new AIT_Tag($row['label'], $row['type'], $this->_id, $this->_pdo, $row['id'], $row);
        }
        $stmt->closeCursor();
        self::debug(self::timer(true), $sql, $this->_id);

        $sql = 'SELECT COUNT(*) '.$sql2;
        $r = new AITResult($ret);
        $r->setQueryForTotal($sql, array($this->_id => PDO::PARAM_INT,), $this->_pdo);

        return $r;
    }
    // }}}

    // {{{ del
    /**
     * Suppression de l'élement courrant
     */
    public function del($cascade = false)
    {
        $tags = $this->getTags();
        foreach($tags as $tag) {
            if ($cascade) $tag->del();
            $this->_decreaseFrequency($tag->getSystemID());
            $this->_decreaseFrequency($tag->get('type'));

            if ($tag->countItems() <= 0) $tag->del();
        }
        $this->_decreaseFrequency($this->_type);
        $this->_rmTagged(null, $this->_id);
        $this->_rmTag($this->_id);
    }
    // }}}
    
    // {{{ getItemType
    /**
     * Retourne le type de tag associé
     */
    public function getItemType()
    {
        $row = $this->_getTagBySystemID($this->_type);

        if (is_array($row)) {
            return new AIT_ItemType($row['label'], $this->_pdo, $this->_type, $row);
        }
    }
    // }}}

    // {{{ getTagsObject
    /**
     * Renvoit l'item sous form d'un objet avec des pointeurs sur ces tags
     */
    public function getTagsObject()
    {
        return new AITTagsObject($this->getTags());
    }
    // }}}

}



