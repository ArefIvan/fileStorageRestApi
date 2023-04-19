<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

use Controllers\ErrorController;
use Core\Router;

require_once('../config.php');
require_once('../autoload.php');
session_start();

$urlList = [
        '/user/' => ['GET' => 'UserController::list',
                     'POST' => 'UserController::add',                  
                    ],
        '/user/{id}' => ['GET' => 'UserController::get',
                        'PUT' => 'UserController::update',                  
                        'DELETE' => 'UserController::delete',                  
                    ],
        '/login/' => ['GET' => 'UserController::login'],
        '/logout/' => ['GET' => 'UserController::logout'],
        '/resetpassword/' => ['GET' => 'UserController::resetPassword'],
        '/user/search/{email}' => ['GET' => 'UserController::search'],
        '/admin/user/' => ['GET' => 'AdminController::userList',],
        '/admin/user/{id}' => ['GET' => 'AdminController::userGet',
                       'PUT' => 'AdminController::userUpdate',                  
                       'DELETE' => 'AdminController::userDelete',                  
                   ],
        '/file/' => ['GET' => 'FileController::list',
                   'POST' => 'FileController::add',                  
                  ],
        '/file/{id}' => ['GET' => 'FileController::get',
                      'PUT' => 'FileController::update',                  
                      'DELETE' => 'FileController::delete',                  
                  ],  
        '/files/share/{id}' => ['GET' => 'FileController::userList'],      
        '/files/share/{id}/{user_id}' => ['PUT' => 'FileController::userAdd',                  
                  'DELETE' => 'FileController::userDelete',                  
              ],
        '/directory/' => ['POST' => 'FileController::directoryAdd',                  
                 ],
        '/directory/{id}' => ['GET' => 'FileController::directoryInfo',
                     'PUT' => 'FileController::directoryRename',                  
                     'DELETE' => 'FileController::directoryDelete',                  
                 ],
    ];
if ( $_SERVER['REQUEST_URI'] !== '/' ) {
    $router = New Router($urlList, $_SERVER["REQUEST_URI"], $_SERVER['REQUEST_METHOD']);
    try{
        $router->run();
    } catch(Exception $e) {
        (new ErrorController)->error(code:500, error: $e->getMessage());
    }
}
