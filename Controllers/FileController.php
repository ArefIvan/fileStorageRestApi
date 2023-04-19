<?php
namespace Controllers;

use Core\DB;
use Entity\File;
use Core\DBDriver;
use Core\Validator;
use Entity\User;
use Exception;

class FileController extends BaseController
{

    public function __construct()
    {
        $this->entity = new File(new DBDriver(DB::getConnect()), new Validator);
        $this->errorController = new ErrorController;
        $this->errors['404'] = 'Файл не найден';
        $this->errors['403'] = 'Доступ запрещен';
    }

    public function list()
    {
        if ( $this->isAuth() ) {
            $user = $_SESSION['user'];
            header(self::$headerJson);
            echo json_encode(
                $this->entity->getAllFilesUser($user['id']) ,
                JSON_UNESCAPED_UNICODE
            );
        } else {
            $this->errorController->error('401', $this->errors['401']);

        }
    }

    public function add()
    {
        if ( $this->isAuth() ) {
            $user = $_SESSION['user'];
            if ( isset($_FILES['file']) && isset($_POST['path']) ) {
                if ( $_FILES['file']['error'] === UPLOAD_ERR_OK){ 
                    $requestData = [
                        'name' => $_FILES['file']['name'],
                        'path' => $_POST['path'],
                        'type' => $_FILES['file']['type'], 
                        'size' => $_FILES['file']['size'],
                        'user' => $user,
                        'is_dir' => 0,
                        'tmpName' => $_FILES['file']['tmp_name'],
                        ]
                    ;
                    try {
                        $idAddedItem = $this->entity->add($requestData);
                        http_response_code(201);
                        header(self::$headerJson);
                        header(sprintf('Location: %s%s%d', ROOT, $_SERVER["REQUEST_URI"], $idAddedItem));
                    
                    } catch ( Exception $e ) {
                        $this->errorController->error(code:422, error: $e->getMessage());
                    }
                } else {
                    $this->errorController->error(code:'500', error: $this->errorLoadMessage($_FILES['file']['error']) );
                }


            } else {
                $this->errorController->error('415', "Не правильный формат запроса");

            }
        } else {
            $this->errorController->error('401', $this->errors['401']);

        }

    }   
   
    public function update($id)
    {
        if ( $this->isAuth() ) { 
            $user = $_SESSION['user'];
            $item = $this->entity->getOne($id);
            if ( $item['owner'] === $user['id'] ) {
                  if ( $item['is_dir'] && $item ['parent_dir_id'] === null ){
                    $this->errorController->error(code:405, error: 'Корневую паку пользователя нельзя переименовать');
                  } else {
                    $requestData = $this->getData();
                    if ( $requestData ) {
                        try {
                            $this->entity->rename($item['id'], $requestData);
                            header(self::$headerJson);
                            header(sprintf('Location: %s%s', ROOT, $_SERVER["REQUEST_URI"]));                       
                        } catch ( Exception $e ) {
                            $this->errorController->error(code:422, error: $e->getMessage());
                        }
                    } else {
                        $error = (json_last_error_msg() == 'No error') ? 'Не переданны параметры':json_last_error_msg();
                        $this->errorController->error(code:415, error: $error) ;
                    } 
                  }
            } else {
                $this->errorController->error('403', $this->errors['403']);
            }
        } else {
            $this->errorController->error('401', $this->errors['401']);
        }
    }

    public function get($id)
    {
        if ( $this->isAuth() ) {
            $user = $_SESSION['user'];
            $item = $this->entity->getOne($id); 
            if ( $item['owner'] === $user['id'] ){
                $list = ($item['is_dir']) 
                    ? $this->entity->getFilesbyDirectory($item['id']) 
                    : $item;
                header(self::$headerJson);
                echo json_encode(
                    $list ,
                    JSON_UNESCAPED_UNICODE
                );
            } else {
                $this->errorController->error('403', $this->errors['403']);
            }
        } else {
            $this->errorController->error('401', $this->errors['401']);
        }    
    }

