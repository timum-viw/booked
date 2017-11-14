<?php
class TelegramResponse extends RestResponse implements \JsonSerializable
{
	private $method = "";
	private $params;

	public function __construct($method, $params)
	{
		$this->method = $method;
		$this->params = $params;
	}

	public function addParam($key, $value)
	{
		$this->params[$key] = $value;
	}

	public function appendParam($key, $value)
	{
		$this->params[$key] .= $value;
	}

	public function jsonSerialize()
	{
		return array_merge(["method" => $this->method], $this->params);
	}
}