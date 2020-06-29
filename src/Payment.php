<?php

namespace Counos\CounosPay;

use Counos\CounosPay\Exceptions\InvalidApiKeyException;
use Counos\CounosPay\Exceptions\PaymentGatewayException;
use Counos\CounosPay\Exceptions\RequestException;
use Counos\CounosPay\Exceptions\UnknownException;
use Counos\CounosPay\Framework\Contracts\HttpClient;
use Counos\CounosPay\Framework\Contracts\Model;
use Counos\CounosPay\Framework\Contracts\RequestMethod;
use Counos\CounosPay\Framework\Builders\RequestBuilder;
use Counos\CounosPay\Framework\HttpClients\CurlClient;
use Counos\CounosPay\Framework\Models\Http\Response;
use Counos\CounosPay\Models\Request\CurrencyToCrypto as CurrencyToCryptoRequest;
use Counos\CounosPay\Models\Request\Order as OrderRequest;
use Counos\CounosPay\Models\Response\CurrencyToCrypto;
use Counos\CounosPay\Models\Response\Order;
use Counos\CounosPay\Models\Ticker;
use Counos\CounosPay\Models\Tickers;

class Payment
{
    private $end_point = 'https://payment.counos.io/api/';
    /**
     * @var string
     */
    private $api_key;
    /**
     * @var HttpClient
     */
    private $httpClient;

    public function __construct($api_key = '', HttpClient $httpClient = null)
    {
        $this->httpClient = $httpClient;
        if ($httpClient === null)
        {
            $this->httpClient = new CurlClient();
        }

        $this->api_key = $api_key;
    }

    /**
     * @param int $order_id
     * @param int $ticker_id
     * @param float $amount
     * @param bool $renew
     * @return Order
     * @throws InvalidApiKeyException
     * @throws PaymentGatewayException
     * @throws RequestException
     * @throws UnknownException
     */
    public function NewOrder($order_id, $ticker_id, $amount, $renew = false)
    {
        $url                   = $this->getApiEndPoint('terminal/order');
        $model                 = new OrderRequest();
        $model->orderId        = $order_id;
        $model->tickerId       = $ticker_id;
        $model->expectedAmount = $amount;
        $model->renew          = $renew;

        $response = $this->postRequest($url, $model);

        $new_order = new Order($response->getJsonBody()->data);

        return $new_order;
    }

    /**
     * @param int $order_id
     * @param string $fiat
     * @param string $crypto
     * @param float $fiat_amount
     * @param bool $renew
     * @return Order
     * @throws InvalidApiKeyException
     * @throws PaymentGatewayException
     * @throws RequestException
     * @throws UnknownException
     */
    public function NewOrderFromFiat($order_id, $fiat, $crypto, $fiat_amount, $renew = false)
    {
        $tickers = $this->Tickers();
        $ticker  = $tickers->findBySymbol($crypto);


        $currency_to_crypto = $this->CurrencyToCrypto($fiat_amount, $fiat, $crypto);

        $url                   = $this->getApiEndPoint('terminal/order');
        $model                 = new OrderRequest();
        $model->orderId        = $order_id;
        $model->tickerId       = $ticker->id;
        $model->expectedAmount = $currency_to_crypto->converted_value;
        $model->renew          = $renew;

        $response = $this->postRequest($url, $model);

        $new_order = new Order($response->getJsonBody()->data);

        return $new_order;
    }

    /**
     * @param $amount
     * @param $from
     * @param $to
     * @return CurrencyToCrypto
     * @throws InvalidApiKeyException
     * @throws PaymentGatewayException
     * @throws RequestException
     * @throws UnknownException
     */
    public function CurrencyToCrypto($amount, $from, $to)
    {
        $url           = $this->getApiEndPoint('exchange/rate/currency2crypto/convert');
        $model         = new CurrencyToCryptoRequest();
        $model->amount = $amount;
        $model->from   = $from;
        $model->to     = $to;

        $response = $this->getRequest($url, $model);

        $new_order = new CurrencyToCrypto($response->getJsonBody()->data);

        return $new_order;
    }

    /**
     * @param int $order_id
     * @return Order
     * @throws InvalidApiKeyException
     * @throws PaymentGatewayException
     * @throws RequestException
     * @throws UnknownException
     */
    public function OrderStatus($order_id)
    {
        $url = $this->getApiEndPoint("terminal/order/$order_id");

        $response = $this->getRequest($url);

        $new_order = new Order(($response->getJsonBody()->data));

        return $new_order;
    }

