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
 * */

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
 * @copyright 2009 Nicolas Thouvenin
 * @license   http://opensource.org/licenses/lgpl-license.php LGPL
 * @link      http://ait.touv.fr/
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
 * @copyright 2009 Nicolas Thouvenin
 * @license   http://opensource.org/licenses/lgpl-license.php LGPL
 * @link      http://ait.touv.fr/
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
        'tagged'   => 'rel',
        'dat'      => 'dat',
        'callbacks' => array(),
        'extends' => array(),
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
        $this->_options['extends'][] = $o;
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
            $func = array($this, 'setOption'.ucfirst($n));
            if (is_callable($func)) {
                call_user_func($func, $v);
            }
            else {
                $this->_options[$n] = $v;
            }
        }
    }
    // }}}
    // {{{ setOption
    /**
     * Fixe l'option "callbacks"
     *
     * @param string $value valeur
     * @return	string
     */
    public function setOptionCallbacks($v)
    {
        reset($v);
        while (list($k, ) = each($v)) {
            if (!isset($this->_options['callbacks'][$k])) {
                $this->_options['callbacks'][$k] = $v[$k];
            }
            elseif (!is_array($this->_options['callbacks'][$k])) {
                $this->_options['callbacks'][$k] = $v[$k];
            }
            elseif (is_array($this->_options['callbacks'][$k])) {
                $this->_options['callbacks'][$k] = array_merge($this->_options['callbacks'][$k], $v[$k]);
            }
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
    public function tagged($crud = false)
    {
        return $this->_options['prefix'].$this->_options['tagged'];
    }
    // }}}
    // {{{ dat 
    /**
     * Renvoit le nom de la table dat
     *
     * @param   boolean
     * @return	string
     */
    public function dat($crud = false)
    {
        return $this->_options['prefix'].$this->_options['dat'];
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
            $sql = sprintf('
                SELECT count(*) n FROM %s WHERE label=\'tag\' or label=\'item\' LIMIT 0,1;
                ',
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
    public function registerSchema($name, array $attr)
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
                        frequency INTEGER(10) UNSIGNED NOT NULL default '0',
                        type INTEGER(11) UNSIGNED NOT NULL default '0',
                        updated timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
                        created timestamp NOT NULL default '0000-00-00 00:00:00',
                        score INTEGER(10) NOT NULL default '0',
                        dat_hash BINARY(20) NULL,
                        language VARCHAR(5) COLLATE latin1_general_cs NULL,
                        scheme VARCHAR(10) COLLATE latin1_general_cs NULL,
                        prefix VARCHAR(50) COLLATE latin1_general_cs NULL,
                        suffix VARCHAR(50) COLLATE latin1_general_cs NULL,
                        buffer VARCHAR(200) COLLATE latin1_general_cs NULL,
                        INDEX (label),
                FULLTEXT (buffer),
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
                        rank INTEGER(11) NOT NULL default '0',
                        PRIMARY KEY (tag_id, item_id),
                INDEX (tag_id),
                INDEX (item_id)
            ) ENGINE=MYISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_cs;
                ", $this->tagged(true)));
                 $this->exec(sprintf("
                    CREATE TABLE %s (
                        hash BINARY(20) NOT NULL,
                        content TEXT COLLATE latin1_general_cs NOT NULL,
                        PRIMARY KEY (hash),
                        FULLTEXT (content)
                    ) ENGINE=MYISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_cs;
                ", $this->dat(true)));

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
                "INSERT INTO %s (id, label, created) VALUES (%s, 'item', now());",
                $this->tag(true),
                AIT::ITEM
            ));
            $this->exec(sprintf(
                "INSERT INTO %s (id, label, created) VALUES (%s, 'tag', now());",
                $this->tag(true),
                AIT::TAG
            ));
        }
        catch (PDOException $e) {
            AIT::catchError($e);
        }
    }
    // }}}

}

/**
 * Objet Racine
 *
 * @category  AIT
 * @package   AIT
 * @author    Nicolas Thouvenin <nthouvenin@gmail.com>
 * @copyright 2009 Nicolas Thouvenin
 * @license   http://opensource.org/licenses/lgpl-license.php LGPL
 * @link      http://ait.touv.fr/
 */
abstract class AITRoot
{
    /**
     * @var PDOAIT
     */
    private $_pdo;
    /**
     * @var array
     */
    protected $_pdo_opt;

    // {{{ getPDO
    /**
     * Renvoit l'objet PDO utilisé
     *
     * @return PDO
     */
    public function getPDO()
    {
        if (is_null($this->_pdo)) {
            $this->_pdo = new PDOAIT(
                $this->_pdo_opt['dsn'],
                $this->_pdo_opt['username'],
                $this->_pdo_opt['password'],
                $this->_pdo_opt['drvropts']
            );
        }
        $this->_pdo->setOptions($this->_pdo_opt);
        return $this->_pdo;
    }
    // }}}

    // {{{ setPDO
    /**
     * Fixe l'objet PDO utilisé
     *
     * @param PDOAIT
     */
    public function setPDO(PDOAIT $pdo)
    {
        $this->_pdo = $pdo;
        $this->_pdo_opt = $pdo->getOptions();
    }
    // }}}

