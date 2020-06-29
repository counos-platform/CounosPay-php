<?php
/**
 * Created by PhpStorm.
 * User: Mojtaba
 * Date: 12/10/2019
 * Time: 5:11 PM
 */

namespace Counos\CounosPay\Framework\HttpClients;


use Counos\CounosPay\Framework\Contracts\HttpClient;
use Counos\CounosPay\Framework\Contracts\RequestMethod;
use Counos\CounosPay\Framework\Exceptions\ConnectionException;
use Counos\CounosPay\Framework\Builders\RequestBuilder;
use Counos\CounosPay\Framework\Models\Http\Request;
use Counos\CounosPay\Framework\Models\Http\Response;

class CurlClient implements HttpClient
{
    private $handler;

    /**
     * @var Request;
     */
    private $request;

    private $ssl_verification = true;

    /**
     * @param RequestBuilder $requestBuilder
     * @return Response
     * @throws ConnectionException
     */
    public function send(RequestBuilder $requestBuilder)
    {
        $this->request = $requestBuilder->build();

        $this->prepareHandler();


        $_response = curl_exec($this->handler);
        $error     = curl_error($this->handler);
        $info      = curl_getinfo($this->handler);

        if (curl_errno($this->handler))
        {
            throw new ConnectionException($error);
        }


        $header_size = $info['header_size'];
        $header      = substr($_response, 0, $header_size);
        $header      = $this->getHeadersFromResponse($header);

        $body = substr($_response, $header_size);

        curl_close($this->handler);

        $response            = new Response();
        $response->body      = $body;
        $response->http_code = $info['http_code'];
        $response->headers   = $header;
        $response->error     = $error;

        return $response;
    }

    public function sslVerification($disable)
    {
        $this->ssl_verification = $disable;
    }

    private function prepareHandler()
    {
        $this->handler = curl_init();

        $headers = $this->prepareHeaders();

        $options = [
            CURLOPT_URL            => $this->request->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_TIMEOUT        => 20,
            CURLINFO_HEADER_OUT    => true,
        ];

        if ($this->ssl_verification === false)
        {
            $options = [
                CURLOPT_SSL_VERIFYPEER => false,
            ];
        }

        if ($this->request->method === RequestMethod::POST)
        {
            $options[CURLOPT_CUSTOMREQUEST] = 'POST';

            if (is_array($this->request->body))
            {
                $data = http_build_query($this->request->body);
            }
            else
            {
                $data = $this->request->body;
            }

            if ($this->request->is_json)
            {
                $data      = json_encode($this->request->json_body);
                $headers[] = 'Content-Type: application/json';
            }

            $options[CURLOPT_POSTFIELDS] = $data;
        }

        $options[CURLOPT_HTTPHEADER] = $headers;

        curl_setopt_array($this->handler, $options);
    }

    /**
     * @return array
     */
    private function prepareHeaders()
    {
        $out = [
            'accept: application/json',
            'cache-control: no-cache',
        ];
        foreach ($this->request->headers as $key => $value)
        {
            $out[] = $key . ': ' . $value;
        }
        return $out;
    }

    private function getHeadersFromResponse($response)
    {
        $headers     = array();
        $header_text = substr($response, 0, strpos($response, "\r\n\r\n"));
        foreach (explode("\r\n", $header_text) as $i => $line)
        {
            if ($i === 0)
            {
                $headers['http_code'] = $line;
            }
            else
            {
                list ($key, $value) = explode(': ', $line);
                $headers[$key] = $value;
            }
        }
        return $headers;
    }
}
