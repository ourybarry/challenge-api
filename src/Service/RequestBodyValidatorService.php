<?php

namespace App\Service;

class RequestBodyValidatorService{
    /**
     * Check if an associative array has some required keys, return array of errors for each missing attribute,
     * if the resulting array is empty, it means the body is valid
     */
    public static function bodyIsValid(array $requiredKeys, $bodyToValidate) : array{
        $errors = [];
        foreach ($requiredKeys as $key) {
            //If a key is missing, we add it to errors array
            if (!array_key_exists($key, $bodyToValidate)) {
                $errors[$key] = 'This attribute is required';
            }
        }
        return $errors;
    }
}