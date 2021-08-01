<?php
declare(strict_types = 1);

namespace App\Libs;

use SimpleXMLElement;

/**
 * Convert a multi-dimensional associative array to an arrayPathCollection where each entry contains contains an array of each key (path) at index [0] and the value at index [1].
 * @param array $array the array to process
 * @return array the resulting arrayPathCollection
 */
function array_path_import($array, array $keys = [], array &$results = []) : array
{
    if (empty($array) && empty($keys)) {
        return [];
    }

    if (is_array($array)) {
        foreach ($array as $key => $value) {
            array_path_import($value, array_merge($keys, [$key]), $results);
        }
    } else {
        $results[] = [$keys, $array];
    }

    return $results;
}

/**
 * Create an mutli-dimensional single entry associative array where $value is located in the tree of $keys
 * @param array $keys
 * @param mixed $value
 * @return array|mixed the array or the value if no key is provided
 */
function array_path_create(array $keys, $value)
{
    return empty($keys) ? $value : [array_shift($keys) => array_path_create($keys, $value)];
}

/**
 * Convert an $arrayPathCollection to a mutli-dimensional associative array
 * @param array $arrayPathCollection the arrayPathCollection to process
 * @return array the resulting mutli-dimensional associative array
 */
function array_path_export(array $arrayPathCollection) : array
{
    $results = [];

    foreach ($arrayPathCollection as $item) {
        [$keys, $value] = $item;
        $results = array_replace_recursive($results, array_path_create($keys, $value));
    }

    return $results;
}

/**
 * Sort the $keys of each arrayPath provided in $arrayPathCollection by using the $order
 * @param array $order the array listing new order
 * @param array $arrayPathCollection the arrayPathCollection to process
 * @return array the resulting arrayPathCollection
 */
function array_path_sort_keys(array $order, array $arrayPathCollection) : array
{
    if (empty($order) || empty($arrayPathCollection)) {
        return $arrayPathCollection;
    }

    $sortedKeys = array_map(fn (&$r) => $r[0], $arrayPathCollection);

    array_multisort($order, ...$sortedKeys);

    array_walk($arrayPathCollection, function (&$keys, $index) use ($sortedKeys) {
        $keys[0] = $sortedKeys[$index];
    });

    return $arrayPathCollection;
}

/**
 * Convert an ArrayPathCollection to an associative array where each entry is formed by the explode of $keys and separator
 * @param string $separator the separator used to implode $keys
 * @param array $arrayPathCollection the collection of arrayPaths to process
 * @return array the resulting array
 */
function array_path_implode(string $separator, array $arrayPathCollection) : array
{
    if (empty($arrayPathCollection)) {
        return $arrayPathCollection;
    }

    $results = [];

    foreach ($arrayPathCollection as $item) {
        [$keys, $value] = $item;
        $key = implode($separator, $keys);

        $results[$key] = $value;
    }

    return $results;
}

/**
 * Convert an associative array to an ArrayPathCollection where the key of each entry is exploded by $separator
 * @param string $separator the separator used to explode $keys
 * @param array $array the associative array to process
 * @return array the resulting ArrayPath collection
 */
function array_path_explode(string $separator, array $array) : array
{
    if (empty($array)) {
        return $array;
    }

    $results = [];

    foreach ($array as $key => $value) {
        $keys = explode($separator, (string)$key);

        $results[] = [$keys, $value];
    }

    return $results;
}

/**
 * Remove the specified $keys from the array
 * @param array $array
 * @param ...$keys
 * @return array|mixed|null the value (if single key is provided), an array of values (if multiple keys provided) or null if key does not exist.
 */
function array_remove(array &$array, ...$keys)
{
    if ($keys === null) {
        return null;
    }

    if (!is_array($keys)) {
        $keys = [$keys];
    }

    $values = [];

    foreach ($keys as $key) {
        if (isset($array[$key])) {
            $value = $array[$key];
            array_push($values, $value);
            unset($array[$key]);
        }
    }

    return count($values) === 1 ? $values[0] : $values;
}

define('ARRAY_FILTER_USE_VALUE', 0);

