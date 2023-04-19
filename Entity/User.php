<?php

namespace Entity;

use Core\DBDriver;
use Core\Validator;
use Entity\BaseEntity;
use Exception;
use PDOException;

class User extends BaseEntity
{
    private $hash = 'asris';
    private int $id;
    private string $email;
    private string $password;
    private int $role = 2;
    private string $firstName;
    private string $lastName;
    private $date_registed;

    protected $sheme = [
        'id' => ['autofilling' => true],
        'email' => [ 'type' => 'email',
                     'required' => true,
                     'length' => [ 'min' => 6, 'max' => 40]
        ],
        'password' => [ 'length' => [ 'min' => 6, 'max' => 20],
        ],
        'role' =>[],
        'first_name' => [ 'length' => [ 'min' => 0, 'max' => 20] ],
        'last_name' => [ 'lenghth' => [ 'min' => 0, 'max' => 20] ],
        'date_registed' => ['autofilling' => true],


    ];

    public function __construct(DBDriver $dBDriver, Validator $validator)
    {
        parent::__construct($dBDriver, 'User', $validator);
        $this->validator->setRules($this->sheme);
    }

    public function getAllWithPublicField()
    {
        $sql = sprintf('SELECT u.email, u.first_name, u.last_name ,r.name as user_role FROM %s as u 
                        LEFT JOIN `Role` as r ON u.role = r.id ',
                        $this->table);

	    return $this->dbDriver->select($sql);
        
    }

    public function add(array $data)
    {
        $this->validator->addRule('password', ['required' => true]);
        if ( $this->validator->execute($data) ) {
            $data = $this->getData($data);
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            try {
                return parent::add($data);
            } catch (PDOException $e) {
                if ( $e->getCode() == '23000' ) {
                    throw new Exception('Такой email уже существует');
                } else {
                    throw $e;
                }
            }
        } else {
            throw new Exception(implode(PHP_EOL, $this->validator->getErrors()));
        }
    }
    public function update($id, $data)
    {
        $this->validator->removeRule('email','required');
        if ( $this->validator->execute($data) ) {
            $data = $this->getData($data);
            if ( isset($data['password']) ){
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            try {
                parent::update($id, $data);
            } catch (PDOException $e) {
                if ( $e->getCode() == '23000' ) {
                    throw new Exception('Такой email уже существует');
                } else {
                    throw $e;
                }
            }
        } else {
            throw new Exception(implode(PHP_EOL, $this->validator->getErrors()));
        }
    } 
    public function getAuthUserByEmail($data)
    {
        $this->validator->addRule('password', ['required' => true]);

        if ( $this->validator->execute($data) ) {
            $data = $this->getData($data);
            $user = $this->getOneByField('email' , $data['email'] );
            if ( $user && password_verify($data['password'], $user['password'])) {
                return $user;
            } else {
                return false;
            } 
        } else {
            throw new Exception(implode(PHP_EOL, $this->validator->getErrors()));
        }

    }
    public function getUserByEmail($data)
    {  
        return $this->getOneByField('email' , $data );
    }

  
}