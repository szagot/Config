<?php
/**
 * Response para HttpRequest
 *
 * @author    Daniel Bispo <daniel@tmw.com.br>
 * @copyright Copyright (c) 2018, TMW E-commerce Solutions
 */

namespace Sz\Config;


class HttpRequestResponse
{
    private $body;
    private $status;

    /**
     * @return object|string
     */
    public function getBody()
    {
        // Tenta converter para objeto primeiro
        if ($json = @json_decode($this->body)) {
            return $json;
        }

        // Caso contrário, devolve só o texto
        return $this->getTextBody();
    }

    /**
     * @return string
     */
    public function getTextBody()
    {
        return $this->body;
    }

    /**
     * @param mixed $body
     *
     * @return HttpRequestResponse
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     *
     * @return HttpRequestResponse
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

}