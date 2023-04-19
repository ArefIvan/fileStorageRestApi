<?php
namespace Controllers;

use Core\DB;
use Exception;
use Entity\User;
use Core\DBDriver;
use Core\Validator;
use Entity\File;
use PHPMailer\PHPMailer\PHPMailer;

class UserController extends BaseController
{

    // private static $headerJson = 'Content-type: application/json; charset=utf-8';
    private static $salt = 'ArisIvanSkillbox';
    public function __construct()
    {
        $this->entity = new User(new DBDriver(DB::getConnect()), new Validator);
        $this->errorController = new ErrorController;
        $this->errors['404'] = 'User not found';

    }

    public function add()
    {
        $requestData = $this->getData();
        if ( null == $requestData ) {
            $error = (json_last_error_msg() == 'No error') ? 'Не переданны параметры':json_last_error_msg();
            $this->errorController->error(code:415, error: $error) ;
        } else {
            try {
                $idUser = $this->entity->add($requestData);
                (new File(new DBDriver(DB::getConnect()), new Validator))->addUSerRootDirectory($idUser);
                http_response_code(201);
                header(self::$headerJson);
                header(sprintf('Location: %s%s%d', ROOT, $_SERVER["REQUEST_URI"], $idUser));
            
            } catch ( Exception $e ) {
                $this->errorController->error(code:422, error: $e->getMessage());
            }
        }
    }  
    public function search($email)
    {
        if ( $user = $this->entity->getUserByEmail($email) ) {
            header(self::$headerJson);
            echo json_encode($user, JSON_UNESCAPED_UNICODE);
        } else {
            $this->errorController->error(code:404, error:'Пользователь с таким email не найден');
        }
    }

    public function login()
    {
            $requestData = $this->getData();

            if ( $requestData ) {
            if ( $user = $this->entity->getAuthUserByEmail($requestData) ) {
                $_SESSION['user'] = $user;
            } else {
                $this->errorController->error(code:404, error:'Пользователь с таким email  и паролем не найден');
            }
        } else {
            $error = (json_last_error_msg() == 'No error') ? 'Не переданны параметры':json_last_error_msg();
            $this->errorController->error(code:415, error: $error) ;
        }     
    }
    public function logout()
    {
        if ( isset($_SESSION['user']) ) {
            unset($_SESSION['user']);
        }
        setcookie('PHPSESSID' , "", time()-200 ,'/');
    }
    public function resetPassword()
    {
        $requestData = $this->getData();
        if ( null == $requestData ) {
            $error = (json_last_error_msg() == 'No error') ? 'Не переданны параметры':json_last_error_msg();
            $this->errorController->error(code:415, error: $error) ;
        } else {
            if ( !isset($requestData['code']) && isset($requestData['email']) ) {
                if ( $user = $this->entity->getUserByEmail($requestData['email']) ) {
                    $code = $this->myHash($user['email']);
                    try {
                        $mail = new PHPMailer(true);
                        $mail->CharSet = $mail::CHARSET_UTF8;
                        $mail->isSMTP();
                        $mail->SMTPAuth = true;
                        $mail->Host = MAIL_HOST;
                        $mail->Port = MAIL_PORT;
                        $mail->Username = MAIL_USERNAME;
                        $mail->Password = MAIL_USER_PASS;
                        $mail->SMTPSecure = "tls";
                    
                        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
                        $mail->addAddress($user['email'], $user['first_name'] ?? 'User');
                    
                        $mail->isHTML(true); 
                        $mail->Subject = "Восстановление пароля";
                        $mail->Body = sprintf(
                            'Перейдите по ссылке: <a href=" %s/resetpassword?email=%s&code=%s ">восстановить пароль</a> , чтобы получить новый временный пароль ',
                            ROOT,
                            $user['email'],
                            $code
                        );
                    
                        $mail->send();
                    } catch (Exception $e) {
                        $this->errorController->error(code:500, error:'Не удалось отправить письмо');

                    }
                } else {
                    $this->errorController->error(code:404, error:'Пользователь с таким email не найден');
                }
            }      
            elseif ( isset($requestData['email']) && isset($requestData['code']) ){
                if ( $user = $this->entity->getOneByField('email', $_GET['email']) ) {
                    if ( $_GET['code'] == $this->myHash($user['email']) ) {
                        $newPassword = $this->randomPass();
                        $this->entity->update($user['id'], ['password' => $newPassword]);
                        try {
                            $mail = new PHPMailer(true);
                            $mail->CharSet = $mail::CHARSET_UTF8;
                            $mail->isSMTP();
                            $mail->SMTPAuth = true;
                            $mail->Host = MAIL_HOST;
                            $mail->Port = MAIL_PORT;
                            $mail->Username = MAIL_USERNAME;
                            $mail->Password = MAIL_USER_PASS;
                            $mail->SMTPSecure = "tls";
                        
                            $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
                            $mail->addAddress($user['email'], $user['first_name'] ?? 'User');
                        
                            $mail->isHTML(true); 
                            $mail->Subject = "Восстановление пароля";
                            $mail->Body = sprintf(
                                'Ваш пароль заменен на временный. Обязательно поменяйте его. <br> Email: %s <br> Временный пароль: %s',
                                $user['email'],
                                $newPassword
                            );
                        
                            $mail->send();
                        } catch (Exception $e) {
                            $this->errorController->error(code:500, error:'Не ужалось отправить письмо');

                        }
                        header(self::$headerJson);
                        echo json_encode([
                            'id'=> $user['id'],
                            'email' => $user['email'],
                            'password' => $newPassword
                        ],
                            JSON_UNESCAPED_UNICODE
                        ); 
                    } else {
                        $this->errorController->error(code:400, error:'Неправильный код запроса');
                    }
                }
            } else {
                $this->errorController->error(code:400, error:'укажите email');
            }   
        }
    }


    private function myHash($str){
		return hash('sha256', $str . self::$salt);
    } 

    private function randomPass($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    

}