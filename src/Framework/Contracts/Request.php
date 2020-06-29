<?php

namespace Counos\CounosPay\Framework\Contracts;


use Counos\CounosPay\Framework\Models\Http\Request as RequestModel;

interface Request
{
    /**
     * @return RequestModel
     */
    public function build();
}