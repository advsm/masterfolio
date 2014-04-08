<?php

namespace Masterfolio;

/**
 * Class Portfolio
 *
 * Класс представляет собой весь портфель на мастерфолио как единый актив.
 *
 * @package Masterfolio
 */
class Portfolio extends Active
{
    /**
     * Возвращает названия класса брокера.
     *
     * @return string
     */
    protected function getSubActiveClass()
    {
        return 'Masterfolio\Broker';
    }

    /**
     * Возвращает список с XML отображением всех брокеров, входящих в состав портфеля.
     *
     * @return \SimpleXmlElement[]
     */
    protected function getSubActivesFromApi()
    {
        $xml = $this->getClient()->send('getaccounts', []);

        $actives = [];
        foreach ($xml->data->accounts->account as $active) {
            $actives[] = $active;
        }

        return $actives;
    }

    /**
     * Возвращает список брокеров.
     * Использует ленивую загрузку при помощи Api.
     *
     * @return Broker[]
     */
    public function getBrokers()
    {
        return $this->getSubActives();
    }

    public function getProfitForPeriod($dateFrom, $dateTo)
    {
        $xml = $this->getClient()->send('getmonitor', [
            'active'   => 2,
            'public'   => 2,
            'prevdate' => $dateFrom,
            'date'     => $dateTo,
            'currency' => 'USD',
        ]);

        return $xml;
    }

    /**
     * Возвращает массив со всеми датами, для которых доступны записи активов.
     *
     * @return array
     */
    public function getDates()
    {
        $date        = strtotime('2013-05-16');
        $currentDate = time();

        $dates = [];
        while ($date <= $currentDate) {
            $dates[] = date('Y-m-d', $date);
            $date += 60*60*24;
        }

        return $dates;
    }

    /**
     * Прогружает из API историю изменения баланса всех ПАММ-счетов.
     *
     * @return bool
     */
    public function loadBalanceHistory()
    {
        $xml = $this->getClient()->send('getbalancehistory', ['sort' => 'asc']);

        foreach ($xml->data->records->record as $record) {
            $pamm = $this->findPammById($record->asset_id->__toString());

            $date  = date('Y-m-d', strtotime($record->date->__toString()));
            $value = $this->getValue($record->balance->__toString(), $record->currency->__toString());

            $pamm->setBalance($date, $value);
        }

        return true;
    }

    /**
     * Загрузает из API историю пополнений баланса и выводов средств для всех ПАММ счетов.
     *
     * @return bool
     */
    public function loadShiftHistory()
    {
        $xml = $this->getClient()->send('getshifts', ['sort'  => 'asc']);

        foreach ($xml->data->shifts->shift as $shift) {
            $pamm = $this->findPammById($shift->asset_id->__toString());

            $date  = date('Y-m-d', strtotime($shift->date->__toString()));
            $value = $this->getValue($shift->amount->__toString(), $shift->currency->__toString());

            $pamm->setShift($date, $value);
        }

        return true;
    }

    /**
     * Находит ПАММ счет по ID Masterfolio среди всех брокеров.
     *
     * @param int $id
     * @return Pamm
     */
    private function findPammById($id)
    {
        foreach ($this->getBrokers() as $broker) {
            foreach ($broker->getPamms() as $pamm) {
                if ($pamm->getId() == $id) {
                    return $pamm;
                }
            }
        }

        return null;
    }
}