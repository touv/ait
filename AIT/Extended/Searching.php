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
require_once 'AIT/Extended.php';



/**
 * Classe permettant de faire des recherche à la google sur une base AIT
 *
 * @category  AIT
 * @package   AIT
 * @author    Nicolas Thouvenin <nthouvenin@gmail.com>
 * @copyright 2009 Nicolas Thouvenin
 * @license   http://opensource.org/licenses/lgpl-license.php LGPL
 * @link      http://ait.touv.fr/
 */
class AIT_Extended_Searching extends AIT_Extended
{
    /**
     * @var callback
     */
    private $callback;

    /**
     * @var string
     */
    private $_separator = '__';

    // {{{ __construct
    /**
     * Constructeur
     * @param callback 
     */
    function __construct($callback)
    {
        if (!is_callable($callback)) 
            die('It\'s not a valid Callback');
        $this->callback = $callback;

      
        parent::__construct(array(
            'callbacks' => array(
                'ItemType' => array(
                    'searchItemsHook' => array($this, 'queryHook'),
                ),
                'Item' => array(
                    'addHook' => array($this, 'bufferHook'),
                ),
                'TagType' => array(
                    'searchTagsHook' => array($this, 'queryHook'),
                ),
                'Tag' => array(
                    'addHook' => array($this, 'bufferHook'),
                ),
            ),
        ));
    }
    // }}}

    // {{{ _normalize
    /**
     * normalize une chaine de caractère
     *
     *  @param string $s valeur
     *  @param string $l valeur
     *
     *  @return string
     */
    private function _normalize($s, $la = 'en')
    {
        return call_user_func($this->callback, $s, $la);
    }
    // }}}


    // {{{ bufferHook
    /**
     * Callback pour alimenter le champ buffer 
     *
     *  @param AIT $o objet appelant
     *
     *  @return string
     */
    function bufferHook($o)
    {
        $str = $this->_normalize($o->get());
        $buf .= $str;
        $buf .= ' ';
        $buf .= $this->_separator.$str;
        $buf .= ' ';
        $buf .= implode('',array_reverse(str_split($str)));
        $o->set(trim($buf), 'buffer');
    }
    // }}}

