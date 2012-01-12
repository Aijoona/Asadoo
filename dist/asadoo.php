<?php
/**
 * Asadoo
 *
 * @author Valentin Starck
 */
// From file: ../src/AsadooCore.php

class AsadooCore {
	private static $instance;
	private $handlers = array();
    private $interrupted = false;

	private function __construct() {
		$this->createRequest();
		$this->createResponse();
		$this->createDependences();
	}

	private function __clone() {}

	public static function getInstance() {
		return self::$instance ? self::$instance : (self::$instance = new self());
	}

	public function add($handler) {
		$this->handlers[] = $handler;
	}

	public function start() {
		$request = $this->request;
		$response = $this->response;
		$dependences = $this->dependences;

		foreach($this->handlers as $handler) {
		    if($this->interrupted) {
                break;
		    }

			if($this->match($handler->conditions)) {
				$fn = $handler->fn;

				$fn($request, $response, $dependences);
			}
		}
	}

	private function createRequest() {
		$this->request = new AsadooRequest();
	}

	private function createResponse() {
		$this->response = new AsadooResponse();
	}
	private function createDependences() {
		$this->dependences = new AsadooDependences();
	}

    public function end() {
        $this->interrupted = true;
    }

	private function match($conditions) {
		foreach($conditions as $condition) {
			if($this->matchCondition($condition)) {
				return true;
			}
		}
		return false;
	}

	private function matchCondition($condition) {
		$request = $this->request;
		$response = $this->response;
		$dependences = $this->dependences;

		if(is_callable($condition)) {
			if($condition($request, $response, $dependences)) {
				return true;
			}
		}

		if(is_string($condition)) {
			if(trim($condition) == '*') {
				return true;
			}

			if($this->matchStringCondition($condition)) {
				return true;
			}
		}

		return false;
	}

	private function matchStringCondition($condition) {
		$url = $this->request->url();

		$keys = array();

		$condition = preg_replace('/\//', '\/', $condition) . '$';

		while(strpos($condition, ':') !== false) {
			$matches = array();

			preg_match('/:(\w+)/', $condition, $matches);

			$keys[] = $matches[1];

			$condition = preg_replace('/:\w+/', '([^\/\?\#]+)', $condition);
		}

		$values = array();

		$result = preg_match('/' . $condition . '/', $url, $values);

		if(!$result) {
			return false;
		}

		if(count($keys)) {
			array_shift($values);

			$this->request->set(
				array_combine($keys, $values)
			);
		}

		return true;
	}
}

function asadoo() {
	return new AsadooHandler();
}
// From file: ../src/AsadooDependences.php

/**
 * @auhtor Fabien Potencer
 * @see http://www.slideshare.net/fabpot/dependency-injection-with-php-53
 */
class AsadooDependences {
    protected $deps = array();

    public function register($id, $value) {
        $this->deps[$id] = $value;
    }

    /**
	 * @param $id
	 * @return mixed
	 */
    public function __get($id) {
        if (!isset($this->deps[$id])) {
            return null;
        }

        if (is_callable($this->deps[$id])) {
            // Lazy loading
            return $this->deps[$id]($this);
        } else {
            return $this->deps[$id];
        }
    }

    public function asShared($callable) {
        return function ($c) use ($callable) {
            static $object;

            if (is_null($object)) {
                $object = $callable($c);
            }
            return $object;
        };
    }
}

// From file: ../src/AsadooRequest.php

class AsadooRequest {
	private $variables = array();

	public function segment($index) {}
	public function has($match) {}

	public function get($key, $fallback = null) {
		if(isset($this->variables[$key])) {
			return $this->variables[$key];
		}

		if(isset($_REQUEST[$key])) {
			return $_REQUEST[$key];
		}

		return $fallback;
	}

	public function set($key, $value = null) {
		if(is_array($key)) {
			foreach($key as $k => $v) {
				$this->set($k, $v);
			}
			return;
		}

		$this->variables[$key] = $value;

		return $value;
	}

	public function isPost() {
		return $_SERVER['REQUEST_METHOD'] == 'POST';
	}

	public function url() {
		$url = $this->domain() . $_SERVER['REQUEST_URI'];

		if(substr($url, -1, 1) == '/') {
			$url = substr($url, 0, -1);
		}

		return $url;
	}

	public function domain() {
		return $_SERVER['SERVER_NAME'];
	}

	public function ip() {}
}
// From file: ../src/AsadooResponse.php

class AsadooResponse {
    private $code = 200;

    private $codes = array(
        '200' => 'OK',
        '201' => 'Created',
        '202' => 'Accepted',
        '203' => 'Non-Authoritative Information',
        '204' => 'No Content',
        '205' => 'Reset Content',
        '206' => 'Partial Content',
        '300' => 'Multiple Choices',
        '301' => 'Moved Permanently',
        '302' => 'Found',
        '303' => 'See Other',
        '304' => 'Not Modified',
        '305' => 'Use Proxy',
        '307' => 'Temporary Redirect',
        '400' => 'Bad Request',
        '401' => 'Unauthorized',
        '402' => 'Payment Required',
        '403' => 'Forbidden',
        '404' => 'Not Found',
        '405' => 'Method Not Allowed',
        '406' => 'Not Acceptable',
        '407' => 'Proxy Authentication Required',
        '408' => 'Request Timeout',
        '409' => 'Conflict',
        '411' => 'Length Required',
        '412' => 'Precondition Failed',
        '413' => 'Request Entity Too Large',
        '414' => 'Request-URI Too Long',
        '415' => 'Unsupported Media Type',
        '416' => 'Requested Range Not Satisfiable',
        '417' => 'Expectation Failed',
        '500' => 'Internal Server Error',
        '501' => 'Not Implemented',
        '502' => 'Bad Gateway',
        '503' => 'Service Unavailable',
        '504' => 'Gateway Timeout',
        '505' => 'HTTP Version Not Supported'
    );

    public function __construct() {
    }

	// header...
	/*public function __call($name, $arguments) {

	}*/

	private function sendResponseCode($code) {
	    if(isset($this->codes[$code])) {
            $this->header('HTTP/1.0', $code . ' ' .$this->codes[$code]);
	        return true;
	    }

	    return false;
	}

    public function setResponseCode($code) {
        $this->code = $code;
        return $this;
    }

	public function setCache() {}
	public function setNoCache() {}

	public function header($key, $value) {
        header($key . ' ' . $value);
	}

	public function send($content) {
		echo $content;
	}

    public function end() {
        AsadooCore::getInstance()->end();

        $this->sendResponseCode($this->code);
       // ob_end_flush();
    }


}
// From file: ../src/AsadooHandler.php

class AsadooHandler {
	public $conditions = array();
	public $fn;
	public $finisher = false;

	public function on($condition) {
		$this->conditions[] = $condition;
		return $this;
	}

	public function handle($fn) {
		$this->fn = $fn;
		$this->register($this);

		return $this;
	}

	public function close() {
		$this->finisher = true;
		return $this;
	}

	public function dependences() {
		return AsadooCore::getInstance()->dependences;
	}

	public function start() {
		AsadooCore::getInstance()->start();
		return $this;
	}

	private function register($handler) {
		AsadooCore::getInstance()->add($handler);
	}
}
