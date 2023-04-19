<?php
namespace Core;

class Request
{
    private $get;
    private $post;
    private $server;
    private $cookie;
    private $files;
    private $session;

	private const METHOD_POST = 'POST' ;
	private const METHOD_GET = 'GET' ;

    public function __construct($get, $post, $server, $cookie, $files, $session)
	{
		$this->get = $get;
		$this->post = $post;
		$this->server = $server;
		$this->cookie = $cookie;
		$this->files = $files;
		$this->session = $session;
	}

    public function get($key = null)
    {
		return $this->arrayKey($this->get,$key);
    }

	public function post($key = null)
    {
		return $this->arrayKey($this->post,$key);
    }

	public function server($key = null)
    {
		return $this->arrayKey($this->server,$key);
    }

	public function cookie($key = null)
    {
		return $this->arrayKey($this->cookie,$key);
    }

	public function files($key = null)
    {
		return $this->arrayKey($this->files,$key);
    }

	public function session($key = null)
    {
		return $this->arrayKey($this->session,$key);
    }
	
    public function isPost()
	{
		return $this->server['REQUEST_METHOD']==self::METHOD_POST;
	}
	
	public function isGet()
	{
		return $this->server['REQUEST_METHOD']==self::METHOD_GET;
	}

	private function arrayKey($arr,$key = null)
	{
		if (!$key) {
			return $arr;
		}

		if (isset($arr[$key])) {
			return $arr[$key];
		}

		return null;
	}
}