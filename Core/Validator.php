<?php

namespace Core;

class Validator
{
    private $rules;

    private $errors = [];
    private $clear = [];
    private $success = false;
    
    public function setRules(array $rules)
    {
        $this->rules = $rules;
    }
    public function addRule(string $field, $rules)    
    {
        if ( isset($this->rules[$field]) ) {
            $this->rules[$field] += $rules;
        } else {
            $this->rules[$field] = $rules;
        }

    }

    public function removeRule(string $field, $rules)    
    {
        if ( isset($this->rules[$field]) ) {
            unset($this->rules[$field][$rules]);
        } 
    }
    

    public function execute($data)
    {
        // foreach ($data as $field => $value) {
        //     if ( !isset($this->rules[$field]) ) {
        //         $this->errors[] = sprintf( 'Недопустимое поле %s', $field );
        //     }
        // }
        foreach($this->rules as $field => $rules)
        {
            if ( isset($rules['required']) ) {
               if ( !isset($data[$field]) || trim($data[$field]) == '' ) {
                $this->errors[] = sprintf( 'Поле %s должно быть заполнено' , $field);
                } 
    
            }
            if ( isset($data[$field]) ) {
                $itemValidated = $data[$field];
                if ( isset($rules['type']) ) {
                    switch ( $rules['type']) {
                        case 'email' :
                            $this->checkEmail($itemValidated);
                            break;
                        case 'file' :
                            $this->checkFileName($itemValidated);
                            break;
                        case 'bool' :
                            $this->checkBool($itemValidated, $field);
                            break;
                        default:
                            $this->errors[] = sprintf('неизвестный формат для валидации');
                    }
                }
                if ( isset($rules['length']) ) {
                    $this->checkLength($itemValidated, 
                                        $rules['length']['min'], 
                                        $rules['length']['max'],
                                        $field );
                }
                if ( isset($rules['autofilling']) ) {
                    if( isset($data[$field]))  {
                        $this->errors[] = sprintf('Поле %s не может быть созданно или измененно', $field);
                    }
                }
                if ( isset($rules['maxSize']) ) {
                    $this->checkSize($itemValidated, 
                                        $rules['maxSize'],
                                        $field );
                }
                
            }
            $this->clear[] = $field;
        }

        if ( $this->errors == [] ) {
            $this->success = true;
        }
    
        return $this->getSuccess();
    }

    private function checkEmail( string $data )
    {
        if ( preg_match('#^[a-zA-Z0-9_.-]+@[a-z_.-]+\.[a-z]{2,}$#', $data) ) {
            return true;
        } else {
            $this->errors[] = sprintf('Некорректный email');
            return false;
        }
    }
    private function checkFileName( string $data )
    {
        if ( preg_match('#^[^\/]+$#', $data) ) {
            return true;
        } else {
            $this->errors[] = sprintf('Некорректное название файла');
            return false;
        }
    }
    private function checkBool($data, $field)
    {
        return  is_bool($data)??$this->errors[] = sprintf(' Поле %s должно быть bool', $field);
    }
    private function checkLength( string $data , int $min = 0 , int $max = 255 , string $field = '') {
        $length = mb_strlen(trim($data));
        if ( $length < $min ) {
            $this->errors[] = sprintf('Длина %s должна быть больше %d' , $field, $min);
            return false;
        }
        if ( $length > $max ) {
            $this->errors[] = sprintf('Длина %s должна быть меньше %d' , $field, $max);
            return false;
        }
        return true;
    }
    private function checkSize( int $data ,int $max = UPLOAD_ERR_INI_SIZE , string $field = '') {
        if ( $data > $max ) {
            $this->errors[] = sprintf('Размер файла не должен быть больше %d' , $max);
            return false;
        }
        return true;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getSuccess()
    {
        return $this->success;
    }

    public function getClear()
    {
        return $this->clear;
    }
}