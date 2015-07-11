<?php namespace Chidi\ORM;

use Chidi\ORM\Connector;

/**
 * MicroORM Framework
 *
 * @package default
 * @author Chidiebere I. Nnadi
 **/

class MicroORM {
  /**
   * Primary Key Value.
   *
   * @var mixed
   */
  protected $id = false;

  /**
   * Database Connector Object.
   *
   * @var mixed
   */
  protected $conn;

  /**
   * Array containing known string data types.
   *
   * @var array
   */
  protected static $STRING_TYPES = ['text', 'varchar'];

  /**
   * Class Parameter - Table configuration.
   *
   * @var array
   */
  protected $model = [];

  /**
   * Key-Value Pairs representing column-row mapping
   *
   * @var array
   */
  protected $fields;

  /**
   * Key-Value Pairs representing accessible column names versus actual column names
   *
   * @var array
   */
  private $key_mapping = [];

  /**
   * Key-Value Pairs representing changes made to an already existing record
   *
   * @var array
   */
  private $changes = [];

  /**
   * Creates a new Class Instance based on fields names and values and an optional id.
   *
   * @param mixed $fields Key-Value Pairs of Field Names and Values
   * @param mixed $id Primary Key Value
   * @return void
   */
  public function __construct($fields, $conn, $id = false) {
    $this->conn = $conn;

    $this->_class = get_called_class();
    if(!isset($this->_table)) {
      $this->_table = strtolower($this->_class);
    }

    if(!($fields instanceof Object)) {
      $fields = (Object) $fields;
    }

    if($id && $fields->id == $id) {
      $this->id = $id;
    }

    $flag = true;

    if($this->__checkConfig()) {
      $_fields = get_object_vars($fields);
      foreach ($this->model as $field => $prop) {
        if(isset($prop['null']) && $prop['null'] == false) {
          if(!array_key_exists($field, $_fields)) {
            $flag = false;
          } else {
            if(!$this->__fieldValidate($field, $fields->$field)) {
              $flag = false;
            }
          }
        } else {
          if(!isset($fields->$field)) {
            $fields->$field = 'NULL';
          } else {
            if(!$this->__fieldValidate($field, $fields->$field)) {
              $flag = false;
            }
          }
        }
      }
      if($flag) {
        $this->fields = $fields;
        foreach (array_keys($_fields) as $key => $value) {
          $k = strtolower(str_replace("_", "", $value));
          $this->key_mapping[$k] = $value;
        }
      } else {
        $this->handleError("Error in field configuration.");
        return false;
      }
    } else {
      $this->handleError("Model is not properly formed.");
      return false;
    }
  }

  /**
   * Saves the current instance of the class with any modifications that have been made
   *
   * @return void
   */

  public function save() {
    $complete = false;
    $table = $this->_table;
    $_fields = get_object_vars($this->fields);

    if(!$this->id) {
      $columns = implode(', ', array_keys($_fields));
      $values = [];
      foreach ($_fields as $k => $v) {
        if(in_array( $this->model[$k]['type'], self::$STRING_TYPES )) {
          $values[] = "'$v'";
        } else {
          $values[] = $v;
        }
      }
      $column_values = implode(', ', $values);

      $q = "INSERT INTO $table ($columns) VALUES ($column_values)";
      $res = $this->conn->query($q);
      if($res) {
        $this->id = $this->conn->insert_id;
        $complete = true;
      }
    } else {
      $updates = [];
      foreach ($this->changes as $k => $v) {
        if(in_array( $this->model[$k]['type'], self::$STRING_TYPES )) {
          $v = "'$v'";
        }
        $updates[] = "$k = $v";
      }
      $_updates = implode(", ", $updates);
      $q = "UPDATE $table SET $_updates WHERE id = " . $this->id;
      $res = $this->conn->query($q);
      if($res) {
        $complete = true;
      }
    }

    if($complete) {
      $q = "SELECT * FROM $table WHERE id = " . $this->id;
      $res = $this->conn->query($q);
      if($res && $res->num_rows > 0) {
        $this->fields = $res->fetch_object();
        return true;
      }
    }
    return false;
  }

