<?php

/**
 * sfMessageSource_MySQL class file.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the BSD License.
 *
 * Copyright(c) 2004 by Qiang Xue. All rights reserved.
 *
 * To contact the author write to {@link mailto:qiang.xue@gmail.com Qiang Xue}
 * The latest version of PRADO can be obtained from:
 * {@link http://prado.sourceforge.net/}
 *
 * @author     Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version    $Id: sfMessageSource_MySQL.class.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 * @package    symfony
 * @subpackage i18n
 */

/**
 * sfMessageSource_MySQL class.
 * 
 * Retrieve the message translation from a MySQL database.
 *
 * See the MessageSource::factory() method to instantiate this class.
 *
 *
 * @author Xiang Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version v1.0, last update on Fri Dec 24 16:58:58 EST 2004
 * @package    symfony
 * @subpackage i18n
 */
class sfMessageSource_tmcMySQL extends sfMessageSource_Database
{
  /**
   * The datasource string, full DSN to the database.
   * @var string 
   */
  protected $source;

  /**
   * The DSN array property, parsed by PEAR's DB DSN parser.
   * @var array 
   */
  protected $dsn;

  /**
   * A resource link to the database
   * @var db 
   */
  protected $db;

  /**
   * Catalogue name to use when running the i18n task extract
   * @var db
   */
  protected static $catalogue_name = 'messages';

  /**
   * Number of messages saved
   * @var db
   */
  protected $num_saved = 0;

  /**
   * Constructor.
   * Creates a new message source using MySQL.
   *
   * @param string $source  MySQL datasource, in PEAR's DB DSN format.
   * @see MessageSource::factory();
   */
  function __construct($source)
  {
    $this->source = (string) $source;
    $this->dsn = $this->parseDSN($this->source);
    $this->db = $this->connect();
  }

  /**
   * Destructor, closes the database connection.
   */
  function __destruct()
  {
    @mysql_close($this->db);
  }

  /**
   * Connects to the MySQL datasource
   *
   * @return resource MySQL connection.
   * @throws sfException, connection and database errors.
   */
  protected function connect()
  {
    $dsninfo = $this->dsn;

    if (isset($dsninfo['protocol']) && $dsninfo['protocol'] == 'unix')
    {
      $dbhost = ':'.$dsninfo['socket'];
    }
    else
    {
      $dbhost = $dsninfo['hostspec'] ? $dsninfo['hostspec'] : 'localhost';
      if (!empty($dsninfo['port']))
      {
        $dbhost .= ':'.$dsninfo['port'];
      }
    }
    $user = $dsninfo['username'];
    $pw = $dsninfo['password'];

    $connect_function = 'mysql_connect';

    if (!function_exists($connect_function))
    {
      throw new RuntimeException('The function mysql_connect() does not exist. Please confirm MySQL is enabled in php.ini');
    }

    if ($dbhost && $user && $pw)
    {
      $conn = @$connect_function($dbhost, $user, $pw);
    }
    elseif ($dbhost && $user)
    {
      $conn = @$connect_function($dbhost, $user);
    }
    elseif ($dbhost)
    {
      $conn = @$connect_function($dbhost);
    }
    else
    {
      $conn = false;
    }

    if (empty($conn))
    {
      throw new sfException(sprintf('Error in connecting to %s.', $dsninfo));
    }

    if ($dsninfo['database'])
    {
      if (!@mysql_select_db($dsninfo['database'], $conn))
      {
        throw new sfException(sprintf('Error in connecting database, dsn: %s.', $dsninfo));
      }
    }
    else
    {
      throw new sfException('Please provide a database for message translation.');
    }

    return $conn;
  }

  /**
   * Gets the database connection.
   *
   * @return db database connection. 
   */
  public function connection()
  {
    return $this->db;
  }

  /**
   * Gets an array of messages for a particular catalogue and cultural variant.
   *
   * @param string $variant the catalogue name + variant
   * @return array translation messages.
   */
  public function &loadData($variant)
  {
    @mysql_query("SET NAMES 'UTF8'", $this->db);

    $variant = mysql_real_escape_string($variant, $this->db);

    $statement =
      "SELECT t.id, t.source, t.target, t.comments
        FROM trans_unit t, catalogue c
        WHERE c.cat_id =  t.cat_id
          AND c.name = '{$variant}'
          AND t.translated = 1
        ORDER BY id ASC";

    $rs = mysql_query($statement, $this->db);

    $result = array();

    while ($row = mysql_fetch_array($rs, MYSQL_NUM))
    {
      $source = $row[1];
      $result[$source][] = $row[2]; //target
      $result[$source][] = $row[0]; //id
      $result[$source][] = $row[3]; //comments
    }

    return $result;
  }

