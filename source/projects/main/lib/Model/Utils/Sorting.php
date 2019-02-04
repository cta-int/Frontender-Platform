<?php

namespace Prototype\Model\Utils;

class Sorting
{
    static $DIRECTION_DESC = 'desc';
    static $DIRECTION_ASC = 'asc';

    public static function sortBy(array $array, string $field, string $direction = self::DIRECTION_ASC) : array
    {
        if ($direction !== self::$DIRECTION_ASC && $direction !== self::$DIRECTION_DESC) {
            throw new Error('This direction is not supported');
        }

        usort($array, function ($a, $b) use ($field, $direction) {
            $aField = $a[$field];
            $bField = $b[$field];

            // If the field contains a date in the name, we will parse the value of the item to a datetime object.
            if (stripos($field, 'date') !== false) {
                $aField = new \DateTime($aField);
                $bField = new \DateTime($bField);
            }

            if ($aField === $bField) {
                return 0;
            }

            if ($direction === self::$DIRECTION_ASC) {
                return $aField < $bField ? -1 : 1;
            }

            return $aField < $bField ? 1 : -1;
        });

        return $array;
    }
}