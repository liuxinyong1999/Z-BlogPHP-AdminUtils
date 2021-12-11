<?php


/**
 * Class ConfigUtils
 *
 * @author 可乐要加冰
 * @version 1.0
 *
 * 插件合集；{@link https://app.zblogcn.com/?auth=e4f2ac7f-b3cc-4f8e-a8eb-90655c6c5500}
 */
class ConfigUtils {

    /**
     * @var string 配置名，一般可以填写应用ID
     */
    public static $name = '';

    public static function config($name = null) {
        global $zbp;

        if ($name === null) {
            $name = self::$name;
        }

        $key = null;
        if (is_string($name)) {
            if ($pos = strpos($name, '.')) {
                $key = substr($name, $pos + 1);
                $name = substr($name, 0, $pos);
            }
            $config = $zbp->Config($name);
        } else {
            $config = $name;
        }

        return new static($config, $key);
    }

    public static function post($filters, $name = null) {
        static::config($name)->save($filters, 'post');
    }

    /**
     * @var Config
     */
    private $config;
    private $key;

    protected function __construct($config, $key = null) {
        $this->config = $config;
        if ($key !== null)
        $this->key = FilterCorrectName($key);
    }

    public function key($key = null) {
        if ($key === null) return $key;

        $this->key = $key;
    }

    public function get($key, $default = null) {
        if ($this->key) {
            $confkey = $this->key;
            if ($this->config->HasKey($confkey)) {
                $conf = $this->config->$confkey;
                if (is_array($conf) && array_key_exists($key, $conf)) {
                    return $conf[$key];
                } elseif (is_object($conf) && isset($conf->$key)) {
                    return $conf->$key;
                } else {
                    return $default;
                }
            } else {
                return $default;
            }
        } else {
            $key = FilterCorrectName($key);
            if ($this->config->HasKey($key)) {
                return $this->config->$key;
            } else {
                return $default;
            }
        }
    }

    public function set($key, $value) {
        if ($this->key) {
            $confkey = $this->key;
            if ($this->config->HasKey($confkey)) {
                $conf = $this->config->$confkey;
            } else {
                $conf = array();
            }

            if (is_array($conf)) {
                $conf[$key] = $value;
            } elseif (is_object($conf)) {
                $conf->$key = $value;
            }

            $this->config->$confkey = $conf;
        } else {
            $key = FilterCorrectName($key);
            $this->config->$key = $value;
        }
    }

    public function save($filters = null, $data = null) {
        if ($filters === null && $data === null) {
            $this->config->Save();
        } else {
            if (is_string($data)) {
                switch (strtolower($data)) {
                    case 'get':
                        $data = $_GET;
                        break;
                    case 'post':
                        $data = $_POST;
                        break;
                    default:
                        $data = $_REQUEST;
                }
            }

            if (is_array($filters)) {
                foreach ($filters as $key => $default) {
                    $filter = null;
                    if (is_array($default)) {
                        $filter = isset($default['filter']) ? $default['filter'] : $default[1];
                        $default = isset($default['default']) ? $default['default'] : $default[0];
                    } elseif (strpos($key, '/')) {
                        $t = substr($key, 0, strpos($key, '/'));
                        $key = substr($key, strpos($key, '/') + 1);
                        switch ($t) {
                            case 'd':
                                $filter = 'intval';
                                break;
                            case 'f':
                                $filter = 'floatval';
                                break;
                            case 'b':
                                $filter = 'boolval';
                                break;
                            case 's':
                                $filter = 'strval';
                                break;
                        }
                    }

                    if (isset($data[$key])) {
                        $value = $filter === null ? $data[$key] : $filter($data[$key]);
                    } else {
                        $value = $default;
                    }
                    $this->set($key, $value);
                }
            } else {
                foreach ($data as $key => $value) {
                    $this->set($key, $value);
                }
            }

            $this->config->Save();
        }
    }
}