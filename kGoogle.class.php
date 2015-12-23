<?php
/**
 * [J2TeaM] Get link google picasa & plus
 *
 * @package Base
 * @author Killer <killer.vn54119@hotmail.com>
 * @version 1.0.0
 * @release: October 11, 2015
 * @update: October 19, 2015
 * @library: PHP Classes cURL https://github.com/php-curl-class/php-curl-class
 *
 */
class J2T_Exception extends Exception {}
class J2T {

	const VERSION = '1.0.0';
	const APPNAME = '[J2TeaM]K-Google';

	protected $Link = '';
	private $_plugins = array(
		'google_picasa' => 'Google_Picasa',
		'google_plus' => 'Google_Plus'
	);

	protected $_is_plugin_loader = null;

	protected $Format = false;

	public function run(){
		$this->plugins();
		if(null === $this->_is_plugin_loader) return;
		$this->_is_plugin_loader->run();
		return $this->render();
	}

	public function render(){
		if('json'==$this->Format){
			header('Content-Type: application/json');
		}
		return json_encode($this->_is_plugin_loader->data);
	}

	public function plugins(array $plugins_name = array()){
		$prefix_class = 'J2T_Plugin_';
		if(empty($plugins_name)){
			$plugins_name = $this->_plugins;
		}
		$class_name = '';
		$_is_plugin_loader = '';
		foreach($plugins_name as $key => $plugin_name){
			$class_name = $prefix_class . $plugin_name;
			if(!class_exists($class_name)){
				throw new J2T_Exception('Attempted to load an invalid or missing plugin!');
			}else{
				$plugin_loader = new $class_name;
				if($plugin_loader->validLink($this->getLink) != false){
					$this->_is_plugin_loader = $plugin_loader;
				}
				unset($plugin_loader);
			}
			unset($class_name);
		}
		return;
	}

	public function __get($name){
		$_method_exists = ((substr($name,0,3) == 'get') ? true : false);
		$method_name = substr($name,3);
		if(!$_method_exists){
			throw new J2T_Exception('Method is not exists!');
		}else{
			if(!method_exists($this,$method_name) == false){
				throw new J2T_Exception('Method is not exists!');
			}else{
				return $this->{$method_name};
			}
		}
		return;
	}

	public function __set($name, $params){

		$_method_exists = ((substr($name,0,3) == 'set') ? true : false);
		$method_name = substr($name,3);
		if(!$_method_exists){
			throw new J2T_Exception('Method is not exists!');
		}else{
			if(!method_exists($this,$method_name) == false){
				throw new J2T_Exception('Method is not exists!');
			}else{
				$this->{$method_name} = $params;
			}
		}
		return;
	}

}
/**
 * Library cURL
 * Source: https://github.com/php-curl-class/php-curl-class
 *
 * @package HTTP
 * @author Killer <killer.vn54119@hotmail.com>
 * @version 1.0.0
 *
 */
class J2T_HTTP {
	const DEFAULT_TIMEOUT = 30;
	/**
	 *
	 * @var object
	 */
	protected static $_instance;
	/**
	 *
	 * @var string
	 */
	public $base_url = '';
	protected $url;
	/**
	 *
	 * @var resource
	 */
	private $ch;
	/**
	 *
	 * @var array
	 */
	protected $_options = array();
	/**
	 *
	 * @var array
	 */
	protected $_headers = array();
	/**
	 *
	 * @var array
	 */
	protected $_cookies = array();
	public $raw_response;
	/**
	 *
	 * @var string
	 */
	public $raw_response_headers = '';
	/**
	 *
	 * @var array
	 */
	public $raw_response_cookies = array();
	/**
	 *
	 * @var array
	 */
	public $errors = array(
		'status',
		'code',
		'msg',
		'curl' => array(
			'status',
			'code',
			'msg',
		),
		'http' => array(
			'status',
			'code',
			'msg'
		)
	);
	public $response;
	public $request_headers;
	public $response_headers;

