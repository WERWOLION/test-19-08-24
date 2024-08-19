<?php

namespace App\Service;


//Генерирует код для активации пользователя
class UserCodeGenerator
{
    public const RANDOM_STRING = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /**
     * @return string
     */
    public function getConfirmationCode()
    {
        $stringLength = strlen(self::RANDOM_STRING);
        $code = '';

        for ($i = 0; $i < $stringLength; $i++) {
            $code .= self::RANDOM_STRING[rand(0, $stringLength - 1)];
        }

        return $code;
    }
}
