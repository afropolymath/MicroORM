<?php namespace Chidi\ORM;

use mysqli;
/**
 * Wrapper Class for MySQLi
 *
 * @package default
 * @author Chidiebere I. Nnadi
 **/
class Connector extends mysqli {
  private $config;
  private $link;

  /**
   * Create a new instance of MySQLi Object using the parent constructor
   *
   * @return void
   **/
  public function __construct() {
    $this->loadConfig();
    parent::__construct($this->config['host'], $this->config['user'], $this->config['pass'], $this->config['db']);
  }

  /**
   * Loads up the database configuration options from the config file
   *
   * @return void
   **/
  public function loadConfig() {
    require_once 'config/config.php';
    $this->config = $config;
  }
}
?>