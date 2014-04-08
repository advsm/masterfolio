<?php

namespace Masterfolio;

class Pamm extends Active
{
    /**
     * Массив, где ключем является дата.
     *
     * @var float[]
     */
    private $balanceHistory;

    /**
     * Массив, где ключем является дата.
     *
     * @var float[]
     */
    private $shiftHistory;

    /**
     * Массив, где ключем является дата.
     *
     * @var float[]
     */
    private $profitHistory;

    /**
     * Актив последнего уровня, кидает Exception.
     *
     * @return string
     * @throws \Exception
     */
    protected function getSubActiveClass()
    {
        throw new Exception('Памм счета не содержат вложенных активов');
    }

    /**
     * Актив последнего уровня, кидает Exception.
     *
     *
     * @return \SimpleXmlElement[]
     * @throws \Exception
     */
    protected function getSubActivesFromApi()
    {
        throw new Exception('Памм счета не содержат вложенных активов');
    }

    /**
     * Прогружает все данные ПАММ счета, используя XML ответ от API.
     *
     * @param \SimpleXmlElement $xml
     * @return $this
     */
    public function fromXml(\SimpleXMLElement $xml)
    {
        $this->setId($xml->id->__toString());
        $this->setName($xml->name->__toString());
        return $this;
    }

    /**
     * Возвращает баланс на конец дня.
     *
     * @param string $date
     * @return float
     */
    public function getBalance($date)
    {
        $this->loadDigits();
        return isset($this->balanceHistory[$date]) ? $this->balanceHistory[$date] : 0;
    }

    /**
     * Устанавливает баланс на определенную дату.
     * Важно! После использования этого метода автоматическая прогрузка значений из API перестанет действовать.
     *
     * @param string $date
     * @param int $value
     * @return $this
     */
    public function setBalance($date, $value)
    {
        $this->balanceHistory[$date] = $value;
        return $this;
    }

    /**
     * Возвращает сумму снятых средств за день.
     *
     * @param string $date
     * @return float
     */
    public function getShift($date)
    {
        $this->loadDigits();
        return isset($this->shiftHistory[$date]) ? $this->shiftHistory[$date] : 0;
    }

    /**
     * Устанавливает операцию ввода/вывода на определенную дату.
     * Если значение было установлено ранее, то значения будут сложены.
     * Важно! После использования этого метода автоматическая прогрузка значений из API перестанет действовать.
     *
     * @param string $date
     * @param int $value
     * @return $this
     */
    public function setShift($date, $value)
    {
        if (!isset($this->shiftHistory[$date])) {
            $this->shiftHistory[$date] = $value;
        } else {
            $this->shiftHistory[$date] += $value;
        }

        return $value;
    }

    /**
     * Возвращает прибыль за день по конкретному ПАММ счету.
     *
     * @param string $date
     * @return float|int
     */
    public function getProfit($date)
    {
        $this->loadDigits();
        return isset($this->profitHistory[$date]) ? $this->profitHistory[$date] : 0;
    }

    /**
     * Загружает показатели balance, shift, profit за всю историю актива.
     *
     * @return bool
     */
    private function loadDigits()
    {
        $this->loadBalanceHistory();
        $this->loadShiftHistory();
        $this->calculateProfitHistory();
        return true;
    }

    /**
     * Прогружает из API историю изменения баланса ПАММ-счета.
     *
     * @return bool
     */
    private function loadBalanceHistory()
    {
        if ($this->balanceHistory !== null) {
            return false;
        }

        $xml = $this->getClient()->send('getbalancehistory', [
            'asset' => $this->getId(),
            'sort'  => 'asc',
        ]);

        $this->balanceHistory = [];
        foreach ($xml->data->records->record as $record) {
            $date  = date('Y-m-d', strtotime($record->date->__toString()));
            $value = $this->getValue($record->balance->__toString(), $record->currency->__toString());

            $this->setBalance($date, $value);
        }

        return true;
    }

    /**
     * Загрузает из API историю пополнений баланса и выводов средств.
     *
     * @return bool
     */
    private function loadShiftHistory()
    {
        if ($this->shiftHistory !== null) {
            return false;
        }

        $xml = $this->getClient()->send('getshifts', [
            'asset' => $this->getId(),
            'sort'  => 'asc',
        ]);

        $this->shiftHistory = [];
        foreach ($xml->data->shifts->shift as $shift) {
            $date  = date('Y-m-d', strtotime($shift->date->__toString()));
            $value = $this->getValue($shift->amount->__toString(), $shift->currency->__toString());

            $this->setShift($date, $value);
        }

        return true;
    }

    /**
     * Вычисляет абсолютную прибыль, используя баланс и записи депозитов и выводов средств.
     *
     * @return bool
     */
    private function calculateProfitHistory()
    {
        if ($this->profitHistory !== null) {
            return false;
        }

        $this->profitHistory = [];
        $totalShifted        = 0;

        foreach ($this->balanceHistory as $date => $balance) {
            if (isset($this->shiftHistory[$date])) {
                $totalShifted += $this->shiftHistory[$date];
            }


            $this->profitHistory[$date] = round($balance - $totalShifted, 2);
        }

        return true;
    }
}