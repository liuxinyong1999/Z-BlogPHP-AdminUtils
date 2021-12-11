<?php


/**
 * Class Admin
 *
 * @author 可乐要加冰
 * @version 1.0
 *
 * 插件合集；{@link https://app.zblogcn.com/?auth=e4f2ac7f-b3cc-4f8e-a8eb-90655c6c5500}
 */
class Admin {

    public static function create($root = null) {
        return new static($root);
    }

    protected $rootPath;

    protected $blogtitle = null;

    protected $menus = array();
    protected $now_menu = '';

    protected $cssfiles = array();
    protected $css = '';

    protected $jsfiles = array();
    protected $js = '';

    private $forms = array();

    private $key = '';

    protected $type = 'table';
    protected $class = 'tableFull';
    protected $style = '';

    protected $fields = array();

    protected $form = array();

    private $config = array();

    public function __construct($root = null) {
        if ($root === null) $root = plugin_dir_path(__FILE__);
        $root = str_replace("\\", '/', $root);
        $this->rootPath = rtrim($root, '/') . '/';
    }

    public function load($file, $base = 'admin') {
        if (GetFileExt($file) != 'php') {
            $file .= '.php';
        }

        if (!$this->_is_fullpath(false)) {
            if (strpos($file, '/')) {
                $file = $this->rootPath . $file;
            } elseif ($this->_is_fullpath($base)) {
                $file = rtrim($base, '/') . '/' . $file;
            } else {
                $file = $this->rootPath . rtrim($base, '/') . '/' . $file;
            }
        }

        $props = include $file;
        if (is_array($props)) {
            foreach ($props as $key => $value) {
                $this->load_prop($key, $value);
            }
        }
    }

    protected function load_prop($key, $value) {
        if ($key == 'forms') {
            foreach ($value as $k => $v) {
                $this->begin($k);
                foreach ($v as $kk => $vv) {
                    $this->load_prop($kk, $vv);
                }
                $this->end();
            }
        } elseif ($key == 'submenu') {
            foreach ($value as $k => $v) {
                $this->submenu($k, $v);
            }
        } elseif (in_array($key, array('type', 'class', 'style', 'form', 'config'))) {
            $this->$key = $value;
        } elseif ($key == 'fields') {
            foreach ($value as $k => $v) {
                $this->field($k, $v);
            }
        } elseif (in_array($key, array('css', 'cssfile' . 'js', 'jsfiile'))) {
            $this->$key($value);
        }
    }

    public function blogtitle($title) {
        $this->blogtitle = $title;
        return $this;
    }

    public function css($css) {
        $this->css = $css;
    }

    public function cssfile($css) {
        if (is_string($css)) {
            $this->cssfiles = array_merge($this->cssfiles, array($css));
        } elseif (is_array($css)) {
            $this->cssfiles = array_merge($this->cssfiles, $css);
        }
    }

    public function js($js) {
        $this->js = $js;
    }

    public function jsfile($js) {
        if (is_string($js)) {
            $this->jsfiles = array_merge($this->jsfiles, array($js));
        } elseif (is_array($js)) {
            $this->jsfiles = array_merge($this->jsfiles, $js);
        }
    }

    public function submenu($key, $menu = null) {
        if (is_array($key)) {
            $this->menus = array_merge($this->menus, $key);
        } else {
            if ($key === null) {
                $key = count($this->menus);
            }
            $this->menus = array_merge($this->menus, array($key => $menu));
        }
        return $this;
    }

    public function nowmenu($key) {
        $this->now_menu = $key;
        return $this;
    }

    public function begin($key = null) {
        $this->end();
        if (is_null($key)) {
            $this->key = count($this->forms);
            $this->form = null;
        } else {
            $this->key = $key;
        }
        return $this;
    }

    public function type($type, $config = array(), $class = 'tableFull', $style = null) {
        $this->type = $type;
        $this->config = $config;
        $this->class = $class;
        $this->style = $style;
        return $this;
    }

