<?php

namespace Masterfolio;

/**
 * Class Config
 * Конфигурация для API, принимается в конструктор всеми объектами, взаимодействующими с API.
 *
 * @package Masterfolio
 */
class Config extends \Zend\Config\Config
{
    /**
     * Возвращает ключ для доступа к Masterfolio.
     *
     * @return string
     * @throws Exception
     */
    public function getApiKey()
    {
        if (!$this->apiKey) {
            throw new Exception('Не установлен ключ для доступа к API');
        }

        return $this->apiKey;
    }

    /**
     * Возвращает email, использующийся в качестве логина на Masterfolio.
     *
     * @return string
     * @throws Exception
     */
    public function getEmail()
    {
        if (!$this->email) {
            throw new Exception('Не установлен email для доступа к Masterfolio');
        }

        return $this->email;
    }

    public function getPassword()
    {
        if (!$this->password) {
            throw new Exception('Не установлен пароль для доступа к Masterfolio');
        }

        return $this->password;
    }
}