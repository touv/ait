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
 * Représente un TYPE de TAG
 *
 * @category  AIT
 * @package   AIT
 * @author    Nicolas Thouvenin <nthouvenin@gmail.com>
 * @copyright 2009 Nicolas Thouvenin
 * @license   http://opensource.org/licenses/lgpl-license.php LGPL
 * @link      http://ait.touv.fr/
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
     * @param array $row Propriétés de l'élément (si déja connu)
     */
    function __construct($l, $i, PDO $pdo, $id = false, $row = false)
    {
        parent::__construct($pdo, 'TagType');

        if (!is_string($l) and !is_null($l) and $id !== false)
            trigger_error('Argument 1 passed to '.__METHOD__.' must be a String, '.gettype($l).' given', E_USER_ERROR);
        if (!is_null($i) && !is_int($i))
            trigger_error('Argument 2 passed to '.__METHOD__.' must be a Integer, '.gettype($i).' given', E_USER_ERROR);
        if ($id !== false && !is_int($id))
            trigger_error('Argument 4 passed to '.__METHOD__.' must be a Integer, '.gettype($id).' given', E_USER_ERROR);
        if ($row !== false && !is_array($row))
            trigger_error('Argument 5 passed to '.__METHOD__.' must be a Array, '.gettype($row).' given', E_USER_ERROR);
        if (!empty($l) and !preg_match(',[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*,',$l))
            trigger_error('Argument 1 passed to '.__METHOD__.' must be compatible with PHP variable names , `'.$l.'` given', E_USER_ERROR);

        $this->_label   = $l;
        $this->_type    = 2;
        $this->_item_id = $i;

        if ($id === false) {
            if (! $this->_checkTag($this->_item_id, self::ITEM)) {
                trigger_error('Argument 2 passed to '.__METHOD__.' not describe a "tagtype"', E_USER_ERROR);
            }
            $this->_id = $this->_addTag($this->_label, 2, $row);
            $this->callClassCallback('addHook', $this);

            if ($row !== false)
                foreach($this->_cols as $n => $t)
                    if (isset($row[$n]))
                        $this->_set($n, $row[$n]);

            if ($this->_checkTagged($this->_id, $this->_item_id) === false) {
                $this->_addTagged($this->_id, $this->_item_id);
                // Ne Pas incrémenter la fréquence, car elle sert à compter le nombre d'items
            }
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

   // {{{ newTag
    /**
     * Crée un tag, son label étant calculé automatiquement
     *
     * @return AIT_Tag
     */
    function newTag()
    {
        $o = new AIT_Tag('NEW', $this->_id, null, $this->getPDO());
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
    function addTag($l, $r = false)
    {
        if (!is_string($l))
            trigger_error('Argument 1 passed to '.__METHOD__.' must be a string, '.gettype($l).' given', E_USER_ERROR);

        return new AIT_Tag($l, $this->_id, null, $this->getPDO(), false, $r);
    }
    // }}}

    // {{{ getTag
    /**
    * Récupère un tag
     *
     * @param string $l nom du tag
     *
     * @return AIT_Tag
     */
    function getTag($l)
    {
        if (!is_string($l))
            trigger_error('Argument 1 passed to '.__METHOD__.' must be a string, '.gettype($l).' given', E_USER_ERROR);

        $sql = sprintf('
            SELECT id, label, type
            FROM %s tag
            WHERE label = ? AND type = ?
            LIMIT 0,1
            ',
            $this->getPDO()->tag()
        );

        if (($r = $this->callClassCallback(
            'getTagCache',
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
            $ret = new AIT_Tag($row['label'], $row['type'], null, $this->getPDO(), $row['id'], $row);
        }
        else $ret = null;
        $stmt->closeCursor();
        self::debug(self::timer(true), $sql, $l, $this->_id);

        if (isset($cid))
            $this->callClassCallback('getTagCache', $cid, $ret);

        return $ret;
    }
    // }}}

    // {{{ defTag
    /**
     * Récupére un tag du type courant
     * Si le tag n'existe pas il est automatiquement créé.
     *
     * @param string $l nom du tag
     *
     * @return AIT_Tag
     */
    function defTag($l)
    {
        $ret = $this->getTag($l);
        if (is_null($ret))
            $ret = new AIT_Tag($l, $this->_id, null, $this->getPDO());
        return $ret;
    }
    // }}}

    // {{{ getTags
    /**
     * Récupére tous les tags du type de tag courant
     *
     * @param integer $offset décalage à parir du premier enregistrement
     * @param integer $lines nombre de lignes à retourner
     * @param integer $ordering flag permettant le tri
     * @param array    $cols filtre sur les champs complémentaires
     *
     * @return AITResult
     */
    function getTags($offset = null, $lines = null, $ordering = null, $cols = array())
    {
        if (!is_null($offset) && !is_int($offset))
            trigger_error('Argument 1 passed to '.__METHOD__.' must be a integer, '.gettype($offset).' given', E_USER_ERROR);
        if (!is_null($lines) && !is_int($lines))
            trigger_error('Argument 2 passed to '.__METHOD__.' must be a integer, '.gettype($offset).' given', E_USER_ERROR);
        if (!is_null($ordering) && !is_int($ordering))
            trigger_error('Argument 3 passed to '.__METHOD__.' must be a integer, '.gettype($ordering).' given', E_USER_ERROR);
        if (!is_array($cols))
            trigger_error('Argument 4 passed to '.__METHOD__.' must be a array'.gettype($cols).' given', E_USER_ERROR);

        $sql1 = 'SELECT id, label, prefix, suffix, buffer, scheme, language, score, frequency, content';
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
            'getTagsCache',
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
            $ret[] = new AIT_Tag($row['label'], $this->_id, null, $this->getPDO(), $row['id'], $row);
        }
        $stmt->closeCursor();
        self::debug(self::timer(true), $sql, $this->_id);

        $sql = 'SELECT COUNT(*) '.$sql2;
        $r = new AITResult($ret);
        $r->setQueryForTotal($sql, array($this->_id => PDO::PARAM_INT,), $this->getPDO());

        if (isset($cid))
            $this->callClassCallback('getTagsCache', $cid, $r);

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

        if (($r = $this->callClassCallback(
            'getTagBySystemIDCache',
            $cid = self::str2cid($i)
        )) !== false) return $r;

        $row = $this->_getTagBySystemID($i);

        if (is_array($row)) {
            $r = new AIT_Tag($row['label'], $row['type'], null, $this->getPDO(), $i);
            if (isset($cid))
                $this->callClassCallback('getTagBySystemIDCache', $cid, $r);
            return $r;
        }
    }
    // }}}

    // {{{ searchTags
    /**
     * Recherche des tags du type courant
     *
     * @param string  $query requete (le format dépend de la search_callback) sans callback c'est du SQL
     * @param integer $offset décalage à parir du premier enregistrement
     * @param integer $lines nombre de lignes à retourner
     * @param integer $ordering flag permettant le tri
     * @param array   $cols filtre sur les champs complémentaires
     *
     * @return AITResult
     */
    function searchTags($query, $offset = null, $lines = null, $ordering = null, $cols = array())
    {
        if (!is_null($offset) && !is_int($offset))
            trigger_error('Argument 2 passed to '.__METHOD__.' must be a integer, '.gettype($offset).' given', E_USER_ERROR);
        if (!is_null($lines) && !is_int($lines))
            trigger_error('Argument 3 passed to '.__METHOD__.' must be a integer, '.gettype($lines).' given', E_USER_ERROR);
        if (!is_null($ordering) && !is_int($ordering))
            trigger_error('Argument 4 passed to '.__METHOD__.' must be a integer, '.gettype($ordering).' given', E_USER_ERROR);
        if (!is_array($cols))
            trigger_error('Argument 5 passed to '.__METHOD__.' must be a array'.gettype($cols).' given', E_USER_ERROR);

        if ($this->isClassCallback('searchTagsHook'))
            $query = $this->callClassCallback('searchTagsHook', $query, $this);

        if ($query !== '' and $query !== false) $query = 'AND '.$query;
        $sql1 = 'SELECT id, label, prefix, suffix, buffer, scheme, language, score, frequency, content';
        $sql2 = sprintf('
            FROM %1$s tag
            LEFT JOIN %2$s dat ON tag.dat_hash=dat.hash
            WHERE tag.type = ? %3$s
            ',
            $this->getPDO()->tag(),
            $this->getPDO()->dat(),
            $query
        );
        $sql = $sql1.$sql2.$this->filter($cols);
        self::sqler($sql, $offset, $lines, $ordering);
        self::timer();

        if (($r = $this->callClassCallback(
            'searchTagsCache',
            $cid = self::str2cid($sql, $this->_id)
        )) !== false) return $r;

        $stmt = $this->getPDO()->prepare($sql);
        $stmt->bindParam(1, $this->_id, PDO::PARAM_INT);
        $stmt->execute();
        settype($this->_id, 'integer');
        $ret = array();
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
            if (is_null($row['id'])) continue;
            settype($row['id'], 'integer');
            $ret[] = new AIT_Tag($row['label'], $this->_id, null, $this->getPDO(), $row['id'], $row);
        }
        $stmt->closeCursor();
        self::debug(self::timer(true), $sql, $this->_id, $this->_id);

        $sql = 'SELECT COUNT(*) '.$sql2;
        $r = new AITResult($ret);
        $r->setQueryForTotal($sql, array($this->_id => PDO::PARAM_INT,), $this->getPDO());

        if (isset($cid))
            $this->callClassCallback('searchTagsCache', $cid, $r);

        return $r;
    }
    // }}}

    // {{{ countTags
    /**
     * Compte le nombre de tags du type de tag courant
     *
     * @return integer
     */
    function countTags()
    {
        try {
            $sql = sprintf('
                SELECT count(*)
                FROM %s tag
                WHERE type = ?
                ',
                $this->getPDO()->tag()
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
    // }}}

    // {{{ countItems
    /**
     * Compte le nombre d'item attaché au tag du 'type' de tag courant
     *
     * @param boolean $reload récupére la valeur en base et non celle du cache de l'objet
     *
     * @return integer
     */
    function countItems($reload = true)
    {
        return (int) $this->_get('frequency', $reload);
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
            $tag->del($cascade);
        }
        $this->_rmTagged($this->_id, null);
        $this->_rmTag();
    }
    // }}}

    // {{{ selectTags
    /**
     * Selectionne des tags du type courant
     *
     * @param string  $query requete (le format dépend de la search_callback) sans callback c'est du SQL
     * @param integer $offset décalage à parir du premier enregistrement
     * @param integer $lines nombre de lignes à retourner
     * @param integer $ordering flag permettant le tri
     * @param array   $cols filtre sur les champs complémentaires
     *
     * @return AITResult
     */
    function selectTags($query, $offset = null, $lines = null, $ordering = null, $cols = array())
    {
        return $this->searchTags($query, $offset, $lines, $ordering, $cols);
    }
    // }}}

}