/**
 * Recursively filter the items of the array.
 * @param array $array
 * @param callable|null $callback
 * @param int $mode
 * @return array
 */
function array_filter_recursive(array $array, ?callable $callback = null, int $mode = 0) : array
{
    foreach ($array as &$value) {
        if (is_array($value)) {
            $value = array_filter_recursive($value, $callback, $mode);
        }
    }

    return array_filter($array, $callback, $mode);
}

/**
 * Apply filter_var on $value. Return but not throw an exception if the validation fails.
 * @param $value
 * @param int $filter
 * @param array $additionalOptions
 * @return mixed
 */
function filter_var_typed($value, int $filter, array $additionalOptions = [])
{
    if (is_string($value)) {
        $value = trim($value);
    }

    return filter_var($value, $filter, ['flags' => FILTER_NULL_ON_FAILURE, 'options' => ['default' => new \Exception()] + $additionalOptions]);
}

/**
 * Encode the $array in XML
 * @param array $array
 * @param string|null $rootElement
 * @param \SimpleXMLElement|null $xml
 * @return string
 * @throws \Exception
 */
function xml_encode(array $array, string $rootElement = null, SimpleXMLElement $xml = null) : string
{
    $xml ??= new SimpleXMLElement($rootElement ?? '<root/>');

    // Visit all key value pair
    foreach ($array as $key => $value) {
        $key = is_numeric($key) ? "item_{$key}" : (string)$key;
        if (is_array($value)) {
            xml_encode($value, $key, $xml->addChild($key));
        } else {
            $xml->addChild($key, (string)$value);
        }
    }

    return $xml->asXML();
}

/**
 * Convert the string with hyphens to StudlyCaps,
 * @param string $string The string to convert
 * @return string
 */
function convertToStudlyCaps($string)
{
    return str_replace(' ', '', ucwords(str_replace('-', ' ', $string)));
}

/**
 * Convert the string with hyphens to camelCase,
 * @param string $string The string to convert
 * @return string
 */
function convertToCamelCase($string)
{
    return lcfirst(convertToStudlyCaps($string));
}

/**
 * Search the nearest $needle in $haystack using $direction.
 * If $direction = 0 or null, returns $needle if it's in array
 * If $direction = -1, search in self or previous values
 * If $direction = 1, search in self or next values
 * @param int $needle
 * @param array $haystack
 * @param int|null $direction
 * @return int|null
 */
function array_search_nearest(int $needle, array $haystack, ?int $direction = 0) : ?int
{
    $haystack = array_filter($haystack, 'is_int');
    $haystack = array_map('intval', $haystack);

    if (in_array($needle, $haystack)) {
        return $needle;
    }

    if ($direction === null) {
        return null;
    } elseif ($direction === 0) {
        $results = [
            array_search_nearest($needle, $haystack, 1),
            array_search_nearest($needle, $haystack, -1)
        ];

        $results = array_filter($results);
        $results = array_fill_keys($results, null);

        array_walk($results, function (&$result, $value) use ($needle) {
            $result = abs($needle - $value);
        });

        $result = @min(array_values($results));
        $nearest = array_search($result, $results);

        if ($nearest === false) {
            return null;
        }

        return $nearest;

    } else {
        $combinedHaystack = $haystack;
        array_push($combinedHaystack, $needle);
        $combinedHaystack = array_unique($combinedHaystack);
        sort($combinedHaystack);

        $needleIndex = array_search($needle, $combinedHaystack);

        if ($needleIndex === false) {
            return null;
        }

        return @$combinedHaystack[$needleIndex + $direction];
    }
}

/**
 * Indicate if the given $string starts with the specified $startString
 * @param $string
 * @param $startString
 * @return bool
 */
function startsWith ($string, $startString)
{
    $len = strlen($startString);
    return (substr($string, 0, $len) === $startString);
}

/**
 * Indicate if the given $string ends with the specified $endString
 * @param $string
 * @param $endString
 * @return bool
 */
function endsWith($string, $endString)
{
    $len = strlen($endString);
    if ($len == 0) {
        return true;
    }
    return (substr($string, -$len) === $endString);
}