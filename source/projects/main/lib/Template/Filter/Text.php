<?php
/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Frontender\Platform\Template\Filter;

class Text extends \Twig_Extension
{
    public function getFilters()
    {
        return [
	        new \Twig_Filter('issueNumber', [$this, 'issueNumber']),
            new \Twig_Filter('humanList', [$this, 'humanizeList'])
        ];
    }

	public function issueNumber($text) {

		preg_match("/[#]{1}(\d+)/", $text, $matches);
		return $matches[1];
	}

    public function humanizeList($array, $concatChars = ' and ', $commaAppendLast = false) {

        // Return when the list is empty
        if( !isset($array[0]) ) return false;

        if($commaAppendLast) {
            $text = implode(', ', $array);
        } else {

            $text = isset($array[0]) ? array_pop($array) : '';
            if( isset($array[0]) ) {
                $text = implode(', ', $array) . $concatChars . $text;
            }
        }

        return $text;
    }

}