    // {{{ __sleep
    /**
     * Avant serialization
     *
     */
    public function __sleep ()
    {
        $this->_pdo_opt = $this->getPDO()->getOptions();
        $vars = array_keys(get_object_vars($this));
        unset($vars[array_search('_pdo', $vars)]);
        return $vars;
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
 * @copyright 2009 Nicolas Thouvenin
 * @license   http://opensource.org/licenses/lgpl-license.php LGPL
 * @link      http://ait.touv.fr/
 */
class AIT extends AITRoot
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
     * @var array
     */
    protected $_methods = array();
    /**
     * @var array
     */
    protected $_cols = array(
        'language'   => 'string',
        'scheme'     => 'string',
        'prefix'     => 'string',
        'suffix'     => 'string',
        'buffer'     => 'string',
        'dat_hash'   => 'string',
        'score'      => 'integer',
        'frequency'  => 'integer',
    );
    /**
     * @var array
     */
    protected $_data  = array();

    const VERSION = '2.0.0';
    const ORDER_ASC = 2;
    const ORDER_DESC = 4;
    const ORDER_BY_LABEL = 8;
    const ORDER_BY_SCORE = 16;
    const ORDER_BY_UPDATED = 32;
    const ORDER_BY_CREATED = 64;
    const ORDER_BY_FREQUENCY = 128;
    const ORDER_BY_RANK = 256;
    const INSERT_FIRST = 0;
    const ITEM = 1;
    const TAG = 2;

    // {{{ __construct
    /**
     * Constructeur
     *
     * @param PDOAIT $pdo objet de connexion à la base
     */
    function __construct(PDOAIT $pdo, $element)
    {
        $this->setPDO($pdo);
        $this->_element = $element;

        $cb = $this->getPDO()->getOption('callbacks');
        if (isset($cb[$element])) $this->setClassCallback($cb[$element]);
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
        foreach($a as $n => $c) {
            if (is_callable($c, true)) {
                $this->_methods[$n] = $c;
            }
            else {
                trigger_error($n.' passed to '.__METHOD__.' must be a valid callback', E_USER_ERROR);
            }
        }
    }

    // }}}

    // {{{ isClassCallback
    /**
     * Test si la callback est définie
     *
     * @access	public
     */
    function isClassCallback($c)
    {
        if (isset($this->_methods[$c]))
            return true;
        else
            return false;
    }
    // }}}

    // {{{ callClassCallback
    /**
     * Lance une callback
     *
     * @param string name
     * @access	public
     */
    function callClassCallback()
    {
        $c = func_get_arg(0);
        $r = func_get_args();
        array_shift($r);
        if ($this->isClassCallback($c)) {
            return call_user_func_array($this->_methods[$c], $r);
        }
        else
            return false;
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
            $this->callClassCallback('renHook', $l, $this);
            $this->_label = $l;
            $this->_set('label', $this->_label);
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
            $sql = sprintf('
                SELECT count(*) FROM %s WHERE id=? LIMIT 0,1
                ', 
                $this->getPDO()->tag()
            );

            $stmt = $this->getPDO()->prepare($sql);
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
            $sql = sprintf('
                SELECT count(*) n FROM %s WHERE id=? AND type=? LIMIT 0,1
                ', 
                $this->getPDO()->tag()
            );

            $stmt = $this->getPDO()->prepare($sql);
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
            $sql = sprintf('
                SELECT count(*) FROM %s WHERE id=? LIMIT 0,1
                ', 
                $this->getPDO()->tag()
            );

            $stmt = $this->getPDO()->prepare($sql);
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
            $sql = sprintf('
                SELECT count(*) n FROM %s WHERE tag_id=? and item_id=? LIMIT 0,1
                ',
                $this->getPDO()->tagged()
            );

            $stmt = $this->getPDO()->prepare($sql);
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
            $sql = sprintf('
                SELECT frequency FROM %s WHERE id = ? LIMIT 0,1
                ', 
                $this->getPDO()->tag()
            );
            self::timer();
            $stmt = $this->getPDO()->prepare($sql);
            $stmt->bindParam(1, $i, PDO::PARAM_INT);
            $stmt->execute();
            settype($i, 'integer');
            $f = (int) $stmt->fetchColumn(0);
            $stmt->closeCursor();
            self::debug(self::timer(true), $sql, $i);

            ++$f;
            if ($f <= 0) $f = 1; // Control IMPORTANT

            $sql = sprintf('
                UPDATE %s SET frequency = ? WHERE id = ?
                ',
                $this->getPDO()->tag(true)
            );
            self::timer();
            $stmt = $this->getPDO()->prepare($sql);
            $stmt->bindParam(1, $f, PDO::PARAM_INT);
            $stmt->bindParam(2, $i, PDO::PARAM_INT);
            $stmt->execute();
            settype($f, 'integer');
            settype($i, 'integer');
            $stmt->closeCursor();
            self::debug(self::timer(true), $sql, $f, $i);
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
            $sql = sprintf('
                SELECT frequency FROM %s WHERE id = ? LIMIT 0,1
                ', 
                $this->getPDO()->tag()
            );
            self::timer();
            $stmt = $this->getPDO()->prepare($sql);
            $stmt->bindParam(1, $i, PDO::PARAM_INT);
            $stmt->execute();
            settype($i, 'integer');
            $f = (int) $stmt->fetchColumn(0);
            $stmt->closeCursor();
            self::debug(self::timer(true), $sql, $i);

            --$f;
            if ($f <= 0) $f = 0; // Control IMPORTANT

            $sql = sprintf('
                UPDATE %s SET frequency = ? WHERE id = ?
                ',
                $this->getPDO()->tag(true)
            );
            self::timer();
            $stmt = $this->getPDO()->prepare($sql);
            $stmt->bindParam(1, $f, PDO::PARAM_INT);
            $stmt->bindParam(2, $i, PDO::PARAM_INT);
            $stmt->execute();
            settype($f, 'integer');
            settype($i, 'integer');
            $stmt->closeCursor();
            self::debug(self::timer(true), $sql, $f, $i);
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
     * @param in $p position
     */
    protected function _addTagged($t, $i, $p = null)
    {
        try {
            if (is_null($p) or $p === 0) {
                $sql = sprintf('
                    INSERT INTO %1$s (tag_id, item_id, rank) SELECT ?, ?, %2$s FROM %1$s WHERE item_id = ? LIMIT 0,1
                    ',
                    $this->getPDO()->tagged(true),
                    ($p === 0 ? 'min(rank) - 1' : 'max(rank) + 1')
                );
                self::timer();
                $stmt = $this->getPDO()->prepare($sql);
                $stmt->bindParam(1, $t, PDO::PARAM_INT);
                $stmt->bindParam(2, $i, PDO::PARAM_INT);
                $stmt->bindParam(3, $i, PDO::PARAM_INT);
                $stmt->execute();
                settype($t, 'integer');
                settype($i, 'integer');
                $stmt->closeCursor();
                self::debug(self::timer(true), $sql, $t, $i, $i);

            }
            else {
                $sql = sprintf('
                    SELECT rank 
                    FROM %s 
                    WHERE tag_id = ? and item_id = ?
                    ',
                    $this->getPDO()->tagged(true)
                );
                self::timer();
                $stmt = $this->getPDO()->prepare($sql);
                $stmt->bindParam(1, $p, PDO::PARAM_INT);
                $stmt->bindParam(2, $i, PDO::PARAM_INT);
                $stmt->execute();
                $rank = (int) $stmt->fetchColumn(0);                
                settype($p, 'integer');
                settype($i, 'integer');
                $stmt->closeCursor();
                self::debug(self::timer(true), $sql, $p, $i);

                $sql =  '
                    SET @rank = ?;  
                ';
                self::timer();
                $stmt = $this->getPDO()->prepare($sql);
                $stmt->bindParam(1, $rank, PDO::PARAM_INT);
                $stmt->execute();
                settype($rank, 'integer');
                settype($i, 'integer');
                $stmt->closeCursor();
                self::debug(self::timer(true), $sql, $rank);

                $sql = sprintf('
                    UPDATE %s SET rank = (SELECT @rank := @rank + 1) WHERE item_id = ? ORDER BY rank;
                ',
                    $this->getPDO()->tagged(true)
                );
                self::timer();
                $stmt = $this->getPDO()->prepare($sql);
                $stmt->bindParam(1, $i, PDO::PARAM_INT);
                $stmt->execute();
                settype($rank, 'integer');
                settype($i, 'integer');
                $stmt->closeCursor();
                self::debug(self::timer(true), $sql, $i);

                ++$rank;
                $sql = sprintf('
                    INSERT INTO %s (tag_id, item_id, rank) VALUES (?, ?, ?);
                ',
                    $this->getPDO()->tagged(true)
                );
                self::timer();
                $stmt = $this->getPDO()->prepare($sql);
                $stmt->bindParam(1, $t, PDO::PARAM_INT);
                $stmt->bindParam(2, $i, PDO::PARAM_INT);
                $stmt->bindParam(3, $rank, PDO::PARAM_INT);
                $stmt->execute();
                settype($t, 'integer');
                settype($i, 'integer');
                settype($rank, 'integer');
                $stmt->closeCursor();
                self::debug(self::timer(true), $sql, $t, $i, $rank);
            } 
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

            $sql = sprintf('
                DELETE FROM %s WHERE %s=?
                ',
                $this->getPDO()->tagged(true),
                $field
            );
            self::timer();
            $stmt = $this->getPDO()->prepare($sql);
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
     */
    protected function _rmTag()
    {

        $dat_hash = $this->_get('dat_hash', true);
        try {
            $sql = sprintf('
                DELETE FROM %s WHERE id=?
                ',
                $this->getPDO()->tag(true)
            );
            self::timer();
            $stmt = $this->getPDO()->prepare($sql);
            $stmt->bindParam(1, $this->_id, PDO::PARAM_INT);
            $stmt->execute();
            settype($this->_id, 'integer');
            $stmt->closeCursor();
            self::debug(self::timer(true), $sql, $this->_id);

            $this->_id = null;

            $sql = sprintf('
                DELETE FROM %2$s
                USING %2$s 
                LEFT JOIN %1$s tag ON %2$s.hash=tag.dat_hash
                WHERE hash=? AND tag.id is NULL
                ',
                $this->getPDO()->tag(true),
                $this->getPDO()->dat(true)
            );
            self::timer();
            $stmt = $this->getPDO()->prepare($sql);
            $stmt->bindParam(1, $dat_hash, PDO::PARAM_STR);
            $stmt->execute();
            $stmt->closeCursor();
            self::debug(self::timer(true), $sql, $dat_hash);
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
     * @param array  $r champ associé
     *
     * @return integer
     */
    protected function _addTag($l, $t = null, $r = false)
    {
        try {
            $sql = sprintf('
                SELECT id FROM %s WHERE label=? and type=?  LIMIT 0,1
                ', 
                $this->getPDO()->tag()
            );

            $stmt = $this->getPDO()->prepare($sql);
            $stmt->bindParam(1, $l, PDO::PARAM_STR);
            $stmt->bindParam(2, $t, PDO::PARAM_INT);
            $stmt->execute();
            settype($t, 'integer');

            $id = $stmt->fetchColumn();
            $stmt->closeCursor();

            if ($id !== false) return (int) $id;

            if (isset($r['content'])) {
                $r['dat_hash'] = sha1($r['content'], true);
                self::timer();
                $sql = sprintf('
                    INSERT IGNORE INTO %s (hash, content) VALUES (?, ?);
                ',
                    $this->getPDO()->dat(true)
                );
                $stmt = $this->getPDO()->prepare($sql);
                $stmt->bindParam(1, $r['dat_hash'], PDO::PARAM_STR);
                $stmt->bindParam(2, $r['content'], PDO::PARAM_STR);
                $ret = $stmt->execute();
                $stmt->closeCursor();
                self::debug(self::timer(true), $sql, $r['dat_hash'], $r['content']);
            }

            $sqlA = $sqlB = '';
            $fields = array();
            foreach($this->_cols as $name => $typage) {
                if (isset($r[$name])) {
                    settype($r[$name], $typage);
                    $fields[] = $this->_data[$name] = $r[$name];
                    $sqlA .= ','.$name;
                    $sqlB .= ',?';
                }
            }


            self::timer();
            if (is_null($t)) {
                $sql = sprintf('
                    INSERT INTO %s (label, updated, created %s) VALUES (?, now(), now() %s);
                ',
                    $this->getPDO()->tag(true),
                    $sqlA,
                    $sqlB
                );
                $stmt = $this->getPDO()->prepare($sql);
                $stmt->bindParam(1, $l, PDO::PARAM_STR);
                $n = 2;
            }
            else {
                $sql = sprintf('
                    INSERT INTO %s (label, type, updated, created %s) VALUES (?, ?, now(), now() %s);
                ',
                    $this->getPDO()->tag(true),
                    $sqlA,
                    $sqlB
                );
                $stmt = $this->getPDO()->prepare($sql);
                $stmt->bindParam(1, $l, PDO::PARAM_STR);
                $stmt->bindParam(2, $t, PDO::PARAM_INT);
                $n = 3;
            }
            foreach($fields as $k => $field) {
                $typage = gettype($field);
                if ($typage === 'integer')
                    $stmt->bindParam($k + $n, $field, PDO::PARAM_INT);
                elseif ($typage === 'string')
                    $stmt->bindParam($k + $n, $field, PDO::PARAM_STR);
            }
            $ret = $stmt->execute();
            $stmt->closeCursor();
            self::debug(self::timer(true), $sql, $l, $t, $r);

            $id = (int) $this->getPDO()->lastInsertId();

        }
        catch (PDOException $e) {
            self::catchError($e);
        }
        return $id;
    }
    // }}}

    // {{{ _getTagBySystemID
    /**
     * Retroune une ligne dans la table tag à partir de l'identifiant physique
     *
     * @param integer $i
     *
     * @return array
     */
    protected function _getTagBySystemID($i)
    {
        $sql = sprintf('
            SELECT id, label, prefix, suffix, buffer, scheme, dat_hash, language, score, frequency, type, content
            FROM %s tag
            LEFT JOIN %s dat ON tag.dat_hash=dat.hash
            WHERE id = ?
            LIMIT 0,1
            ',
            $this->getPDO()->tag(),
            $this->getPDO()->dat()
        );
        self::timer();
        if (($r = $this->callClassCallback(
            'getTagBySystemIDCache',
            $cid = self::str2cid($sql, $i)
        )) !== false) return $r;

        try {
            $stmt = $this->getPDO()->prepare($sql);
            $stmt->bindParam(1, $i, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
        }
        catch (PDOException $e) {
            self::catchError($e);
        }

        self::debug(self::timer(true), $sql, $i);

        if (is_array($row)) {
            settype($row['type'], 'integer');
            settype($row['id'], 'integer');
        }

        if (isset($cid))
            $this->callClassCallback('getTagBySystemIDCache', $cid, $row);

        return $row;
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
        $this->callClassCallback('setHook', $n, $v, $this);

        $this->_data[$n] = $v;

        if ($n === 'content') {
            $dat_hash = sha1($r['content'], true);
            $this->_set('dat_hash', $dat_hash);

            self::timer();
            $sql = sprintf('
                INSERT IGNORE INTO %s (hash, content) VALUES (?, ?);
            ',
                $this->getPDO()->dat(true)
            );
            $stmt = $this->getPDO()->prepare($sql);
            $stmt->bindParam(1, $dat_hash, PDO::PARAM_STR);
            $stmt->bindParam(2, $v, PDO::PARAM_STR);
            $ret = $stmt->execute();
            $stmt->closeCursor();
            self::debug(self::timer(true), $sql, $dat_hash, $v);
        }


        try {
            self::timer();
            $sql = sprintf('
                UPDATE %s set %s=? WHERE id=?
                ', 
                $this->getPDO()->tag(true), $n
            );
            $stmt = $this->getPDO()->prepare($sql);
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
     * à partir d'un tableau de données
     *
     * @param array $a
     */
    protected function _fill($a)
    {
        if (is_array($a)) {
            foreach($this->_cols as $n => $t) {
                if (isset($a[$n])) {
                    $this->_data[$n] = $a[$n];
                    settype($this->_data[$n], $t);
                }
            }
            if (isset($a['content']))  {
                $this->_data['content'] = $a['content'];
            }
        }
    }
    // }}}
    //
        // {{{ filter
        /**
         * Création de filtre sql sur les colonnes complétaires 
         *
         * @param array $a
         * @param strinf $c
         */
        protected function filter($a, $c = 'tag')
        {
            $sql = '';
            if (is_array($a)) 
                foreach($this->_cols as $n => $t)
                    if (isset($a[$n])) 
                        $sql = ' AND '.$c.'.'.$n.'='.$this->getPDO()->quote($a[$n], $t === 'integer' ? PDO::PARAM_INT : PDO::PARAM_STR);
            return $sql;
        }
        // }}}

        // {{{ _get
        /**
         * Méthode permettant d'accéder à la valeur d'une colonne
         *
         * @param string $n nom de la colonne
         * @param boolean $reload récupére la valeur en base et non celle du cache de l'objet
         *
         * @return mixed
         */
        protected function _get($n, $reload = false)
        {
            if ($reload === true && isset($this->_data[$n])) unset($this->_data[$n]);

            if (!isset($this->_data[$n])) {
                try {
                    self::timer();
                    $sql = sprintf('
                        SELECT id, label, prefix, suffix, buffer, scheme, dat_hash, language, score, frequency, type, content
                        FROM %s tag
                        LEFT JOIN %s dat ON tag.dat_hash=dat.hash
                        WHERE id = ?
                        LIMIT 0,1
                        ',
                        $this->getPDO()->tag(),
                        $this->getPDO()->dat()
                    );
                    $stmt = $this->getPDO()->prepare($sql);
                    $stmt->bindParam(1, $this->_id, PDO::PARAM_INT);
                    $stmt->execute();
                    settype($this->_id, 'integer');
                    $a = $stmt->fetch(PDO::FETCH_ASSOC);
                    $this->_fill($a);
                    $stmt->closeCursor();
                    self::debug(self::timer(true), $sql, $this->_id);
                }
                catch (PDOException $e) {
                    self::catchError($e);
                }
            }
            return isset($this->_data[$n]) ? $this->_data[$n] : null;
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
                $value = $this->$name;
            }
            if (isset($this->_cols[$name]) or $name === 'content') {
                $value = $this->_get($name);
            }
            if ($this->isClassCallback('getHook')) {
                $value = $this->callClassCallback('getHook', $name, $value, $this);
            }
            return $value;
        }
        // }}}

        // {{{ set
        /**
         * Setter
         *
         * @param string $value
         * @param string $name nom de l'attribut
         *
         * @return	mixed
         */
        public function set($value, $name = 'label')
        {
            if ($name === 'label') {
                return $this->ren($value);
            }
            elseif (isset($this->_cols[$name])) {
                return $this->_set($name, $value);
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
                $sql = sprintf('
                    SELECT UNIX_TIMESTAMP(updated), UNIX_TIMESTAMP(created) FROM %s WHERE id=? LIMIT 0,1
                    ',
                    $this->getPDO()->tag()
                );
                $stmt = $this->getPDO()->prepare($sql);
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

        // {{{ search
        /**
         * Recherche un objet quelque soit sont type
         *
         * @param string  $query requete (le format dépend de la search_callback) sans callback c'est du SQL
         * @param integer $offset décalage à parir du premier enregistrement
         * @param integer $lines nombre de lignes à retourner
         * @param integer $ordering flag permettant le tri
         *
         * @return AITResult
         */
        public function search($query, $offset = null, $lines = null, $ordering = null)
        {
            if (!is_null($offset) && !is_int($offset))
                trigger_error('Argument 3 passed to '.__METHOD__.' must be a integer, '.gettype($offset).' given', E_USER_ERROR);
            if (!is_null($lines) && !is_int($lines))
                trigger_error('Argument 4 passed to '.__METHOD__.' must be a integer, '.gettype($lines).' given', E_USER_ERROR);
            if (!is_null($ordering) && !is_int($ordering))
                trigger_error('Argument 5 passed to '.__METHOD__.' must be a integer, '.gettype($ordering).' given', E_USER_ERROR);

            if ($this->isClassCallback('searchHook'))
                $query = $this->callClassCallback('searchHook', $query, $this);

            if ($query !== '' and $query !== false) $query = 'AND '.$query;
            $sql1 = 'SELECT tag.id id, tag.label label, tag.type type, tag.prefix prefix, tag.suffix suffix, tag.buffer buffer, tag.scheme scheme, tag.dat_hash dat_hash, tag.language language, tag.score score, tag.frequency frequency, b.type crtl';
            $sql2 = sprintf('
                FROM %1$s tag
                LEFT JOIN %1$s b ON tag.type=b.id
                WHERE tag.type != 0 %2$s
                ',
                $this->getPDO()->tag(),
                $query
            );
            $sql = $sql1.$sql2;

            self::sqler($sql, $offset, $lines, $ordering);
            self::timer();

            $stmt = $this->getPDO()->prepare($sql);
            $stmt->execute();
            $ret = array();
            while (($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
                if (is_null($row['id'])) continue;
                $ret[] = self::factory($this->getPDO(), $row);
            }
            $stmt->closeCursor();
            self::debug(self::timer(true), $sql, $this->_id, $this->_id);

            $sql = 'SELECT COUNT(*) '.$sql2;
            $r = new AITResult($ret);
            $r->setQueryForTotal($sql, array($this->_id => PDO::PARAM_INT,), $this->getPDO());

            return $r;
        }
        // }}}

        // {{{ factory
        /**
         * Créer un objet à partir d'un tableau de donnée
         *
         * @param PDOAIT $pdo pointeur sur la base de données
         * @param array  $row
         *
         * @return mixed
         */
        public static function factory(PDOAIT $pdo, $row)
        {
            if (!is_array($row))
                trigger_error('Argument 2 passed to '.__METHOD__.' must be a array, '.gettype($row).' given', E_USER_ERROR);

            if (!isset($row['type']) or !isset($row['id']) or !isset($row['crtl']) or !isset($row['label']))
                trigger_error('Argument 2 passed to '.__METHOD__.' has one or more keys missing (id, label, type, crtl)', E_USER_ERROR);

            settype($row['type'], 'integer');
            settype($row['id'], 'integer');
            settype($row['crtl'], 'integer');

            $o = null;
            if ($row['type'] === self::ITEM) {
                $o = new AIT_ItemType($row['label'], $pdo, $row['id'], $row);
            }
            elseif ($row['type'] === self::TAG) {
                $o = new AIT_TagType($row['label'], null, $pdo, $row['id'], $row);
            }
            elseif ($row['crtl'] === self::ITEM) {
                $o = new AIT_Item($row['label'], $row['type'], $pdo, $row['id'], $row);
            }
            elseif ($row['crtl'] === self::TAG) {
                $o = new AIT_Tag($row['label'], $row['type'], null, $pdo, $row['id'], $row);
            }
            return $o;
        }
        // }}}

        // {{{ getBySystemID
        /**
         * Récupère un objet quelque soit son type
         *
         * @param PDOAIT $pdo pointeur sur la base de données
         * @param integer $id identifiant systéme
         */
        public static function getBySystemID(PDOAIT $pdo, $id)
        {
            if (!is_null($id) && !is_int($id))
                trigger_error('Argument 2 passed to '.__METHOD__.' must be a integer, '.gettype($id).' given', E_USER_ERROR);

            try {
                $sql = sprintf('
                    SELECT a.id id, a.label label, a.type type, b.type crtl
                    FROM %s a
                    LEFT JOIN %s b ON a.type=b.id
                    WHERE a.id = ?
                    LIMIT 0,1
                    ',
                    $pdo->tag(),
                    $pdo->tag()
                );
                self::timer();
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(1, $id, PDO::PARAM_INT);
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $stmt->closeCursor();
                self::debug(self::timer(true), $sql, $id);

                if (is_array($row))
                    return self::factory($pdo, $row);
            }
            catch (PDOException $e) {
                self::catchError($e);
            }

        }
        // }}}

        // {{{ sqler
        /**
         * Ajout les close ORDER et LIMIT à une requete sql
         *
         * @param string  $sql chaine contenant du SQL
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
                elseif ( (self::ORDER_BY_RANK & $ordering) === self::ORDER_BY_RANK)
                    $sql .= ' rank';
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
                $dbg = debug_backtrace();
                for($i = 2; $i > 0; $i--) {
                    if (isset($dbg[$i]['function']) and isset($dbg[$i]['class']))
                        echo $dbg[$i]['class'].'::'.$dbg[$i]['function']. ($i != 1 ? ' => ' : '');
                }
                echo substr(php_sapi_name(), 0, 3) == 'cli'  ? "\n\t" : "<br/>\n&nbsp;&nbsp;&nbsp;&nbsp;\t";

                $argc = func_num_args();
                for ($i = 0; $i < $argc; $i++) {
                    $value = func_get_arg($i);
                    echo implode(' ', array_map('trim',explode("\n",$value))). ($i < $argc - 1 ? ' / ' : '');

                }
                echo substr(php_sapi_name(), 0, 3) == 'cli'  ? "\n" : "<br/>\n";
            }
        }
        // }}}

        // {{{ timer
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

        // {{{ str2cid
        /**
         * DEBUG
         *
         */
        public static function str2cid()
        {
            $ret = '';
            $argc = func_num_args();
            for ($i = 0; $i < $argc; $i++) {
                $arg = func_get_arg($i);
                if (!is_object($arg)) $ret .= $arg;
            }

            return md5($ret);
        }
        // }}}

        // {{{ __call
        /**
         * Traitement des méthodes ajoutées
         *
         * @param string $name
         * @param array $arguments
         */
        function __call($name, array $arguments)
        {
            if (
                isset($this->_methods[$name]) &&
                is_callable($this->_methods[$name])
            ) {
                array_unshift($arguments, $this);
                return call_user_func_array($this->_methods[$name], $arguments);
            }
            else {
                trigger_error('Call to undefined method '.__CLASS__.'::'.$name, E_USER_ERROR);
            }
        }
        // }}}

        // {{{ __sleep
        /**
         * Avant serialization
         *
         */
        public function __sleep () 
        {
            return array_merge(parent::__sleep(), array (
                "\0*\0"."_id",
                "\0*\0"."_element",
                "\0*\0"."_label",
                "\0*\0"."_type",
                "\0*\0"."_methods",
                "\0*\0"."_cols",
                "\0*\0"."_data",
            ));
        }
        // }}}
    }



/**
 * Objet représantant une requete au sens AIT
 *
 * @category  AIT
 * @package   AIT
 * @author    Nicolas Thouvenin <nthouvenin@gmail.com>
 * @copyright 2009 Nicolas Thouvenin
 * @license   http://opensource.org/licenses/lgpl-license.php LGPL
 * @link      http://ait.touv.fr/
 */
class AITQuery extends AITRoot {

    protected $_sql = '';

    protected $_step = array();

    // {{{ __construct
    /**
     * Constructeur
     *
     * @param PDOAIT $pdo objet de connexion à la base
     */
    function __construct(PDOAIT $pdo)
    {
        $this->setPDO($pdo);
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
        $this->_sql = '';
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
                $w = sprintf('tag_id = %s',
                    $tag->getSystemID()
                );
            }
            else {
                $w = sprintf('tag_id = %s AND item_id IN (SELECT item_id FROM %s WHERE %s)',
                    $tag->getSystemID(),
                    $this->getPDO()->tagged(),
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
        return sprintf('
            SELECT item_id FROM %s WHERE %s
            ', 
            $this->getPDO()->tagged(), 
            $this->_sql
        );
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
            if ($this->_sql === '')
                $this->_sql = $sql;
            else
                $this->_sql .= ' OR '.$sql;
        }
        else {
            if ($this->_sql === '')
                $this->_sql = $sql;
            else
                $this->_sql = sprintf(
                    ' (%s) AND item_id IN (SELECT item_id FROM %s WHERE %s)',
                    $sql,
                    $this->getPDO()->tagged(),
                    $this->_sql
                );
        }
    }
    // }}}

    // {{{ __sleep
    /**
     * Avant serialization
     *
     */
    public function __sleep () 
    {
        return array_merge(parent::__sleep(), array (
            "\0*\0"."_step",
            "\0*\0"."_sql",
        ));
    }
    // }}}

}


/**
 * Objet représantant une requete au sens AIT
 *
 * @category  AIT
 * @package   AIT
 * @author    Nicolas Thouvenin <nthouvenin@gmail.com>
 * @copyright 2009 Nicolas Thouvenin
 * @license   http://opensource.org/licenses/lgpl-license.php LGPL
 * @link      http://ait.touv.fr/
 */
class AITResult extends AITRoot implements Countable, Iterator, ArrayAccess {

    protected $_total = 0;
    protected $_sql = null;
    protected $_params = array();
    protected $_array = array();

    // {{{ __construct
    /**
     * Constructeur
     *
     * @param array
     */
    function __construct($a, $pdo = null)
    {
        $this->_array = $a;
        if (!is_null($pdo))
            $this->setPDO($pdo);
    }
    // }}}

    // {{{ Interfaces ...
    /**
     * Defined by Countable interface
     *
     * @return int
     */
    public function count()
    {
        return count($this->_array);
    }
    /**
     * Defined by Iterator interface
     *
     * @return mixed
     */
    public function current()
    {
        $k = key($this->_array);
        return($this->_array[$k]);
    }
    /**
     * Defined by Iterator interface
     *
     * @return mixed
     */
    public function key()
    {
        return key($this->_array);
    }
    /**
     * Defined by Iterator interface
     *
     */
    public function next()
    {
        return next($this->_array);
    }
    /**
     * Defined by Iterator interface
     *
     */
    public function rewind()
    {
        return reset($this->_array);
    }
    /**
     * Defined by Iterator interface
     *
     * @return boolean
     */
    public function valid()
    {
        return array_key_exists(key($this->_array),$this->_array);
    }
    /**
     * Defined by ArrayAccess interface
     *
     */
    public function offsetExists ($offset)
    {
        return array_key_exists($offset, $this->_array);
    }
    /**
     * Defined by ArrayAccess interface
     *
     */
    public function offsetGet($offset)
    {
        if ($this->offsetExists($offset))
            return $this->_array[$offset];
    }
    /**
     * Defined by ArrayAccess interface
     *
     */
    public function offsetSet($offset, $value)
    {
        $this->_array[$offset] = $value;
    }
    /**
     * Defined by ArrayAccess interface
     *
     */
    public function offsetUnset($offset)
    {
        unset($this->_array[$offset]);
    }
    // }}}

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
    public function setQueryForTotal($sql,  $params, $pdo = null)
    {
        $this->_sql = $sql;
        $this->_params = $params;
        if (!is_null($pdo))
            $this->setPDO($pdo);
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
        $stmt = $this->getPDO()->prepare($this->_sql);
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

    // {{{ __sleep
    /**
     * Avant serialization
     *
     */
    public function __sleep () 
    {
        return array_merge(parent::__sleep(), array (
            "\0*\0"."_total",
            "\0*\0"."_sql",
            "\0*\0"."_params",
            "\0*\0"."_array",
        ));
    }
    // }}}
}



/**
 * Objet représantant un ensemble de tags
 *
 * @category  AIT
 * @package   AIT
 * @author    Nicolas Thouvenin <nthouvenin@gmail.com>
 * @copyright 2009 Nicolas Thouvenin
 * @license   http://opensource.org/licenses/lgpl-license.php LGPL
 * @link      http://ait.touv.fr/
 */
class AITTagsObject implements Countable, Iterator {

    protected $_tags;

    // {{{ construct
    /**
     * Constructeur
     *
     * @param AITResult $tags
     */
    function __construct($tags = null)
    {
        if (!is_null($tags)) $this->setTags($tags);
    }
    // }}}

    // {{{ setTags
    /**
     * Remplie l'objet avec des tags en vrac
     *
     * @param $tags
     */
    function setTags($tags)
    {
        $this->_tags = array();
        foreach($tags as $tag) {
            if (! $tag instanceof AIT_Tag) continue;
            $this->addTag($tag);
        }
    }
    // }}}

    // {{{ addTag
    /**
     * Ajout un Tag
     *
     * @param $tag
     */
    function addTag(AIT_Tag $tag)
    {
        $type = $tag->getTagType();
        $name = $type->get();
        if (!isset($this->_tags[$name])) {
            $this->_tags[$name] = new ArrayObject();
        }
        $this->_tags[$name]->append($tag);
    }
    // }}}

    // {{{ __set
    /**
     * Magic function to set value
     *
     * @param string $name The variable name.
     * @param mixed $val The variable value.
     * @return void
     */
    public function __set($name, ArrayObject $val)
    {
        $this->_tags[$name] = $val;
    }
    // }}}

    // {{{ __get
    /**
     * Retourne les tags d'un type donnée
     *
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name) {
        if (array_key_exists($name, $this->_tags))
            return $this->_tags[$name];
        else
            return null;
    }
    // }}}

    // {{{ count
    /**
     * Defined by Countable interface
     *
     * @return integer
     */
    public function count()
    {
        return count($this->_tags);
    }
    // }}}

    // {{{ valid
    /**
     * Defined by Iterator interface
     *
     */
    public function valid()
    {
        return array_key_exists(key($this->_tags),$this->_tags);
    }
    // }}}

    // {{{ next
    /**
     * Defined by Iterator interface
     *
     */
    public function next()
    {
        return next($this->_tags);
    }
    // }}}

    // {{{ rewind
    /**
     * Defined by Iterator interface
     *
     */
    public function rewind()
    {
        return reset($this->_tags);
    }
    // }}}

    // {{{ key
    /**
     * Defined by Iterator interface
     *
     */
    public function key()
    {
        return key($this->_tags);
    }
    // }}}

    // {{{ current
    /**
     * Defined by Iterator interface
     *
     */
    public function current()
    {
        $k = key($this->_tags);
        return($this->_tags[$k]);
    }
    // }}}

    // {{{ __isset
    /**
     * Magic function to test key
     *
     * @param string $key The variable name.
     * @return boolean
     */
    public function __isset($key)
    {
        return isset($this->_tags[$key]);
    }
    // }}}

    // {{{ __unset
    /**
     * Magic function to unset key
     *
     * @param string $key The variable name.
     * @return boolean
     */
    public function __unset($key)
    {
        unset($this->_tags[$key]);
    }
    // }}}

    // {{{ __sleep
    /**
     * Avant serialization
     *
     */
    public function __sleep () 
    {
        return array (
            "\0*\0"."_tags",
        );
    }
    // }}}
}


