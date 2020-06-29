<?php

namespace Counos\CounosPay\Framework\Contracts;


use Counos\CounosPay\Framework\Builders\RequestBuilder;
use Counos\CounosPay\Framework\Models\Http\Response;

interface HttpClient
{
    /**
     * @param RequestBuilder $request
     * @return Response
     */
    public function send(RequestBuilder $request);
}