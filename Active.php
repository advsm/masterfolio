<?php

namespace Masterfolio;

/**
 * Class Active
 *
 * Абстрактный класс актива, предоставляет функционал для получения доступа к API, хранению общей информации по активу,
 * получение баланса и доходности, как в абсолютном, так и в относительном соотношении.
 *
 * Активом является как весь портфель, так и площадки, и даже отдельные ПАММ счета.
 *
 * @package Masterfolio
 */
abstract class Active
{
    /**
     * ID актива в API Masterfolio.
     *
     * @var int
     */
    protected $id;

    /**
     * Название актива в API Masterfolio.
     *
     * @var string
     */
    protected $name;

    /**
     * Клиент для работы с Api по HTTP.
     *
     * @var Api
     */
    protected $client;

    /**
     * Возвращает список активов, входящих в состав текущего актива.
     *
     * @var Active[]
     */
    protected $subActives;

    /**
     * Объект конфигурации API.
     *
     * @var Config
     */
    protected $config;

    /**
     * Абстрактная функция, загружающая субактивы из Api.
     *
     * @return \SimpleXmlElement[]
     */
    abstract protected function getSubActivesFromApi();

    /**
     * Возвращает название класса актива, который входит в состав текущего актива.
     * @return string
     */
    abstract protected function getSubActiveClass();

    /**
     * Прогрузка параметров актива из XML ответа от API.
     *
     * @param \SimpleXMLElement $xml
     * @return $this
     */
    abstract public function fromXml(\SimpleXMLElement $xml);

    /**
     * Констуктор
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->setConfig($config);
    }

    /**
     * Устанавливает объект конфигурации API для актива.
     *
     * @param Config $config
     * @return $this
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * Возвращает объект конфигурации API.
     *
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Возвращает баланс актива за определенную дату.
     *
     * @param string $date Дата в формате Y-m-d
     * @return float
     */
    public function getBalance($date)
    {
        $balance = 0;
        foreach ($this->getSubActives() as $active) {
            $balance += $active->getBalance($date);
        }

        return $balance;
    }

    /**
     * Возвращает сумму ввода-вывода средств за определенную дату.
     *
     * @param string $date
     * @return float
     */
    public function getShift($date)
    {
        $shift = 0;
        foreach ($this->getSubActives() as $active) {
            $shift += $active->getShift($date);
        }

        return $shift;
    }

    /**
     * Возвращает прибыль актива за определенную дату.
     *
     * @param string $date Дата в формате Y-m-d
     * @return float
     */
    public function getProfit($date)
    {
        $profit = 0;
        foreach ($this->getSubActives() as $active) {
            $profit += $active->getProfit($date);
        }

        return $profit;
    }

    /**
     * Возвращает прибыль актива в процентах за определенную дату.
     *
     * @param string $date Дата в формате Y-m-d
     * @return float
     */
    public function getRelativeDailyProfit($date)
    {
        return 0;
    }

    /**
     * Возвращает сумму в долларах.
     *
     * @param float $value
     * @param string $currency
     * @return float
     */
    public function getValue($value, $currency)
    {
        if ($currency == "RUR") {
            return round($value/$this->getConfig()->getRurQuote(), 2);
        }

        return $value;
    }

    /**
     * Устанавливает HTTP клиент для работы с Masterfolio API.
     *
     * @param Api $client
     * @return $this
     */
    public function setClient(Api $client)
    {
        $this->client = $client;
        return $this;
    }

    /**
     * Возвращает ID актива на площадке Masterfolio.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Устанавливает ID актива.
     *
     * @param $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Возвращает название актива, полученное из API.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Устанавливает название актива.
     *
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Возвращает название актива на русском, адаптированное для чтения человеком.
     *
     * @return string
     */
    public function getRusName()
    {
        return $this->name;
    }

    /**
     * Возвращает текстовый идентификатор актива.
     *
     * @return string
     */
    public function getKey()
    {
        $key = $this->getName();
        if ($key == 'Альпари') {
            return 'alpari';
        }

        $key = trim($key);
        $key = strtolower($key);
        $key = str_replace(' ', '', $key);
        $key = str_replace('-', '', $key);
        $key = str_replace('-', '', $key);

        return $this->translit($key);
    }

    /**
     * Возвращает активы, входящие в состав данного актива.
     *
     * @return Active[]
     */
    protected function getSubActives()
    {
        if (!$this->subActives) {
            $this->load();
        }

        return $this->subActives;
    }

    /**
     * Добавляет составляющий актив в состав текущего актива.
     *
     * @param Active $active
     * @return $this
     */
    protected function addSubActive(Active $active)
    {
        $this->subActives[ $active->getId() ] = $active;
        return $this;
    }

    /**
     * Возвращает HTTP клиент для работы с API Masterfolio.
     *
     * @return Api
     */
    protected function getClient()
    {
        if (!$this->client) {
            $this->client = new Api($this->getConfig());
        }

        return $this->client;
    }

    /**
     * Загружает инвестиционный портфель из API Masterfolio.
     *
     * @return $this
     */
    private function load()
    {
        foreach ($this->getSubActivesFromApi() as $xml) {
            $class = $this->getSubActiveClass();

            /** @var Active $subActive */
            $subActive = new $class($this->getConfig());
            $subActive->setClient($this->getClient());
            $subActive->fromXml($xml);

            $this->addSubActive($subActive);
        }
    }

    /**
     * Транслитерирует строку.
     *
     * @param string $string
     * @return string
     */
    private function translit($string)
    {
        $string = strtr($string,
            "абвгдежзийклмнопрстуфыэАБВГДЕЖЗИЙКЛМНОПРСТУФЫЭ",
            "abvgdegziyklmnoprstufieABVGDEGZIYKLMNOPRSTUFIE"
        );

        $string = strtr($string, array(
            'ё'=>"yo",    'х'=>"h",  'ц'=>"ts",  'ч'=>"ch", 'ш'=>"sh",
            'щ'=>"shch",  'ъ'=>'',   'ь'=>'',    'ю'=>"yu", 'я'=>"ya",
            'Ё'=>"Yo",    'Х'=>"H",  'Ц'=>"Ts",  'Ч'=>"Ch", 'Ш'=>"Sh",
            'Щ'=>"Sh",    'Ъ'=>'',   'Ь'=>'',    'Ю'=>"Yu", 'Я'=>"Ya",
        ));

        return $string;
    }
}