	/**
    private $successFunction = null;
    private $errorFunction = null;
    private $completeFunction = null;
    **/
	/**
	 *
	 * @var type
	 */
    private $_is_json = null;
	/**
	 *
	 * @var array
	 */
    private $_pattern = array(
    	'json' => '/^(?:application|text)\/(?:[a-z]+(?:[\.-][0-9a-z]+){0,}[\+\.]|x-)?json(?:-[a-z]+)?/i',
    	'xml' => '~^(?:text/|application/(?:atom\+|rss\+)?)xml~i'
    );

	/**
	 *
	 * @param string $base_url
	 * @throws J2T_Exception
	 */
	public function __construct($base_url = null){
		if(!extension_loaded('curl')){
			throw new J2T_Exception('cURL library is not loaded!');
		}
		$this->setup($base_url);
	}

	/**
	 *
	 * @return object
	 */
	public static function instance(){
		if(self::$_instance == NULL){
			self::$_instance = new self;
		}
		return self::$_instance;
	}

	/**
	 *
	 * @param string $url
	 * @return \J2T_HTTP
	 */
	public function setup($url = null){
		$this->ch = curl_init();
		$this->_defaultUserAgent();
		$this->_defaultTimeout();

		//.....
		$this->setOption(CURLINFO_HEADER_OUT, true);
		$this->setOption(CURLOPT_HEADERFUNCTION, array($this, '_rawResponseHeaders'));
		$this->setOption(CURLOPT_RETURNTRANSFER, true);
		$this->_headers = new CaseInsensitiveArray();
		$this->setURL($url);
		return $this;
	}

	/**
	 *
	 * @param string $url
	 * @param array $data
	 * @return string
	 */
    public function get($url, $data = array()){
        if(is_array($url)){
            $data = $url;
            $url = $this->base_url;
        }
        $this->setURL($url, $data);
        $this->setOption(CURLOPT_CUSTOMREQUEST, 'GET');
        $this->setOption(CURLOPT_HTTPGET, true);
        return $this->execute();
    }

	/**
	 *
	 * @param string $url
	 * @param array $data
	 * @return string
	 */
    public function post($url, $data = array()){
        if (is_array($url)) {
            $data = $url;
            $url = $this->base_url;
        }
        $this->setURL($url);
        $this->setOption(CURLOPT_CUSTOMREQUEST, 'POST');
        $this->setOption(CURLOPT_POST, true);
        $this->setOption(CURLOPT_POSTFIELDS, $this->_buildPostParams($data));
        return $this->execute();
    }

	/**
	 *
	 * @param string $url
	 * @param array $data
	 * @return string
	 */
    public function options($url, $data = array()){
        if (is_array($url)) {
            $data = $url;
            $url = $this->base_url;
        }
        $this->setURL($url, $data);
        $this->unsetHeader('Content-Length');
        $this->setOption(CURLOPT_CUSTOMREQUEST, 'OPTIONS');
        return $this->execute();
    }

	/**
	 *
	 * @return string
	 */
	public function execute(){
		//self::_callfunc($this->beforeSendFunction);
		$this->raw_response = $this->_rawResponse();
		$this->errors['curl']['code'] = $this->_raw_curl_errno();

		$this->errors['curl']['msg'] = $this->_raw_curl_error_msg();
		$this->errors['curl']['status'] = !($this->errors['curl']['code'] === 0);

		$this->errors['http']['code'] = $this->_getinfo_http_code();
		$this->errros['http']['status'] = in_array(floor($this->errors['http']['code'] / 100), array(4, 5));

		$this->errors['status'] = $this->errors['curl']['status'] || $this->errros['http']['status'];
		$this->errors['code'] = $this->errors['status'] ? ($this->errors['curl']['status'] ? $this->errors['curl']['code'] : $this->errors['http']['code']) : 0;

        if($this->getOption(CURLINFO_HEADER_OUT) === true) {
            $this->request_headers = $this->_parseRequestHeaders($this->_getinfo_header_out());
        }
        $this->response_headers = $this->_parseResponseHeaders($this->raw_response_headers);
        list($this->response, $this->raw_response) = $this->_parseResponse($this->response_headers, $this->raw_response);

        $this->errors['http']['msg'] = '';

        if($this->errors['status']){
            if (isset($this->response_headers['Status-Line'])) {
                $this->errors['http']['msg'] = $this->response_headers['Status-Line'];
            }
            $this->errors['msg'] = $this->errors['curl']['status'] ? $this->errors['curl']['msg'] : $this->errors['http']['msg'];
            //self::_callfunc($this->errorFunction);
        }else{
        	//self::_callfunc($this->successFunction);
        }
        //self::_callfunc($this->completeFunction);
        return $this->response;
	}

