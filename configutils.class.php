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

    public static $name = '';

    public static function config($name = null) {
        global $zbp;

        if ($name === null) {
            $name = self::$name;
        } elseif (strpos($name, '.') === 0) {
            $name = self::$name . $name;
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

    private $saving = false;

    protected function __construct($config, $key = null) {
        $this->config = $config;
        if ($key !== null) $this->key = FilterCorrectName($key);
    }

    public function key($key = null) {
        if ($key === null) return $key;

        $this->key = $key;
    }

    public function get($key, $default = null) {
        if ($this->key != '' && !$this->saving) {
            $key = $this->key . '.' . $key;
        }
        if (strpos($key, '.')) {
            $value = $this->_get_value($this->config, $key, $default);
            return $value;
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
        if ($this->key != '' && !$this->saving) {
            $key = $this->key . '.' . $key;
        }
        if (strpos($key, '.')) {
            $arr = explode('.', $key);
            do {
                $key = array_pop($arr);
                $conf = $this->get(implode('.', $arr));
                if (is_array($conf)) {
                    $conf = array_merge($conf, array($key => $value));
                } elseif (is_object($conf)) {
                    $conf->$key = $value;
                } elseif ($conf === null) {
                    $conf = array($key => $value);
                } else {
                    return false;
                }
                $value = $conf;
            } while (count($arr) > 1);

            $key = current($arr);
        }

        $key = FilterCorrectName($key);
        $this->config->$key = $value;

        return true;
    }

    public function save($filters = null, $data = null) {
        if ($filters === null && $data === null) {
            $this->config->Save();
        } else {
            $this->saving = true;
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

                    if (is_int($key)) {
                        $key = $default;
                        $default = null;
                    }

                    $filter = null;
                    if (is_array($default)) {
                        var_dump($default);
                        $filter = isset($default['filter']) ? $default['filter'] : null;
                        $default = isset($default['default']) ? $default['default'] : null;
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

                    $value = $this->_get_value($data, $key);
                    if ($value === null) {
                        $value = $default;
                    } elseif ($filter !== null) {
                        $value = $filter($value);
                    }

                    if ($this->key != '') {
                        $key = $this->key . '.' . $key;
                    }

                    $this->set($key, $value);
                }
            } else {
                foreach ($data as $key => $value) {

                    if ($this->key != '') {
                        $key = $this->key . '.' . $key;
                    }

                    $this->set($key, $value);
                }
            }
            $this->saving = false;

            $this->config->Save();
        }
    }

    private function _get_value($object, $key, $default = null) {
        if (strpos($key, '.')) {
            $arr = explode('.', $key);
            $key = array_pop($arr);
            foreach ($arr as $k) {
                $object = $this->_get_value($object, $k);
                if (!is_object($object) && !is_array($object)) {
                    return $default;
                }
            }
        }
        if (is_object($object)) {
            return isset($object->$key) ? $object->$key : $default;
        } elseif (is_array($object)) {
            return array_key_exists($key, $object) ? $object[$key] : $default;
        } else {
            return $default;
        }
    }
}