    public function form($url = null, $method = null, $csrf = true) {
        if ($url === false) {
            $this->form = null;
        } elseif (is_array($url)) {
            $this->form = $url;
        } else {
            $this->form = array(
                'name' => is_string($this->key) && $this->key ? $this->key : '',
                'url' => $url,
                'method' => $method,
                'csrf' => $csrf
            );
        }
    }

    public function title($title, $subtitle = null) {
        return $this->field(null, array('type' => 'title', 'title' => $title, 'subtitle' => $subtitle));
    }

    public function submit($text, $button = false) {
        return $this->field(null, array('type' => 'submit', 'text' => $text, 'button' => $button));
    }

    public function button($text, $button = false) {
        return $this->field(null, array('type' => 'button', 'text' => $text, 'button' => $button));
    }

    public function help($text, $color = null, $style = null) {
        return $this->field(null, array('type' => 'help', 'help' => $text, 'helpcolor' => $color, 'helpstyle' => $style));
    }

    public function field($key, $args) {
        if ($key === null) {
            $key = count($this->fields);
        }
        $field = array($key => $args);
        $this->fields = array_merge($this->fields, $field);
        return $this;
    }

    public function end() {
        if ($this->key == '') return $this;
        $default = new static();
        $forms = array();
        foreach (array('type', 'style', 'class', 'fields', 'form', 'config') as $key) {
            $forms[$this->key][$key] = $this->$key;
            $this->$key = $default->$key;
        }
        array_merge($this->forms, $forms);
        $this->key = '';
        return $this;
    }

    private $building = false;

    public function build() {
        $this->end();
        if ($this->forms && !$this->building) {
            $this->building = true;
            $html = '';
            reset($this->forms);
            foreach ($this->forms as $form) {
                foreach (array('type', 'style', 'class', 'fields', 'form', 'config') as $key) {
                    $this->$key = $form[$key];
                }
                $html .= $this->build();
            }

            $default = new static();
            foreach (array('type', 'style', 'class', 'fields', 'form', 'config') as $key) {
                $this->$key = $default->$key;
            }

            return $html;
        }

        $fields = $this->fields;

        if (empty($fields)) return '';

        $html = $this->build_head();
        foreach ($fields as $key => $value) {
            $html .= $this->build_field($key, $value);
        }
        $html .= $this->build_foot();

        return $html;
    }

    public function buildSubMenu() {
        $html = '';
        foreach ($this->menus as $id => $menu) {
            $html .= '<a id="' . $id . '" href="' . $menu['url'] . '"';
            if (isset($menu['float'])) {
                $html = ' style="float:' . $menu['float'] . '"';
            }
            if (isset($menu['target'])) {
                $html .= ' target="' . $menu['target'] . '"';
            }
            if (isset($menu['title'])) {
                $html .= ' title="' . $menu['title'] . '" ' . 'alt="' . $menu['title'] . '"';
            }
            $html .= '>';

            $html .= '<span';

            $class = isset($menu['class']) ? $menu['class'] : '';
            if ($id == $this->now_menu) {
                if ($class != '') $class .= ' ';
                $class .= 'm-now';
            }
            if ($class != '') {
                $html .= ' class="' . $class . '"';
            }

            $style = isset($menu['style']) ? $menu['style'] : '';
            if ($style) $style = rtrim($style, "; \t\n\r\0\x0B") . ';';
            if ($style != '') {
                $html .= ' style="' . $style . '"';
            }

            $html .= '>' . $menu['name'] . '</span></a>';
        }

        return $html;
    }

