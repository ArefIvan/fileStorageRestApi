<?php
namespace Controllers;

class AdminController extends UserController
{
    private $error = 'Войдите под учетной записью администратора';
    public function userList()
    {
        if ( $this->isAdmin()) {
            parent::list();
        } else {
            $this->callError();
        }
 
    } 
    public function userAdd()
    {
        if ( $this->isAdmin()) {
            parent::add();
        } else {
            $this->callError();
        }
    }   
    public function userGet($id)
    {
        if ( $this->isAdmin()) {
            parent::get($id);
        } else {
            $this->callError();
        }
    }   
    public function userUpdate($id)
    {
        if ( $this->isAdmin()) {
            parent::update($id);
        } else {
            $this->callError();
        }
    }
    public function userDelete($id)
    {
        if ( $this->isAdmin()) {
            parent::delete($id);
        } else {
            $this->callError();
        }
    }
    private function callError()
    {
        (new ErrorController)->error(code : 403, error : 'Войдите под учетной записью администратора');
   
    }

}