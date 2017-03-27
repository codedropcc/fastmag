<?php

namespace Fastmag;

class ArrayHelper
{
    public static function map(callable $callback, $array) {
        $newArray = array();
        foreach ($array as $index => $key) {
            $newArray[] = call_user_func($callback, $key);
        }
        return $newArray;
    }

    public static function zip() {
        $args = func_get_args();
        $result = [];
        $shortest = call_user_func_array([__CLASS__, 'shortest'], $args);

        $args = self::map(
            function ($item) use ($shortest) {
                return array_slice($item, 0, $shortest);
            },
            self::filter(
                function ($item) {
                    return is_array($item);
                },
                $args
            )
        );

        foreach ($args as $array) {
            $i = 0;
            foreach ($array as $item) {
                $result[$i][] = $item;
                $i++;
            }
        }
        return $result;
    }

    public static function zip3() {
        $args = func_get_args();
        $fill = array_shift($args);

        $result = [];
        $longest = call_user_func_array([__CLASS__, 'longest'], $args);

        $args = self::filter(
            function ($item) {
                return is_array($item);
            },
            $args
        );

        foreach ($args as $array) {
            $i = 0;
            for ($i = 0; $i < $longest; $i++) {
                if ($i >= count($array))
                    $item = $fill;
                else
                    $item = $array[$i];
                $result[$i][] = $item;
            }
        }
        return $result;
    }

    public static function shortest() {
        $args = func_get_args();
        return self::reduce(function ($shortest, $item) {
            if ($shortest > count($item)) {
                $shortest = count($item);
            }
            return $shortest;
        }, $args, count(self::first($args)));
    }

    public static function longest() {
        $args = func_get_args();
        return self::reduce(function ($longest, $item) {
            if ($longest < count($item)) {
                $longest = count($item);
            }
            return $longest;
        }, $args, count(self::first($args)));
    }
    
    public static function flatMap(callable $callback, $array) {
        return self::flat(self::map($callback, $array));
    }
    
    public static function filter($callback, $array) {
        $newArray = array();
        foreach ($array as $key => $value) {
            if (self::filterable_callback($callback, $value)) {
                $newArray[] = $value;
            }
        }
        return $newArray;
    }
    
    public static function any($callback, $array) {
        foreach ($array as $key => $value) {
            if (self::filterable_callback($callback, $value)) {
                return true;
            }
        }
        return false;
    }

    protected static function filterable_callback($callback, $value) {
        if ($callback == 'true' && $value)
            return true;
        else if (is_callable($callback) && call_user_func($callback, $value) === true)
            return true;
        return false;
    }

    public static function reduce(callable $callback, $array, $start_value = NULL) {
        if ($start_value === NULL)
            $value = array_values($array)[0];
        else
            $value = $start_value;

        $i = 0;
        foreach ($array as $index => $key) {
            if ($i++ === 0 && $start_value === NULL) continue;
            $value = call_user_func($callback, $value, $key);
        }

        return $value;
    }

    public static function walk(callable $callback, &$array) {
        foreach ($array as $index => &$key) {
            call_user_func_array($callback, array(&$key));
        }
    }

    public static function flat($array, $limit = 0, $deep = 0) {
        $return = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if ($limit == 0 || $deep < $limit) {
                    $return = array_merge($return, self::flat($value, $limit, $deep + 1));
                }
                else {
                    $return[] = $value;
                }
            }
            else {
                $return[] = $value;
            }
        }
        return $return;
    }

    public static function is_multi($array) {
        foreach ($array as $key => $value) {
            if (is_array($value)) return true;
        }
        return false;
    }
    
    public static function is_assoc($array) {
        return array_keys($array) != range(0, count($array)-1);
    }

    public static function first($array) {
        return array_values($array)[0];
    }

    public static function last($array) {
        return array_values($array)[count($array)-1];
    }
}
