<?php

namespace Axm\Raxm\Support;

use Axm\Validation\Validator;

trait ValidatesInput
{
    protected $errorBag;
    protected $withValidatorCallback;

    public function getErrorBag()
    {
        return $this->errorBag ?? [];
    }

    public function hasErrorBag()
    {
        return !empty($this->errorBag);
    }

    public function addError($name, $message)
    {
        return $this->errorBag[$name] = $message;
    }

    public function setErrorBag($bag)
    {
        return $this->errorBag = !empty($bag)
            ? $bag
            : [];
    }

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

    public function removeValidation($field = null)
    {
        return Validator::getInstance()
            ->removeValidation($field);
    }

    public function resetValidation($field = null)
    {
        $this->resetErrorBag($field);
    }

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

    protected function getRules()
    {
        if (method_exists($this, 'rules')) return $this->rules();
        if (property_exists($this, 'rules')) return $this->rules;

        return [];
    }

    protected function getMessages()
    {
        if (method_exists($this, 'messages')) return $this->messages();
        if (property_exists($this, 'messages')) return $this->messages;

        return [];
    }

    protected function getValidationAttributes()
    {
        if (method_exists($this, 'validationAttributes')) return $this->validationAttributes();
        if (property_exists($this, 'validationAttributes')) return $this->validationAttributes;

        return [];
    }

    protected function getValidationCustomValues()
    {
        if (method_exists($this, 'validationCustomValues')) return $this->validationCustomValues();
        if (property_exists($this, 'validationCustomValues')) return $this->validationCustomValues;

        return [];
    }

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

    public function ruleWithNumbersReplacedByStars($dotNotatedProperty)
    {
        return preg_replace('/\d+/', '*', $dotNotatedProperty);
    }

    public function missingRuleFor($dotNotatedProperty)
    {
        return !$this->hasRuleFor($dotNotatedProperty);
    }

    protected function checkRuleMatchesProperty($rules, $data)
    {
        foreach (array_keys($rules) as $ruleKey) {
            if (!array_key_exists($this->beforeFirstDot($ruleKey), $data)) {
                throw new \Exception('No property found for validation: [' . $ruleKey . ']');
            }
        }
    }

    public function validate()
    {
        $packRules = $this->getRules();

        if (empty($packRules)) return [];

        $inputData  = $this->serverMemo['data'] ?? [];
        $this->validateCompile($inputData, $packRules);
    }


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


    protected function getDataForValidation()
    {
        return $this->getPublicPropertiesDefinedBySubClass();
    }
}
