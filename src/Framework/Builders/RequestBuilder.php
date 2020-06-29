<?php

namespace Counos\CounosPay\Framework\Builders;


use Counos\CounosPay\Framework\Models\Http\Request;

class RequestBuilder
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * RequestBuilder constructor.
     * @param string $url
     */
    public function __construct($url)
    {
        $this->request      = new Request();
        $this->request->url = $url;
    }

    /**
     * @param bool json
     * @return $this
     */
    public function bodyIsJson($is_json)
    {
        $this->request->is_json = $is_json;
        return $this;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setUrl($url)
    {
        $this->request->url = $url;
        return $this;
    }

    /**
     * @param string $method
     * @return $this
     */
    public function setMethod($method)
    {
        $this->request->method = $method;
        return $this;
    }

    /**
     * @param $body
     * @return $this
     */
    public function setBody($body)
    {
        $this->request->body = $body;
        return $this;
    }


    /**
     * @param array $body
     * @return $this
     */
    public function setJsonBody($body)
    {
        $this->bodyIsJson(true);
        $this->request->json_body = $body;
        return $this;
    }


    /**
     * @param array $params
     * @return $this
     */
    public function setQueryStrings($params)
    {
        return $this->setUrl($this->request->url . '?' . http_build_query($params));
    }

    /**
     * @param [] $headers
     * @return $this
     */
    public function setHeaders($headers)
    {
        $this->request->headers = $headers;
        return $this;
    }

    /**
     * @return Request
     */
    public function build()
    {
        return $this->request;
    }
}