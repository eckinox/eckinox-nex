<?php

namespace Eckinox\Nex;

/**
 * Used to consolidate and minify Script and CSS
 * @version 1.0.1
 * @update (18/11/2013) [ML] 1.0.1 - Cache file will now be separated by host and have a signature to differentiate from multiple configs
 */
class Compiler {

    protected $config;
    protected $host;
    protected $needs_update = false;
    protected $dir;
    protected $urls = [];
    protected $files = [];
    protected $raw_signature = '';
    protected $last_mdate;

    public function __construct() {
        $this->config = Nex::config('compiler');
    }

    // Compile Javascript files
    public function compileScripts($arr, $name = 'compiled') {
        $this->raw_signature = url::host(url::site());
        $this->dir = PUB_PATH . $this->config['dir'] . 'script/';

        if ($this->config['script']['consolidate']) {
            $this->findUrls($arr, '.js', 'script');
            $name = md5($this->raw_signature) . $name;

            switch ($this->config['script']['consolidate']) {
                case 3: $this->consolidate($this->urls, $name . '.all', '.js');
                    break;
                case 2: $this->consolidateAgressive($this->urls, $name, '.js');
                    break;
                default: $this->consolidateConservative($this->urls, $name, '.js');
            }
        } else {
            return $arr;
        }

        if ($this->config['script']['minify']) {
            $this->minifyJS();
        }

        $this->writeFiles();

        return $this->arrFromFiles();
    }

    // Compile CSS files
    public function compileStylesheets($arr, $name = 'compiled') {
        $this->raw_signature = url::host(url::site());
        $this->dir = PUB_PATH . $this->config['dir'] . 'css/';

        if ($this->config['stylesheet']['consolidate']) {
            $this->findUrls($arr, '.css', 'css');
            $name = md5($this->raw_signature) . $name;

            switch ($this->config['script']['consolidate']) {
                case 3: $this->consolidate($this->urls, $name . '.all', '.css');
                    break;
                case 2: $this->consolidateAgressive($this->urls, $name, '.css');
                    break;
                default: $this->consolidateConservative($this->urls, $name, '.css');
            }
        } else {
            return $arr;
        }

        if ($this->config['stylesheet']['minify']) {
            $this->minifyCSS();
        }

        $this->writeFiles();

        return $this->arrFromFiles();
    }

    /**
     * Find files to combine in an aggressive manner.
     * All local files will be consolidated together and added at the end of the array
     * The original include order may not be preserved
     */
    protected function consolidate($urls, $name, $ext) {
        $name .= $ext;
        $path = DOC_ROOT . $this->dir . $name;
        $content = '';
        $compile = false;

        if (!file_exists($path) || $this->last_mdate > filemtime($path)) {
            $compile = true;
            foreach ($urls as $arr) {
                if ($arr['type'] == 'script' || $arr['type'] == 'css') {
                    $content .= '/* ' . $arr['url'] . ' */' . NEX_EOL;
                }

                if ($arr['is_local']) {
                    if ($arr['type'] == 'css') {
                        $content .= $this->absoluteCSS(file_get_contents(DOC_ROOT . $arr['url']), $arr['url']) . NEX_EOL;
                    } else {
                        $content .= file_get_contents(DOC_ROOT . $arr['url']) . NEX_EOL;
                    }
                } else {
                    $url = (substr($arr['url'], 0, 2) == '//' ? 'http:' : '') . $arr['url'];
                    $content .= file_get_contents($url) . NEX_EOL;
                }
            }
        }

        $this->files[$name] = array('content' => $content, 'compile' => $compile, 'is_local' => true);
    }

    /**
     * Find files to combine in an aggressive manner.
     * All local files will be consolidated together and added at the end of the array
     * The original include order may not be preserved
     */
    protected function consolidateAgressive($urls, $name, $ext) {
        $locals = [];

        foreach ($urls as $arr) {
            if ($arr['is_local']) {
                $locals[] = $arr;
            } else {
                $this->files[$arr['url']] = array('content' => '', 'compile' => false, 'is_local' => false);
            }
        }

        $this->consolidate($locals, $name, $ext);
    }

