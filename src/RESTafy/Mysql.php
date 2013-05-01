<?php

namespace RESTafy\Mysql;

require_once 'RESTafy.php';

use RESTafy;

class PDO implements RESTafy\DBI {
  private $_dsn;
  protected $handle_;

  function __construct($_dsn_) {
    $this->_dsn = $_dsn_;
  }

  function open() {
    if (NULL !== $this->handle_) {
      throw new RESTafy\InvalidOperationException('XXX');
    }

    try {
      $handle = new \PDO($this->_dsn,
        $this->userName_, $this->password_, array(
          \PDO::ATTR_ERRMODE
          => \PDO::ERRMODE_EXCEPTION,
          \PDO::ATTR_PERSISTENT
          => true,
          \PDO::MYSQL_ATTR_INIT_COMMAND
          => "SET NAMES utf8"
        ));
    } catch (\PDOException $e) {
      throw new RESTafy\DBIException('Unable to connect to MySQL.', $e);
    }

    // Tell PDO to throw an exception on error.
    //$handle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    //$handle->query('SET NAMES \'utf8\'');

    $this->handle_ =& $handle;
  }

  function close() {
    if (NULL === $this->handle_) {
      throw new RESTafy\InvalidOperationException('XXX');
    }

    $this->handle_ = NULL;
  }

  function free($_result_) {
    $_result_->closeCursor();
  }

  function quote($_value_) {
    $this->open();
    return $this->handle_->quote($_value_);
  }

  function lastInsertId() {
    return $this->handle_->lastInsertId();
  }

  function prepare($_query_) {
    $this->open();

    try {
      $stmt = $this->handle_->prepare($_query_);
    } catch (\PDOException $e) {
      throw new RESTafy\DBIException('Unable to prepare stmt MySQL.', $e);
    }

    return $stmt;
  }

  function query($_query_) {
    $this->open();

    try {
      $result = $this->handle_->query($_query_);
    } catch (\PDOException $e) {
      throw new RESTafy\DBIException('Unable to query MySQL.', $e);
    }

    return $result;
  }
}

class Mysqli implements RESTafy\DBI {
  protected
    $database_,
    $host_,
    $password_,
    $userName_,
    $handle_;

  function __construct($_host_, $_database_, $_userName_, $_password_) {
    $this->database_ = $_database_;
    $this->host_     = $_host_;
    $this->userName_ = $_userName_;
    $this->password_ = $_password_;
  }

  function open() {
    if (NULL !== $this->handle_) {
      throw new RESTafy\InvalidOperationException('XXX');
    }

    $handle = new \mysqli($this->host_, $this->userName_,
      $this->password_, $this->database_);

    if (\mysqli_connect_errno()) {
      throw new RESTafy\DBIException(
        'Unable to connect to MySQL: ' . mysqli_connect_error());
    }

    $handle->query("SET NAMES 'utf8'");

    $this->handle_ =& $handle;
  }

  function close() {
    if (NULL === $this->handle_) {
      throw new RESTafy\InvalidOperationException('XXX');
    }

    if (\FALSE === $this->handle_->close()) {
      \error_log('Unable to close connexion to MySQL: ' . $this->handle_->error);
    }

    $this->handle_ = NULL;
  }

  function free($_result_) {
    $_result_->free();

    if ($this->handle_->more_results()) {
      $this->handle_->next_result();
    }
  }

  function nextResult() {
    $this->handle_->next_result();
  }

  function quote($_value_) {
    $this->open();
    return $this->handle_->real_escape_string($_value_);
  }

  function lastInsertId() {
    return $this->handle_->insert_id;
  }

  function multiQuery($_queries_) {
    $this->open();

    $result = $this->handle_->multi_query($_queries_);

    if (\FALSE === $result) {
      throw new RESTafy\DBIException(
        'Unable to query MySQL: ' . $this->handle_->error);
    }

    return $result;
  }

  function query($_query_) {
    $this->open();

    $result = $this->handle_->query($_query_);

    if (\FALSE === $result) {
      throw new RESTafy\DBIException(
        'Unable to query MySQL: ' . $this->handle_->error);
    }

    return $result;
  }

  function storeResult() {
    return $this->handle_->store_result();
  }
}

// EOF