    // {{{ queryHook
    /**
     * Callback pour traiter les requetes search
     *
     * Syntaxe de la requete :
     * REQUETE : MOT [OPEARTEUR | MOT]
     * OPEARTEUR = AND | OR
     * MOT = [ ^ | * | " ]TERME[ * | "]
     * TERME = Tout caractères
     *
     *  @param string $s valeur de la requete
     *  @param AIT $o objet appelant
     *
     *  @return string
     */
    function queryHook($s, $o)
    {
        $trq = array();
        $qry = array();
        $str = array();
        $opr = array('AND');
        $nb = 0;
        $no = 0;
        $gr = false;


        // Analyse (découpe de la requete en mots)
        $tabqry = explode(' ', $s);

        // Parcours de la requete
        foreach($tabqry as $v) {
        	//si le mot est 'empty', on passe au suivant
            if (empty($v)) continue;

			//si le mot est un opérateur logique, on renseigne le tableau $opr et on passe au mot suivant
            if ($v == 'OR' || $v == 'AND' || $v == 'NOT') {
                ++$no;
                $opr[$no] = $v;
                continue;
            }

			// l'initiale du mot
            $a = substr($v, 0, 1);
            // la dernière lettre du mot
            $b =  substr($v, -1, 1);
            // l'avant dernière lettre
            $c = substr($v, -2, 1);

            // On traite les opérateurs
            if (!$gr && ($a == '^' || $a == '*')) {
                $aa = $a;
                $v = substr($v, 1);
                $a = substr($v, 0, 1);
            }
            elseif (!$gr) $aa = '';

            if (!$gr && ($b == '*')) {
                $bb = $b;
                $v = substr($v, 0, -1);
                $b = substr($v, -1, 1);
            }
            elseif ($gr && ($b == '*') and $c == '"' ) {
                $bb = $b;
                $v = substr($v, 0, -2);
                $b = $c;
            }
            elseif (!$gr) $bb = '';

            // {{{ Traitement des mots
			// si le mot n'est pas entouré de guillemets
            if ($gr && $a != '"' && $b != '"' ) {
                $gv .= ' '. $this->_normalize($v);
                continue;
            }
			// si le mot est entouré de guillemets
            elseif ($gr && $a == '"' && $b == '"') {
                $gr = false;
                // récupére ce qu'il y a entre les quotes
                $qry[$no][$nb] = $gv.' '.$this->_normalize(substr($v,1,-1));
                ++$nb;
            }
			// si le mot commence par un guillemet
			elseif ($gr && $a == '"' && $b != '"') {
                $gr = false;
                $qry[$no][$nb] = $gv;
                $v = substr($v, 1); // la chaine sans le guillemet
            }
            // si le mot termine par un guillemet
            elseif ($gr && $a !='"' && $b == '"') {
                $gr = false;
                $v = $gv.' '.substr($v, 0, -1); // la chaine sans le guillemet
            }
            // si le mot unique est entouré de guillemets
            elseif (!$gr && $a == '"' && $b == '"') {
                $v = substr($v, 1, -1);
            }
            // si on ouvre les guillemets
            elseif (!$gr && $a == '"' && $b != '"') {
                $gr = true;
                $gv = $this->_normalize(substr($v, 1));
                continue;
            }
            // }}}


            // {{{ Traitement des opérateurs
            $a = $aa != '' ? $aa : $a;
            $b = $bb != '' ? $bb : $b;

            if ($a == '^' && $b != '*') {
                $trq[$nb] = 'S';
            }
            elseif ($a == '^' && $b == '*') {
                $trq[$nb] = 'SR';
            }
            elseif ($a == '*' && $b != '*' ) {
                $trq[$nb] = 'L';
            }
            elseif ($a == '*' && $b == '*') {
                $trq[$nb] = 'B';
            }
            elseif ($a != '*' && $b == '*') {
                $trq[$nb] = 'R';
            }
            else {
                $trq[$nb] = 'N';
            }
            // }}}

            $qry[$no][$nb] = $this->_normalize($v);
            ++$nb;
        }
        if ($gr) {
            $trq[$nb] = 'N';
            $qry[$no][$nb] = $this->_normalize($gv);
        }

        // Debug :
//         print_r($qry);
//         print_r($opr);
//         print_r($trq);

        // Constitution :
        $sql = '(';
        // parcours opérateurs, ie des sous-requetes
        if (is_array($opr)) foreach($opr as $ok => $ov) {
            // parcours des éléments de la sous-requete
            if (isset($qry[$ok]) && is_array($qry[$ok])) foreach($qry[$ok] as $qk => $qvv) {
                $gu = (strpos($qvv, ' ') ? '"' : '');
                $qv = str_replace("'","\\'",$qvv);
                $t = $trq[$qk];
                $qq = $gu.$qv.$gu;
                if (empty($qv)) continue;
                if ($t == 'N')     $va = $gu.$qv.$gu;
                elseif ($t == 'S') $va = $gu.$this->_separator.$qv.$gu;
                elseif ($t == 'SR' && empty($gu)) $va = $this->_separator.$qv.'*';
                elseif ($t == 'SR' && !empty($gu)) $va = $this->_separator.str_replace(' ',' +',$qv).'*';
                elseif ($t == 'R'  && empty($gu)) $va = $qv.'*';
                elseif ($t == 'R'  && !empty($gu)) $va = str_replace(' ',' +',$qv).'*';
                elseif ($t == 'L' && empty($gu)) $va = implode('',array_reverse(str_split($qq))).'*';
                elseif ($t == 'L' && !empty($gu)) $va = str_replace(' ',' +',implode('',array_reverse(str_split($qv)))).'*';
                elseif ($t == 'B') $va = $qq.'*';

                if ($ok !== 0) $sql .= ' '.$ov.' ';
                $va = ' +'.$va;
                $sql .= sprintf('MATCH (tag.buffer) AGAINST(\'%s\' IN BOOLEAN MODE)', $va);
            }
        } // fin parcours opérateurs

        $sql .= ')';
        if ($sql == '()') $sql = '';
        return $sql;
    }
    // }}}
}




