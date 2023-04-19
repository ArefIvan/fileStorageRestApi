<?php

namespace Entity;

use Core\DBDriver;
use Core\Validator;
use Entity\BaseEntity;
use Exception;
use PDOException;

class File extends BaseEntity
{
    private $rootDir;
    protected $sheme = [
        'id' => ['autofilling'=>true],
        'name' => [ 
            'required' => true,
            'length' => [ 'min' => 1, 'max' => 240]
        ],
        'path' => [ 
            'length' => [ 'min' => 1, 'max' => 240],
        ],
        'parent_dir_id' => ["notFalse" => true],
        'owner' => ['autofilling'=>true],
        'is_dir' =>['type' => 'bool'],
        'date_loaded' => ['autofilling'=>true],
        'type' => ['allowedType' => ['']],
        'size' => ['maxSize' => 3*1024*1024],
    ];
    public function __construct(DBDriver $dBDriver, Validator $validator)
    {
        parent::__construct($dBDriver, 'File', $validator);
        $this->validator->setRules($this->sheme);
    }

    public function getAllFilesUser(int $userID)
    {
        $sql = sprintf('SELECT * FROM %s WHERE `owner` = :owner AND `parent_dir_id` IS NOT NULL ORDER BY `is_dir` DESC' , $this->table);
        $params = ['owner' => $userID];
	    return $this->dbDriver->select($sql, $params);
    }

    public function add(array $data)
    {
        $this->validator->addRule('path', ['required' => true]);
        $user = $data['user'];
        $tmpName = $data['tmpName'] ?? null;
        $data['path'] = '/' . $user['id'] . $data['path'];
        unset($data['user'],$data['tmpName']);
        if ( $this->validator->execute($data) ) {
            $data = $this->getData($data);
            $data['parent_dir_id'] = $this->pathtoId($user['id'], $data['path']);
            if ( $data['parent_dir_id'] === false ) {
                throw new Exception('путь не найден');
            }
            unset($data['size'],$data['type'],$data['path']);
            $data['owner'] = $user['id'];
                if ( $data['is_dir'] ) {
                    $data['name'] = $this->unicDirName($data['name'], $data['owner'], $data['parent_dir_id']);
                } else {
                    $this->rootDir = sprintf('./storage/%s/',$user['id']);
                    if (!file_exists($this->rootDir)) { 
                        mkdir($this->rootDir);
                    }
                    $data['unic_name'] = $this->unicFileName($user['id'] , $data['parent_dir_id']);
                    try {
                        move_uploaded_file($tmpName, $this->rootDir . $data['unic_name']);         
                    } catch(Exception $e) {
                        throw new Exception($e->getMessage());
                    }
                }           
                try { 
                    return parent::add($data);
                } catch (Exception $e) {
                    throw new Exception($e->getMessage());
                }
            
            
        } else {
            throw new Exception(implode(PHP_EOL, $this->validator->getErrors()));
        }
    }

    public function addUSerRootDirectory($id)
    {
        $this->rootDir = './storage/' . $id;
        if (!file_exists($this->rootDir)) { 
            mkdir($this->rootDir);
        }
        $data = ['name' => $id,
                'owner' =>$id, 
                'is_dir' => true,];
        return parent::add($data);
    }

    public function getFilesbyDirectory($dirId)
    {
        $sql = sprintf('SELECT * FROM %s WHERE `parent_dir_id` = :dirId AND `is_dir` = :is_dir' , $this->table );
        $params = ['dirId' => $dirId, 'is_dir' => 0];
	    return $this->dbDriver->select($sql, $params);

    }

    public function rename($id, $data)
    {
        if ( $this->validator->execute($data) ) {
            $data = $this->getData($data);
            $name = $data['name'];
            try {
                parent::update($id, ['name' => $name]);
            } catch (PDOException $e) {
                throw $e;
            }
        } else {
            throw new Exception(implode(PHP_EOL, $this->validator->getErrors()));
        }
    }

