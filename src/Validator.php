<?php

namespace App\Validator;

use App\ValidatorInterface\ValidatorInterface;

class Validator
{
    public function validate(array $data)
    {
        $errors = [];
        if ($data['name'] === '') {
            $errors['name'] = "Can't be blank";
        }
        if (strlen($data['name']) < 4) {
            $errors['name'] = "Name should be longer then 4 letters";
        }

        if ($data['email'] === '') {
            $errors['email'] = "Can't be blank";
        }
        if ((strpos($data['email'], '@') === false)) {
            $errors['email'] = "Email should have @";
        }
        return $errors;
    }
}
