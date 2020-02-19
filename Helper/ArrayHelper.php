<?php

namespace wlec\Framework\Helper;

class ArrayHelper {

    /**
     * Returns true if associative array $needle is a complete subset of associative array $haystack
     * where {id:5, name:"Test"} is a subset of {id:5, identifier:"sample", name:"Test"}
     *
     * @param array $haystack
     * @param array $needle
     * @return bool
     */
    public static function isSubsetOf(array $haystack, array $needle) {
        foreach ($needle as $k => $v) {
            if (is_string($v) && preg_match('/^\/.+\/$/', $v)) {
                if (!preg_match($v, $haystack[$k])) {
                    return false;
                }
            } else if (is_array($v)) {
                if (!self::isSubsetOf($haystack[$k], $v)) {
                    return false;
                }
            } else if ($haystack[$k] !== $v) {
                return false;
            }
        }
        return true;
    }

    /**
     * Returns the position (int) of an associative array in a numeric array $haystack,
     * if it is a superset of associative array $needle
     * Returns 1 if $haystack is [{id:4}, {id:5, identifier:"sample", name:"Test"}, {id:6}] and $needle is {id:5, name:"Test"}
     * Returns null if $needle is not contained in $haystack
     *
     * @param array $haystack
     * @param array $needle
     * @return int|null
     */
    public static function findSupersetOf(array $haystack, array $needle) {
        foreach ($haystack as $position => $item) {
            if (self::isSubsetOf($item, $needle)) {
                return $position;
            }
        }
    }

    /**
     * Returns true if numeric array $haystack contains an associative array which is a superset of associative array $needle
     * where [{id:4}, {id:5, identifier:"sample", name:"Test"}, {id:6}] contains a superset of {id:5, name:"Test"}
     *
     * @param array $haystack
     * @param array $needle
     * @return bool
     */
    public static function containsSupersetOf(array $haystack, array $needle) {
        return is_numeric(self::findSupersetOf($haystack, $needle));
    }

}
