<?php

namespace Frontender\Platform\Model\Cache;

use Kevinrob\GuzzleCache\Strategy\GreedyCacheStrategy;
use Psr\Http\Message\RequestInterface;

class Strategy extends GreedyCacheStrategy
{
    protected function getCacheKey(RequestInterface $request)
    {
	    $request->getBody()->rewind();
        return hash(
            'sha256',
            'greedy'.$request->getMethod().$request->getUri().$request->getBody()->getContents()
        );
    }
}