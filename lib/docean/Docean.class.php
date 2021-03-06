<?php

abstract class ObjectMapper {

  abstract protected function getObject();

  function __call($method, $args) {
    return call_user_func_array([$this->getObject(), $method], $args);
  }

}

class Docean extends ObjectMapper implements SmanServers {

  // for autocomplete
  static function get() {
    /* @var $a Docean|DoceanApi */
    static $a;
    if (isset($a)) return $a;
    $a = new self;
    return $a;
  }

  protected $api;

  function __construct() {
    $this->api = new DoceanApiCached;
  }

  protected function getObject() {
    return $this->api;
  }

  function servers() {
    return $this->api->servers();
  }

  function server($name, $strict = true) {
    if (!($r = Arr::getValueByKey($this->api->servers(), 'name', $name))) {
      if ($strict) throw new NotFoundException("server '$name'");
      else return false;
    }
    return $r;
  }

  function createServer($name) {
    $this->api->createServer($name);
    output("Waiting for server is active");
    while (true) {
      if ($this->server($name)['status'] == 'active') {
        break;
      }
      sleep(5);
    }
  }

  function deleteServer($name) {
    $this->api->deleteServer($this->server($name)['id']);
    if (($sshKeyId = $this->sshKeyId($name))) $this->api->deleteSshKey($sshKeyId);
  }

  function sshKeyId($name) {
    return Arr::getSubValue($this->api->sshKeys(), 'name', $name, 'id');
  }

}