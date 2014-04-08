Masterfolio API Client
===============

Реализация API для работы с портфелем на [Masterfolio](https://monitor.masterfolio.ru).

Установка
---------

Установка производится при помощи [Composer](https://getcomposer.org/).

1) Создайте файл `composer.json` со следующим содержимым:
```json
{
    "require": {
        "advsm/masterfolio": "dev-master"
    }
}
```

2) Запустите команду установки:
```
composer.phar install
```

Как использовать?
-----------------

```php
// Инициализация API
$config = new Masterfolio\Config(array(
    'apiKey'   => '', // Ключ доступа к API. Выдается по запросу через тикеты
    'email'    => '', // Логин для доступа к Masterfolio
    'password' => '', // Пароль для доступа к Masterfolio
));
$portfolio = new Masterfolio\Portfolio($config);

// Вывод прибыли по портфелю за 1 апреля 2014
$portfolio->getProfit(strtotime('2014-04-01'));

// Получение прибыли всех ПАММ счетов за 1 апреля 2014
foreach ($portfolio->getBrokers() as $broker) {
    foreach ($broker->getPamms() as $pamm) {
        printf(
            "%s: %s\n",
            $pamm->getName(),
            $pamm->getProfit(strtotime('2014-04-01'))
        );
    }
}
```