    public function displayFull($title = null) {
        global $blogpath, $blogtitle;
        if ($title == null) $title = $this->blogtitle;
        if ($title != null) $blogtitle = $title;
        foreach (array('zbp', 'lang', 'blogname', 'blogtitle', 'bloghost', 'blogversion', 'action') as $key) {
            if (isset($GLOBALS[$key])) {
                $$key = &$GLOBALS[$key];
            }
        }
        unset($key, $title);
        require $blogpath . 'zb_system/admin/admin_header.php';
        foreach ($this->cssfiles as $file) {
            echo '<link rel="stylesheet" href="';
            if (strpos($file, 'http://') === 0
                || strpos($file, 'https://') === 0
                || strpos($file, '//') === 0) {

                echo $file;
            } else {
                echo plugin_dir_url($this->rootPath) . ltrim($file, '/');
            }
            echo '">';
        }
        if ($this->css) {
            echo '<style>' . $this->css . '</style>';
        }
        require $blogpath . 'zb_system/admin/admin_top.php';
        echo <<<html
<div id="divMain">
<div class="divHeader"><?php echo $blogtitle; ?></div>
<div class="SubMenu">
html;
        $this->displaySubMenu();
        echo <<<html
</div>
<div id="divMain2">
html;
        $this->display();
        echo <<<html
</div>
</div>
html;
        foreach ($this->jsfiles as $file) {
            echo '<script src="';
            if (strpos($file, 'http://') === 0
                || strpos($file, 'https://') === 0
                || strpos($file, '//') === 0) {

                echo $file;
            } else {
                echo plugin_dir_url($this->rootPath) . ltrim($file, '/');
            }
            echo '"></script>';
        }
        if ($this->js) {
            echo '<script>' . $this->js . '</script>';
        }
        require $blogpath . 'zb_system/admin/admin_footer.php';
        RunTime();
    }

    public function displaySubMenu() {
        echo $this->buildSubMenu();
    }

    public function display() {
        echo $this->build();
    }

    public function field_help() {
        return '';
    }

    public function field_textarea($args) {
        $html = '<textarea';

        $arr = array('id', 'class', 'style', 'name', 'placeholder');
        foreach ($arr as $key) {
            if (isset($args[$key])) {
                $val = htmlspecialchars($args[$key]);
                $html .= " $key=\"{$val}\"";
            }
        }
        $html .= '>' . htmlspecialchars($args['value']) . '</textarea>';

        return $html;
    }

    public function field_submit($args) {
        return $this->field_button($args);
    }

    public function field_button($args) {
        if ($this->_get_value($args, 'button')) {
            $html = '<button';

            $arr = array('id', 'class', 'style', 'type', 'name', 'value');
            foreach ($arr as $key) {
                if (isset($args[$key])) {
                    $val = htmlspecialchars($args[$key]);
                    $html .= " $key=\"{$val}\"";
                }
            }
            $html .= '>' . $args['text'] . '</button>';

            return $html;
        } else {
            if (in_array($args['type'], array('submit', 'button'))) {
                $args['input'] = $args['type'];
            } else {
                $args['input'] = 'button';
            }
            $args['value'] = $args['text'];
            return $this->field_input($args);
        }
    }

    public function field_redios($args) {
        if (isset($args['value']) && is_array($args['value']) && count($args['value']) > 0) {
            $args['value'] = current($args['value']);
        }
        return $this->field_checkbox($args);
    }

    public function field_checkbox($args) {
        if (isset($args['options'])) {
            $options = $args['options'];
            if (isset($args['name']) && $args['type'] == 'checkbox') {
                $args['name'] .= '[]';
            }
        } elseif (isset($args['option'])) {
            $options = array(1 => $args['option']);
        } else {
            return '';
        }

        $values = $args['value'];
        if (!is_array($values)) {
            $values = array($values);
        }

        $html = '';
        foreach ($options as $option => $label) {
            if ($html != '') $html .= '&nbsp;';
            $html .= '<label>';
            $args2 = array('input' => $args['type'], 'name' => $args['name']);
            if (in_array($option, $values)) $args2['checked'] = 'checked';
            $html .= $this->field_input($args2);
            $html .= '&nbsp;' . $label . '</label>';
        }

        return $html;
    }