    public function delete($id)
    {
        if ( $this->isAuth() ) {
            $user = $_SESSION['user'];
            $item = $this->entity->getOne($id); 
            if ( $item['owner'] == $user['id'] ){
                if( $item['is_dir'] ) {
                    $this->entity->deleteDir($item['id']);                    
                } else {
                    $this->entity->deleteFile($item['id']);
                }
            } else {
                $this->errorController->error('403', $this->errors['403']);
            }
        } else {
            $this->errorController->error('401', $this->errors['401']);
        }        
    }

    public function directoryadd()
    {
        if ( $this->isAuth() ) { 
            $user = $_SESSION['user'];
            $requestData = $this->getData();
            if ( $requestData ) {
                $requestData['user'] =  $user;
                $requestData['is_dir'] =  true;
                try {
                    $idAddedItem = $this->entity->add($requestData);
                    http_response_code(201);
                    header(self::$headerJson);
                    header(sprintf('Location: %s%s%d', ROOT, $_SERVER["REQUEST_URI"], $idAddedItem));
                
                } catch ( Exception $e ) {
                    $this->errorController->error(code:422, error: $e->getMessage());
                }

            } else {
                $error = (json_last_error_msg() == 'No error') ? 'Не переданны параметры':json_last_error_msg();
                $this->errorController->error(code:415, error: $error) ;
            }
        } else {
            $this->errorController->error('403', $this->errors['403']);
        }
    } 

    public function directoryInfo($id)
    {
        $this->get($id);
    } 
    public function directoryRename($id)
    {
        $this->update($id);
    } 
    public function directoryDelete($id)
    {
        $this->delete($id);    
    }   
    public function userList($id)
    {
        if ( $this->isAuth() ) { 
            $user = $_SESSION['user'];
            $item = $this->entity->getOne($id);
            if ( $item['owner'] == $user['id'] ) {
                if ( $item['is_dir'] == 0) {
                    header(self::$headerJson);
                    echo json_encode(
                        $this->entity->getShareUser($id)  ,
                        JSON_UNESCAPED_UNICODE
                    );
                } else {
                    $this->errorController->error('404', "Файл не найден");
                }

            } else {
                $this->errorController->error('403', $this->errors['403']);
            }
        } else {
            $this->errorController->error('401', $this->errors['401']);
        }
    }
    public function userAdd($id, $userId)
    {
        if ( $this->isAuth() ) { 
            $user = $_SESSION['user'];
            $item = $this->entity->getOne($id);
            if ( $item['owner'] == $user['id'] ) {
                if ( $item && $item['is_dir'] == 0  ) {
                    if ( (new User(new DBDriver(DB::getConnect()), new Validator))->getOne($userId) ) {
                        $this->entity->addShareUser($id, $userId ) ;
                    } else {
                        $this->errorController->error('404', "Пользователь не найден");
                    }
                } else {
                    $this->errorController->error('404', "Файл не найден");
                }
            } else {
                $this->errorController->error('403', $this->errors['403']);
            }
        } else {
            $this->errorController->error('401', $this->errors['401']);
        }
    }    
    public function userDelete($id, $userId)
    {
        if ( $this->isAuth() ) { 
            $user = $_SESSION['user'];
            $item = $this->entity->getOne($id); 
            if ( $item && $item['is_dir'] == 0  ) {
                if ( (new User(new DBDriver(DB::getConnect()), new Validator))->getOne($userId) ) {
                    $this->entity->deleteShareUser($id, $userId) ;
                } else {
                    $this->errorController->error('404', "Пользователь не найден");
                }
            } else {
                $this->errorController->error('404', "Файл не найден");
            }

            if ( $item['owner'] == $user['id'] ) {
            } else {
                $this->errorController->error('403', $this->errors['403']);
            }
        } else {
            $this->errorController->error('401', $this->errors['401']);
        }
    } 

    private function errorLoadMessage(int $err)
    {
        switch ( $err ) {
            case 1 : return 'Файл больше разрешенного размера. Разрешенный размер: ' . ini_get('upload_max_filesize');
            break;
            case 3 : return 'Загружаемый файл был получен только частично';
            break;
            case 4 : return 'Файл не был загружен';
            break;
            case 6 : return 'Отсутствует временная папка';
            break;
            case 7 : return 'Не удалось записать файл на диск';
            break;
            case 8 : return 'Модуль PHP остановил загрузку файла.';
        }
    }

}