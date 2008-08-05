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
     */
    function __construct($l, $t, PDOAIT $pdo, $id = false)
    {
        parent::__construct($pdo, 'Item');

        if (!is_string($l))
            trigger_error('Argument 1 passed to '.__METHOD__.' must be a string, '.gettype($l).' given', E_USER_ERROR);
        if (!is_null($t) && !is_int($t))
            trigger_error('Argument 2 passed to '.__METHOD__.' must be a integer, '.gettype($t).' given', E_USER_ERROR);
        if ($id !== false && !is_int($id))
            trigger_error('Argument 4 passed to '.__METHOD__.' must be a integer, '.gettype($id).' given', E_USER_ERROR);

        $this->_label = $l;
        $this->_type = $t;
        if ($id !== false) {
            $this->_id = (int) $id;
        }
        elseif ($this->_checkTag($this->_type, 1)) {
            $this->_id = $this->_addTag($this->_label, $this->_type);
        }
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
     * @return ArrayObject
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
     * @return ArrayObject
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
    * @return	ArrayObject
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
            if ($n === 0) return new ArrayObject(array());
            else $w = ' AND ('.$w.')';
        }
        $sql = sprintf("
            SELECT id, label, type
            FROM %s a
            LEFT JOIN %s b ON a.tag_id=b.id
            WHERE item_id = ? %s
            ",
            $this->_pdo->tagged(),
            $this->_pdo->tag(),
            $w
        );
        $this->sqler($sql, $offset, $lines, $ordering);
        $this->debug($sql, $this->_id);
        $stmt = $this->_pdo->prepare($sql);
        $stmt->bindParam(1, $this->_id, PDO::PARAM_INT);
        $stmt->execute();
        settype($this->_id, 'integer');
        $ret = array();
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
            settype($row['type'], 'integer');
            settype($row['id'], 'integer');
            $ret[] = new AIT_Tag($row['label'], $row['type'], $this->_id, $this->_pdo, $row['id']);
        }
        return new ArrayObject($ret);
    }
    // }}}
}