    public function deleteDir($dirId)
    {
        $files = $this->getFilesbyDirectory($dirId);

        foreach ($files as $file) {
            $fileName = sprintf('./storage/%d/%s', $file['owner'] , $file['unic_name']);
            unlink($fileName);
            $this->delete($file['id']);

        }
        $this->delete($dirId);
    }

    public function deleteFile($fileId)
    {
        $file = $this->getOne($fileId);
        $fileName = sprintf('./storage/%d/%s', $file['owner'] , $file['unic_name']);
        unlink($fileName);
        $this->delete($fileId);

    }

    public function getShareUser($idFile)
    {
        $sql = sprintf( 'SELECT u.id , u.email , u.first_name, u.last_name FROM `User` as u 
        JOIN User_File as fu ON u.id = fu.user_id 
        JOIN File as f ON f.id = fu.file_id 
        WHERE fu.file_id = :id');
        $params = [ 
            'id' => $idFile
        ];
        return $this->dbDriver->select($sql, $params);
    }

    public function deleteShareUser($idFile, $idUser )
    {
        $where = 'user_file = :user_file';
        $params = [ 
            'user_file' => $idUser . '_' . $idFile
        ];
        return $this->dbDriver->delete( 'User_File' ,$params, $where);
    }

    public function addShareUser($idFile ,$idUser)
    {
        $params = [ 
            'user_file' => $idUser . '_' .$idFile,
            'user_id' => $idUser,
            'file_id' => $idFile
        ];
        $this->dbDriver->add('User_File', $params);
    }

    private function unicDirName($name ,$idUser, $dirId)
    {
        $dirName = $name;
        $i=1;
        while($this->fileExistByName($dirName ,$idUser, $dirId) )
        {
            $dirName = $name. "_" . $i;
            $i++;
        } 
        return $dirName;
    }
    // private function unicFileName($name, $path) : string
    // {
    //     $ext='';
    //     $name = preg_replace_callback('#^([^\.]+)(\.{1}.+)$#', 
    //         function($matches) use (&$ext) {
    //             $ext = $matches[2];
    //             return $matches[1];
    //         },
    //     $name);       
    //     $filename = $name;
    //     $i = 1;
    //     while (file_exists( $path. $filename . $ext )) {
    //         $filename = $name. "_" . $i;
    //         $i++;
    //     }
    //     return $filename . $ext;
    // }
    private function unicFileName($prefix, $path) : string
    {
        $unicName = uniqid($prefix);
        while ( file_exists($unicName . $path)){
            $unicName = uniqid($prefix);
        }   
        return $unicName;
    }
    private function fileExistByName($name, $idUser, $id=null) {
        $isNull = (null == $id)?' IS NULL':'=:par_dir';
        $sql = sprintf('SELECT * FROM %s WHERE parent_dir_id%s AND `owner`=:idUser AND `name`=:name',
                        $this->table,
                        $isNull);
        $params = ['name' => $name , 'idUser' => $idUser];
        if ( $id ) {
            $params['par_dir'] = $id;
        }
        return ($this->dbDriver->select($sql, $params)) ? true : false;
    } 
    private function searchDir($name, $idUser, $id = null)
    {
        $isNull = (null == $id)?' IS NULL':'=:par_dir';
        $sql = sprintf('SELECT id FROM %s WHERE parent_dir_id%s AND `owner`=:idUser AND `name`=:name AND `is_dir`=:is_dir ',
                         $this->table,
                        $isNull);
        $params = ['name' => $name, 'is_dir' => 1, 'idUser' => $idUser];
        if ( $id ) {
            $params['par_dir'] = $id;
        }
	    return $this->dbDriver->select($sql, $params)[0]['id']??false;
    }

    private function pathtoId($idUser, string $path = null )
    {    
        $path = trim($path, '/');
        $parentId = null;
        if (!$path == '') {
            $names =  explode('/', $path);
            foreach ($names as $name) {
                if ( $parentId !== false)
                $parentId = $this->searchDir($name, $idUser, $parentId);
            }
        }
        return $parentId;

    }

}