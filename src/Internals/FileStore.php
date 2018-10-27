<?php

namespace Covert\Internals;

class FileStore
{
    private $path;
    private $content;

    public function __construct($path = false)
    {
        $this->path = $path ? $path : sys_get_temp_dir() . '/php-covert/';

        if (!$this->exists()) {
            mkdir($this->path, 0777, true);
        }

        $this->path .= 'php-covert-processes.json';

        if (!$this->exists()) {
            $this->write([
              'processes' => []
            ]);
        }
    }

    public function write($content)
    {
        $this->content = $content;

        file_put_contents($this->path, json_encode($content));

        return $this;
    }

    public function read()
    {
        $this->content = json_decode(file_get_contents($this->path), true);

        return $this->content;
    }

    public function exists()
    {
        return file_exists($this->path);
    }
}
