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
require_once 'AIT/ItemType.php';
require_once 'AIT/Item.php';
require_once 'AIT/TagType.php';
require_once 'AIT/Tag.php';


/**
 * Objet représantant un schéma au sens AIT
 *
 * @category  AIT
 * @package   AIT
 * @author    Nicolas Thouvenin <nthouvenin@gmail.com>
 * @copyright 2008 Nicolas Thouvenin
 * @license   http://opensource.org/licenses/lgpl-license.php LGPL
 * @link      http://www.pxxo.net/fr/ait
 */
class AITSchema {

    private $_pointers = array();
    // {{{ __construct
    /**
     * purifie une chaine de carctère représantant un type en une chaine compatible php
     *
     * @param PDOAIT  connexion à la base
     * @param string Type d'Item
     * @param array tableau de type de tag
     */
    function __construct(PDOAIT $pdo, $name, array $attributs)
    {
        $n = $this->_toVarname($name);
        $this->_pointers[$n] = new AIT_ItemType($name, $pdo);

        foreach($attributs as $attr) {
            $a = $this->_toVarname($attr);
            $this->_pointers[$a] = $this->_pointers[$n]->getTag($attr);
            if (is_null($this->_pointers[$a])) {
                $this->_pointers[$a] = $this->_pointers[$n]->addTag($attr);
            }
        }
    }
    // }}}
    // {{{ _toVarname
    /**
     * purifie une chaine de carctère représantant un type en une chaine compatible php
     *
     * @param string $name
     *
     * @return string
     */
    private function _toVarname($s)
    {
        return '_'.md5(trim(strtolower($s)));
    }
    // }}}
    // {{{ __get
    /**
     * Retroune un objet AIT
     *
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name) {
        $n = $this->_toVarname($name);
        if (array_key_exists($n, $this->_pointers))
            return $this->_pointers[$n];
        else
            return null;
    }
    // }}}
}



/**
 * Objet de connexion à la base
 *
 * @category  AIT
 * @package   AIT
 * @author    Nicolas Thouvenin <nthouvenin@gmail.com>
 * @copyright 2008 Nicolas Thouvenin
 * @license   http://opensource.org/licenses/lgpl-license.php LGPL
 * @link      http://www.pxxo.net/fr/ait
 */
class PDOAIT extends PDO
{
    private $_options = array(
        'dsn'      => '',
        'username' => '',
        'password' => '',
        'drvropts' => array(),
        'prefix'      => '',
        'opt'      => 'opt',
        'tag'      => 'tag',
        'tagged'   => 'tagged',
        'space_callback' => array(),
        'query_callback' => array(),
        'class_callback' => array(),
    );

