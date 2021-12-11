<?php


class AdminUtils {

    /**
     * @var string Admin的类名前缀，如果有命名空间也要一起写上
     */
    public static $preffix = '';

    public static function load($default = '', $key = 'get.act', $base = 'admin') {
        global $zbp;

        if (strpos($key, '.')) {
            $type = SplitAndGet($key, '.', 0);
            $key = SplitAndGet($key, '.', 1);
            $file = GetVars($key, $type);
        } else {
            $file = GetVars($key);
        }

        if (is_string($default)) {
            $default = GetFileExt($default);
            $default = include $default;
        }

        if (isset($default['allow_actions'])) {
            if (!in_array($file, $default['allow_actions'])) {
                $zbp->ShowError(2);
            }
        } else {
            $file = FormatString($file, '[filename]');
        }

        $is_post = count($_POST) > 0;
        $is_ajax = strtolower((string)GetVars('HTTP_X_REQUESTED_WITH')) == 'xmlhttprequest';

        if ($is_ajax) {
            CheckIsRefererValid();
            $file = 'ajax_' . $file;
        } elseif ($is_post) {
            CheckIsRefererValid();
            $file = 'post_' . $file;
        }

        $file = self::_get_fullpath($file, $base);

        if ($is_ajax || $is_post) {
            if ($is_ajax) {
                Add_Filter_Plugin('Filter_Plugin_Debug_Handler', get_class() . '::json_error');
            }
            include $file;
        } else {
            $props = include $file;
            $props = array_merge($default, $props);
            (${self::$preffix . 'Admin::create'}($props))->load($props)->displayFull();
        }
    }

    public static function json_error($type, $error) {
        if ($type == 'Exception') {
            self::json_return(2, $error->getMessage());
        } elseif ($type == 'Error' || $type == 'Shutdown') {
            self::json_return(2, $error[1]);
        }
    }

    public static function json_return($code = 0, $data = null) {
        ob_clean();
        if ($code == 0) {
            echo json_encode(array('code' => $code, 'data' => $data));
        } else {
            echo json_encode(array('code' => $code, 'msg' => $data));
        }
        die();
    }

    private static function _get_fullpath($file, $base = 'admin') {
        if (GetFileExt($file) != 'php') {
            $file .= '.php';
        }

        if (!self::_is_fullpath($file)) {
            if (strpos($file, '/')) {
                $file = plugin_dir_path(__LINE__) . $file;
            } elseif (self::_is_fullpath($base)) {
                $file = rtrim($base, '/') . '/' . $file;
            } else {
                $file = plugin_dir_path(__FILE__) . rtrim($base, '/') . '/' . $file;
            }
        }

        return $file;
    }

    private static function _is_fullpath($file) {
        if (PHP_SYSTEM == SYSTEM_WINDOWS && preg_match('/^[a-z]\:[\\\/]/i', $file)) {
            return true;
        } elseif (strpos($file, '/') === 0) {
            return true;
        }
        return false;
    }
}