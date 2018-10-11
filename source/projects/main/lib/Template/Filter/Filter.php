<?php
/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Prototype\Template\Filter;

class Filter extends \Twig_Extension
{
    public function getFilters()
    {
        return [
            new \Twig_Filter('sortArray', [$this, 'sortArray'])
        ];
    }

    public function sortArray($array, $key, $direction = 'asc') {
    	if(!is_array($array)) {
    		return $array;
	    }

	    usort($array, function($a, $b) use ($key, $direction) {
		    if($a[$key] == $b[$key]) {
		    	return 0;
		    }

		    if($direction === 'asc') {
		    	return $a[$key] < $b[$key] ? -1 : 1;
		    } else {
			    return $a[$key] < $b[$key] ? 1 : -1;
		    }
	    });

    	return $array;
    }
}