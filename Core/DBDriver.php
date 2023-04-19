<?php 
namespace Core;

use PDOException;

class DBDriver
{

    const FETCH_ALL = "all";
    const FETCH_ONE = "one";

    private $pdo;

    public function __construct(\PDO $pdo )
    {
        $this->pdo = $pdo;       
    }


   
    public function select($sql, array $params = [], $fetch = self::FETCH_ALL)
    {
        $query = $this->dbQuery($sql,$params) ;
        if($fetch === self::FETCH_ALL)
        {
            return $query->fetchAll($this->pdo::FETCH_ASSOC);
        }
        elseif($fetch === self::FETCH_ONE )
        {
            return $query->fetch($this->pdo::FETCH_ASSOC);
        }
        else
        {
            return 'err';
        }
        
    }

    public function add($table,array $params)
    {
        $keyMask1 = sprintf('%s' , implode(' , ', array_keys($params)));
        $keyMask2 = sprintf(':%s', implode(' , :', array_keys($params)));
        $sql = sprintf('INSERT INTO %1$s (%2$s) VALUES (%3$s)', $table ,$keyMask1, $keyMask2);
        $this->dbQuery($sql, $params);
		return $this->pdo->lastInsertId();
    }

    public function update($table,array $params, $where)
    {
        $keyMask = implode(' , ',array_map(function($k){
            return sprintf('%1$s = :%1$s',$k);}
            ,array_keys($params)
            )
        );
        $sql = sprintf('UPDATE %1$s SET %2$s WHERE  %3$s', $table , $keyMask , $where);
        $query = $this->pdo->prepare($sql);
        $query->execute($params);
        return $query;     
    }

    public function delete($table,array $params, $where )
    {
        $sql = sprintf('DELETE FROM %s WHERE %s', $table, $where);
        $this->dbQuery($sql, $params);
    }

    private function dbQuery($sql, $params = [])
    {
        $db = $this->pdo;
        $query = $db->prepare($sql);

        try {
            $query->execute($params);

        } catch (PDOException $e){
            throw $e;
        }       
        return $query;

    }
    // private function dbCheckError($query)
    // {
    //     $info = $query->errorInfo();
    //     if($info[0] != \PDO::ERR_NONE)
    //     {
    //         exit($info[2]);
    //     }
    // }

}