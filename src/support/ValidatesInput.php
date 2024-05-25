<?php

declare(strict_types=1);

namespace Axm\Raxm\Support;

use Validation\Validator;


/**
 * A trait for handling input validation and error tracking.
 */
trait ValidatesInput
{
    /**
     * The error bag to store validation errors.
     */
    protected array $errorBag;

    /**
     * Callback to execute with the validator.
     */
    protected $withValidatorCallback;

    /**
     * Get the error bag containing validation errors.
     */
    public function getErrorBag(): array
    {
        return $this->errorBag ?? [];
    }

    /**
     * Check if there are errors in the error bag.
     */
    public function hasErrorBag(): bool
    {
        return !empty($this->errorBag);
    }

    /**
     * Add an error to the error bag.
     */
    public function addError(string $name, string $message): string
    {
        return $this->errorBag[$name] = $message;
    }

    /**
     * Set the error bag to a specific value.
     */
    public function setErrorBag(array $bag): array
    {
        return $this->errorBag = !empty($bag)
            ? $bag
            : [];
    }

    /**
     * Reset the error bag for a given field or all fields if no field is provided.
     * If a field is provided, only the errors for that field will be removed.
     */
    public function resetErrorBag(string|array $field = null)
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
     * Remove validation rules for a given field or all fields if no field is provided
     */
    public function removeValidation(mixed $field = null)
    {
        return Validator::getInstance()
            ->removeValidation($field);
    }

    /**
     * Reset validation errors for a specific field or all fields if no field is provided
     */
    public function resetValidation(mixed $field = null)
    {
        $this->resetErrorBag($field);
    }

    /**
     * Filter validation errors and exclude the specified fields
     */
    public function errorBagExcept(array $fields): array
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
     */
    protected function getRules(): array
    {
        if (method_exists($this, 'rules'))
            return $this->rules();
        if (property_exists($this, 'rules'))
            return $this->rules;

        return [];
    }

    /**
     * Get the validation messages defined by the subclass.
     */
    protected function getMessages(): array
    {
        if (method_exists($this, 'messages'))
            return $this->messages();
        if (property_exists($this, 'messages'))
            return $this->messages;

        return [];
    }

    /**
     * Get the validation attributes defined by the subclass.
     */
    protected function getValidationAttributes(): array
    {
        if (method_exists($this, 'validationAttributes'))
            return $this->validationAttributes();
        if (property_exists($this, 'validationAttributes'))
            return $this->validationAttributes;

        return [];
    }

    /**
     * Get the validation custom values defined by the subclass.
     */
    protected function getValidationCustomValues(): array
    {
        if (method_exists($this, 'validationCustomValues'))
            return $this->validationCustomValues();
        if (property_exists($this, 'validationCustomValues'))
            return $this->validationCustomValues;

        return [];
    }

    /**
     * Perform validation on the provided input data using defined rules.
     */
    function rulesForModel(mixed $rules, mixed $name): array
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
     * Check if a rule exists for the given dot-notated property.
     */
    public function hasRuleFor(mixed $dotNotatedProperty): bool
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
     * Replaces numbers in a dot-notated property string with asterisks.
     */
    public function ruleWithNumbersReplacedByStars(string $dotNotatedProperty): string
    {
        return preg_replace('/\d+/', '*', $dotNotatedProperty);
    }

    /**
     * Checks if a rule exists for a given dot-notated property.
     */
    public function missingRuleFor(mixed $dotNotatedProperty): bool
    {
        return !$this->hasRuleFor($dotNotatedProperty);
    }

    /**
     * Iterates through the rules and checks if any rule matches the given data.
     */
    protected function checkRuleMatchesProperty(array $rules, array $data)
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

        if (empty($packRules))
            return [];

        $inputData = $this->serverMemo['data'] ?? [];
        $this->validateCompile($inputData, $packRules);
    }

    /**
     * Perform validation on a specific field using defined rules.
     */
    public function validateOnly(string $field)
    {
        $packRules = $this->getRules();
        if (empty($packRules))
            return [];

        $data = data_get($this->updates, '0.payload');

        if (!isset($data['name']))
            return;

        $name = $data['name'];
        $value = $data['value'];

        $inputData = [$name => $value];
        $this->validateCompile($inputData, $packRules);
    }

    /**
     * Compile and execute validation based on provided input data and rules.
     */
    public function validateCompile(array $inputData, array $packRules)
    {
        $matchRules = array_intersect_key($inputData, $packRules);
        $validator = Validator::make($packRules, $matchRules);

        if ($validator->fails()) {
            foreach ($matchRules as $field => $rule) {
                $this->addError($field, $validator->getFirstErrorByField($field));
            }
        }

        $this->messages = $this->getErrorBag();
    }

    /**
     * Get the data to be used for validation.
     */
    protected function getDataForValidation(): array
    {
        return $this->getPublicPropertiesDefinedBySubClass();
    }
}
