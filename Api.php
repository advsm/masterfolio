<?php

namespace Masterfolio;

use Zend\Http\Client;

/**
 * Class MasterfolioApi
 *
 * @link https://docs.google.com/document/d/1LK6Fy7N3HnqYWAFvf94muV3JHRIElitUaB-kXilvqjE/edit#
 * @package Masterfolio
 */
class Api
{
    const URI = 'https://monitor.masterfolio.ru/api/';

    /**
     * Клиент для работы с HTTP Api.
     *
     * @var Client
     */
    private $client;

    /**
     * Ключ для работы с masterfolio API. Для получения обращайтесь в саппорт.
     *
     * @var string
     */
    private $apiKey;

    /**
     * Токен инициализируется в процессе работы с API, после запроса к auth.
     *
     * @var string
     */
    private $token;

    /**
     * Создает экземпляр клиента для работы с Masterfolio Api.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->apiKey = $config->getApiKey();

        $response = $this->send('auth', [
            'email'    => $config->getEmail(),
            'password' => $config->getPassword(),
        ]);

        $this->token = current($response->xpath('//token'))->__toString();
    }

    /**
     * Клиент для работы с HTTP Api.
     *
     * @return Client
     */
    public function getClient()
    {
        if (!$this->client) {
            $this->client = new Client();
            $this->client->setOptions(array('sslverifypeer' => false));
        }

        return $this->client;
    }

    /**
     * Посылает запрос на определенный метод API, с переданными параметрами.
     *
     * @param string $apiMethod
     * @param array $requestParams
     * @return \SimpleXMLElement
     */
    public function send($apiMethod, $requestParams = [])
    {
        $requestParams = array_merge($requestParams, [
            'apikey' => $this->apiKey,
            'token'  => $this->token,
        ]);

        $response = $this->getClient()
            ->resetParameters()
            ->setMethod('GET')
            ->setUri(self::URI . $apiMethod)
            ->setParameterGet($requestParams)
            ->send();
        $body = $response->getBody();

        $this->log($this->getClient()->getLastRawRequest());
        $this->log($this->getClient()->getLastRawResponse());

        return new \SimpleXmlElement($body);
    }

    /**
     * Выводит в лог сообщение.
     *
     * @param string $message
     * @return void
     */
    private function log($message)
    {
        $date = date('Y-m-d H:i:s');
        $f = fopen('api.log', 'a');
        fwrite($f, "[$date] $message");
        fclose($f);
    }


}