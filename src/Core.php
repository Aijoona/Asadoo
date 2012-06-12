<?php
namespace asadoo;

final class Core extends Mixin {
    /**
     * @var Core
     */
    private static $instance;
    private $handlers = array();
    private $interrupted = false;
    private $started = false;

    private $beforeCallback = null;
    private $afterCallback = null;

    private function __construct() {
        $this->request = new Request($this);
        $this->response = new Response($this);
        $this->responseMatcher = new Dispatcher($this);
        $this->dependences = new \Pimple();
    }

    private function __clone() {
    }

    public static function getInstance() {
        return self::$instance ? self::$instance : (self::$instance = new self());
    }

    public function add($handler) {
        $this->handlers[] = $handler;
    }

    public function start() {
        if ($this->started) {
            return;
        }

        $this->started = true;

        $this->before();

        foreach ($this->handlers as $handler) {
            if ($this->interrupted) {
                break;
            }

            if ($this->request->match($handler->conditions)) {
                $fn = $handler->fn;
                $fn($this->request, $this->response, $this->dependences);
            }
        }

        if (!$this->interrupted) {
            $this->response->end();
        }
    }

    public function end() {
        $this->interrupted = true;
        $this->after();
    }

    public function after($fn = null) {
        if ($fn) {
            $this->afterCallback = $fn;
        } else if (is_callable($fn = $this->afterCallback)) {
            $fn($this->request, $this->response, $this->dependences);
        }

        return $this;
    }

    public function before($fn = null) {
        if ($fn) {
            $this->beforeCallback = $fn;
        } else if (is_callable($fn = $this->beforeCallback)) {
            $fn($this->request, $this->response, $this->dependences);
        }

        return $this;
    }

    public function baseURL() {
        return $this->request->baseURL();
    }

    public function handle($name) {
        foreach ($this->handlers as $handler) {
            if($handler->name() == $name) {
                $fn = $handler->fn;
                $fn($this->request, $this->response, $this->dependences);
                return $this;
            }
        }

        return $this;
    }
}