<?php

namespace engine;

/**
 * Klasa zarządzająca danymi wejściowymi
 *
 * Class input
 * @package engine
 */
class input {
    /**
     * @var Konfiguracja strony pobrana z pliku
     */
    public $config;

    /**
     * @var Parametry pobrane z tablicy $_GET
     */
    public $params;

    /**
     * @var Parametry pobrane z tablicy $_POST
     */
    public $form;

    public function __construct() {
//        $this->getParams();
//        $this->getFormParams();
        return $this;
    }

    /**
     * Metoda pobierająca konfigurację strony z pliku
     *
     * @param null $part @deprecated
     * @return mixed Zwraca całość lub fragment konfiguracji, lub false w przypadku błędu
     */
    public function getConfig($part = null) {
        if (file_exists('./config.json')) {
            $config = json_decode(file_get_contents('./config.json'), true);

        } else {
            $t = file_get_contents('./engine/config.default.json');
            $config = json_decode($t, true);
        }
        $this->config = $config;
        if ($part == null) {
            return $config;
        } elseif(isset($config[$part])) {
            return $config[$part];
        }
        return false;
    }

    /**
     * @deprecated
     */
    public function getParams() {
        if (isset($_GET)) {
            $this->params = $_GET;
        }
    }

    /**
     * @deprecated
     */
    public function getFormParams() {
        if (isset($_POST)) {
            $this->form = $_POST;
        }
    }

    /**
     * @deprecated
     */
    public function get($key) {
        if (isset($this->form[$key])) {
            return $this->form[$key];
        } elseif (isset($this->params[$key])) {
            return $this->params[$key];
        }
        return false;
    }

    /**
     * Metoda sprawdzająca czy wywołanie strony nastąpiło asynchrnocznie za pomocą AJAX'a
     *
     * @return bool Zwraca true jeżeli użyte zostało żadanie AJAX, w przeciwnym wypadku false
     */
    public function isAjaxRequest() {
        return ($this->get('new_url')) ? true : false;
    }

    public function getPath() {
        $path = explode('?',urldecode($_SERVER['REQUEST_URI']));
        if (core::$config['site']['root_directory'] != '/') {
            $path = preg_replace('#'.core::$config['site']['root_directory'].'#', '', $path[0],1);
        } else {
            $path = $path[0];
        }
        return $path;
    }
    
}

?>
