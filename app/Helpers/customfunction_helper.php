<?php
   
   function validateRequest($input, array $rules, array $messages =[]){
    $this->validator = Services::Validation()->setRules($rules);
    // If you replace the $rules array with the name of the group
    if (is_string($rules)) {
        $validation = config('Validation');

        // If the rule wasn't found in the \Config\Validation, we
        // should throw an exception so the developer can find it.
        if (!isset($validation->$rules)) {
            throw ValidationException::forRuleNotFound($rules);
        }

        // If no error message is defined, use the error message in the Config\Validation file
        if (!$messages) {
            $errorName = $rules . '_errors';
            $messages = $validation->$errorName ?? [];
        }

        $rules = $validation->$rules;
    }
    return $this->validator->setRules($rules, $messages)->run($input);
}  
    
    



?>