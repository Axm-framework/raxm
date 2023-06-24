<?php

namespace Axm\LiveAxm;

// use \Collection;
// use \Arr;
use Axm\Exception\AxmException;

class HtmlRootTagAttributeAdder

{

    // public function __invoke($dom, $data)
    // {
    //     helpers('enumerable', true);

    //     $stateInitial = collect($data)
    //         ->mapWithKeys(function ($value, $key) {
    //             return ["axm:{$key}" => static::escapeStringForHtml($value)];
    //         })->map(function ($value, $key) {
    //             return sprintf('%s="%s"', $key, $value);
    //         })->implode(' ');

    //     preg_match('/(?:\n\s*|^\s*)<([a-zA-Z0-9\-]+)/', $dom, $matches, PREG_OFFSET_CAPTURE);

    //     $tagName = $matches[1][0];
    //     $lengthOfTagName = strlen($tagName);
    //     $positionOfFirstCharacterInTagName = $matches[1][1];

    //     if (!count($matches)) throw new AxmException(
    //         "Liveaxm encountered a missing root tag when trying to render a " .
    //             "component. \n When rendering a Blade view, make sure it contains a root HTML tag."
    //     );

    //     return
    //         substr_replace(
    //             $dom,
    //             ' ' . $stateInitial,
    //             $positionOfFirstCharacterInTagName + $lengthOfTagName,
    //             0
    //         );
    // }

    public function __invoke($dom, $data)
    {
        $stateInitial = [];
        foreach ($data as $key => $value) {
            $stateInitial["axm:{$key}"] = static::escapeStringForHtml($value);
        }

        $stateInitial = array_map(function ($value, $key) {
            return sprintf('%s="%s"', $key, $value);
        }, $stateInitial, array_keys($stateInitial));

        $stateInitial = implode(' ', $stateInitial);

        preg_match('/(?:\n\s*|^\s*)<([a-zA-Z0-9\-]+)/', $dom, $matches, PREG_OFFSET_CAPTURE);

        $tagName = $matches[1][0];
        $lengthOfTagName = strlen($tagName);
        $positionOfFirstCharacterInTagName = $matches[1][1];

        if (!count($matches)) {
            throw new AxmException(
                'Liveaxm encountered a missing root tag when trying to render a ' .
                    "component. \n When rendering a Blade view, make sure it contains a root HTML tag."
            );
        }

        $newDom = substr_replace(
            $dom,
            ' ' . $stateInitial,
            $positionOfFirstCharacterInTagName + $lengthOfTagName,
            0
        );

        return $newDom;
    }


    protected static function escapeStringForHtml($subject)
    {
        if (is_string($subject) || is_numeric($subject)) {
            return htmlspecialchars($subject, ENT_QUOTES | ENT_SUBSTITUTE);
        }

        return htmlspecialchars(json_encode($subject), ENT_QUOTES | ENT_SUBSTITUTE);
    }
}
