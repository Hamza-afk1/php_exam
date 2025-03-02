<?php
class Validator {
    private $errors = [];
    private $data = [];
    
    public function __construct($data) {
        $this->data = $data;
    }
    
    public function validate($rules) {
        foreach ($rules as $field => $validations) {
            // Check if the field exists in the data
            if (!isset($this->data[$field]) && strpos($validations, 'required') !== false) {
                $this->addError($field, ucfirst($field) . ' is required');
                continue;
            }
            
            // If the field is not required and is empty, skip validation
            if (!isset($this->data[$field]) || empty($this->data[$field])) {
                if (strpos($validations, 'required') === false) {
                    continue;
                }
            }
            
            $value = isset($this->data[$field]) ? $this->data[$field] : '';
            $validationArray = explode('|', $validations);
            
            foreach ($validationArray as $validation) {
                $params = [];
                
                // Check if this validation has parameters
                if (strpos($validation, ':') !== false) {
                    list($rule, $param) = explode(':', $validation);
                    $params = explode(',', $param);
                } else {
                    $rule = $validation;
                }
                
                switch ($rule) {
                    case 'required':
                        if (empty($value)) {
                            $this->addError($field, ucfirst($field) . ' is required');
                        }
                        break;
                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $this->addError($field, ucfirst($field) . ' must be a valid email address');
                        }
                        break;
                    case 'min':
                        if (strlen($value) < $params[0]) {
                            $this->addError($field, ucfirst($field) . ' must be at least ' . $params[0] . ' characters');
                        }
                        break;
                    case 'max':
                        if (strlen($value) > $params[0]) {
                            $this->addError($field, ucfirst($field) . ' must be at most ' . $params[0] . ' characters');
                        }
                        break;
                    case 'matches':
                        $matchField = $params[0];
                        if ($value !== $this->data[$matchField]) {
                            $this->addError($field, ucfirst($field) . ' must match ' . $matchField);
                        }
                        break;
                    case 'unique':
                        // This requires a database check
                        // Implementation would depend on specific database setup
                        break;
                    case 'numeric':
                        if (!is_numeric($value)) {
                            $this->addError($field, ucfirst($field) . ' must be numeric');
                        }
                        break;
                    case 'int':
                        if (!filter_var($value, FILTER_VALIDATE_INT)) {
                            $this->addError($field, ucfirst($field) . ' must be an integer');
                        }
                        break;
                }
            }
        }
        
        return empty($this->errors);
    }
    
    private function addError($field, $message) {
        $this->errors[$field] = $message;
    }
    
    public function getErrors() {
        return $this->errors;
    }
    
    public function getError($field) {
        return isset($this->errors[$field]) ? $this->errors[$field] : '';
    }
    
    public function hasErrors() {
        return !empty($this->errors);
    }
}
?>
