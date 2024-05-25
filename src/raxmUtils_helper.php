<?php


if (!function_exists('raxm')) {

    /**
     * Initialize and use a Raxm component.
     * This function is used to initialize and use a Raxm component within the application.
     */
    function raxm(string $component): mixed
    {
        $raxm = app('raxm');
        $names = $raxm::parserComponent($component);

        // Initialize and use the specified Raxm component.
        return $raxm::mountComponent(new $names, true);
    }
}

if (!function_exists('error')) {
    /**
     * Function error
     * This function is used to retrieve an error message associated with a specific field.
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
