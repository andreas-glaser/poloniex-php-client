<?php

namespace AndreasGlaser\PPC;

use AndreasGlaser\Helpers\ArrayHelper;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Result
 *
 * @package AndreasGlaser\PPC

 */
class Result
{

    /**
     * @var \GuzzleHttp\Psr7\Response
     */
    public $response;

    /**
     * @var string
     */
    public $contents;

    /**
     * @var \stdClass|null
     */
    public $decoded;

    /**
     * Result constructor.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     */
    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
        $this->contents = $this->response->getBody()->getContents();

        // decode json
        if ($this->response->hasHeader('Content-Type')) {
            $contentType = ArrayHelper::getFirstValue($this->response->getHeader('Content-Type'));
            if ($contentType === 'application/json') {
                $this->decoded = json_decode($this->contents, true);
            }
        }
    }
}