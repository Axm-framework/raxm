<?php

if (!function_exists('error')) {
    /**
     * Function error
     *
     * This function is used to retrieve an error message associated with a specific field.
     * @param string $field             The name of the field for which you want to retrieve an error message.
     * @param array|string $messages    An associative array of error messages where keys are field names and values are corresponding error messages. Alternatively, it can be a string representing a single error message.
     * @param string $defaultMessage    (Optional) A default error message to use if no message is found for the specified field in $messages.
     * @return string                   Returns the error message associated with the specified field. If no message is found for the field, it returns the default message or an empty string if no default message is provided.
     */
    if (!function_exists('error')) {

        function error(string $field, array|string $messages = [], string $defaultMessage = ''): string
        {
            if (is_string($messages)) {
                return $messages;
            }

            if (is_array($messages) && empty($messages[$field])) {
                return $defaultMessage;
            }

            return $messages[$field] ?? $defaultMessage;
        }
    }
}
