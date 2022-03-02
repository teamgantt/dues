<?php

namespace TeamGantt\Dues;

class Arr
{
    /**
     * @param mixed[] $array
     * @param mixed[] $replacements
     *
     * @return mixed[]
     */
    public static function replaceKeys(array $array, array $replacements): array
    {
        $keys = array_keys($array);
        foreach ($keys as $k) {
            if (isset($replacements[$k])) {
                $array[$replacements[$k]] = $array[$k];
                unset($array[$k]);
            }
        }

        return $array;
    }

    /**
     * @param mixed[] $array
     *
     * @return mixed[]
     */
    public static function mapcat(array $array, callable $fn): array
    {
        $results = [];
        foreach ($array as $item) {
            $result = $fn($item);
            if (is_array($result)) {
                $results = [...$results, ...$result];
            } else {
                array_push($results, $result);
            }
        }

        return $results;
    }

    /**
     * @param mixed[]  $array
     * @param string[] $keys
     *
     * @return mixed[]
     */
    public static function selectKeys(array $array, array $keys): array
    {
        $filtered = [];
        foreach ($array as $key => $value) {
            if (false !== array_search($key, $keys)) {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    /**
     * @param mixed[]  $array
     * @param string[] $keys
     *
     * @return mixed[]
     */
    public static function dissoc(array $array, array $keys): array
    {
        $filtered = [];
        foreach ($array as $key => $value) {
            if (false === array_search($key, $keys)) {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    /**
     * A fairly naive "update in place" update function that allows
     * updating nested values in an array based on a path.
     *
     * @param mixed[]  $array
     * @param string[] $path
     *
     * @return mixed[]
     */
    public static function updateIn(array $array, array $path, callable $updater): array
    {
        $depth = 0;
        $target = count($path);

        if (0 === $target) {
            return $updater($array);
        }

        $up = function (&$m) use ($updater, &$path, &$up, &$depth, $target) {
            if (empty($path) || !is_array($m)) {
                return;
            }
            $k = array_shift($path);
            $atDepth = ++$depth === $target;

            if (!isset($m[$k])) {
                $m[$k] = []; // path is invalid
            }

            if (!$atDepth) {
                $up($m[$k]); // keep looking

                return;
            }

            $m[$k] = $updater($m[$k]);

            $up($m[$k]);
        };

        $up($array);

        return $array;
    }

    /**
     * @param mixed[]  $array
     * @param string[] $path
     * @param mixed    $value
     *
     * @return mixed[]
     */
    public static function assocIn(array $array, array $path, $value)
    {
        return static::updateIn($array, $path, fn () => $value);
    }

    /**
     * @param mixed[] $array
     *
     * @return mixed[]
     */
    public static function filter(array $array, callable $fn): array
    {
        return array_values(array_filter($array, $fn));
    }
}
