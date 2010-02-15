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
require_once 'AIT/Item.php';
require_once 'AIT/TagType.php';


/**
 * Représente un TYPE d'ITEM
 *
 * @category  AIT
 * @package   AIT
 * @author    Nicolas Thouvenin <nthouvenin@gmail.com>
 * @copyright 2009 Nicolas Thouvenin
 * @license   http://opensource.org/licenses/lgpl-license.php LGPL
 * @link      http://ait.touv.fr/
 */
class AIT_ItemType extends AIT
{
    private $TagTypes = array();
    // {{{ __construct
    /**
     * Constructeur
     *
     * @param string $label nom du tupe d'item
     * @param PDOAIT $pdo objet de connexion à la base
     * @param integer $id Identifiant système de l'élement (si déjà connu).
     * @param array $row Propriétés de l'élément (si déja connu)
     */
    function __construct($l, PDOAIT $pdo, $id = false, $row = false)
    {
        parent::__construct($pdo, 'ItemType');

        if (!is_string($l) and !is_null($l) and $id !== false)
            trigger_error('Argument 1 passed to '.__METHOD__.' must be a string, '.gettype($l).' given', E_USER_ERROR);
        if ($id !== false && !is_int($id))
            trigger_error('Argument 3 passed to '.__METHOD__.' must be a integer, '.gettype($id).' given', E_USER_ERROR);
        if ($row !== false && !is_array($row))
            trigger_error('Argument 4 passed to '.__METHOD__.' must be a Array, '.gettype($row).' given', E_USER_ERROR);


        $this->_label = $l;
        $this->_type  = self::ITEM;
        if ($id === false) {
            $this->_id = $this->_addTag($this->_label, $this->_type, $row);
            $this->callClassCallback('addHook', $this);

            if ($row !== false)
                foreach($this->_cols as $n => $t)
                    if (isset($row[$n]))
                        $this->_set($n, $row[$n]);
        }
        else {
            if ($row !== false) $this->_fill($row);
            $this->_id = (int) $id;
            if (is_null($this->_label)) {
                $r = $this->_getTagBySystemID($id);
                $this->_label = $r['label'];
            }
        }
    }
    // }}}

    // {{{ addTagType
    /**
     * Ajout d'un type de tag au type d'item courant
     *
     * @param string $l label
     *
     * @return AIT_TagType
     */
    function addTagType($l, $r = false)
    {
        if (!is_string($l))
            trigger_error('Argument 1 passed to '.__METHOD__.' must be a String, '.gettype($l).' given', E_USER_ERROR);

        return new AIT_TagType($l, $this->_id, $this->getPDO(), false, $r);
    }
    function addTag($l, $r = false) { return $this->addTagType($l, $r); }
    // }}}