  public function delete() {
    $table = $this->_table;
    $q = "DELETE FROM $table WHERE id = " . $this->id;
    $res = $this->conn->query($q);
    if($res) {
      $this->__destruct();
      return true;
    }
    return false;
  }

  /**
   * Modifies the current object based on the properties passed
   *
   * @param array $props Key Value Pairs of properties to be modified
   * @return void
   */
  public function extend($props) {
    foreach ($props as $k => $v) {
      $this->update($k, $v);
    }
  }

  /**
   * Updates a single field to a new value
   *
   * @param string $field Column to be modified
   * @param mixed $value Value to set the column to
   * @return void
   */
  public function update($field, $value) {
    if($this->__fieldValidate($field, $value)) {
      if($this->id != false) {
        $this->changes[$field] = $value;
      }
      $this->fields->$field = $value;
    } else {
      $this->handleError("Trying to update using invalid data");
      return false;
    }
  }

  /**
   * Returns an object representing the current state of the record
   *
   * @return Object Row object
   */
  public function asObject() {
    return $this->fields;
  }

  /**
   * Returns an boolean telling whether field value is valid or not
   *
   * @param string $field Column to be checked
   * @param mixed $value Value of the column to be checked
   * @return bool Field value is valid or not
   */
  protected function __fieldValidate($field, $value) {
    $type = $this->model[$field]['type'];
    $valid = true;
    switch ($type) {
      case 'varchar':
        $length = $this->model[$field]['length'];
        if(strlen($value) > $length) {
          $valid = false;
        }
        break;
      case 'text':
        break;
      case 'timestamp':
        break;
      case 'int':
        if(!is_numeric($value)) {
          $valid = false;
        }
        break;
    }
    return $valid;
  }
  /**
   * Checks to ensure model configuration is set and valid
   *
   * @return bool If valid or not
   */
  protected function __checkConfig() {
    $valid = true;

    if(count($this->model) > 0) {
      foreach ($this->model as $field => $props) {
        if(array_key_exists('type', $props)) {
          if(strtolower($props['type']) == 'varchar') {
            $valid = array_key_exists('size', $props) && !is_numeric($props['size']) ? false : $valid;
          }
        } else {
          $valid = false;
        }
        if(!$valid) {
          break;
        }
      }
    } else {
      $valid = false;
    }

    return $valid;
  }

  /**
   * Static function to enable creation of the required table outside this class
   *
   * @return void
   */
  // public function __createTable() {

  // }

  /**
   * Get a single row as Class Object based on  Primary Key value
   *
   * @param mixed $id Primary Key Value
   * @return mixed New Class Instance or false if the record is not found or there is an error
   */
  public static function get($id) {
    $_class = get_called_class();
    $_table = strtolower($_class) . 's';

    $conn = new Connector();
    $res = $conn->query("SELECT * FROM $_table WHERE id = $id");

    if(!$conn->error && $res->num_rows > 0) {
      $result = $res->fetch_object();
      return $result;
    } else {
      self::handleError("Database error or no available records -> " . $conn->error);
      return false;
    }

    $res->close();
  }

  /**
   * Caters for field getters and setters
   *
   * @param string $name The name of the function that was called
   * @param array $argv The parameters that were passed to the function
   */
  public function __call($name, $argv) {
    $type = substr($name, 0, 3);
    $var = strtolower(substr($name, 3));
    if(in_array($var, array_keys($this->key_mapping))) {
      $_k = $this->key_mapping[$var];
      switch ($type) {
        case 'set':
          $this->update($_k, $argv[0]);
          break;
        case 'get':
          return $this->fields->$_k;
          break;
        default:
          $this->handleError("Invalid Method Call");
          break;
      }
    } else {
      $this->handleError("Invalid Method Call");
    }
  }

  protected function handleError($message) {
    die($message);
  }
}
?>
