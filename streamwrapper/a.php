<?php

class StreamWrapper
{
    private $content;
    private $position;

    private function register()
    {
        stream_wrapper_unregister("file");
        stream_wrapper_register("file", __CLASS__);
    }

    public function stream_open($path)
    {
        stream_wrapper_restore("file");
        $content = file_get_contents($path);
        $parts = pathinfo($path);
        if ($parts['extension'] === 'json') {
          $body = json_decode($content);
          $this->content = '<?php'.PHP_EOL;
          foreach ($body as $key => $value) {
            $this->content .= "function ${key}() { echo $value; }";
          }
        } else {
          $this->content = str_replace("hoge", "hogehoge", $content);
        }
        $this->register();
        return true;
    }

    public function stream_read($count)
    {
        $ret = substr($this->content, $this->position, $count);
        $this->position += strlen($ret);
        return $ret;
    }

    public function stream_stat()
    {
        return [];
    }

    public function stream_eof()
    {
        return $this->position >= strlen($this->content);
    }
}

stream_wrapper_unregister("file");
stream_wrapper_register("file", "StreamWrapper");

require 'b.php';

require 'a.json';

echo hello() .PHP_EOL;
echo json_key() .PHP_EOL;