	/**
	 *
	 * @return string
	 */
	protected function _getinfo_header_out(){
		return $this->getInfo(CURLINFO_HEADER_OUT);
	}

	/**
	 *
	 * @return string
	 */
	protected function _getinfo_http_code(){
		return $this->getInfo(CURLINFO_HTTP_CODE);
	}

	/**
	 *
	 * @return type
	 */
	protected function _raw_curl_error_msg(){
		return curl_error($this->ch);
	}

	/**
	 *
	 * @return type
	 */
	protected function _raw_curl_errno(){
		return curl_errno($this->ch);
	}

	/**
	 *
	 * @return string
	 */
	protected function _rawResponse(){
		return curl_exec($this->ch);
	}

	/**
	 *
	 * @param resource $ch
	 * @param string $headers
	 * @return int
	 */
	protected function _rawResponseHeaders($ch, $headers){
        if (preg_match('/^Set-Cookie:\s*([^=]+)=([^;]+)/mi', $headers, $cookies) == 1) {
            $this->raw_response_cookies[$cookies[1]] = $cookies[2];
        }
        $this->raw_response_headers .= $headers;
        return strlen($headers);
	}

	/**
	 *
	 * @param array $params
	 * @return \CURLFile
	 */
	protected function _buildPostParams($params){
		if(is_array($params)){
			if(J2T_Array::is_array_multidim($params)){
				if(isset($this->_headers['Content-Type'])){
					$json_enc = json_encode($params);
					if(!($json_enc === false)){
						$params = $json_enc;
					}
				}else{
					$params = self::http_build_multi_query($params);
				}
			}else{
                $binary_data = false;
                foreach($params as $key => $value){
                    if (is_array($value) && empty($value)) {
                        $params[$key] = '';
                    }elseif(is_string($value) && strpos($value, '@') === 0){
                        $binary_data = true;
                        if (class_exists('CURLFile')) {
                            $params[$key] = new CURLFile(substr($value, 1));
                        }
                    }elseif($value instanceof CURLFile) {
                        $binary_data = true;
                    }
				}
                if(!$binary_data){
                    if(isset($this->headers['Content-Type']) &&
                        preg_match($this->_pattern['json'], $this->headers['Content-Type'])) {
                        $json_str = json_encode($data);
                        if (!($json_str === false)) {
                            $data = $json_str;
                        }
                    } else {
                        $data = http_build_query($data, '', '&');
                    }
                }
			}
		}
		return $params;
	}

	/**
	 *
	 * @param string $opt
	 * @return string|array
	 */
	public function getOption($opt){
		return $this->_options[$opt];
	}

	/**
	 *
	 */
	private function _defaultTimeout(){
		$this->setTimeout(self::DEFAULT_TIMEOUT);
	}

	/**
	 *
	 */
	private function _defaultUserAgent(){
		$this->setUserAgent("aaa");
	}

