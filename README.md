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
$portfolio = new Masterfolio\Portfolio('email', 'password', 'apikey');

// Вывод прибыли по портфелю за 1 апреля 2014
$portfolio->getProfit('2014-04-01');

// Получение прибыли всех ПАММ счетов за 1 апреля 2014
foreach ($portfolio->getBrokers() as $broker) {
    foreach ($broker->getPamms() as $pamm) {
        printf(
            "%s: %s\n",
            $pamm->getName(),
            $pamm->getProfit('2014-04-01')
        );
    }
}
```