    // {{{ getTagType
    /**
     * Récupére un type de tag du type d'item courant
     *
     * @param string $l label
     *
     * @return AIT_TagType
     */
    function getTagType($l)
    {
        if (!is_string($l))
            trigger_error('Argument 1 passed to '.__METHOD__.' must be a String, '.gettype($l).' given', E_USER_ERROR);

        $sql = sprintf('
            SELECT tag.id id, label, type
            FROM %s tag
            LEFT JOIN %s b ON tag.id=b.tag_id
            WHERE label = ? and type = %s and item_id = ?
            LIMIT 0,1
            ',
            $this->getPDO()->tag(),
            $this->getPDO()->tagged(),
            self::TAG
        );
        self::timer();
        $stmt = $this->getPDO()->prepare($sql);
        $stmt->bindParam(1, $l, PDO::PARAM_STR);
        $stmt->bindParam(2, $this->_id, PDO::PARAM_INT);
        $stmt->execute();
        settype($this->_id, 'integer');
        if (($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
            if (is_null($row['id'])) continue;
            settype($row['type'], 'integer');
            settype($row['id'], 'integer');
            $ret = new AIT_TagType($row['label'], $this->_id, $this->getPDO(), $row['id'], $row);
        }
        else $ret = null;
        $stmt->closeCursor();
        self::debug(self::timer(true), $sql, $l, $this->_id);

        return $ret;
    }
    function getTag($l) { return $this->getTagType($l); }
    // }}}

    // {{{ defTagType
    /**
     * Récupére un type tag associé au type d'item courant.
     * Si le tag n'existe pas il est automatiquement créé.
     *
     * @param string $l nom du tag
     *
     * @return AIT_TagType
     */
    function defTagType($l)
    {
        $ret = $this->getTagType($l);
        if (is_null($ret)) {
            $ret = new AIT_TagType($l, $this->_id, $this->getPDO());
        }
        return $ret;
    }
    function defTag($l) { return $this->defTagType($l); }
    // }}}

    // {{{ getTagTypes
    /**
     * Récupére tout les types de tags de l'item courant
     *
     * @param integer $offset décalage à parir du premier enregistrement
     * @param integer $lines nombre de lignes à retourner
     * @param integer $ordering flag permettant le tri
     * @param array   $cols filtre sur les champs complémentaires
     *
     * @return AITResult
     */
    function getTagTypes($offset = null, $lines = null, $ordering = null, $cols = array())
    {
        if (!is_null($offset) && !is_int($offset))
            trigger_error('Argument 1 passed to '.__METHOD__.' must be a integer, '.gettype($offset).' given', E_USER_ERROR);
        if (!is_null($lines) && !is_int($lines))
            trigger_error('Argument 2 passed to '.__METHOD__.' must be a integer, '.gettype($lines).' given', E_USER_ERROR);
        if (!is_null($ordering) && !is_int($ordering))
            trigger_error('Argument 3 passed to '.__METHOD__.' must be a integer, '.gettype($ordering).' given', E_USER_ERROR);
        if (!is_array($cols))
            trigger_error('Argument 4 passed to '.__METHOD__.' must be a array'.gettype($cols).' given', E_USER_ERROR);


        $sql1 = 'SELECT id, label, prefix, suffix, buffer, scheme, language, score, frequency, type, content ';
        $sql2 = sprintf('
            FROM %1$s tagged
            LEFT JOIN %2$s tag ON tagged.tag_id=tag.id
            LEFT JOIN %3$s dat ON tag.dat_hash=dat.hash
            WHERE item_id = ?
            ',
            $this->getPDO()->tagged(),
            $this->getPDO()->tag(),
            $this->getPDO()->dat()
        );
        $sql = $sql1.$sql2.$this->filter($cols);
        self::sqler($sql, $offset, $lines, $ordering);

        if (($r = $this->callClassCallback(
            'getTagTypesCache',
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
            settype($row['id'], 'integer');
            $ret[] = new AIT_TagType($row['label'], $this->_id, $this->getPDO(), $row['id'], $row);
        }
        $stmt->closeCursor();
        self::debug(self::timer(true), $sql, $this->_id);

        $sql = 'SELECT COUNT(*) '.$sql2;
        $r = new AITResult($ret);
        $r->setQueryForTotal($sql, array($this->_id => PDO::PARAM_INT,), $this->getPDO());

        if (isset($cid))
            $this->callClassCallback('getTagTypesCache', $cid, $r);

        return $r;
    }
    function getTags($offset = null, $lines = null, $ordering = null) { return $this->getTagTypes($offset, $lines, $ordering); }
    // }}}

    // {{{ countTagTypes
    /**
     * Compte le nombre de tags associés au type d'item courant
     *
     * @return integer
     */
    function countTagTypes()
    {
        try {
            $sql = sprintf('
                SELECT count(*)
                FROM %s tagged
                WHERE item_id = ?
                ',
                $this->getPDO()->tagged()
            );

            self::timer();
            $stmt = $this->getPDO()->prepare($sql);
            $stmt->bindParam(1, $this->_id, PDO::PARAM_INT);
            $stmt->execute();
            settype($this->_id, 'integer');

            $c = (int)$stmt->fetchColumn(0);
            $stmt->closeCursor();
            self::debug(self::timer(true), $sql, $this->_id);

            return $c;
        }
        catch (PDOException $e) {
            self::catchError($e);
        }
    }
    function countTags() { return $this->countTagTypes(); }
    // }}}

    // {{{ newItem
    /**
     * Crée un item son label étant calculé automatiquement
     *
     * @return AIT_Item
     */
    function newItem()
    {
        $o = new AIT_Item('NEW', $this->_id, $this->getPDO());
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
    function addItem($l, $r = false)
    {
        if (!is_string($l))
            trigger_error('Argument 1 passed to '.__METHOD__.' must be a String, '.gettype($l).' given', E_USER_ERROR);

        return new AIT_Item($l, $this->_id, $this->getPDO(), false, $r);
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

        $sql = sprintf('
            SELECT id, label, type
            FROM %s tag
            WHERE label = ? AND type = ?
            LIMIT 0,1
            ',
            $this->getPDO()->tag()
        );

        if (($r = $this->callClassCallback(
            'getItemCache',
            $cid = self::str2cid($l, $this->_id)
        )) !== false) return $r;


        self::timer();
        $stmt = $this->getPDO()->prepare($sql);
        $stmt->bindParam(1, $l, PDO::PARAM_STR);
        $stmt->bindParam(2, $this->_id, PDO::PARAM_INT);
        $stmt->execute();
        settype($this->_id, 'integer');
        if (($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
            if (is_null($row['id'])) continue;
            settype($row['type'], 'integer');
            settype($row['id'], 'integer');
            $ret = new AIT_Item($row['label'], $this->_id, $this->getPDO(), $row['id'], $row);
        }
        else $ret = null;

        $id = (int)$stmt->fetchColumn(0);
        $stmt->closeCursor();
        self::debug(self::timer(true), $sql, $l, $this->_id);

        if (isset($cid))
            $this->callClassCallback('getItemCache', $cid, $ret);

        return $ret;
    }
    // }}}

    // {{{ defItem
    /**
     * Récupére un item associé au type d'item courant.
     * Si l'item n'existe pas il est automatiquement créé.
     *
     * @param string $l nom de l'item
     *
     * @return AIT_Item
     */
    function defItem($l)
    {
        $ret = $this->getItem($l);
        if (is_null($ret))
            $ret = new AIT_Item($l, $this->_id, $this->getPDO());
        return $ret;
    }
    // }}}

    // {{{ getItems
    /**
     * Récupére tous les items du type d'item courant
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
        if (!is_null($offset) && !is_int($offset))
            trigger_error('Argument 1 passed to '.__METHOD__.' must be a integer, '.gettype($offset).' given', E_USER_ERROR);
        if (!is_null($lines) && !is_int($lines))
            trigger_error('Argument 2 passed to '.__METHOD__.' must be a integer, '.gettype($offset).' given', E_USER_ERROR);
        if (!is_null($ordering) && !is_int($ordering))
            trigger_error('Argument 3 passed to '.__METHOD__.' must be a integer, '.gettype($ordering).' given', E_USER_ERROR);
        if (!is_array($cols))
            trigger_error('Argument 4 passed to '.__METHOD__.' must be a array'.gettype($cols).' given', E_USER_ERROR);


        $sql1 = 'SELECT id, label, prefix, suffix, buffer, scheme, language, score, frequency, content ';
        $sql2 = sprintf('
            FROM %s tag
            LEFT JOIN %s dat ON tag.dat_hash=dat.hash
            WHERE type = ?
            ',
            $this->getPDO()->tag(),
            $this->getPDO()->dat()
        );
        $sql = $sql1.$sql2.$this->filter($cols);
        self::sqler($sql, $offset, $lines, $ordering);

        if (($r = $this->callClassCallback(
            'getItemsCache',
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
            settype($row['id'], 'integer');
            $ret[] = new AIT_Item($row['label'], $this->_id, $this->getPDO(), $row['id'], $row);
        }
        $stmt->closeCursor();
        self::debug(self::timer(true), $sql, $this->_id);

        $sql = 'SELECT COUNT(*) '.$sql2;
        $r = new AITResult($ret);
        $r->setQueryForTotal($sql, array($this->_id => PDO::PARAM_INT,), $this->getPDO());

        if (isset($cid))
            $this->callClassCallback('getItemsCache', $cid, $r);

        return $r;
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
     * @param array   $cols filtre sur les champs complémentaires
     *
     * @return	AITResult
     */
    function fetchItems(ArrayObject $tags, $offset = null, $lines = null, $ordering = null, $cols = array())
    {
        if (!is_null($offset) && !is_int($offset))
            trigger_error('Argument 2 passed to '.__METHOD__.' must be a integer, '.gettype($offset).' given', E_USER_ERROR);
        if (!is_null($lines) && !is_int($lines))
            trigger_error('Argument 3 passed to '.__METHOD__.' must be a integer, '.gettype($lines).' given', E_USER_ERROR);
        if (!is_null($ordering) && !is_int($ordering))
            trigger_error('Argument 4 passed to '.__METHOD__.' must be a integer, '.gettype($ordering).' given', E_USER_ERROR);
        if (!is_array($cols))
            trigger_error('Argument 4 passed to '.__METHOD__.' must be a array'.gettype($cols).' given', E_USER_ERROR);


        $n = 0;
        $w  = '';
        if ($tags->count() == 0) return new AITResult(array());
        foreach($tags as $tag) {
            if (! $tag instanceof AIT_Tag) {
                trigger_error('Line #'.($n + 1).' of Argument 1 passed to '.__METHOD__.' must be a instance of AIT_Tag, '.gettype($tag).' given and ignored', E_USER_NOTICE);
                continue;
            }
            if (empty($w)) {
                $w .= sprintf(' tagged.tag_id = %s ', $tag->getSystemID());
            }
            else {
                $w .= sprintf(' AND EXISTS (SELECT null FROM %s t WHERE t.item_id = tagged.item_id and t.tag_id = %s)',
                    $this->getPDO()->tagged(),
                    $tag->getSystemID()
                );
            }
            $n++;
        }
        if ($n === 0) return new AITResult(array());

        $sql1 = 'SELECT DISTINCT id, label, prefix, suffix, buffer, scheme, language, score, frequency, type, content ';
        $sql2 = sprintf('
            FROM %s tagged
            LEFT JOIN %s tag ON tagged.item_id = tag.id
            LEFT JOIN %s dat ON tag.dat_hash=dat.hash
            WHERE %s AND type = ?
            ',
            $this->getPDO()->tagged(),
            $this->getPDO()->tag(),
            $this->getPDO()->dat(),
            $w
        );
        $sql = $sql1.$sql2.$this->filter($cols);
        self::sqler($sql, $offset, $lines, $ordering);

        if (($r = $this->callClassCallback(
            'fetchItemsCache',
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
            settype($row['type'], 'integer');
            settype($row['id'], 'integer');
            $ret[] = new AIT_Item($row['label'], $row['type'], $this->getPDO(), $row['id'], $row);
        }
        $stmt->closeCursor();
        self::debug(self::timer(true), $sql, $this->_id);

        $sql = 'SELECT count(DISTINCT id) '.$sql2;
        $r = new AITResult($ret, $this->getPDO());
        if ($tags->count() > 1) {
            $r->setQueryForTotal($sql, array($this->_id => PDO::PARAM_INT,));
        }
        else {
            // Pour un seul tag il est intule de faire un "count"
            // alors que sa frequence contient le nombre exacte d'items
            $r->setTotal($tags->offsetGet(0)->countItems());
        }
        if (isset($cid))
            $this->callClassCallback('fetchItemsCache', $cid, $r);

        return $r;
    }
    // }}}

    // {{{ searchItems
    /**
     * Recherche des items du type courant à partir des tags
     *
     * Important : Ne sont ramenés que des items possédant des tags.
     *
     * @param string  $query requete (le format dépend de la search_callback) sans callback c'est du SQL
     * @param integer $offset décalage à parir du premier enregistrement
     * @param integer $lines nombre de lignes à retourner
     * @param integer $ordering flag permettant le tri
     * @param array   $cols filtre sur les champs complémentaires
     *
     * @return AITResult
     */
    function searchItems($query, $offset = null, $lines = null, $ordering = null, $cols = array())
    {
        if (!is_null($offset) && !is_int($offset))
            trigger_error('Argument 2 passed to '.__METHOD__.' must be a integer, '.gettype($offset).' given', E_USER_ERROR);
        if (!is_null($lines) && !is_int($lines))
            trigger_error('Argument 3 passed to '.__METHOD__.' must be a integer, '.gettype($lines).' given', E_USER_ERROR);
        if (!is_null($ordering) && !is_int($ordering))
            trigger_error('Argument 4 passed to '.__METHOD__.' must be a integer, '.gettype($ordering).' given', E_USER_ERROR);
        if (!is_array($cols))
            trigger_error('Argument 5 passed to '.__METHOD__.' must be a array'.gettype($cols).' given', E_USER_ERROR);


        if ($this->isClassCallback('searchItemsHook'))
            $query = $this->callClassCallback('searchItemsHook', $query, $this);

        if ($query !== '' and $query !== false) $query = 'AND '.$query;
        $sql1 = 'SELECT DISTINCT item.id id, item.label label, item.prefix prefix, item.suffix suffix, item.buffer buffer, item.scheme scheme, item.language language, item.score score, item.frequency frequency, content';
        $sql2 = sprintf('
            FROM %1$s tag
            LEFT JOIN %2$s b ON tag.type=b.tag_id
            LEFT JOIN %2$s d ON tag.id=d.tag_id
            LEFT JOIN %1$s item ON d.item_id=item.id
            LEFT JOIN %3$s dat ON tag.dat_hash=dat.hash
            WHERE b.item_id = item.type AND item.type = ? %4$s
            ',
            $this->getPDO()->tag(),
            $this->getPDO()->tagged(),
            $this->getPDO()->dat(),
            $query
        );
        $sql = $sql1.$sql2.$this->filter($cols);

        self::sqler($sql, $offset, $lines, $ordering);

        if (($r = $this->callClassCallback(
            'searchItemsCache',
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
            settype($row['id'], 'integer');
            $ret[] = new AIT_Item($row['label'], $this->_id, $this->getPDO(), $row['id'], $row);
        }
        $stmt->closeCursor();
        self::debug(self::timer(true), $sql, $this->_id);

        $sql = 'SELECT COUNT(DISTINCT item.id) '.$sql2;
        $r = new AITResult($ret);
        $r->setQueryForTotal($sql, array($this->_id => PDO::PARAM_INT,), $this->getPDO());

        if (isset($cid))
            $this->callClassCallback('searchItemsCache', $cid, $r);

        return $r;
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
            return new AIT_Item($row['label'], $this->_id, $this->getPDO(), $row['id']);
        }
    }
    // }}}

    // {{{ queryItems
    /**
     * On recherche des items associés au TYPE d'ITEM courant à partir d'un objet AITQuery
     *
     * @param AITQuery $query requete
     * @param integer $offset décalage à parir du premier enregistrement
     * @param integer $lines nombre de lignes à retourner
     * @param integer $ordering flag permettant le tri
     * @param array   $cols filtre sur les champs complémentaires
     *
     * @return	AITResult
     */
    function queryItems(AITQuery $query, $offset = null, $lines = null, $ordering = null, $cols = array())
    {
        if (!is_null($offset) && !is_int($offset))
            trigger_error('Argument 2 passed to '.__METHOD__.' must be a integer, '.gettype($offset).' given', E_USER_ERROR);
        if (!is_null($lines) && !is_int($lines))
            trigger_error('Argument 3 passed to '.__METHOD__.' must be a integer, '.gettype($lines).' given', E_USER_ERROR);
        if (!is_null($ordering) && !is_int($ordering))
            trigger_error('Argument 4 passed to '.__METHOD__.' must be a integer, '.gettype($ordering).' given', E_USER_ERROR);
        if (!is_array($cols))
            trigger_error('Argument 4 passed to '.__METHOD__.' must be a array'.gettype($cols).' given', E_USER_ERROR);


        $w = $query->getSQL();
        $sql1 = 'SELECT id, label, prefix, suffix, buffer, scheme, language, score, frequency, type, content ';
        $sql2 = sprintf('
            FROM (%s) temp
            LEFT JOIN %s tag ON temp.item_id = tag.id
            LEFT JOIN %s dat ON tag.dat_hash=dat.hash
            WHERE type = ?
            ',
            $w,
            $this->getPDO()->tag(),
            $this->getPDO()->dat()
        );
        $sql = $sql1.$sql2.$this->filter($cols);
        self::sqler($sql, $offset, $lines, $ordering);

        if (($r = $this->callClassCallback(
            'queryItemsCache',
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
            settype($row['type'], 'integer');
            settype($row['id'], 'integer');
            $ret[] = new AIT_Item($row['label'], $row['type'], $this->getPDO(), $row['id'], $row);
        }
        $stmt->closeCursor();
        self::debug(self::timer(true), $sql, $this->_id);

        $sql = 'SELECT COUNT(*) '.$sql2;
        $r = new AITResult($ret);
        $r->setQueryForTotal($sql, array($this->_id => PDO::PARAM_INT,), $this->getPDO());

        if (isset($cid))
            $this->callClassCallback('queryItemsCache', $cid, $r);

        return $r;
    }
    // }}}

    // {{{ getAll
    /**
     * Récupére Tous les types d'items de la base
     *
     * @param PDOAIT $pdo pointeur sur la base de données
     * @param integer $offset décalage à parir du premier enregistrement
     * @param integer $lines nombre de lignes à retourner
     * @param integer $ordering flag permettant le tri
     *
     * @return	AITResult
     */
    static function getAll(PDOAIT $pdo, $offset = null, $lines = null, $ordering = null)
    {
        if (!is_null($offset) && !is_int($offset))
            trigger_error('Argument 2 passed to '.__METHOD__.' must be a integer, '.gettype($offset).' given', E_USER_ERROR);
        if (!is_null($lines) && !is_int($lines))
            trigger_error('Argument 3 passed to '.__METHOD__.' must be a integer, '.gettype($lines).' given', E_USER_ERROR);
        if (!is_null($ordering) && !is_int($ordering))
            trigger_error('Argument 4 passed to '.__METHOD__.' must be a integer, '.gettype($ordering).' given', E_USER_ERROR);

        $sql1 = 'SELECT id, label, prefix, suffix, buffer, scheme, language, score, frequency, content';
        $sql2 = sprintf('
            FROM %s tag
            LEFT JOIN %s dat ON tag.dat_hash=dat.hash
            WHERE type = 1
            ',
            $pdo->tag(),
            $pdo->dat()
        );
        $sql = $sql1.$sql2;
        self::sqler($sql, $offset, $lines, $ordering);
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $ret = array();
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
            if (is_null($row['id'])) continue;
            settype($row['type'], 'integer');
            settype($row['id'], 'integer');
            $ret[] = new AIT_ItemType($row['label'], $pdo, $row['id'], $row);
        }
        $stmt->closeCursor();

        $sql = 'SELECT COUNT(*) '.$sql2;
        $r = new AITResult($ret);
        $r->setQueryForTotal($sql, array(), $pdo);

        return $r;
    }
    // }}}

    // {{{ countItems
    /**
     * Compte le nombre d'items du type d'item courant
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
    public function del()
    {
        $items = $this->getItems();
        foreach($items as $item) {
            $item->del(true);
        }
        $tags = $this->getTags();
        foreach($tags as $tag) {
            $tag->del(true);
        }
        $this->_rmTag();
    }
    // }}}

    // {{{ select
    /**
     * Selectionne des items du type courant à partir des items eux-même
     *
     * @param string  $query requete (le format dépend de la search_callback) sans callback c'est du SQL
     * @param integer $offset décalage à parir du premier enregistrement
     * @param integer $lines nombre de lignes à retourner
     * @param integer $ordering flag permettant le tri
     * @param array   $cols filtre sur les champs complémentaires
     *
     * @return AITResult
     */
    function selectItems($query, $offset = null, $lines = null, $ordering = null, $cols = array())
    {
        if (!is_null($offset) && !is_int($offset))
            trigger_error('Argument 2 passed to '.__METHOD__.' must be a integer, '.gettype($offset).' given', E_USER_ERROR);
        if (!is_null($lines) && !is_int($lines))
            trigger_error('Argument 3 passed to '.__METHOD__.' must be a integer, '.gettype($lines).' given', E_USER_ERROR);
        if (!is_null($ordering) && !is_int($ordering))
            trigger_error('Argument 4 passed to '.__METHOD__.' must be a integer, '.gettype($ordering).' given', E_USER_ERROR);
        if (!is_array($cols))
            trigger_error('Argument 5 passed to '.__METHOD__.' must be a array'.gettype($cols).' given', E_USER_ERROR);


        if ($this->isClassCallback('selectItemsHook'))
            $query = $this->callClassCallback('selectItemsHook', $query, $this);

        if ($query !== '' and $query !== false) $query = 'AND '.$query;
        $sql1 = 'SELECT DISTINCT id, label, prefix, suffix, buffer, scheme, language, score, frequency, content';
        $sql2 = sprintf('
            FROM %1$s item 
            LEFT JOIN %2$s dat ON item.dat_hash=dat.hash
            WHERE item.type = ? %3$s
            ',
            $this->getPDO()->tag(),
            $this->getPDO()->dat(),
            $query
        );
        $sql = $sql1.$sql2.$this->filter($cols, 'item');

        self::sqler($sql, $offset, $lines, $ordering);

        if (($r = $this->callClassCallback(
            'selectItemsCache',
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
            settype($row['id'], 'integer');
            $ret[] = new AIT_Item($row['label'], $this->_id, $this->getPDO(), $row['id'], $row);
        }
        $stmt->closeCursor();
        self::debug(self::timer(true), $sql, $this->_id, $this->_id);

        $sql = 'SELECT COUNT(*) '.$sql2;
        $r = new AITResult($ret);
        $r->setQueryForTotal($sql, array($this->_id => PDO::PARAM_INT,), $this->getPDO());

        if (isset($cid))
            $this->callClassCallback('selectItemsCache', $cid, $r);

        return $r;
    }
    // }}}

    // {{{ __get
    /**
     * Retourne un type de tag associé
     *
     * @param string $name
     *
     * @return AIT_TypeTag
     */
    public function __get($name) {
        if (is_null($name) or $name == '')
            return null;
        if (!isset($this->TagTypes[$name]))
            $this->TagTypes[$name] = $this->defTag($name);

        return $this->TagTypes[$name];
    }
    // }}}

}




