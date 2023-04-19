<?php
namespace Entity;

use Core\DBDriver;
use Core\Validator;

abstract class BaseEntity
{
    protected $dbDriver;
    protected $table;
    protected $validator;
    protected $sheme;


    public function __construct(DBDriver $dbDriver, $table, Validator $validator )
    {
        $this->dbDriver = $dbDriver;
        $this->table = $table;
        $this->validator = $validator;
    }

    public function getAll()
    {
        $sql = sprintf('SELECT * FROM %s', $this->table);
	    return $this->dbDriver->select($sql);
    }
    public function getOne($id)
    {

        $sql = sprintf('SELECT * FROM %s WHERE id = :id', $this->table);
        return $this->dbDriver->select($sql,['id'=>$id],'one');
    }


    public function add(array $data)
    {   
        return $this->dbDriver->add($this->table,$data);
    }
    
    public function update($id, $data)
    {
        $where = sprintf('id = %d' , $id);
        return $this->dbDriver->update($this->table, $data, $where  );
    }
    public function delete($id)
    {
        $where = 'id = :id';
        $this->dbDriver->delete($this->table ,['id' => $id], $where) ;
    } 
    public function deleteByFields($fieldName, $fieldValue)
    {
        $where = sprintf('%s = :field', $fieldName );
        $this->dbDriver->delete($this->table , ['field' => $fieldValue ],$where) ;
    } 
    public function getOneByField($fieldName, $fieldValue)
    {
        $sql = sprintf('SELECT * FROM %s WHERE %s = :field', $this->table,$fieldName);
        return $this->dbDriver->select($sql,['field' => $fieldValue],'one');
    }
    public function updateWhere($data, $where)
    {
        return $this->dbDriver->update($this->table, $data, $where  );
    }

    protected function getData($data)
    {
        foreach ($data as $field => $value) {
            if ( !isset($this->sheme[$field]) ) {
                unset($data[$field]);
            }
        }
        return $data;
    }
}


?>