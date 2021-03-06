<?php
class Builder {
    private $dest = 'all.digest';
    private $separator = '';
    private $content = '';
    private $base_path = '';
    private $formatter = null;

    private $added = 0;
    private $proccessed = 0;

    public function set_dest($dest) {
        $this->dest = $dest;
        return $this;
    }

    public function add_file($file, $prefix = null) {
        $this->add($file, $prefix);
        return $this;
    }

    public function add_files($files, $prefix = null) {
        foreach ($files as $file) {
            $this->add($file, $prefix);
        }

        return $this;
    }

    public function add_directory($path) {
        $path = $this->base_path . $path;

        $this->add_files(
            $this->get_directory_files($path)
        );

        $subdirectories = $this->get_directory_directories($path);

        foreach ($subdirectories as $dir) {
            $this->add_directory($dir);
        }

        return $this;
    }

    public function set_path($path) {
        $this->base_path = $path;
        return $this;
    }

    public function set_separator($separator) {
        $this->separator = $separator;
        return $this;
    }

    public function process() {
        $path = $this->dest;

        if (!is_writable($path)) {
            // error
        }

        if (!file_put_contents($path, $this->content)) {
            // error
        }

        return $this;
    }

    private function add($file, $prefix  = null) {
        $file = (is_null($prefix) ? $this->base_path : $prefix) . $file;

        if (is_readable($file) && !is_dir($file)) {
            $this->content .= $this->apply_format(file_get_contents($file), $file);
            $this->content .= $this->separator;

            $this->added++;
        }

        $this->proccessed++;

        return $this;
    }

    private function get_directory_files($dir) {
        if (!is_dir($dir)) {
            return array();
        }

        $dh = opendir($dir);
        $files = array();
        while (($file = readdir($dh)) !== false) {
            if ($file !== '.' && $file !== '..' && !is_dir($file)) {
                $files[] = $dir . DIRECTORY_SEPARATOR . $file;
            }
        }
        return $files;
    }

    private function get_directory_directories($dir) {
        if (!is_dir($dir)) {
            return array();
        }

        $dh = opendir($dir);
        $dirs = array();
        while (($file = readdir($dh)) !== false) {
            if ($file !== '.' && $file !== '..' && is_dir($file)) {
                $dirs[] = $dir . DIRECTORY_SEPARATOR . $file;
            }
        }
        return $dirs;
    }

    public function text() {
        return $this->content;
    }

    public function result() {
        return $this->added . '/' . $this->proccessed;
    }

    public function format($formatter) {
        $this->formatter = $formatter;

        return $this;
    }

    public function apply_format($content, $filename) {
        if (!isset($this->formatter)) {
            return $content;
        }

        $formatter = $this->formatter;

        return $formatter($content, $filename);
    }
}