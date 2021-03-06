<?php

namespace engine;

class db {

    public $pdo = null;

    public $stmt;
	public $bind;
	public $queryType;
	public $values;
	    
    public function __construct() {
        $this->connect();
    }

    public function isConnected() {
        return (boolean)($this->pdo);
    }
    
    public function _select($table) {
    	$this->stmt = 'SELECT * FROM '.$table.' ';
    	$this->queryType = 'select';	
    	return $this;
    }
    
    public function _where($condition) {
    	if(strpos($this->stmt, 'WHERE') === false) {
    		$this->stmt .= 'WHERE '.$condition.' ';
    	} else {
    		$this->stmt .= $condition.' ';
    	}
    	return $this;
    }
    
    public function _and($condition) {
    	$this->stmt .= 'AND '.$condition.' ';
    	return $this;
    }

    public function _orderBy($column, $desc = false) {
        $this->stmt .= 'ORDER BY '.$column.' '.(($desc) ? 'DESC ' : 'ASC ');
        return $this;
    }
    
    public function _bind($alias, $value) {
   		$this->bind[$alias] = $value;
   		return $this;
    }
    
    public function _onOfThem($array) {
    	$this->stmt .= '('.implode(' OR ', $array).')';
    	return $this;
    }
    
    public function _insert($table) {
        $this->queryType = 'insert';
    	$this->stmt = 'INSERT INTO '.$table.' ';
    	return $this;
    } 
    
    public function _value($column, $alias) {
        $this->values[$column] = $alias;
        return $this;
    }
    
    public function _update($table) {
        $this->queryType = 'update';
        $this->stmt = 'UPDATE '.$table.' ';
        return $this; 
    }
    
    public function _set($column, $alias) {
        if (strpos($this->stmt, 'SET') === false) {
            $this->stmt .= 'SET '.$column.'='.$alias.' ';
        } else {
            $this->stmt .= ', '.$column.'='.$alias.' ';
        }
        return $this;
    }
    
    public function _delete($table) {
        $this->queryType = 'delete';
        $this->stmt = 'DELETE FROM '.$table.' ';
        return $this;
    }
    
    public function _execute($keepInArray = false) {
        try {
            switch ($this->queryType) {
                case 'select': return $this->_executeSelect($keepInArray); break;
                case 'insert': return $this->_executeInsert(); break;
                case 'update': return $this->_executeUpdate(); break;
                case 'delete': return $this->_executeDelete(); break;
                case 'create-table': return $this->_executeCreateTable(); break;
            }
            $this->queryType = null;
            $this->bind = null;
        } catch (\PDOException $e) {
            return null;
        }
    }	
    
    public function _executeSelect($keepInArray = false) {
    	$objects = array();
        if ($this->pdo == null) {
            return false;
        }
    	$results = $this->pdo->prepare($this->stmt);
    	$this->stmt = null;
        if ($this->bind != null) {
            foreach($this->bind as $alias=>$value) {
                $results->bindValue($alias, $value);
                unset($this->bind[$alias]);
            }
        }
    	$results->execute();
    	while ($row = $results->fetch(\PDO::FETCH_ASSOC)){
            $objects[] = $row;
        }
        switch (count($objects)) {
        	case 0: return null; break;
        	case 1: return (($keepInArray == true) ? $objects : $objects[0]); break;
        	default: return $objects; break; 
        }
    }
    
    public function _executeInsert() {
        $columns = $values = array();
        foreach ($this->values as $column=>$alias) {
            $columns[] = '`'.$column.'`';
            $values[] = $alias;
            unset($this->values[$column]);
        }
        $this->stmt .= '('.implode(', ', $columns).') VALUES ('.implode(', ', $values).')';  
        $results = $this->pdo->prepare($this->stmt);
        foreach ($this->bind as $alias=>$value) {
            $results->bindValue($alias, $value);
            unset($this->bind[$alias]);
        }
    	$results->execute();
        return $this->_lastId();
    }
    
    public function _executeUpdate() {
        $results = $this->pdo->prepare($this->stmt);
        foreach($this->bind as $alias=>$value) {
    		$results->bindValue($alias, $value);
    		unset($this->bind[$alias]);
    	}
        return $results->execute();
    }
    