    /**
     * Find files to combine in an conservative manner.
     * Local files are grouped together separated by external files.
     * The original include order is preserved
     */
    protected function consolidateConservative($urls, $name, $ext) {
        $this->last_mdate = null;
        $locals = [];
        $i = 1;

        foreach ($urls as $arr) {
            if ($arr['is_local']) {
                $locals[] = $arr;
                if ($arr['mdate'] > $this->last_mdate)
                    $this->last_mdate = $arr['mdate'];
            }
            else {
                if (count($locals)) {
                    $this->consolidate($locals, $name . $i, $ext);
                    $last_mdate = null;
                    $i++;
                    $locals = [];
                }

                $this->files[$arr['url']] = array('content' => '', 'compile' => false, 'is_local' => false);
            }
        }

        if (count($locals)) {
            $this->consolidate($locals, $name . $i, $ext);
        }
    }

    protected function findUrls($arr, $ext, $type) {
        $root_url = url::site_root();
        $root_url_len = strlen($root_url);
        foreach ($arr as $i => $url) {
            $this->raw_signature .= $url;

            $is_local = false;
            if (basename($url) == $url) {
                $url .= (stripos($url, '.') == false) ? $ext : '';
                $url = Nex::skinPath($type . '/' . $url);
                $is_local = true;
            } elseif ($root_url === substr($url, 0, $root_url_len)) {
                $url = substr($url, $root_url_len);
                $is_local = true;
            }

            $path = DOC_ROOT . $url;
            if ($is_local && file_exists($path)) {
                $mdate = filemtime($path);
                $this->urls[] = array('url' => $url, 'is_local' => true, 'mdate' => $mdate, 'type' => $type);
                if ($mdate > $this->last_mdate) {
                    $this->last_mdate = filemtime($path);
                }
            } else {
                $this->urls[] = array('url' => $url, 'is_local' => false, 'mdate' => null, 'type' => $type);
            }
        }
    }

    protected function minifyCSS() {
        foreach ($this->files as $k => $arr) {
            if (!$arr['compile'] || !$arr['is_local'])
                continue;

            /* remove comments */
            $arr['content'] = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $arr['content']);

            /* remove tabs, spaces, newlines, etc. */
            $arr['content'] = preg_replace('/\s+/', ' ', $arr['content']);
            $arr['content'] = str_replace('; ', ';', $arr['content']);
            $arr['content'] = str_replace(': ', ':', $arr['content']);
            $arr['content'] = str_replace(' {', '{', $arr['content']);
            $arr['content'] = str_replace('{ ', '{', $arr['content']);
            $arr['content'] = str_replace(', ', ',', $arr['content']);
            $arr['content'] = str_replace('} ', '}', $arr['content']);
            $arr['content'] = str_replace(';}', '}', $arr['content']);

            $this->files[$k]['content'] = $arr['content'];
        }
    }

    protected function absoluteCSS($content, $file) {
        $dir = dirname($file) . '/';
        return preg_replace_callback("/url\(\s*['\"]?(?!data:|http:\/\/|https:\/\/)([^'\"\)]+)['\"]?\s*\)/", create_function(
                        '$matches', 'return "url(\'".url::site(url::resolve("' . $dir . '".$matches[1]))."\')";'
                ), $content);
    }

    protected function minifyJS() {
        foreach ($this->files as $k => $arr) {
            if (!$arr['compile'] || !$arr['is_local'])
                continue;

            $this->files[$k]['content'] = JSMin::minify($arr['content']);
        }
    }

    protected function writeFiles() {
        $path = DOC_ROOT . $this->dir;

        if (!is_dir($path))
            mkdir($path, 0775, true);

        foreach ($this->files as $file => $arr) {
            if ($arr['compile'] && $arr['is_local'])
                file_put_contents($path . $file, $arr['content']);
        }
    }

    protected function arrFromFiles() {
        $arr = [];
        foreach ($this->files as $file => $r) {
            if ($r['is_local']) {
                $modified_date = md5(filemtime(DOC_ROOT . $this->dir . $file));

                if (Nex::config('system.url.rewrite_public')) {
                    $url = url::site($this->dir . $modified_date . '_' . $file, false);
                } else {
                    $url = url::addParam(url::site($this->dir . $file), '_m', $modified_date);
                }
            } else {
                $url = $file;
            }

            $arr[] = $url;
        }

        return $arr;
    }

}
