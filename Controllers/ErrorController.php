<?php
namespace Controllers;

class ErrorController
{
    public function error($code = 404, $error = "url not found"  )
    {
        http_response_code($code);
        header('Content-Type: text/plain; charset=UTF-8');
        echo  $error ;
    }

} 