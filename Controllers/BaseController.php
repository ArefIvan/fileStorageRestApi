<?php 

namespace Controllers;

use Exception;

class BaseController
{
    protected $entity;
    protected $errorController;
    protected $errors = [
        "400" => '',
        '401' => 'Войдите под своей учетной записью',
        '404' => '',
        '403' => 'Войдите под учетной записью администратора',
        '415' => '',
        '422' => ''];
    protected static $headerJson = 'Content-type: application/json; charset=utf-8';
    
    public function list() {
        header(self::$headerJson);
        echo json_encode(
            $this->entity->getAll() ,
            JSON_UNESCAPED_UNICODE
        ); 
    }
    public function get($id)
    {
        $item = $this->entity->getOne($id);
        if ($item) {
            header(self::$headerJson);
            echo json_encode($item, JSON_UNESCAPED_UNICODE);
        } else {
            $this->errorController->error(code:404, error: $this->errors['404']);
        }
    }  
    public function update($id)
    {
        if ( $item = $this->entity->getOne($id) ) {
            $requestData = $this->getData();
            if ( null == $requestData ) {
                $error = (json_last_error_msg() == 'No error') ? 'Не переданны параметры':json_last_error_msg();
                $this->errorController->error(code:415, error: $error) ;
            } else {
                $this->entity->update($id, $requestData);
            }
        } else {
            $this->errorController->error(code:404, error: $this->errors['404']);
        }
    }

    public function add()
    {
        $requestData = $this->getData();
        if ( null == $requestData ) {
            $this->errorController->error(code:415, error: json_last_error_msg());
        } else {
            try {
                $idAddedItem = $this->entity->add($requestData);
                http_response_code(201);
                header(self::$headerJson);
                header(sprintf('Location: %s%s%d', ROOT, $_SERVER["REQUEST_URI"], $idAddedItem));
            
            } catch ( Exception $e ) {
                $this->errorController->error(code:422, error: $e->getMessage());
            }
        }
    }  
    public function delete($id)
    {
        if ( $item = $this->entity->getOne($id) ) {
            try{
                $this->entity->delete($id);
                http_response_code(204);
            } catch(Exception $e) {
                $this->errorController->error(error:$e->getMessage());
            }
        } else {
            $this->errorController->error(code:404, error:$this->errors['404']);
        }        
    }

    protected function isAdmin()
    {
        return isset($_SESSION['user']) && $_SESSION['user']['role'] == '1';
   
    }

    protected function isAuth()
    {
        return isset($_SESSION['user']);
    }

    protected function getData()
    {
        if ( isset($_SERVER["CONTENT_TYPE"]) && $_SERVER["CONTENT_TYPE"] == 'application/json') {
            return json_decode(file_get_contents('php://input'),true);
        } elseif ( $_SERVER['REQUEST_METHOD'] == 'GET' ) {
            return $_GET;
        } elseif ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
            return $_POST;
        } else {
            return $this->parse_raw_http_request();          
        }
    }
    private function parse_raw_http_request()
    {
      $input = file_get_contents('php://input');

      preg_match('/boundary=(.*)$/', $_SERVER['CONTENT_TYPE'], $matches);
      $boundary = $matches[1];
 
      
      $a_blocks = preg_split("/-+$boundary/", $input);
      array_pop($a_blocks);

          
      foreach ($a_blocks as $id => $block)
      {

        if (empty($block))
          continue;
        if (strpos($block, 'application/octet-stream') !== FALSE)
        {
          preg_match('/name=\"([^\"]*)\".*stream[\n|\r]+([^\n\r].*)?$/s', $block, $matches);
        }
        else
        {
          preg_match('/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $block, $matches);

        }
        $a_data[$matches[1]] = $matches[2];
      }
      return $a_data;
    }
}