    public function _executeDelete() {
        $results = $this->pdo->prepare($this->stmt);
        foreach($this->bind as $alias=>$value) {
    		$results->bindValue($alias, $value);
    		unset($this->bind[$alias]);
    	}
    	$results->execute();
    }
    
    public function connect($config = null) {
        if ($config == null) {
            $config = core::$config;
        }
//        var_dump($config);
        if (!isset($config['db'])) return false;
        try {
            switch ($config['db']['db_type']) {
                case 'sqlite':
                    try {
                        $pdo = new \PDO('sqlite:'.$config['db']['db_path']);
                    } catch (\PDOException $e) {
                        return false;
                    }
                    break;
                case 'mysql':
                    try {
                        $pdo = new \PDO('mysql:host='.$config['db']['db_host'].';dbname='.$config['db']['db_name'], $config['db']['db_user'], $config['db']['db_pass'], array(\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION));
                        $res = $pdo->prepare('SHOW DATABASES');
                        $this->pdo = $pdo;
                        return $res->execute();
                    } catch (\PDOException $e) {
                        return false;
                    }

                    break;
            }
            //$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
//            var_dump($this->test($pdo));
        } catch (\PDOException $e) {
            echo '<span style="color: red;">Jakiś błąd: '.$e->getMessage().'</span>';
            return false;
        }
        $this->pdo = $pdo;
        return $pdo;
    }

    public function test($DBH){
        try{
            $DBH->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
            $DBH->prepare('SELECT * FROM users');
        }
        catch(\PDOException $e) {
            echo $e->getMessage();
            return false;
        }
    }

    public function addObject(&$object) {
        $this->_insert('objects')
             ->_value('id', ':id')->_bind(':id', $object->ID)
             ->_value('parent_id', ':pid')->_bind(':pid', $object->parentID)
             ->_value('alias', ':alias')->_bind(':alias', $object->alias)
             ->_value('class', ':class')->_bind(':class', get_class($object))
             ->_execute();
    }

    public function _createTable($name) {
        $this->queryType = 'create-table';
        $this->stmt = 'CREATE TABLE IF NOT EXISTS '.$name.' (';
        return $this;
    }

    public function _addCol($name, $type, $autoIncrement = false, $notNull = false) {
        if ($this->stmt[strlen($this->stmt)-1] != '(') {
            $this->stmt .= ',';
        }
        if ($this->queryType == 'create-table') {
            $this->stmt .= '`'.$name.'`';
            $this->stmt .= ' '.str_replace(
                    array(
                        'string', 'password', 'text', 'integer', 'boolean'),
                    array(
                        'CHAR(255)', 'CHAR(255)', 'TEXT', 'INT', 'BOOLEAN'),
                    $type);
            if ($notNull) $this->stmt .= ' NOT NULL';
            if ($autoIncrement) $this->stmt .= ' AUTO_INCREMENT';
        }
        return $this;
    }

    public function _addKey($name, $primary = false, $index = false) {
        if ($primary) $this->stmt .= ', PRIMARY KEY (`'.$name.'`)';
        if ($index) $this->stmt .= ', KEY `'.$name.'` (`'.$name.'`)';
        return $this;
    }

    public function _executeCreateTable() {
        $this->stmt .= ')';
//        var_dump($this->pdo);
        $results = $this->pdo->prepare($this->stmt);
//        var_dump($results);
        $results->execute();
//        var_dump($results->errorInfo());
//        var_dump();
        return $results->execute();
    }
    
    
    public function updateObject($object) {
        $this->_update('objects')
             ->_set('parent_id', ':pid')->_bind(':pid', $object->parentID)
             ->_set('alias', ':alias')->_bind(':alias', $object->alias)
             ->_where('id = :id')->_bind(':id', $object->ID)
             ->_execute();
        return true;
    }

    public function _tableExists($table) {
        $return = $this->pdo->query('SHOW TABLES LIKE \''.$table.'\'');
        if ($return->fetch()) {
            return true;
        } else {
            return false;
        }
    }

    public function _lastId() {
        return $this->pdo->lastInsertId();
    }

}

?>