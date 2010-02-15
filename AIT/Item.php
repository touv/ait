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


/**
 * Représente un ITEM
 *
 * @category  AIT
 * @package   AIT
 * @author    Nicolas Thouvenin <nthouvenin@gmail.com>
 * @copyright 2009 Nicolas Thouvenin
 * @license   http://opensource.org/licenses/lgpl-license.php LGPL
 * @link      http://ait.touv.fr/
 */
class AIT_Item extends AIT
{
    /**
     * @var AITTagsObject  TagsObject de l'item courant
     */
    private $TagsObject;

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
            if (! $this->_checkTag($t, self::ITEM)) {
                trigger_error('Argument 2 passed to '.__METHOD__.' not describe a "typeitem" ', E_USER_ERROR);
            }
            $this->_id = $this->_addTag($this->_label, $this->_type, $row);
            $this->callClassCallback('addHook', $this);

            if ($row !== false)
                foreach($this->_cols as $n => $t)
                    if (isset($row[$n]))
                        $this->_set($n, $row[$n]);

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

        $sql = sprintf('
            SELECT a.id id, label, type
            FROM %s a
            LEFT JOIN %s b ON a.id=b.tag_id
            WHERE label = ? and type = ? and item_id = ?
            LIMIT 0,1
            ',
            $this->getPDO()->tag(),
            $this->getPDO()->tagged()
        );
        self::timer();
        $stmt = $this->getPDO()->prepare($sql);
        $stmt->bindParam(1,  $l, PDO::PARAM_STR);
        $stmt->bindParam(2,  $o->getSystemID(), PDO::PARAM_INT);
        $stmt->bindParam(3,  $this->_id, PDO::PARAM_INT);
        $stmt->execute();
        settype($this->_id, 'integer');
        if (($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
            if (is_null($row['id'])) continue;
            settype($row['type'], 'integer');
            settype($row['id'], 'integer');
            $ret = new AIT_Tag($row['label'], $row['type'], $this->_id, $this->getPDO(), $row['id'], $row);
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
            $ret = new AIT_Tag($l, $o->getSystemID(), $this->_id, $this->getPDO());
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
        return new AIT_Tag($l, $o->getSystemID(), $this->_id, $this->getPDO());
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

        $o = new AIT_Tag($l, $o->getSystemID(), $this->_id, $this->getPDO());
        $o->del();
    }
    // }}}

    // {{{ attach
    /**
     * Ajoute une association entre le tag et un item
     *
     * @param AIT_Tag $o Tag
     * @param mixed $r AIT::INSERT_FIRST or AIT_Tag
     *
     * @return AIT_Item
     */
    function attach($o, $r = null)
    {
        if ($o instanceof AIT_Tag) {
            $o->attach($this, $r);
            return $this;
        }
        elseif ($o instanceof AIT_Item) {
            $i = $o->getSystemID();
            if ($this->_checkTagged($i, $this->_id) === false) {
                if ($r instanceof AIT_Item or $r instanceof AIT_Tag) {
                    $r = $r->getSystemID();
                }
                $this->_addTagged($i, $this->_id, $r);
//                $this->_increaseFrequency($this->_id);
//                $this->_increaseFrequency($this->_type);
            }
            return $this;
        }
        else {
            trigger_error('Argument 1 passed to '.__METHOD__.' must be a instance of AIT_Tag or AIT_Item, '.gettype($type).' given', E_USER_ERROR);
        }
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
        if ($o instanceof AIT_Tag) {
            trigger_error('Not yet implemented, sorry.', E_USER_ERROR);
            return $this;
        }
        elseif ($o instanceof AIT_Item) {
            trigger_error('Not yet implemented, sorry.', E_USER_ERROR);
            return $this;
        }
        else {
            trigger_error('Argument 1 passed to '.__METHOD__.' must be a instance of AIT_Tag or AIT_Item, '.gettype($type).' given', E_USER_ERROR);
        }
    }
    // }}}

    // {{{ getTags
    /**
     * Récupére tout les tags de l'item courant
     *
     * @param integer $offset décalage à parir du premier enregistrement
     * @param integer $lines nombre de lignes à retourner
     * @param integer $ordering flag permettant le tri
     * @param array   $cols filtre sur les champs complémentaires
     *
     * @return AITResult
     */
    function getTags($offset = null, $lines = null, $ordering = null, $cols = array())
    {
        return $this->fetchElements(new ArrayObject(), $offset, $lines, $ordering, $cols, self::TAG);
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
     * @param array   $cols filtre sur les champs complémentaires
     *
     * @return AITResult
     */
    function getTypedTags(AIT_TagType $typetag, $offset = null, $lines = null, $ordering = null, $cols = array())
    {
        return $this->fetchElements(new ArrayObject(array($typetag)), $offset, $lines, $ordering, $cols, self::TAG);
    }
    // }}}

    // {{{ fetchTags
    /**
     * Récupére les tags possédant de un ou plusieurs tags donnée
     *
     * @param ArrayObject $types Tableau de type de tag
     * @param integer $offset décalage à parir du premier enregistrement
     * @param integer $lines nombre de lignes à retourner
     * @param integer $ordering flag permettant le tri
     * @param array   $cols filtre sur les champs complémentaires
     *
     * @return AITResult
     */
    function fetchTags(ArrayObject $types, $offset = null, $lines = null, $ordering = null, $cols = array())
    {
        return $this->fetchElements($types, $offset, $lines, $ordering, $cols, self::TAG);
    }
    // }}}

    // {{{ getItems
    /**
     * Récupére l'ensemble des items quelque soit leur type
     *
     * @param integer $offset décalage à parir du premier enregistrement
     * @param integer $lines nombre de lignes à retourner
     * @param integer $ordering flag permettant le tri
     * @param array   $cols filtre sur les champs complémentaires
     *
     * @return AITResult
     */
    function getItems($offset = null, $lines = null, $ordering = null, $cols = array())
    {
        return $this->fetchElements(new ArrayObject(), $offset, $lines, $ordering, $cols, self::ITEM);
    }
    // }}}

    // {{{ getTypedItems
    /**
     * Récupére l'ensemble des items attachés à condtion qu'il soit d'un certain type
     *
     * @param AIT_ItemType $typeitem un type de tag
     * @param integer $offset décalage à parir du premier enregistrement
     * @param integer $lines nombre de lignes à retourner
     * @param integer $ordering flag permettant le tri
     * @param array   $cols filtre sur les champs complémentaires
     *
     * @return AITResult
     */
    function getTypedItems(AIT_ItemType $typeitem, $offset = null, $lines = null, $ordering = null, $cols = array())
    {
        return $this->fetchElements(new ArrayObject(array($typeitem)), $offset, $lines, $ordering, $cols, self::ITEM);
    }
    // }}}

    // {{{ fetchItems
    /**
     * Récupére les items attachés en filtrant par leur type
     *
     * @param ArrayObject $types Tableau de type d'item
     * @param integer $offset décalage à parir du premier enregistrement
     * @param integer $lines nombre de lignes à retourner
     * @param integer $ordering flag permettant le tri
     * @param array   $cols filtre sur les champs complémentaires
     *
     * @return AITResult
     */
    function fetchItems(ArrayObject $types, $offset = null, $lines = null, $ordering = null, $cols = array())
    {
        return $this->fetchElements($types, $offset, $lines, $ordering, $cols, self::ITEM);
    }
    // }}}

    // {{{ getElements
    /**
     * Récupére l'ensemble des élements quelque soit leur type
     *
     * @param integer $offset décalage à parir du premier enregistrement
     * @param integer $lines nombre de lignes à retourner
     * @param integer $ordering flag permettant le tri
     * @param array   $cols filtre sur les champs complémentaires
     *
     * @return AITResult
     */
    function getElements($offset = null, $lines = null, $ordering = null, $cols = array())
    {
        return $this->fetchElements(new ArrayObject(), $offset, $lines, $ordering, $cols);
    }
    // }}}

    // {{{ getTypedElements
    /**
     * Récupére l'ensemble des élements attachés à condtion qu'il soit d'un certain type
     *
     * @param mixed $type un type de tag
     * @param integer $offset décalage à parir du premier enregistrement
     * @param integer $lines nombre de lignes à retourner
     * @param integer $ordering flag permettant le tri
     * @param array   $cols filtre sur les champs complémentaires
     *
     * @return AITResult
     */
    function getTypedElements($type, $offset = null, $lines = null, $ordering = null, $cols = array())
    {
        if (! $type instanceof AIT_TagType and ! $type instanceof AIT_ItemType)
            trigger_error('Argument 1 passed to '.__METHOD__.' must be a instance of AIT_TagType or AIT_ItemType, '.gettype($type).' given', E_USER_ERROR);
        return $this->fetchElements(new ArrayObject(array($type)), $offset, $lines, $ordering, $cols);
    }
    // }}}

    // {{{ fetchElements
    /**
     * Récupére les tags possédant de un ou plusieurs tags donnée
     *
     * @param ArrayObject $types Tableau de type de tag
     * @param integer $offset décalage à parir du premier enregistrement
     * @param integer $lines nombre de lignes à retourner
     * @param integer $ordering flag permettant le tri
     * @param array   $cols filtre sur les champs complémentaires
     * @param integer $control flag permettant le tri
     *
     * @return AITResult
     */
    function fetchElements(ArrayObject $types, $offset = null, $lines = null, $ordering = null, $cols = array(), $control = null)
    {
        if (!is_null($offset) && !is_int($offset))
            trigger_error('Argument 2 passed to '.__METHOD__.' must be a integer, '.gettype($offset).' given', E_USER_ERROR);
        if (!is_null($lines) && !is_int($lines))
            trigger_error('Argument 3 passed to '.__METHOD__.' must be a integer, '.gettype($lines).' given', E_USER_ERROR);
        if (!is_null($ordering) && !is_int($ordering))
            trigger_error('Argument 4 passed to '.__METHOD__.' must be a integer, '.gettype($ordering).' given', E_USER_ERROR);
        if (!is_array($cols))
            trigger_error('Argument 5 passed to '.__METHOD__.' must be a array'.gettype($cols).' given', E_USER_ERROR);
        if (!is_null($control) && $control != self::ITEM && $control != self::TAG)
            trigger_error('Argument 6 passed to '.__METHOD__.' must be a equal to AIT::TAG or AIT::ITEM, '.gettype($control).' given', E_USER_ERROR);

        $n = 0;
        $w  = '';
        if ($types->count() != 0) {
            foreach($types as $type) {
                if (! $type instanceof AIT_TagType and ! $type instanceof AIT_ItemType) {
                    trigger_error('Line '.$n.' of Argument 1 passed to '.__METHOD__.' must be a instance of AIT_TagType or AIT_ItemType, '.gettype($type).' given and ignored', E_USER_NOTICE);
                    continue;
                }
                if (!empty($w)) $w .= ' OR ';
                $w .= 'tag.type = '. $type->getSystemID();
                $n++;
            }
            if ($n === 0) return new AITResult(array());
            else $w = ' AND ('.$w.')';
        }
        if (!is_null($control)) {
            $w .= ' AND c.type = '. $control;
        }

        $sql1 = 'SELECT tag.id id, tag.label label, tag.prefix prefix, tag.suffix suffix, tag.buffer buffer, tag.scheme scheme, tag.language language, tag.score score, tag.frequency frequency, tag.type type, c.type crtl, content ';
        $sql2 = sprintf('
            FROM %1$s a
            LEFT JOIN %2$s tag ON a.tag_id=tag.id
            LEFT JOIN %2$s c ON tag.type=c.id
            LEFT JOIN %3$s dat ON tag.dat_hash=dat.hash
            WHERE a.item_id = ? %4$s
            ',
            $this->getPDO()->tagged(),
            $this->getPDO()->tag(),
            $this->getPDO()->dat(),
            $w
        );
        $sql = $sql1.$sql2.$this->filter($cols);
        self::sqler($sql, $offset, $lines, $ordering);

        if (($r = $this->callClassCallback(
            'fetchElementsCache',
            $cid = self::str2cid($sql, $this->_id)
        )) !== false) return $r;

        self::timer();
        $stmt = $this->getPDO()->prepare($sql);
        $stmt->bindParam(1, $this->_id, PDO::PARAM_INT);
        $stmt->execute();
        settype($this->_id, 'integer');
        $ret = array();
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
            if (is_null($row['id'])) continue;
            $ret[] = self::factory($this->getPDO(), $row);
        }
        $stmt->closeCursor();
        self::debug(self::timer(true), $sql, $this->_id);

        $sql = 'SELECT COUNT(*) '.$sql2;
        $r = new AITResult($ret);
        $r->setQueryForTotal($sql, array($this->_id => PDO::PARAM_INT,), $this->getPDO());

        if (isset($cid))
            $this->callClassCallback('fetchElementsCache', $cid, $r);

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
        $this->_rmTag();
    }
    // }}}

    // {{{ getItemType
    /**
     * Retourne le type d'item associé
     */
    public function getItemType()
    {
        if (($r = $this->callClassCallback(
            'getItemTypeCache',
            $cid = self::str2cid($this->_type)
        )) !== false) return $r;


        $row = $this->_getTagBySystemID($this->_type);

        if (is_array($row)) {
            $r = new AIT_ItemType($row['label'], $this->getPDO(), $this->_type, $row);
            if (isset($cid))
                $this->callClassCallback('getItemTypeCache', $cid, $r);
            return $r;

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

    // {{{ __get
    /**
     * Retourne les tags associé d'un type donnée
     *
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name) {
        if (is_null($name) or $name == '')
            return null;
        if (is_null($this->TagsObject))
            $this->TagsObject = $this->getTagsObject();

        return $this->TagsObject->{$name};
    }
    // }}}

}



