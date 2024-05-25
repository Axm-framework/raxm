<?php

namespace Axm\Raxm;

use RuntimeException;

/**
 * The HtmlRootTagAttributeAdder class is responsible for a
 * dding attributes to the root HTML tag in a DOM string.
 */
class HtmlRootTagAttributeAdder
{
    /**
     * Adds attributes to the root HTML tag in the given DOM string.
     */
    public function __invoke(string $dom, array $data): array|string
    {
        // Initialize state with escaped attribute values.
        $stateInitial = [];
        foreach ($data as $key => $value) {
            $stateInitial["axm:{$key}"] = static::escapeStringForHtml($value);
        }

        // Convert state to attribute strings.
        $stateInitial = array_map(function ($value, $key) {
            return sprintf('%s="%s"', $key, $value);
        }, $stateInitial, array_keys($stateInitial));

        $stateInitial = implode(' ', $stateInitial);

        // Find the position of the root HTML tag and insert the attributes.
        preg_match('/(?:\n\s*|^\s*)<([a-zA-Z0-9\-]+)/', $dom, $matches, PREG_OFFSET_CAPTURE);

        $tagName = $matches[1][0];
        $lengthOfTagName = strlen($tagName);
        $positionOfFirstCharacterInTagName = $matches[1][1];

        if (!count($matches)) {
            throw new RuntimeException(
                'Raxm encountered a missing root tag when trying to render a ' .
                "component. \n When rendering a Blade view, make sure it contains a root HTML tag."
            );
        }

        // Insert the attributes into the DOM string.
        $newDom = substr_replace(
            $dom,
            ' ' . $stateInitial,
            $positionOfFirstCharacterInTagName + $lengthOfTagName,
            0
        );

        return $newDom;
    }

    /**
     * Escapes a string for safe inclusion in HTML attributes.
     */
    protected static function escapeStringForHtml($subject): string
    {
        if (is_string($subject) || is_numeric($subject)) {
            return htmlspecialchars($subject, ENT_QUOTES | ENT_SUBSTITUTE);
        }

        return htmlspecialchars(json_encode($subject), ENT_QUOTES | ENT_SUBSTITUTE);
    }
}