    // {{{ __construct
    /**
     * Constructeur
     *
     * @param string $dsn chaine de connexion
     * @param string $username user de connexion
     * @param string $password mot de passe de connexion
     * @param array $driver_options options pour pdo
     */
    public function __construct($dsn, $username = null, $password = null, $driver_options = null)
    {
        parent::__construct($dsn, $username, $password, $driver_options);
        $this->setOption('dsn',      $dsn);
        $this->setOption('username', $username);
        $this->setOption('password', $password);
        $this->setOption('drvropts', $driver_options);
    }
    // }}}
    // {{{ extendsWith
    /**
     * Ajoute à AIT un module complémentaire
     *
     * @param AIT_Extended $o
     *
     * @return PDOAIT
     */
    public function extendWith(AIT_Extended $o)
    {
        $o->register($this);
        return $this;
    }
    // }}}
    // {{{ getOption
    /**
     * renvoit la valeur d'une option
     *
     * @param string $name name
     * @return	string
     */
    public function getOption($n)
    {
        if (isset($this->_options[$n])) {
            return $this->_options[$n];
        }
    }
    // }}}
    // {{{ getOptions
    /**
     * Retourne les options
     *
     * @param array $a tableau d'option
     */
    public function getOptions()
    {
        return $this->_options;
    }
    // }}}
    // {{{ setOption
    /**
     * Fixe une option
     *
     * @param string $name nom
     * @param string $value valeur
     * @return	string
     */
    public function setOption($n, $v)
    {
        if (isset($this->_options[$n]) && !is_null($v)) {
            $this->_options[$n] = $v;
        }
    }
    // }}}
    // {{{ setOptions
    /**
     * Fixe les options
     *
     * @param array $a tableau d'option
     */
    public function setOptions(array $a)
    {
        foreach($a as $n => $v) $this->setOption($n, $v);
    }
    // }}}
    // {{{ opt
    /**
     * Renvoit le nom de la table ait
     *
     * @return	string
     */
    public function opt()
    {
        return $this->_options['prefix'].$this->_options['opt'];
    }
    // }}}
    // {{{ tag
    /**
     * Renvoit le nom de la table tag
     *
     * @param   boolean
     * @return	string
     */
    public function tag($crud = false)
    {
        return $this->_options['prefix'].$this->_options['tag'];
    }
    // }}}
    // {{{ tagged
    /**
     * Renvoit le nom de la table tagged
     *
     * @param   boolean
     * @return	string
     */
    public function tagged($crue = false)
    {
        return $this->_options['prefix'].$this->_options['tagged'];
    }
    // }}}
    // {{{ checkup
    /**
     * Controle la validité de la structure de données
     *
     * @param boolean $init Lance ou non l'initaliasation automatatique (par défaut true)
     *
     * @return boolean
     */
    public function checkup($init = true)
    {
        try {
            $sql = sprintf(
                "SELECT count(*) n FROM %s WHERE label='tag' or label='item' LIMIT 0,1;",
                $this->tag());
            $stmt = $this->query($sql);

            $c = (int)$stmt->fetchColumn(0);
            $stmt->closeCursor();
            if ($c === 0 and $init) $this->_initData();
            elseif ($c === 0 and !$init) return false;
        }
        catch (PDOException $e) {
            if ($init) $this->_initTable();
            else return false;
        }
        return true;
    }
    // }}}
    // {{{ registerSchema
    /**
     * Enregistre un schema AIT (soit un type d'item associé à des types de tags)
     *
     * @param string Type d'Item
     * @param array tableau de type de tag
     *
     * @return AITSchema
     */
    private function registerSchema($name, array $attr)
    {
        return new AITSchema($this, $name, $attr);
    }
    // }}}
    // {{{ _initTable
    /**
     * Initialise la structure de données
     */
    private function _initTable()
    {
        try {
            $driver = strtolower($this->getAttribute(PDO::ATTR_DRIVER_NAME));
            switch ($driver) {
            case 'mysql':
                $this->exec(sprintf("
                    CREATE TABLE %s (
                        id INTEGER(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                        label VARCHAR(200) COLLATE latin1_general_cs NOT NULL,
                        space VARCHAR(200) COLLATE latin1_general_cs NULL,
                        score INTEGER(10) NOT NULL default '0',
                        frequency INTEGER(10) UNSIGNED NOT NULL default '0',
                        type INT NULL,
                        updated timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
                        created timestamp NOT NULL default '0000-00-00 00:00:00',
                        INDEX (label),
                        FULLTEXT (space),
                        INDEX (score),
                        INDEX (type),
                        INDEX (type,created),
                        INDEX (type,updated),
                        INDEX (type,score),
                        INDEX (type,frequency)
                    ) ENGINE=MYISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_cs;
                ", $this->tag(true)));
                $this->exec(sprintf("
                    CREATE TABLE %s (
                        tag_id INTEGER(11) UNSIGNED NOT NULL,
                        item_id INTEGER(11) UNSIGNED NOT NULL,
                        PRIMARY KEY (tag_id, item_id),
                INDEX (tag_id),
                INDEX (item_id)
            ) ENGINE=MYISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_cs;
                ", $this->tagged(true)));
                $this->exec(sprintf("
                    CREATE TABLE %s (
                        name VARCHAR(10) COLLATE latin1_general_cs NOT NULL,
                        value VARCHAR(200) COLLATE latin1_general_cs NOT NULL,
                        PRIMARY KEY (name)
                    ) ENGINE=MYISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_cs;
                ", $this->opt(true)));
                $this->exec(sprintf("
                    INSERT INTO %s VALUES ('version', '%s');
                ", $this->opt(true), AIT::VERSION));
                $this->_initData();
                break;
            default:
                throw new Exception($driver.' not supported by AIT.');
            }
        }
        catch (PDOException $e) {
            AIT::catchError($e);
        }
    }
    // }}}
    // {{{ _initData
    /**
     * Initialise les données obligatoires
     * @param PDO $this Pointeur sur la base de données
     */
    private function _initData()
    {
        try {
            $this->exec(sprintf(
                "INSERT INTO %s VALUES (1, 'item', null, 0, 0, null, now(), now());",
                $this->tag(true)
            ));
            $this->exec(sprintf(
                "INSERT INTO %s VALUES (2, 'tag', null, 0, 0, null, now(), now());",
                $this->tag(true)
            ));
        }
        catch (PDOException $e) {
            AIT::catchError($e);
        }
    }
    // }}}

}



/**
 * Classe principale.
 * Utilisé de manière statique, elle gére la connection à la base et son initialisation.
 * Dans les autres cas elle sert de classe abstraite pour toutes les composantes du système.
 *
 * @category  AIT
 * @package   AIT
 * @author    Nicolas Thouvenin <nthouvenin@gmail.com>
 * @copyright 2008 Nicolas Thouvenin
 * @license   http://opensource.org/licenses/lgpl-license.php LGPL
 * @link      http://www.pxxo.net/fr/ait
 */
class AIT
{
    /**
     * @var boolean
     */
    static $debugging = false;
    /**
     * @var integer
     */
    static $time = 0;
    /**
     * @var PDOAIT
     */
    protected $_pdo;
    /**
     * @var array
     */
    protected $_pdo_opt;
    /**
     * @var integer
     */
    protected $_id;
    /**
     * @var string
     */
    protected $_element;
    /**
     * @var string
     */
    protected $_label;
    /**
     * @var integer
     */
    protected $_type;
    /**
     * @var callback
     */
    protected $_fillspace;
    /**
     * @var callback
     */
    protected $_queryspace;
    /**
     * @var callback
     */
    protected $_findspace;
    /**
    * @var array
    */
    protected $_methods = array();
    /**
    * @var array
    */
    protected $_cols  = array('space' => 'string', 'score' => 'integer', 'frequency' => 'integer',);
    /**
    * @var array
    */
    protected $_data  = array();


    const VERSION = '1.0.2';
    const ORDER_ASC = 2;
    const ORDER_DESC = 4;
    const ORDER_BY_LABEL = 8;
    const ORDER_BY_SCORE = 16;
    const ORDER_BY_UPDATED = 32;
	const ORDER_BY_CREATED = 64;
	const ORDER_BY_FREQUENCY = 128;


    // {{{ __construct
    /**
    * Constructeur
    *
    * @param PDOAIT $pdo objet de connexion à la base
    */
    function __construct(PDOAIT $pdo, $element)
    {
        $this->_pdo = $pdo;
        $this->_element = $element;

        $scb = $this->_pdo->getOption('space_callback');
        if (isset($scb[$element])) $this->setSpaceCallback($scb[$element]);
        $qcb = $this->_pdo->getOption('query_callback');
        if (isset($qcb[$element])) $this->setSearchCallback($qcb[$element]);
        $ccb = $this->_pdo->getOption('class_callback');
        if (isset($ccb[$element])) $this->setClassCallback($ccb[$element]);

    }
    // }}}

    // {{{ getPDO
    /**
    * Renvoit l'objet PDO utilisé
    *
    * @return PDO
    */
    public function getPDO()
    {
        return $this->_pdo;
    }
    // }}}

    // {{{ catchError
    /**
    * Attrape toutes les erreurs de l'objet
    *
    * @param integer $m identifiant du tag
    * @param integer $i identifiant du tag
    *
    * @return boolean
    */
    public static function catchError(PDOException $e)
    {
        trigger_error($e->getMessage(), E_USER_ERROR);
    }
    // }}}

    // {{{ connect
    /**
    *  Connection à la base
    *
    * @param string $dsn chaine de connexion
    * @param string $username user de connexion
    * @param string $password mot de passe de connexion
    * @param array $driver_options options pour pdo
    * @param array $ait_options options pour pdo
    *
    *  @return PDO
    */
    public static function connect($dsn, $username = 'root', $password = '', array $driver_options = array(), array $ait_options = array())
    {
        try {
            $pdo = new PDOAIT($dsn, $username, $password, $driver_options);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            AIT::catchError($e);
        }
        $pdo->setOptions($ait_options);
        return $pdo;
    }
    // }}}

    // {{{ setClassCallback
    /**
    * Fixe des callback de class cad l'ajout de nouvelle méthode
    *
    * @access	public
    */
    function setClassCallback(array $a)
    {
        $this->_methods = $a;
    }
    // }}}

    // {{{ setSpaceCallback
    /**
    * Fixe la callback de remplissage du champ space
    *
    * @param callback $c 
    *
    * @access	public
    */
    function setSpaceCallback($c)
    {
        if (is_callable($c, true)) {
            $this->_fillspace = $c;
        }
        else {
            trigger_error('Argument 1 passed to '.__METHOD__.' must be a valid callback', E_USER_ERROR);
        }
    }
    // }}}

    // {{{ setSearchCallback
    /**
     * Fixe la callback de traitement du paramètre query des méthodes searchXXX
     *
     * @param callback $c 
    *
    * @access	public
    */
    function setSearchCallback($c)
    {
        if (is_callable($c, true)) {
            $this->_queryspace = $c;
        }
        else {
            trigger_error('Argument 1 passed to '.__METHOD__.' must be a valid callback', E_USER_ERROR);
        }
    }
    // }}}

    // {{{ ren
    /**
    * Renomme l'élement courrant
    *
    * @param string $l nouveau label
    */
    function ren($l)
    {
        if ($l !== $this->_label) {
            $this->_label = $l;
            $this->_set('label', $this->_label);
            $s = '';
            if (is_callable($this->_fillspace)) {
                $s = call_user_func($this->_fillspace, $l, $this);
            }
            if (!is_string($s)) {
                trigger_error('fillspace callback must return string, `'.gettype($s).'` is given', E_USER_ERROR);
            }
            $this->_set('space', $s);

        }
    }
    // }}}


    // {{{ exists
    /**
    * Verifie l'existence de l'élement courrant
    */
    function exists()
    {
        try {
            $sql = sprintf("SELECT count(*) FROM %s WHERE id=? LIMIT 0,1", $this->_pdo->tag());

            $stmt = $this->_pdo->prepare($sql);
            $stmt->bindParam(1, $this->_id, PDO::PARAM_INT);
            $stmt->execute();
            settype($this->_id, 'integer');

            $c = (int)$stmt->fetchColumn(0);
            $stmt->closeCursor();
            if ($c > 0) return true;
            else return false;
        }
        catch (PDOException $e) {
            self::catchError($e);
        }
    }
    // }}}

    // {{{ _checkTag
    /**
    * Vérifie l'existance et le type d'un tag
    * retourne  false en cas de problème
    *
    * @param integer $i identifiant du tag
    * @param integer $t identifiant de son type
    *
    * @return boolean
    */
    protected function _checkTag($i, $t)
    {
        try {
            $sql = sprintf("SELECT count(*) n FROM %s WHERE id=? AND type=? LIMIT 0,1", $this->_pdo->tag());

            $stmt = $this->_pdo->prepare($sql);
            $stmt->bindParam(1, $i, PDO::PARAM_INT);
            $stmt->bindParam(2, $t, PDO::PARAM_INT);
            $stmt->execute();
            settype($i, 'integer');
            settype($t, 'integer');

            $c = (int)$stmt->fetchColumn(0);
            $stmt->closeCursor();
            if ($c > 0) return true;
            else return false;
        }
        catch (PDOException $e) {
            self::catchError($e);
        }
    }
    // }}}

    // {{{ _checkType
    /**
    * Vérifie l'existance d'un type d'un tag
    *
    * @param integer $t identifiant de son type
    *
    * @return boolean
    */
    protected function _checkType($t)
    {
        try {
            $sql = sprintf("SELECT count(*) FROM %s WHERE id=? LIMIT 0,1", $this->_pdo->tag());

            $stmt = $this->_pdo->prepare($sql);
            $stmt->bindParam(1, $t, PDO::PARAM_INT);
            $stmt->execute();

            $c = (int)$stmt->fetchColumn(0);
            $stmt->closeCursor();
            if ($c > 0) return true;
            else return false;
        }
        catch (PDOException $e) {
            self::catchError($e);
        }
    }
    // }}}

    // {{{ _checkTagged
    /**
    * Vérifie l'existance de l'association d'un tag et d'un item
    *
    * @param integer $m identifiant du tag
    * @param integer $i identifiant de l'item
    *
    * @return boolean
    */
    protected function _checkTagged($m, $i)
    {
        try {
            $sql = sprintf(
                "SELECT count(*) n FROM %s WHERE tag_id=? and item_id=? LIMIT 0,1",
                $this->_pdo->tagged()
            );

            $stmt = $this->_pdo->prepare($sql);
            $stmt->bindParam(1, $m, PDO::PARAM_INT);
            $stmt->bindParam(2, $i, PDO::PARAM_INT);
            $stmt->execute();
            settype($m, 'integer');
            settype($i, 'integer');

            $c = (int)$stmt->fetchColumn(0);
            $stmt->closeCursor();
            if ($c > 0) return true;
            else return false;
        }
        catch (PDOException $e) {
            self::catchError($e);
        }
    }
    // }}}

    // {{{ _increaseFrequency
    /**
    * Incremente la frequence d'une ligne dans TAG
    *
    * @param string $t id
    */
    protected function _increaseFrequency($i)
    {
        try {
            if (isset($this->_data['frequency'])) unset($this->_data['frequency']);
            $sql = sprintf(
                "UPDATE %s SET frequency=frequency+1 WHERE id = ?",
                $this->_pdo->tag(true)
            );
            self::timer();
            $stmt = $this->_pdo->prepare($sql);
            $stmt->bindParam(1, $i, PDO::PARAM_INT);
            $stmt->execute();
            $stmt->closeCursor();
            self::debug(self::timer(true), $sql, $i);
        }
        catch (PDOException $e) {
            self::catchError($e);
        }
    }
    // }}}

    // {{{ _decreaseFrequency
    /**
    * Incremente la frequence d'une ligne dans TAG
    *
    * @param string $t id
    */
    protected function _decreaseFrequency($i)
    {
        try {
            if (isset($this->_data['frequency'])) unset($this->_data['frequency']);
            $sql = sprintf(
                "UPDATE %s SET frequency=frequency-1 WHERE id = ?",
                $this->_pdo->tag(true)
            );
            self::timer();
            $stmt = $this->_pdo->prepare($sql);
            $stmt->bindParam(1, $i, PDO::PARAM_INT);
            $stmt->execute();
            $stmt->closeCursor();
            self::debug(self::timer(true), $sql, $i);
        }
        catch (PDOException $e) {
            self::catchError($e);
        }
    }
    // }}}



    // {{{ _addTagged
    /**
    * Ajout d'une ligne dans la table tagged
    *
    * @param string $t tag id
    * @param string $i item id
    */
    protected function _addTagged($t, $i)
    {
        try {
            $sql = sprintf(
                "INSERT INTO %s VALUES (?, ?);",
                $this->_pdo->tagged(true)
            );
            self::timer();
            $stmt = $this->_pdo->prepare($sql);
            $stmt->bindParam(1, $t, PDO::PARAM_INT);
            $stmt->bindParam(2, $i, PDO::PARAM_INT);
            $stmt->execute();
            $stmt->closeCursor();
            self::debug(self::timer(true), $sql, $t, $i);
        }
        catch (PDOException $e) {
            self::catchError($e);
        }
    }
    // }}}

    // {{{ _rmTagged
    /**
    * Supprime une ligne dans la table tagged
    *
    * @param string $t tag id
    * @param string $i item id
    */
    protected function _rmTagged($t, $i)
    {
        try {
            if (is_null($t) and !is_null($i)) {
                $field = 'item_id';
                $value = $i;
            }
            elseif (!is_null($t) and is_null($i)) {
                $field = 'tag_id';
                $value = $t;
            }
            else
                throw new Exception('Bad Arguments');

            $sql = sprintf(
                "DELETE FROM %s WHERE %s=?",
                $this->_pdo->tagged(true),
                $field
            );
            self::timer();
            $stmt = $this->_pdo->prepare($sql);
            $stmt->bindParam(1, $value, PDO::PARAM_INT);
            $stmt->execute();
            $stmt->closeCursor();
            self::debug(self::timer(true), $sql, $this->_id);
        }
        catch (PDOException $e) {
            self::catchError($e);
        }
    }
    // }}}

    // {{{ _rmTag
    /**
    * Suppression d'une ligne dans tag
    *
    * @param string $i id
    */
    protected function _rmTag($i)
    {
        try {
            $sql = sprintf(
                "DELETE FROM %s WHERE id=?", 
                $this->_pdo->tag(true)
            );
            self::timer();
            $stmt = $this->_pdo->prepare($sql);
            $stmt->bindParam(1, $this->_id, PDO::PARAM_INT);
            $stmt->execute();
            settype($this->_id, 'integer');
            $stmt->closeCursor();
            self::debug(self::timer(true), $sql, $this->_id);

            $this->_id = null;
        }
        catch (PDOException $e) {
            self::catchError($e);
        }
    }
    // }}}

    // {{{ _addTag
    /**
    * Ajout d'une ligne dans la table tag
    *
    * @param string $l label
    * @param string $t type
    *
    * @return integer
    */
    protected function _addTag($l, $t = null)
    {
        try {
            $sql = sprintf("SELECT id FROM %s WHERE label=? and type=?  LIMIT 0,1", $this->_pdo->tag());

            $stmt = $this->_pdo->prepare($sql);
            $stmt->bindParam(1, $l, PDO::PARAM_STR);
            $stmt->bindParam(2, $t, PDO::PARAM_INT);
            $stmt->execute();
            settype($t, 'integer');

            $id = $stmt->fetchColumn();
            $stmt->closeCursor();

            if ($id !== false) return (int) $id;

            $s = '';
            if (is_callable($this->_fillspace)) {
                $s = call_user_func($this->_fillspace, $l, $this);
            }
            if (!is_string($s)) {
                trigger_error('fillspace callback must return string, `'.gettype($s).'` is given', E_USER_ERROR);
            }

            self::timer();
            if (is_null($t)) {
                $sql = sprintf(
                    "INSERT INTO %s (label, space, updated, created) VALUES (?, ?, now(), now());",
                    $this->_pdo->tag(true)
                );
                $stmt = $this->_pdo->prepare($sql);
                $stmt->bindParam(1, $l, PDO::PARAM_STR);
                $stmt->bindParam(2, $s, PDO::PARAM_STR);
            }
            else {
                $sql = sprintf(
                    "INSERT INTO %s (label, space, type, updated, created) VALUES (?, ?, ?, now(), now());",
                    $this->_pdo->tag(true)
                );
                $stmt = $this->_pdo->prepare($sql);
                $stmt->bindParam(1, $l, PDO::PARAM_STR);
                $stmt->bindParam(2, $s, PDO::PARAM_STR);
                $stmt->bindParam(3, $t, PDO::PARAM_INT);
            }
            $ret = $stmt->execute();
            $stmt->closeCursor();
            self::debug(self::timer(true), $sql, $l, $s, $t);

            $id = (int) $this->_pdo->lastInsertId();

        }
        catch (PDOException $e) {
            self::catchError($e);
        }
        return $id;
    }
    // }}}

    // {{{ _addTag
    /**
    * Retroune une ligne dans la table tag à partir de l'identifiant physique
    *
    * @param integer $i
    *
    * @return array
     */
    protected function _getTagBySystemID($i)
    {
        try {
                $sql = sprintf("
                    SELECT id, label, type
                    FROM %s
                    WHERE id = ?
                    LIMIT 0,1
                    ",
                    $this->_pdo->tag()
                );
                self::timer();
                $stmt = $this->_pdo->prepare($sql);
                $stmt->bindParam(1, $i, PDO::PARAM_INT);
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $stmt->closeCursor();
                self::debug(self::timer(true), $sql, $i);

                if (is_array($row)) {
                    settype($row['type'], 'integer');
                    settype($row['id'], 'integer');
                }

                return $row;
            }
            catch (PDOException $e) {
                self::catchError($e);
            }
    }
    // }}}

    // {{{ _set
    /**
    * Méthode permettant de changer la valeur d'une colonne
    *
    * @param string $name nom de la colonne
    * @param mixed $value valeur de la colonne
    */
    protected function _set($n, $v)
    {
        $this->_data[$n] = $v;
        try {
            self::timer();
            $sql = sprintf("UPDATE %s set %s=? WHERE id=?", $this->_pdo->tag(true), $n);
            $stmt = $this->_pdo->prepare($sql);
            $typ = gettype($v);
            if ($typ === 'string')
                $stmt->bindParam(1, $v, PDO::PARAM_STR);
            elseif ($typ === 'integer')
                $stmt->bindParam(1, $v, PDO::PARAM_INT);
            else
                throw new Exception('type not supported (`'.$typ.'`)');
            $stmt->bindParam(2, $this->_id, PDO::PARAM_INT);

            $stmt->execute();
            settype($this->_id, 'integer');
            $stmt->closeCursor();
            self::debug(self::timer(true), $sql, $v, $this->_id);
        }
        catch (PDOException $e) {
            self::catchError($e);
        }
    }
    // }}}

    // {{{ _fill
    /**
     * Méthode permettant de charger les propritétes de l'objet 
     * à partir d'un atbleau de données
    *
    * @param array $a
    */
    protected function _fill($a)
    {
        foreach($this->_cols as $n => $t)
            if (isset($a[$n])) {
                $this->_data[$n] = $a[$n];
                settype($this->_data[$n], $t);
            }
    }

    // {{{ _get
    /**
    * Méthode permettant d'accéder à la valeur d'une colonne
    *
    * @param string $name nom de la colonne
    *
    * @return mixed
    */
    protected function _get($n)
    {
        if (!isset($this->_data[$n])) {
            try {
                $sql = sprintf("SELECT %s FROM %s WHERE id=? LIMIT 0,1", $n, $this->_pdo->tag());
                self::timer();
                $stmt = $this->_pdo->prepare($sql);
                $stmt->bindParam(1, $this->_id, PDO::PARAM_INT);
                $stmt->execute();
                settype($this->_id, 'integer');
                $this->_data[$n] = $stmt->fetchColumn(0);
                $stmt->closeCursor();
                self::debug(self::timer(true), $sql, $this->_id);
            }
            catch (PDOException $e) {
                self::catchError($e);
            }
        }
        return $this->_data[$n];
    }
    // }}}

    // {{{ getSystemID
    /**
    * getSystemID
    *
    * @return	integer
    */
    public function getSystemID()
    {
        return $this->_id;
    }
    // }}}

    // {{{ get
    /**
    * Getter
    *
    * @param string $name nom de l'attribut
    *
    * @return	mixed
    */
    public function get($name = 'label')
    {
        $attr = array(
            'internal_id' => 'id',
            'type'       => 'type',
            'label'      => 'label',
            'value'      => 'label',
        );

        if (isset($attr[$name])) {
            $name = '_'.$attr[$name];
            return $this->$name;
        }
        if (isset($this->_cols[$name])) {
            return $this->_get($name);
        }
    }
    // }}}

    // {{{ getTimestamps
    /**
    * getTimestamps
    *
    * @return ArrayObject
    */
    public function getTimestamps()
    {
        try {
            $sql = sprintf("SELECT UNIX_TIMESTAMP(updated), UNIX_TIMESTAMP(created) FROM %s WHERE id=? LIMIT 0,1", $this->_pdo->tag());
            $stmt = $this->_pdo->prepare($sql);
            $stmt->bindParam(1, $this->_id, PDO::PARAM_INT);
            $stmt->execute();
            settype($this->_id, 'integer');
            $ret = $stmt->fetch();
            $stmt->closeCursor();
            return new ArrayObject( 
                array(
                    'updated' => (int) $ret[0],
                    'created' => (int) $ret[1],
                ), ArrayObject::ARRAY_AS_PROPS
            );
        }
        catch (PDOException $e) {
            self::catchError($e);
        }
    }
    // }}}

    // {{{ getScore
    /**
    * Revoit le score de l'élement
    *
    * @return integer
    */
    public function getScore()
    {
        return (int) $this->_get('score');
    }
    // }}}

    // {{{ setScore
    /**
    * Revoit le score de l'élement
    *
    * @param integer $i
    */
    public function setScore($i)
    {
        if (!is_int($i))
        trigger_error('Argument 1 passed to '.__METHOD__.' must be a integer, '.gettype($i).' given', E_USER_ERROR);
        $this->_set('score', $i);
    }
    // }}}

    // {{{ setScore
    /**
    * Renvoit la frequence de l'élement
    *
    * @param integer $i
    */
    public function setFrequency($i)
    {
        if (!is_int($i))
        trigger_error('Argument 1 passed to '.__METHOD__.' must be a integer, '.gettype($i).' given', E_USER_ERROR);
        $this->_set('frequency', $i);
    }
    // }}}


    // {{{ sqler
    /**
    *Ajout les close ORDER et LIMIT à une requete sql
    *
    * @param integer $offset décalage à parir du premier enregistrement
    * @param integer $lines nombre de lignes à retourner
    * @param integer $ordering flag permettant le tri
    *
    * @return string
    */
    public static function sqler(&$sql, $offset, $lines, $ordering)
    {
        if (!is_null($ordering)) {
            $sql .= ' ORDER BY';
            if ( (self::ORDER_BY_LABEL & $ordering) === self::ORDER_BY_LABEL)
            $sql .= ' label';
            elseif ( (self::ORDER_BY_SCORE & $ordering) === self::ORDER_BY_SCORE)
                $sql .= ' score';
            elseif ( (self::ORDER_BY_UPDATED & $ordering) === self::ORDER_BY_UPDATED)
                $sql .= ' updated';
            elseif ( (self::ORDER_BY_CREATED & $ordering) === self::ORDER_BY_CREATED)
                $sql .= ' created';
            elseif ( (self::ORDER_BY_FREQUENCY & $ordering) === self::ORDER_BY_FREQUENCY)
                $sql .= ' frequency';
            else
                $sql .= ' id';

            if ( (self::ORDER_ASC & $ordering) === self::ORDER_ASC)
            $sql .= ' ASC';
            elseif ( (self::ORDER_DESC & $ordering) === self::ORDER_DESC)
            $sql .= ' DESC';
        }
        if (!is_null($offset) && !is_null($lines)) {
            $sql .= sprintf(' LIMIT %d,%d', (int)$offset, (int)$lines);
        }
    }
    // }}}

    // {{{ debug
    /**
    * DEBUG
    *
    */
    public static function debug()
    {
        if (self::$debugging === true)  {
            $argc = func_num_args();
            for ($i = 0; $i < $argc; $i++) {
                $value = func_get_arg($i);
                echo implode(' ', array_map('trim',explode("\n",$value))). ($i < $argc - 1 ? ' / ' : '');
                
            }
            echo substr(php_sapi_name(), 0, 3) == 'cli'  ? "\n" : "<br/>";
        }
    }
    // }}}

    // {{{ debug
    /**
    * timer
    *
    */
    public static function timer($compute = false)
    {
        static $t = 0;
        if (self::$debugging === true)  {
            if ($compute === false || $t === 0) {
                $t = microtime(true);
            }
            else {
                $t = microtime(true) - $t;
                self::$time += $t;
            }
            return $t;
        }
    }
    // }}}



    // {{{ dump
    /**
    * Dump
    *
    * @param string $s chaine de caratcère à afficher
    * @param booelan $r si vrai a retrouren la chaine à afficher plutot que de l'afficher
    */
    public function dump($s = '', $r = false)
    {
        $buf = '';
        if (!$r) {
            $buf .= '<pre>';
        }
        $buf .= $s;
        $buf .= "\t [";
        $buf .= $this->_element;
        $buf .= "]\t #";
        $buf .= $this->_id;
        $buf .= "\t @";
        $buf .= $this->_type;
        $buf .= "\t (";
        $buf .= $this->_label;
        $buf .= ')';
        if (!$r) {
            $buf .= "\n";
            $buf .= "</pre>";
        }
        if ($r) return $buf;
        else echo $buf;
    }
    // }}}

    /**
     * Traitement des méthodes ajoutées
     *
     * @param string $name
     * @param array $arguments
     */
    function __call($name, array $arguments)
    {
        if (isset($this->_methods[$name]) &&
            is_callable($this->_methods[$name])
        ) {
            array_unshift($arguments, $this);
            return call_user_func_array($this->_methods[$name], $arguments);
        }
        else {
            trigger_error('Call to undefined method '.__CLASS__.'::'.$name, E_USER_ERROR);
        }
    }


    /** 
     * Avant serialization
     *
     */
    public function __sleep () 
    {
        $this->_pdo_opt = $this->_pdo->getOptions();
        $vars = array_keys(get_object_vars($this));
        unset($vars[array_search('_pdo', $vars)]);
        return $vars;
    }

    /** 
     * Avant unserialization
     *
     */
    public function __wakeup() 
    {
        $this->_pdo = new PDOAIT(
            $this->_pdo_opt['dsn'],
            $this->_pdo_opt['username'],
            $this->_pdo_opt['password'],
            $this->_pdo_opt['drvropts']
        );
        $this->_pdo->setOptions($this->_pdo_opt);
     }
}



/**
 * Objet représantant une requete au sens AIT
 *
 * @category  AIT
 * @package   AIT
 * @author    Nicolas Thouvenin <nthouvenin@gmail.com>
 * @copyright 2008 Nicolas Thouvenin
 * @license   http://opensource.org/licenses/lgpl-license.php LGPL
 * @link      http://www.pxxo.net/fr/ait
 */
class AITQuery {


    protected $sql = '';

    /**
     * @var PDOAIT
     */
    protected $_pdo;

    private $_step = array();


    // {{{ __construct
    /**
     * Constructeur
     *
     * @param PDOAIT $pdo objet de connexion à la base
     */
    function __construct(PDOAIT $pdo)
    {
        $this->_pdo = $pdo;
        $this->clean();
    }
    // }}}

    // {{{ clean
    /**
     * On efface tout et on recommence
     *
     * @return boolean 
     */
    public function clean()
    {
        array_push($this->_step, 'start');
        $this->sql = '';
    }
    // }}}


    // {{{ or
    /**
     * Appique un Or entre 
     *
     * @param ArrayObject
     *
     * @return boolean 
     */
    public function eitheror()
    {
        if (end($this->_step) == 'eitheror') return;
        array_push($this->_step, 'eitheror');
    }
    // }}}

    // {{{ all
    /**
     * Recherche les items ayant tout les tags donnés en paramètres
     * Renvoit false si aucun tag n'a été trouvé dans le tableau d'entrée sinon true. 
     *
     * @param ArrayObject
     *
     * @return boolean 
     */
    public function all(ArrayObject $tags)
    {
        array_push($this->_step, 'all');
        $n = 0;
        $w  = '';
        if ($tags->count() == 0) return false;
        foreach($tags as $tag) {
            if (! $tag instanceof AIT_Tag) {
                trigger_error('Line #'.($n + 1).' of Argument 1 passed to '.__METHOD__.' must be a instance of AIT_Tag, '.gettype($tag).' given and ignored', E_USER_NOTICE);
                continue;
            }
            if (empty($w))  {
                $w = sprintf("tag_id = %s",
                    $tag->getSystemID()
                );
            }
            else {
                $w = sprintf("tag_id = %s AND item_id IN (SELECT item_id FROM %s WHERE %s)",
                    $tag->getSystemID(), 
                    $this->_pdo->tagged(),
                    $w
                );

            }
            $n++;
        }
        if ($n === 0) return false;

        $this->_concat($w);
        return true;
    }
    // }}}

    // {{{ one
    /**
     * Recherche les items ayant au moins l'un des tags passé en paramètres
     * Renvoit false si aucun tag n'a été trouvé dans le tableau d'entrée sinon true. 
     *
     * @param ArrayObject
     *
     * @return boolean 
     */
    public function one($tags)
    {
        array_push($this->_step, 'one');
        $n = 0;
        $w  = '';
        if ($tags->count() == 0) return false;
        foreach($tags as $tag) {
            if (! $tag instanceof AIT_Tag) {
                trigger_error('Line #'.($n + 1).' of Argument 1 passed to '.__METHOD__.' must be a instance of AIT_Tag, '.gettype($tag).' given and ignored', E_USER_NOTICE);
                continue;
            }
            if (!empty($w)) $w .= ' OR ';
            $w .= 'tag_id = '. $tag->getSystemID();
            $n++;
        }
        if ($n === 0) return false;
        $this->_concat($w);

        return true;
    }
    // }}}

    // {{{ getSQL
    /**
     * Retourne le SQL correspondant à la requete 
     *
     * @return string
     */
    public function getSQL()
    {
         return sprintf('SELECT item_id FROM %s WHERE %s', $this->_pdo->tagged(), $this->sql);
    }
    // }}}


    // {{{ _concat
    /**
     * Ajoute une nouvelle condition SQL
     *
     * @return string
     */
    protected function _concat($sql)
    {
        array_pop($this->_step);
        if (end($this->_step) === 'eitheror') {
            if ($this->sql === '') 
                $this->sql = $sql;
            else 
                $this->sql .= ' OR '.$sql;
        }
        else {
            if ($this->sql === '') 
                $this->sql = $sql;
            else 
                $this->sql = sprintf(
                    " (%s) AND item_id IN (SELECT item_id FROM %s WHERE %s)",
                    $sql,
                    $this->_pdo->tagged(),
                    $this->sql
                );
        }
    }
    // }}}

}


/**
 * Objet représantant une requete au sens AIT
 *
 * @category  AIT
 * @package   AIT
 * @author    Nicolas Thouvenin <nthouvenin@gmail.com>
 * @copyright 2008 Nicolas Thouvenin
 * @license   http://opensource.org/licenses/lgpl-license.php LGPL
 * @link      http://www.pxxo.net/fr/ait
 */
class AITResult extends ArrayObject {

    private $_total = 0;
    private $_sql = null;
    private $_params = array();
    private $_pdo = null;

    // {{{ setTotal 
    /**
     * Fixe le nombre total de résultats trouvés
     *
     * @return string
     */
    public function setTotal($i)
    {
        $this->_total = (int) $i;
    }
    // }}}

    // {{{ setQueryForTotal
    /**
     * Fixe le nombre total de résultats trouvés
     *
     * @param string $sql la requete SQL 
     * @param array $params les paramètres nécessaire à la requete
     * @param pdo $pdo pointeur vers la base de données
     */
    public function setQueryForTotal($sql,  $params, $pdo) 
    {
        $this->_sql = $sql;
        $this->_params = $params;
        $this->_pdo = $pdo;
    }
    // }}}

    // {{{ total 
    /**
     * Retourne le nombre total de résultats trouvés
     *
     * @return string
     */
    public function total()
    {
        if (is_null($this->_sql) or !is_array($this->_params))
            return $this->_total;

        $time = AIT::timer();
        $stmt = $this->_pdo->prepare($this->_sql);
        $i = 1;
        foreach($this->_params as $k => $v) {
            $stmt->bindParam($i++, $k, $v);
        }
        $stmt->execute();
        $this->_total = (int) $stmt->fetchColumn(0);
        $stmt->closeCursor();
        AIT::debug(AIT::timer(true), $this->_sql, implode('/', array_keys($this->_params)));

        $this->_sql = null;
        $this->_params = array();

        return $this->_total;
    }
    // }}}
}