    /**
     * @return Tickers
     * @throws InvalidApiKeyException
     * @throws PaymentGatewayException
     * @throws RequestException
     * @throws UnknownException
     */
    public function Tickers()
    {
        $tickers = new Tickers();
        $url     = $this->getApiEndPoint('terminal/tickers');

        $response = $this->getRequest($url);

        $_tickers = $response->getJsonBody()->data;

        foreach ($_tickers as $ticker)
        {
            $tickers[] = new Ticker($ticker);
        }

        return $tickers;
    }


    /**
     * @param $url
     * @return $this
     */
    public function setApiEndPoint($url)
    {
        $this->end_point = rtrim($url, '/') . '/';
        return $this;
    }

    /**
     * @param string $path
     * @return string
     */
    public function getApiEndPoint($path = '')
    {
        $_path = $path ?: '';
        return $this->end_point . $_path;
    }

    /**
     * @param string $api_key
     * @return $this;
     */
    public function setApiKey($api_key)
    {
        $this->api_key = $api_key;
        return $this;
    }


    /**
     * @return HttpClient
     */
    private function getHttpClient()
    {
        return $this->httpClient;
    }

    /**
     * @param $url
     * @param Model $model
     * @return Response
     * @throws InvalidApiKeyException
     * @throws PaymentGatewayException
     * @throws RequestException
     * @throws UnknownException
     */
    private function postRequest($url, Model $model = null)
    {
        $request = (new RequestBuilder($url))->setMethod(RequestMethod::POST)
                                             ->setHeaders([
                                                 'x-api-key' => $this->api_key
                                             ]);
        if ($model !== null)
        {
            $request->setJsonBody($model->toArray());
        }

        $response = $this->getHttpClient()
                         ->send($request);

        $this->checkError($response);

        return $response;
    }

    /**
     * @param $url
     * @param Model $model
     * @return Response
     * @throws InvalidApiKeyException
     * @throws PaymentGatewayException
     * @throws RequestException
     * @throws UnknownException
     */
    private function getRequest($url, Model $model = null)
    {
        $request = (new RequestBuilder($url))->setMethod(RequestMethod::GET)
                                             ->setHeaders([
                                                 'x-api-key' => $this->api_key
                                             ]);
        if ($model !== null)
        {
            $request->setQueryStrings($model->toArray());
        }

        $response = $this->getHttpClient()
                         ->send($request);

        $this->checkError($response);

        return $response;
    }

    /**
     * @param Response $response
     * @throws InvalidApiKeyException
     * @throws PaymentGatewayException
     * @throws RequestException
     * @throws UnknownException
     */
    private function checkError(Response $response)
    {
        if (!$this->isResponseSuccess($response))
        {
            try
            {
                $data    = $response->getJsonBody();
                $message = $data ? $data->statusMessage : '';
            }
            catch (\Exception $e)
            {
                throw new UnknownException('Unknown error occurred: ' . $e->getMessage());
            }

            if (stripos($message, 'secret key is invalid') !== false)
            {
                throw new InvalidApiKeyException('Api key is invalid, please check your api key.');
            }

            throw new RequestException("cannot perform action there is an error, http status code: {$response->http_code}", $response->http_code);
        }

        if ($response->error)
        {
            throw new RequestException($response->error);
        }

        $message     = '';
        $status_code = 0;
        $body        = $response->getJsonBody();


        if (!empty($body))
        {
            $status_code = $body ? $body->statusCode : 0;
            $message     = $body ? $body->statusMessage : '';
        }

        if (!$this->isResponseBodySuccess($response))
        {
            throw new PaymentGatewayException($message, $status_code);
        }
    }

    /**
     * @param Response $response
     * @return bool
     */
    private function isResponseBodySuccess(Response $response)
    {
        $body        = $response->getJsonBody();
        $status_code = $body ? $body->statusCode : 0;

        return $status_code >= 200 && $status_code <= 299;
    }

    /**
     * @param Response $response
     * @return bool
     */
    private function isResponseSuccess(Response $response)
    {
        $status_code = $response->http_code;

        return $status_code >= 200 && $status_code <= 299;
    }
}