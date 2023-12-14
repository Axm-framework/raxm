<?php

namespace Axm\Raxm\Support;

use Axm\Validation\Validator;

/**
 * A trait for handling input validation and error tracking.
 */
trait ValidatesInput
{
    /**
     * @var array The error bag to store validation errors.
     */
    protected $errorBag;

    /**
     * @var callable|null Callback to execute with the validator.
     */
    protected $withValidatorCallback;

    /**
     * Get the error bag containing validation errors.
     * @return array The error bag.
     */
    public function getErrorBag()
    {
        return $this->errorBag ?? [];
    }

    /**
     * Check if there are errors in the error bag.
     * @return bool Whether the error bag is not empty.
     */
    public function hasErrorBag()
    {
        return !empty($this->errorBag);
    }

    /**
     * Add an error to the error bag.
     *
     * @param string $name    The name of the error.
     * @param string $message The error message.
     * @return string The added error message.
     */
    public function addError($name, $message)
    {
        return $this->errorBag[$name] = $message;
    }

    /**
     * Set the error bag to a specific value.
     *
     * @param array $bag The error bag to set.
     * @return array The set error bag.
     */
    public function setErrorBag($bag)
    {
        return $this->errorBag = !empty($bag)
            ? $bag
            : [];
    }
    
    /**
     * resetErrorBag
     *
     * @param  mixed $field
     * @return void
     */
    public function resetErrorBag($field = null)
    {
        $fields = (array) $field;

        if (empty($fields)) {
            return $this->errorBag = [];
        }

        $this->setErrorBag(
            $this->errorBagExcept($fields)
        );
    }
    
    /**
     * removeValidation
     *
     * @param  mixed $field
     * @return void
     */
    public function removeValidation($field = null)
    {
        return Validator::getInstance()
            ->removeValidation($field);
    }
    
    /**
     * resetValidation
     *
     * @param  mixed $field
     * @return void
     */
    public function resetValidation($field = null)
    {
        $this->resetErrorBag($field);
    }
    
    /**
     * errorBagExcept
     *
     * @param  mixed $fields
     * @return void
     */
    public function errorBagExcept($fields)
    {
        $filteredErrors = [];
        foreach ($this->errorBag as $key => $messages) {
            if (!in_array($key, $fields)) {
                $filteredErrors[$key] = $messages;
            }
        }

        return $filteredErrors;
    }

    /**
     * Get the validation rules defined by the subclass.
     * @return array The validation rules.
     */
    protected function getRules()
    {
        if (method_exists($this, 'rules')) return $this->rules();
        if (property_exists($this, 'rules')) return $this->rules;

        return [];
    }

    /**
     * Get the validation messages defined by the subclass.
     * @return array The validation messages.
     */
    protected function getMessages()
    {
        if (method_exists($this, 'messages')) return $this->messages();
        if (property_exists($this, 'messages')) return $this->messages;

        return [];
    }

    /**
     * Get the validation attributes defined by the subclass.
     * @return array The validation attributes.
     */
    protected function getValidationAttributes()
    {
        if (method_exists($this, 'validationAttributes')) return $this->validationAttributes();
        if (property_exists($this, 'validationAttributes')) return $this->validationAttributes;

        return [];
    }
    
    /**
     * getValidationCustomValues
     *
     * @return void
     */
    protected function getValidationCustomValues()
    {
        if (method_exists($this, 'validationCustomValues')) return $this->validationCustomValues();
        if (property_exists($this, 'validationCustomValues')) return $this->validationCustomValues;

        return [];
    }
    
    /**
     * rulesForModel
     *
     * @param  mixed $rules
     * @param  mixed $name
     * @return void
     */
    function rulesForModel($rules, $name)
    {
        $filteredRules = [];

        if (empty($rules)) {
            return $filteredRules;
        }

        foreach ($rules as $key => $value) {
            if ($this->beforeFirstDot($key) === $name) {
                $filteredRules[$key] = $value;
            }
        }

        return $filteredRules;
    }
    
    /**
     * hasRuleFor
     *
     * @param  mixed $dotNotatedProperty
     * @return void
     */
    public function hasRuleFor($dotNotatedProperty)
    {
        $rules = $this->getRules();
        $propertyWithStarsInsteadOfNumbers = $this->ruleWithNumbersReplacedByStars($dotNotatedProperty);

        // If the property has numeric indexes on it,
        if ($dotNotatedProperty !== $propertyWithStarsInsteadOfNumbers) {
            $ruleKeys = array_keys($rules);
            return in_array($propertyWithStarsInsteadOfNumbers, $ruleKeys);
        }

        $ruleKeys = array_keys($rules);
        $filteredKeys = array_map(function ($key) {
            return explode('.*', $key)[0];
        }, $ruleKeys);

        return in_array($dotNotatedProperty, $filteredKeys);
    }
    
    /**
     * ruleWithNumbersReplacedByStars
     *
     * @param  mixed $dotNotatedProperty
     * @return void
     */
    public function ruleWithNumbersReplacedByStars($dotNotatedProperty)
    {
        return preg_replace('/\d+/', '*', $dotNotatedProperty);
    }
    
    /**
     * missingRuleFor
     *
     * @param  mixed $dotNotatedProperty
     * @return void
     */
    public function missingRuleFor($dotNotatedProperty)
    {
        return !$this->hasRuleFor($dotNotatedProperty);
    }
    
    /**
     * checkRuleMatchesProperty
     *
     * @param  mixed $rules
     * @param  mixed $data
     * @return void
     */
    protected function checkRuleMatchesProperty($rules, $data)
    {
        foreach (array_keys($rules) as $ruleKey) {
            if (!array_key_exists($this->beforeFirstDot($ruleKey), $data)) {
                throw new \Exception('No property found for validation: [' . $ruleKey . ']');
            }
        }
    }

    /**
     * Perform validation on the provided input data using defined rules.
     */
    public function validate()
    {
        $packRules = $this->getRules();

        if (empty($packRules)) return [];

        $inputData  = $this->serverMemo['data'] ?? [];
        $this->validateCompile($inputData, $packRules);
    }

    /**
     * Perform validation on a specific field using defined rules.
     * @param string $field The field to validate.
     */
    public function validateOnly($field)
    {
        $packRules = $this->getRules();
        if (empty($packRules)) return [];

        $data  = data_get($this->updates, '0.payload');

        if (!isset($data['name'])) return;

        $name  = $data['name'];
        $value = $data['value'];

        $inputData  = [$name => $value];
        $this->validateCompile($inputData, $packRules);
    }

    /**
     * Compile and execute validation based on provided input data and rules.
     *
     * @param array $inputData The input data to validate.
     * @param array $packRules The validation rules to apply.
     */
    public function validateCompile($inputData, $packRules)
    {
        $matchRules = array_intersect_key($inputData, $packRules);
        $validator  = Validator::make($packRules, $matchRules);

        if ($validator->fails()) {
            foreach ($matchRules as $field => $rule) {
                $this->addError($field, $validator->getFirstErrorByField($field));
            }
        }

        $this->messages = $this->getErrorBag();
    }

    /**
     * Get the data to be used for validation.
     * @return array The data for validation.
     */
    protected function getDataForValidation()
    {
        return $this->getPublicPropertiesDefinedBySubClass();
    }
}
