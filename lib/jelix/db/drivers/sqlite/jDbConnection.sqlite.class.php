<?php
/**
* @package    jelix
* @subpackage db
* @version    $Id:$
* @author     Loic Mathaud
* @contributor 
* @copyright  2006 Loic Mathaud
* @link      http://www.jelix.org
* @licence  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
*/

/**
 *
 */
class jDbConnectionSqlite extends jDbConnection {

    /**
    * begin a transaction
    */
    public function beginTransaction (){
        $this->_doQuery ('BEGIN');
    }

    /**
    * Commit since the last begin
    */
    public function commit (){
        $this->_doQuery ('COMMIT');
    }

    /**
    * Rollback since the last BEGIN
    */
    public function rollBack (){
        $this->_doQuery ('ROLLBACK');
    }

    /**
    *
    */
    public function prepare ($query){
        throw new JException('jelix~db.error.feature.unsupported', array('sqlite','prepare'));
    }

    public function errorInfo(){
        return array(sqlite_last_error($this->_connection), sqlite_error_string($this->_connection));
    }

    public function errorCode(){
        return sqlite_last_error($this->_connection);
    }
    
    protected function _connect (){
        $funcconnect= ($this->profil['persistent']? 'sqlite_popen':'sqlite_open');
        if ($cnx = @$funcconnect(JELIX_APP_VAR_PATH. 'db/sqlite/'.$this->profil['database'])) {
            return $cnx;
        } else {
            throw new JException('jelix~db.error.connection',$this->profil['database']);
        }
    }

    protected function _disconnect (){
        return sqlite_close($this->_connection);
    }

    protected function _doQuery($query){
        if ($qI = sqlite_query($query, $this->_connection)){
            return new jDbResultSetSqlite($qI);
        } else {
            throw new JException('jelix~db.error.query.bad', sqlite_error_string($this->_connection).'('.$query.')');
        }
    }

    protected function _doExec($query){
        if ($qI = sqlite_query($query, $this->_connection)){
            return sqlite_changes($this->_connection);
        } else { 
            throw new JException('jelix~db.error.query.bad', sqlite_error_string($this->_connection).'('.$query.')');
        }
        exit;
    }

    protected function _doLimitQuery ($queryString, $offset, $number){
        $queryString.= ' LIMIT '.$offset.','.$number;
        $result = $this->_doQuery($queryString);
        return $result;
    }

    public function lastInsertId($fromSequence=''){// on n'a pas besoin de l'argument pour mysql
        return sqlite_last_insert_rowid($this->_connection);
    }

    /**
    * tell mysql to be autocommit or not
    * @param boolean state the state of the autocommit value
    * @return void
    */
    protected function _autoCommitNotify ($state){
        $this->query ('SET AUTOCOMMIT='.$state ? '1' : '0');
    }

    /**
    * renvoi une chaine avec les caract�res sp�ciaux �chapp�s
    * @access private
    */
    protected function _quote($text){
        return sqlite_escape_string($text);
    }

}
?>