  /**
   * Gets the last modified unix-time for this particular catalogue+variant.
   * We need to query the database to get the date_modified.
   *
   * @param string $source catalogue+variant
   * @return int last modified in unix-time format.
   */
  protected function getLastModified($source)
  {
    $source = mysql_real_escape_string($source, $this->db);

    $rs = mysql_query("SELECT date_modified FROM catalogue WHERE name = '{$source}'", $this->db);

    $result = $rs ? intval(mysql_result($rs, 0)) : 0;

    return $result;
  }

  /**
   * Checks if a particular catalogue+variant exists in the database.
   *
   * @param string $variant catalogue+variant
   * @return boolean true if the catalogue+variant is in the database, false otherwise.
   */ 
  public function isValidSource($variant)
  {
    $variant = @mysql_real_escape_string ($variant, $this->db);

    $rs = @mysql_query("SELECT COUNT(*) FROM catalogue WHERE name = '{$variant}'", $this->db);

    $row = @mysql_fetch_array($rs, MYSQL_NUM);

    $result = $row && $row[0] == '1';

    return $result;
  }

  /**
   * Retrieves catalogue details, array($cat_id, $variant, $count).
   *
   * @param string $catalogue catalogue
   * @return array catalogue details, array($cat_id, $variant, $count). 
   */
  protected function getCatalogueDetails($catalogue = null)
  {
    $this->db = $this->connect();
    
    if (is_null($catalogue))
    {
      $catalogue = $this->getCatalogueName();
    }

    $variant = $catalogue.'.'.$this->culture;

    $name = $this->getSource($variant);

    $rs = @mysql_query("SELECT cat_id FROM catalogue WHERE name = '{$name}'", $this->db);

    if (@mysql_num_rows($rs) == 0)
    {
      // catalogue doesn't exist, create it
      $time = date('Y-m-d H:i:s');
      $rs = mysql_query("INSERT INTO catalogue (name, date_created, date_modified) VALUES ('{$name}', '{$time}', '{$time}')", $this->db);
      if ($rs)
      {
        $rs = @mysql_query("SELECT cat_id FROM catalogue WHERE name = '{$name}'", $this->db);
      }
    }
    $cat_id = intval(mysql_result($rs, 0));

    // first get the catalogue ID
    $rs = @mysql_query("SELECT COUNT(*) FROM trans_unit WHERE translated = 1 AND cat_id = {$cat_id}", $this->db);

    $count = intval(mysql_result($rs, 0));

    return array($cat_id, $variant, $count);
  }

  /**
   * Updates the catalogue last modified time.
   *
   * @return boolean true if updated, false otherwise. 
   */
  protected function updateCatalogueTime($cat_id, $variant)
  {
    $time = date('Y-m-d H:i:s');

    $result = mysql_query("UPDATE catalogue SET date_modified = '{$time}' WHERE cat_id = {$cat_id}", $this->db);

    if ($this->cache)
    {
      $this->cache->remove($variant.':'.$this->culture);
    }

    return $result;
  }