    public function field_hidden($args) {
        $args['input'] = 'hidden';
        return $this->field_input($args);
    }

    public function field_file($args) {
        $args['input'] = 'file';
        return $this->field_input($args);
    }

    public function field_number($args) {
        $args['input'] = 'number';
        if (isset($args['value'])) {
            $args['value'] = preg_replace('/[^0-9\.]/', '', $args['value']);
        }
        return $this->field_input($args);
    }

    public function field_input($args) {
        $html = '<input';
        if (isset($args['input'])) {
            $html .= ' type="' . $args . '"';
        }

        $arr = array('id', 'class', 'style', 'name', 'value', 'checked', 'placeholder', 'pattern', 'max', 'min', 'step', 'accept', 'src');
        foreach ($arr as $key) {
            if (isset($args[$key])) {
                $val = htmlspecialchars($args[$key]);
                $html .= " $key=\"{$val}\"";
            }
        }

        return $html;
    }

    protected function build_field($key, $args) {
        if (isset($args['name']) && !is_int($key)) {
            $args['name'] = $key;
        }
        if (isset($args['value'])) {
            if (isset($args['default'])) {
                $args['value'] = $this->_get_config($key, $args['default']);
            } else {
                $args['value'] = $this->_get_config($key);
            }
        }

        $html = '';

        if ($args['type'] == 'title') {
            if ($this->type == 'table') {
                $html .= '<tr><th colspan="2">' . $args['label'];
                if (isset($args['subtitle'])) {
                    $html .= '<small>' . $args['subtitle'] . '</small>';
                }
                $html .= '</th></tr>';
            } elseif ($this->type == 'div') {
                $html .= '<h3>' . $args['title'];
                if (isset($args['subtitle'])) {
                    $html .= '<small>' . $args['subtitle'] . '</small>';
                }
                $html .= '</h3>';
            } elseif (strpos($this->type, 'custom_') === 0) {
                $fun = substr($this->type, 7);
                if (function_exists($fun)) {
                    $html .= $fun('form_title', $this, $args);
                }
            }
        } else {
            if ($this->type == 'table') {
                if ($args['type'] == 'hidden') {
                    $html .= '<tr style="display:none">';
                } else {
                    $html .= '<tr>';
                }
                if (isset($args['label'])) {
                    $html .= '<td>' . $args['label'] . '</td>';
                    $html .= '<td>';
                } else {
                    $html .= '<td colspan="2">';
                }
            } elseif ($this->type == 'div') {
                if ($args['type'] == 'hidden') {
                    $html .= '<p style="display:none">';
                } else {
                    $html .= '<p>';
                }
                if (isset($args['label'])) {
                    $html .= '<span>' . $args['label'] . '</span>';
                }
            } elseif (strpos($this->type, 'custom_') === 0) {
                $fun = substr($this->type, 7);
                if (function_exists($fun)) {
                    $html .= $fun('field_head', $this, $args);
                }
            }

            $fun = 'field_' . $args['type'];
            if (method_exists($this, $fun)) {
                $html .= $this->$fun($args);
            } elseif (strpos($args['type'], 'custom_')) {
                $fun = substr($args['type'], 7);
                if (function_exists($fun)) {
                    $html .= $fun($args, $this);
                }
            }

            if ($this->type == 'table') {
                if (isset($args['help'])) {
                    if (isset($args['helpstyle'])) {
                        $html .= '<p style="' . $args['helpstyle'] . '">' . $args['help'] . '</p>';
                    } elseif (isset($args['helpcolor'])) {
                        $html .= '<p style="color:' . $args['helpcolor'] . '">' . $args['help'] . '</p>';
                    } else {
                        $html .= '<p>' . $args['help'] . '</p>';
                    }
                }
                $html .= '</td></tr>';
            } elseif ($this->type == 'div') {
                if (isset($args['help'])) {
                    if (isset($args['helpstyle'])) {
                        $html .= '<p style="' . $args['helpstyle'] . '">' . $args['help'] . '</p>';
                    } elseif (isset($args['helpcolor'])) {
                        $html .= '<p style="color:' . $args['helpcolor'] . '">' . $args['help'] . '</p>';
                    } else {
                        $html .= '<p>' . $args['help'] . '</p>';
                    }
                }
                $html .= '</p>';
            } elseif (strpos($this->type, 'custom_') === 0) {
                $fun = substr($this->type, 7);
                if (function_exists($fun)) {
                    $html .= $fun('field_foot', $this, $args);
                }
            }
        }

        return $html;
    }