	/**
	 *
	 * @param string $username
	 * @param string $password
	 * @return \J2T_HTTP
	 */
    public function setBasicAuthentication($username, $password = ''){
        $this->setOption(CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        $this->setOption(CURLOPT_USERPWD, $username . ':' . $password);
		return $this;
    }

	/**
	 *
	 * @param string $username
	 * @param string $password
	 * @return \J2T_HTTP
	 */
    public function setDigestAuthentication($username, $password = ''){
        $this->setOption(CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        $this->setOption(CURLOPT_USERPWD, $username . ':' . $password);
		return $this;
    }

	/**
	 *
	 * @param string $key
	 * @param string $value
	 * @return type
	 */
    public function setCookie($key, $value){
        $this->_cookies[$key] = $value;
        return $this->setOption(CURLOPT_COOKIE, str_replace(' ', '%20', urldecode(http_build_query($this->_cookies, '', '; '))));
    }

	/**
	 *
	 * @param string $key
	 * @return string
	 */
    public function getCookie($key){
        return isset($this->raw_response_cookies[$key]) ? $this->raw_response_cookies[$key] : null;
    }

	/**
	 *
	 * @param int $port_num
	 * @return type
	 */
	public function setPort($port_num){
		return $this->setOption(CURLOPT_PORT, intval($port_num));
	}

	/**
	 *
	 * @param int $sec
	 * @return type
	 */
	public function setConnectTimeout($sec){
		return $this->setOption(CURLOPT_CONNECTTIMEOUT, $sec);
	}

	/**
	 *
	 * @param string $cookiefile
	 * @return type
	 */
	public function setCookieFile($cookiefile){
		return $this->setOption(CURLOPT_COOKIEFILE, $cookiefile);
	}

	/**
	 *
	 * @param string $cookiejar
	 * @return type
	 */
	public function setCookieJar($cookiejar){
		return $this->setOption(CURLOPT_COOKIEJAR,$cookiejar);
	}

	/**
	 *
	 * @param string $option
	 * @return string
	 */
	public function getInfo($option){
		return $this->_getInfo($option);
	}

	/**
	 *
	 * @param string $option
	 * @param string $value
	 * @return type
	 * @throws J2T_Exception
	 */
	public function setOption($option, $value){
        $required_options = array(
            CURLOPT_RETURNTRANSFER => 'CURLOPT_RETURNTRANSFER',
        );
        if (in_array($option, array_keys($required_options), true) && !($value === true)) {
			throw new J2T_Exception($required_options[$option] . ' is a required option');
        }
		$this->_options[$option] = $value;
		return $this->_setOpt($option,$value);
	}

	/**
	 *
	 * @param string $opt
	 * @return string
	 */
	protected function _getInfo($opt){
		return curl_getinfo($this->ch, $opt);
	}

	/**
	 *
	 * @param string $opt
	 * @param string $val
	 * @return type
	 */
	protected function _setOpt($opt, $val){
		return curl_setopt($this->ch,$opt,$val);
	}

	/**
	 *
	 * @param string $k
	 * @param string $val
	 * @return type
	 */
	public function setHeader($k,$val){
		$this->_headers[$k] = $val;
		$headers = array();
		foreach($this->_headers as $key => $value){
			$headers[] = $key . ': ' . $value;
		}
		return $this->setOption(CURLOPT_HTTPHEADER, $headers);
	}

	/**
	 *
	 * @param string $ref
	 * @return type
	 */
	public function setReferrer($ref){
		return $this->setOption(CURLOPT_REFERER, $ref);
	}

	/**
	 *
	 * @param int $sec
	 * @return type
	 */
	public function setTimeout($sec){
		return $this->setOption(CURLOPT_TIMEOUT, $sec);
	}

	/**
	 *
	 * @param string $url
	 * @param array $data
	 * @return type
	 */
	public function setURL($url,$data = array()){
		$this->base_url = $url;
		$this->url = $this->_buildURL($url, $data);
		return $this->setOption(CURLOPT_URL, $this->url);
	}

	/**
	 *
	 * @param string $useragent
	 * @return type
	 */
	public function setUserAgent($useragent){
		return $this->setOption(CURLOPT_USERAGENT,$useragent);
	}

	/**
	 *
	 * @param array $data
	 * @param string $key
	 * @return string
	 */
    public static function http_build_multi_query($data, $key = null){
        $query = array();
        if(empty($data)){
            return $key . '=';
        }
        $is_array_assoc = J2T_Array::is_array_assoc($data);
        foreach($data as $k => $value) {
            if(is_string($value) || is_numeric($value)){
                $brackets = $is_array_assoc ? '[' . $k . ']' : '[]';
                $query[] = urlencode($key === null ? $k : $key . $brackets) . '=' . rawurlencode($value);
            }elseif (is_array($value)){
                $nested = $key === null ? $k : $key . '[' . $k . ']';
                $query[] = self::http_build_multi_query($value, $nested);
            }
        }
        return implode('&', $query);
    }

	/**
	 *
	 * @param string $url
	 * @param array $data
	 * @return string
	 */
    private function _buildURL($url, $data = array()){
        return $url . (empty($data) ? '' : '?' . http_build_query($data));
    }

	/**
	 *
	 * @param array $raw_headers
	 * @return array
	 */
    private function _parseHeaders($raw_headers){
        $raw_headers = preg_split('/\r\n/', $raw_headers, null, PREG_SPLIT_NO_EMPTY);
        $http_headers = new CaseInsensitiveArray();
        $raw_headers_count = count($raw_headers);
        for($i = 1; $i < $raw_headers_count; $i++){
            list($key, $value) = explode(':', $raw_headers[$i], 2);
            $key = trim($key);
            $value = trim($value);
            // Use isset() as array_key_exists() and ArrayAccess are not compatible.
            if(isset($http_headers[$key])) {
                $http_headers[$key] .= ',' . $value;
            }else{
                $http_headers[$key] = $value;
            }
        }
        return array(isset($raw_headers['0']) ? $raw_headers['0'] : '', $http_headers);
    }

	/**
	 *
	 * @param array $raw_headers
	 * @return type
	 */
    private function _parseRequestHeaders($raw_headers){
        $request_headers = new CaseInsensitiveArray();
        list($first_line, $headers) = $this->_parseHeaders($raw_headers);
        $request_headers['Request-Line'] = $first_line;
        foreach($headers as $key => $value){
            $request_headers[$key] = $value;
        }
        return $request_headers;
    }

	/**
	 *
	 * @param type $raw_response_headers
	 * @return type
	 */
    private function _parseResponseHeaders($raw_response_headers){
        $response_header_array = explode("\r\n\r\n", $raw_response_headers);
        $response_header  = '';
        for($i = count($response_header_array) - 1; $i >= 0; $i--){
            if(stripos($response_header_array[$i], 'HTTP/') === 0){
                $response_header = $response_header_array[$i];
                break;
            }
        }
        $response_headers = new CaseInsensitiveArray();
        list($first_line, $headers) = $this->_parseHeaders($response_header);
        $response_headers['Status-Line'] = $first_line;
        foreach ($headers as $key => $value) {
            $response_headers[$key] = $value;
        }
        return $response_headers;
    }

	/**
	 *
	 * @param type $response_headers
	 * @param type $raw_response
	 * @return array
	 */
    private function _parseResponse($response_headers, $raw_response){
        $response = $raw_response;
        if(isset($response_headers['Content-Type'])) {
            if (preg_match($this->_pattern['json'], $response_headers['Content-Type'])) {
                $json_decoder = $this->_is_json;
                if (is_callable($json_decoder)) {
                    $response = $json_decoder($response);
                }
            }elseif(preg_match($this->_pattern['xml'], $response_headers['Content-Type'])){
                $xml_obj = @simplexml_load_string($response);
                if (!($xml_obj === false)) {
                    $response = $xml_obj;
                }
            }
        }
        return array($response, $raw_response);
    }

	/**
	 *
	 */
	protected static function _callfunc(){
        $args = func_get_args();
        $function = array_shift($args);
        if(is_callable($function)){
            array_unshift($args, self::instance());
            call_user_func_array($function, $args);
        }
	}

	/**
	 *
	 * @param string $key
	 */
    public function unsetHeader($key){
        $this->setHeader($key, '');
        unset($this->_headers[$key]);
    }

	/**
	 *
	 * @param type $on
	 * @param type $output
	 * @return \J2T_HTTP
	 */
    public function verbose($on = true, $output=STDERR){
        // Turn off CURLINFO_HEADER_OUT for verbose to work. This has the side
        // effect of causing Curl::requestHeaders to be empty.
        if ($on){
            $this->setOption(CURLINFO_HEADER_OUT, false);
        }
        $this->setOption(CURLOPT_VERBOSE, $on);
        $this->setOption(CURLOPT_STDERR, $output);
		return $this;
    }

	public function __destruct(){
		if(is_resource($this->ch)){
			curl_close($this->ch);
		}
		unset($this->options);
		$this->_is_json = null;
	}
}
/**
 * Library Array & CaseInsensitiveArray
 *
 * @package Array
 * @author Killer <killer.vn54119@hotmail.com>
 * @version 1.0.0
 *
 */
class J2T_Array {
	/**
	 *
	 * @return boolean
	 */
	public static function is_array_multidim(){
        if (!is_array($array)){
            return false;
        }
        return (bool)count(array_filter($array, 'is_array'));
	}

	/**
	 *
	 * @param array $array
	 * @return boolean
	 */
    public static function is_array_assoc($array){
        return (bool)count(array_filter(array_keys($array), 'is_string'));
    }
}
class CaseInsensitiveArray implements ArrayAccess, Countable, Iterator {

    private $container = array();
    public function offsetSet($offset, $value) {
        if($offset === null) {
            $this->container[] = $value;
        }else{
            $index = array_search(strtolower($offset), array_keys(array_change_key_case($this->container, CASE_LOWER)));
            if (!($index === false)) {
                $keys = array_keys($this->container);
                unset($this->container[$keys[$index]]);
            }
            $this->container[$offset] = $value;
        }
    }

    public function offsetExists($offset){
        return array_key_exists(strtolower($offset), array_change_key_case($this->container, CASE_LOWER));
    }

    public function offsetUnset($offset){
        unset($this->container[$offset]);
    }

    public function offsetGet($offset){
        $index = array_search(strtolower($offset), array_keys(array_change_key_case($this->container, CASE_LOWER)));
        if($index === false){
            return null;
        }
        $values = array_values($this->container);
        return $values[$index];
    }

    public function count(){
        return count($this->container);
    }

    public function current(){
        return current($this->container);
    }

    public function next(){
        return next($this->container);
    }

    public function key(){
        return key($this->container);
    }

    public function valid(){
        return !($this->current() === false);
    }

    public function rewind(){
        reset($this->container);
    }
}
/**
 * Plugin Picasa & Plus
 *
 * @package Plugin
 * @author Killer <killer.vn54119@hotmail.com>
 * @version 1.0.0
 *
 */
abstract class J2T_Plugin_Abstract {
	protected $plugin_id;
	public $HTTP;
	public $base_url;
	public $data;

	public function __construct(){
		$this->plugin_id = null;
		$this->HTTP = J2T_HTTP::instance();
		$this->base_url = null;
		$this->data = null;
	}

    protected function _getFormatQuality($resolution){
        switch(true){
            case ($resolution >= 200 && $resolution < 300):
                return '240p';
                break;
            case ($resolution >= 600 && $resolution < 700):
                return '360p';
                break;
            case ($resolution >= 800 && $resolution < 900):
                return '480p';
                break;
            case ($resolution >= 1200 && $resolution < 1300):
                return '720p';
                break;
            case ($resolution >= 1900 && $resolution < 2000):
                return '1080p';
                break;
            default:
                return false;
        }
    }
	abstract protected function run();
	abstract protected function getSource();
	abstract protected function validLink($base_url);
}
class J2T_Plugin_Google_Picasa extends J2T_Plugin_Abstract {

	/**
	 *
	 * @var string
	 */
	protected $plugin_id = 'picasa.google.com';
	/**
	 *
	 * @var int
	 */
	private $_photoid = null;
	/**
	 *
	 * @var array
	 */
	private $_pattern = array(
		'valid_link' => array(
			'/picasaweb.google.com\/lh\/photo\/[a-zA-Z0-9_-]+/i',
			'/picasaweb.google.com\/([0-9]{21,23})\/[\w]*#([0-9]{19,20})/i',
			//'/picasaweb.google.com\/([0-9]{21,23})\/[\w]*([a-zA-Z0-9_=?]+)#([0-9]{19,20})/i',
			'/picasaweb.google.com\/([0-9]{21,23})\/[\w]*([a-zA-Z0-9_=?]+)/i'
		),
		'json' => array(
			'/{\'preload\':\s*(.+)}},/ism',
			'/feedPreload:\s*(.+)}},/ism'
		)
	);
	/**
	 *
	 * @var array
	 */
	public $data;

	function __construct(){
		parent::__construct();
	}

	public function run(){
		$this->data = $this->getMediaContent();
		return;
	}

	/**
	 *
	 * @return array
	 */
	private function getMediaContent(){
		$media_title = $this->getPhoto()->title;
		$media_content = $this->getPhoto()->media->content;
		$data = array();
		foreach($media_content as $key => $value){
			if($value->type == 'video/mpeg4'){
				$quality = $this->_getFormatQuality($value->width);
				$quality = ($quality != false) ? $quality : $value->width .'p';
				$default = ($quality == '360p') ? true : false;
				$url = (strpos($value->url, "googleusercontent") == true) ? $value->url . '?filename='.$media_title : $value->url;
				$data[] = array(
                    //'type'      => $value->type,
                    'type'      => 'mp4',
                    'label'     => $quality,
                    'height'    => $value->height,
                    'width'     => $value->width,
                    'file'      => $url,
                    'default'   => $default
				);
			}
			unset($media_content[$key]);
		}
		return $data;
	}

	/**
	 *
	 * @return array
	 */
	private function getPhoto(){
		$source = $this->getSource();
		foreach($this->_pattern['json'] as $pattern){
			$cFeed = preg_match($pattern,$source,$matches);
			if($cFeed > 0){
				$feedJson = json_decode($matches[1]."}");
				$contentFeed = $feedJson->feed;
				$_current_photo = array();
				if(isset($contentFeed->entry)){
					foreach($contentFeed->entry as $key => $photo){
						if($this->_photoid == $photo->{'gphoto$id'}){
							$_current_photo = $contentFeed->entry[$key];
							break;
						}else{
							$_current_photo = $contentFeed->entry[$key];
						}
						unset($contentFeed->entry[$key]);
					}
				}else{
					$_current_photo = $contentFeed;
					break;
				}
				unset($contentFeed,$feedJson);
			}
			unset($matches);
		}
		unset($source);
		return $_current_photo;
	}

	/**
	 *
	 * @return string
	 */
	public function getSource(){
		$this->HTTP->setOption(CURLOPT_ENCODING , 'gzip');
		$this->HTTP->setOption(CURLOPT_SSL_VERIFYPEER, false);
		return $this->HTTP->get($this->base_url);
	}

	/**
	 *
	 * @param string $base_url
	 * @return boolean
	 */
	public function validLink($base_url){
		foreach($this->_pattern['valid_link'] as $pattern){
			if(preg_match($pattern, $base_url) != false){
				$this->base_url = $base_url;
				$photoid = preg_match('/[\^#](\d+)/',$base_url,$matches);
				if($photoid > 0){
					$this->_photoid = $matches[1];
				}
				return true;
			}
			unset($pattern);
		}
		return false;
	}
}
class J2T_Plugin_Google_Plus extends J2T_Plugin_Abstract {

	/**
	 *
	 * @var string
	 */
	protected $plugin_id = 'plus.google.com';

	/**
	 *
	 * @var int
	 */
	private $_photoid = null;

	/**
	 *
	 * @var boolean
	 */
	private $_is_auth = false;

	/**
	 *
	 * @var string
	 */
	private $_replace_url = 'https://plus.google.com/_/%s';

	/**
	 *
	 * @var array
	 */
	private $_pattern = array(
		'valid_link' => array(
			'/plus.google.com\/photos\/([0-9]{21,23})\/albums\/([0-9]{19,20})/i',
		),
		'json' => array(
			'/[\"]%s[\"],\[\]\s(.+)\["\/_\/photos\/timedtext\/\d+\/%s\?",\[\]/isU',
			'/[0-9]{2},[0-9]{3,4},[0-9]{3,4},\"(.*?)\"/i',
			'/([0-9]{2}),([0-9]{3,4}),([0-9]{3,4}),[\"](.*?)[\"]/i',
			'/[a-zA-Z0-9 _-]+.(mp4|mkv)/is'
		)
	);

	/**
	 *
	 * @var array
	 */
	public $data;

	function __construct(){
		parent::__construct();
	}

	public function run(){
		$this->data = $this->getMediaContent();
		return;
	}

	/**
	 *
	 * @return array
	 */
	public function getMediaContent(){
		$media = $this->getPhoto();
		$media_title = $media['title'];
		$media_content = $media['content'];
		$data = array();
		foreach($media_content as $key => $value){
			if($value['type'] == 'video/mpeg4'){
				$quality = $this->_getFormatQuality($value['width']);
				$quality = ($quality != false) ? $quality : $value['width'] .'p';
				$default = ($quality == '360p') ? true : false;
				$url = (strpos($value['url'], "googleusercontent") == true) ? $value['url'] . '?filename='.$media_title : $value['url'];
				$data[] = array(
                    //'type'      => $value['type'],
                    'type'      => 'mp4',
                    'label'     => $quality,
                    'height'    => $value['height'],
                    'width'     => $value['width'],
                    'file'      => $url,
                    'default'   => $default
				);
			}
			unset($media_content[$key]);
		}
		return $data;
	}

	/**
	 *
	 * @return array
	 */
	public function getPhoto(){
		$source = $this->getSource();
		if(null === $this->_photoid){
			$pattern = sprintf($this->_pattern['json'][0],'\d+','\d+');
		}else{
			$pattern = sprintf($this->_pattern['json'][0],$this->_photoid,$this->_photoid);
		}
		$cP = preg_match($pattern, $source, $matches);
		if($cP > 0){
			$_cm = preg_match_all($this->_pattern['json'][1],$matches[0],$matchs);
		}
		$mediaArr = array_unique($matchs[0]);
		$data = array();
		$cT = preg_match($this->_pattern['json'][3],$matches[0],$matchT);
		if($cT > 0){
			$data['title'] = trim($matchT[0]);
		}else{
			$data['title'] = range('A', 'Z') . '.mp4';
		}
		unset($matchs,$matches,$matchT, $source);
		foreach($mediaArr as $key => $value){
			$value = str_replace('\u003d','=',$value);
			$value = str_replace('\u0026','&',$value);
			preg_match($this->_pattern['json'][2],$value,$matches);
			$data['content'][] = array(
                'type' => $this->_getTypeMedia($matches[1]),
                'height' => $matches[3],
                'width' => $matches[2],
                'url' => $matches[4],
			);
			unset($mediaArr[$key],$matches);
		}
		return $data;
	}

	/**
	 *
	 * @param int $itag
	 * @return string
	 */
	protected function _getTypeMedia($itag){
		switch (true) {
			case ($itag == 36):
				return 'video/3gpp';
				break;
			case ($itag == 18 || $itag == 22 || $itag == 37):
				return 'video/mpeg4';
				break;
			case ($itag == 34 || $itag == 35):
				return 'application/x-shockwave-flash';
				break;
			default:
				return 'video/mpeg4';
				break;
		}
	}

	/**
	 *
	 * @return string
	 */
	public function getSource(){
		$this->HTTP->setOption(CURLOPT_ENCODING , 'gzip');
		$this->HTTP->setOption(CURLOPT_SSL_VERIFYPEER, false);
		return $this->HTTP->get($this->base_url);
	}

	/**
	 *
	 * @param string $base_url
	 * @return boolean
	 */
	public function validLink($base_url){
		foreach($this->_pattern['valid_link'] as $pattern){
			if(preg_match($pattern, $base_url) != false){
				$photoid = preg_match('/\/albums\/([0-9]{19,20})\/([0-9]{19,20})/',$base_url,$matches);
				if($photoid > 0){
					$this->_photoid = $matches[2];
				}
				if(strpos($base_url, "authkey") !== false){
					$this->_is_auth = true;
				}
				$param = strstr($base_url,'photos/');
				if(strpos($param,"?") !== false){
					$this->base_url = sprintf($this->_replace_url, $param)."&rt=j";
				}else{
					$this->base_url = sprintf($this->_replace_url, $param)."?rt=j";
				}
				return true;
			}
			unset($pattern);
		}
		return false;
	}
}