  /**
   * Saves the list of untranslated blocks to the translation source. 
   * If the translation was not found, you should add those
   * strings to the translation source via the <b>append()</b> method.
   *
   * @param string $catalogue the catalogue to add to
   * @return boolean true if saved successfuly, false otherwise.
   */
  function save($catalogue = null)
  {
    $messages = $this->untranslated;

    if (count($messages) <= 0)
    {
      return false;
    }

    if (is_null($catalogue))
    {
      $catalogue = $this->getCatalogueName();
    }

    $details = $this->getCatalogueDetails($catalogue);

    if ($details)
    {
      list($cat_id, $variant, $count) = $details;
    }
    else
    {
      return false;
    }

    if ($cat_id <= 0)
    {
      return false;
    }
    $inserted = 0;

    $time = date('Y-m-d H:i:s');

    foreach ($messages as $message)
    {
      $count++;
      $message = mysql_real_escape_string($message, $this->db);
      // avoid inserted repeated messages
      $rs = @mysql_query("SELECT cat_id FROM trans_unit WHERE cat_id={$cat_id} and source='{$message}'", $this->db);
      if (@mysql_num_rows($rs) > 0)
      {
        continue;
      }
      $inserted++;
      $statement = "INSERT INTO trans_unit
        (cat_id,source,date_created,date_modified) VALUES
        ({$cat_id},'{$message}','{$time}','{$time}')";
      mysql_query($statement, $this->db);
    }
    if ($inserted > 0)
    {
      $this->updateCatalogueTime($cat_id, $variant);
    }
    
    $this->num_saved = $inserted;

    return $inserted > 0;
  }

  /**
   * Deletes a particular message from the specified catalogue.
   *
   * @param string $message   the source message to delete.
   * @param string $catalogue the catalogue to delete from.
   * @return boolean true if deleted, false otherwise. 
   */
  function delete($message, $catalogue = null)
  {
    $this->db = $this->connect();

    if (is_null($catalogue))
    {
      $catalogue = $this->getCatalogueName();
    }

    $details = $this->getCatalogueDetails($catalogue);
    if ($details)
    {
      list($cat_id, $variant, $count) = $details;
    }
    else
    {
      return false;
    }

    $text = mysql_real_escape_string($message, $this->db);

    $statement = "DELETE FROM trans_unit WHERE cat_id = {$cat_id} AND source = '{$message}'";
    $deleted = false;

    mysql_query($statement, $this->db);

    if (mysql_affected_rows($this->db) == 1)
    {
      $deleted = $this->updateCatalogueTime($cat_id, $variant);
    }

    return $deleted;
  }

  /**
   * Updates the translation.
   *
   * @param string $text      the source string.
   * @param string $target    the new translation string.
   * @param string $comments  comments
   * @param string $catalogue the catalogue of the translation.
   * @return boolean true if translation was updated, false otherwise. 
   */
  function update($text, $target, $comments, $catalogue = null)
  {
    if (is_null($catalogue))
    {
      $catalogue = $this->getCatalogueName();
    }
    $details = $this->getCatalogueDetails($catalogue);
    if ($details)
    {
      list($cat_id, $variant, $count) = $details;
    }
    else
    {
      return false;
    }

    $comments = mysql_real_escape_string($comments, $this->db);
    $target = mysql_real_escape_string($target, $this->db);
    $text = mysql_real_escape_string($text, $this->db);

    $time = date('Y-m-d H:i:s');

    $statement = "UPDATE trans_unit SET target = '{$target}', comments = '{$comments}', date_modified = '{$time}' WHERE cat_id = {$cat_id} AND source = '{$text}'";

    $updated = false;

    mysql_query($statement, $this->db);
    if (mysql_affected_rows($this->db) == 1)
    {
      $updated = $this->updateCatalogueTime($cat_id, $variant);
    }

    return $updated;
  }

  /**
   * Returns a list of catalogue as key and all it variants as value.
   *
   * @return array list of catalogues 
   */
  function catalogues()
  {
    $statement = 'SELECT name FROM catalogue ORDER BY name';
    $rs = mysql_query($statement, $this->db);
    $result = array();
    while($row = mysql_fetch_array($rs, MYSQL_NUM))
    {
      $details = explode('.', $row[0]);
      if (!isset($details[1]))
      {
        $details[1] = null;
      }

      $result[] = $details;
    }

    return $result;
  }

  /**
   * Sets the catagolue name
   */
  public function setCatalogueName($name = 'messages')
  {
    self::$catalogue_name = $name;
  }
  /**
   * Gets the catagolue name
   */
  public function getCatalogueName()
  {
    return self::$catalogue_name;
  }
  /**
   * Gets the number of messages saved
   */
  public function getNumSaved()
  {
    return $this->num_saved;
  }

  /**
   * override of the load method to be aware of the catalogue
   */
  function load($catalogue = null)
  {
    if (is_null($catalogue))
    {
      $catalogue = $this->getCatalogueName();
    }
    return parent::load($catalogue);
  }
}