    protected function build_head() {
        global $zbp;

        if ($this->type == '') return '';

        $html = '';

        if ($this->form !== null) {
            $html .= '<form';
            if (isset($this->form['name'])) {
                $html .= ' name="' . $this->form['name'] . '"';
            }
            if (isset($this->form['url'])) {
                $html .= ' action="' . $this->form['url'] . '"';
            }
            if (isset($this->form['method'])) {
                $html .= 'method="' . $this->form['method'] . '"';
            }
            $html .= '>';

            $csrf = $this->_get_value($this->form, 'csrf', true);
            if (is_array($csrf)) {
                $html .= '<input name="' . $csrf[1] . '" type="hidden" value="' . $zbp->GetCSRFToken($csrf[0]) . '">';
            } elseif (is_string($csrf)) {
                $html .= '<input name="csrfTokdn" type="hidden" value="' . $zbp->GetCSRFToken($csrf) . '">';
            } elseif ($csrf) {
                $html .= '<input name="csrfTokdn" type="hidden" value="' . $zbp->GetCSRFToken() . '">';
            }
        }

        if ($this->type == 'table') {
            $html .= '<table';
            if ($this->class) {
                $html .= ' class="' . $this->class . '"';
            }
            if ($this->style) {
                $html .= ' style="' . $this->style . '"';
            }
            $html .= ' border="1"><tbody>';
        } elseif ($this->type == 'div') {
            $html .= '<div';
            if ($this->class) {
                $html .= ' class="' . $this->class . '"';
            }
            if ($this->style) {
                $html .= ' style="' . $this->style . '"';
            }
            $html .= '>';
        } elseif (strpos($this->type, 'custom_') === 0) {
            $fun = substr($this->type, 7);
            if (function_exists($fun)) {
                $html .= $fun('form_head', $this);
            }
        }

        return $html;
    }

    protected function build_foot() {
        if ($this->type == '') return '';

        $html = '';
        if ($this->type == 'table') {
            $html = '</tbody></table>';
        } elseif ($this->type == 'div') {
            $html = '</div>';
        } elseif (strpos($this->type, 'custom_') === 0) {
            $fun = substr($this->type, 7);
            if (function_exists($fun)) {
                $html = $fun('form_foot', $this);
            }
        }

        if ($this->form !== null) {
            $html .= '</form>';
        }

        return $html;
    }

    protected function _get_config($key, $default = null) {
        if (is_object($this->config)) {
            return isset($this->config->$key) ? $this->config->$key : $default;
        } elseif (is_array($this->config)) {
            return array_key_exists($key, $this->config) ? $this->config[$key] : $default;
        }
    }

    private function _get_value($object, $key, $default = null) {
        if (is_object($object)) {
            return isset($object->$key) ? $object->$key : $default;
        } elseif (is_array($object)) {
            return array_key_exists($key, $object) ? $object[$key] : $default;
        } else {
            return $default;
        }
    }

    private function _is_fullpath($file) {
        if (PHP_SYSTEM == SYSTEM_WINDOWS && preg_match('/^[a-z]\:/i', $file)) {
            return true;
        } elseif (strpos($file, '/') === 0) {
            return true;
        }
        return false;
    }
}