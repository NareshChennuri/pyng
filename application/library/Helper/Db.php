<?php

class Helper_Db {
    
    /**
     * @param string $table
     * @return array 
     */
    public static function describeTable($table) {
        $db = JO_Db::getDefaultAdapter();
        $result = $db->describeTable($table);
        $data = array();
        foreach($result AS $res) {
            $data[$res['COLUMN_NAME']] = $res['DEFAULT'];
        }
        return $data;
    }

    /**
     * Prepares and executes an SQL statement with bound data.
     *
     * @param  mixed  $sql  The SQL statement with placeholders.
     *                      May be a string or JO_Db_Select.
     * @param  mixed  $bind An array of data to bind to the placeholders.
     * @return JO_Db_Statement_Interface
     */
    public static function query($sql) {
        $db = JO_Db::getDefaultAdapter();
        return $db->query($sql);
    }
    
    /**
     * @param string $table
     * @param array $where
     * @return number
     */
    public static function delete($table, $where) {
        $db = JO_Db::getDefaultAdapter();
        return $db->delete($table, $where);
    }
    
    /**
     * @param string $table
     * @param array $data
     * @return number|string
     */
    public static function insert($table, $data) {
    	return self::create($table, $data);
    }
    
    /**
     * @param string $table
     * @param array $data
     * @return number|string
     */
    public static function create($table, $data) {
        $db = JO_Db::getDefaultAdapter();
        
        $rows = self::describeTable($table);
        
        $insert = array();
        
        foreach($rows AS $row => $default) {
            if( array_key_exists($row, $data) ) {
                $insert[$row] = is_null($data[$row]) ? '' : $data[$row];
            } else {
            	$insert[$row] = is_null($default) ? '' : $default;
            }
        }
        
        if(!$insert) {
            return 0;
        }
        
        $db->insert($table, $insert);
        
        return $db->lastInsertId();   
    }
    
    /**
     * @param string $table
     * @param array $data
     * @param array $where
     * @return boolean|number
     */
    public static function update($table, $data, $where) {
        $db = JO_Db::getDefaultAdapter();
        
        if($where) {
        	$rows = self::getRow($table, $where);
        } else {
        	$rows = self::describeTable($table);
        }
        
        if(!$rows) {
        	return false;
        }
        
        $update = array();
        
        foreach($rows AS $row => $default) {
        	if($row == 'date_modified' && !isset($data['date_modified'])) {
        		$update[$row] = new JO_Db_Expr('NOW()');
        	} elseif( array_key_exists($row, $data) ) {
                $update[$row] = is_null($data[$row]) ? '' : $data[$row];
            } else {
            	//$update[$row] = is_null($default) ? '' : $default;
            }
        }
        
        if(!$update) {
            return false;
        }
        
        return $db->update($table, $update, $where);   
    }
    
    /**
     * @param string $table
     * @param array $where
     * @return boolean|Ambigous <multitype:, mixed>
     */
    public static function getRow($table, $where) {
        $db = JO_Db::getDefaultAdapter();
        
        if(!is_array($where)) {
        	return false;
        }
        
        list($row, $value) = each($where);
        $row = preg_replace('/([^a-z0-9\_\-])/i', '', $row);
        
        $rows = self::describeTable($table); 
        if(!array_key_exists($row, $rows)) {
        	return false;
        }
        
        
        $query = $db->select()
        			->from($table)
        			->where('`' . $row . '` = ?', (string)$value)
        			->limit(1);
       return $db->fetchRow($query);
    }
    
    /**
     * @param string $table
     * @param array $where
     * @return boolean|Ambigous <multitype:, mixed>
     */
    public static function getRows($table, $where) {
    	$db = JO_Db::getDefaultAdapter();
    	 
    	if(!is_array($where)) {
    		return false;
    	}
    
    	list($row, $value) = each($where);
    	$row = preg_replace('/([^a-z0-9\_\-])/i', '', $row);
    
    	$rows = self::describeTable($table);
    	if(!array_key_exists($row, $rows)) {
    		return false;
    	}
    
    
    	$query = $db->select()
    	->from($table)
    	->where('`' . $row . '` = ?', (string)$value)
    	->limit(1);
    	return $db->fetchAll($query);
    }

}

?>