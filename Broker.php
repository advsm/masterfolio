<?php

namespace Masterfolio;

/**
 * Class Broker
 *
 * Класс представляет собой набор активов определенной площадки на Masterfolio как единый актив.
 *
 * @package Masterfolio
 */
class Broker extends Active
{
    /**
     * Возвращает названия класса ПАММ счета.
     *
     * @return string
     */
    protected function getSubActiveClass()
    {
        return 'Masterfolio\Pamm';
    }

    /**
     * Возвращает список с XML отображением всех брокеров, входящих в состав портфеля.
     *
     * @return \SimpleXmlElement[]
     */
    protected function getSubActivesFromApi()
    {
        $xml = $this->getClient()->send('getassets', [
            'account' => $this->getId(),
            'active'  => 2,
            'public'  => 2,
        ]);

        $actives = [];
        foreach ($xml->data->assets->asset as $active) {
            $actives[] = $active;
        }

        return $actives;
    }

    /**
     * Прогружает все данные брокера, используя XML ответ от API.
     *
     * @param \SimpleXmlElement $xml
     * @return $this
     */
    public static function fromXml(\SimpleXMLElement $xml)
    {
        $broker = new self();
        $broker->setId($xml->id->__toString());
        $broker->setName($xml->platform_name->__toString());
        return $broker;
    }

    /**
     * Возвращает список ПАММ счетов брокера, участвующих в формировании портфеля.
     *
     * @return Pamm[]
     */
    public function getPamms()
    {
        return $this->getSubActives();
    }
}