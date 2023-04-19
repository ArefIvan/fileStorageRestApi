<?php

namespace Core;


class Router
{
    private $id;
    private $userId;
    private $userEmail;
    private $url;
    private $routes;
    private $controller;
    private $method;
    private $requestMethod;
    private $route;
    private $params;

    public function __construct(array $routes, string $url, string $requestMethod)
    {
        $this->routes = $routes;
        $this->url = $url;
        $this->requestMethod = $requestMethod;
    }

    private function urlToRoute()
    {
        $this->searchParams();
        $this->searchEmail();
        $this->searchUserID();
        $this->searchID();
    }

    private function setRoute()
    {
        $this->urlToRoute();
        if( isset($this->routes[$this->url][$this->requestMethod] )) {            
            $this->route = explode("::", $this->routes[$this->url][$this->requestMethod]);
        }
        
    }
    private function setController()
    {
        if ( null == $this->route ){
            $this->controller = 'Controllers\\ErrorController';
            $this->method = 'error';
        } else {
            $this->controller = 'Controllers\\' . $this->route[0];
            $this->method = $this->route[1];
        }
    }

    private function searchParams()
    {
        $this->url =  preg_replace_callback('#/?\?{1}(.+)$#', 
        function($matches) {
                $this->params = $matches[1];
                return '/';
            },
        $this->url);
    }
    private function searchUserID()
    {
        $this->url = preg_replace_callback('#/([0-9]+)/([0-9]+)/?$#', 
            function($matches) {
                    $this->id = $matches[1];
                    $this->userId = $matches[2];
                    return '/{id}/{user_id}';
                },
            $this->url);
    }
    private function searchID()
    {
        $this->url = preg_replace_callback('#/([0-9]+)/?$#', 
            function($matches) {
                    $this->id = $matches[1];
                    return '/{id}';
                },
            $this->url);
    }
    private function searchEmail()
    {
        $this->url = preg_replace_callback('#/([a-zA-Z0-9_.-]+@[a-z_.-]+\.[a-z]{2,})/?$#', 
            function($matches) {
                    $this->userEmail = $matches[1];
                    return '/{email}';
                },
            $this->url);
    }


    public function run()
    {

        $this->setRoute();
        $this->setController();

        $controller = new $this->controller();
        $method = $this->method; 
        switch (true) {
            case $this->controller == 'Controllers\\ErrorController' :
                $controller->$method(); 
                break; 
            case $this->userEmail :
                $controller->$method($this->userEmail);
                break;
            case $this->userId :
                $controller->$method($this->id , $this->userId);
                break;
            case $this->id :
                $controller->$method($this->id);
                break;             
            default :
                $controller->$method();
        }
    }
}
