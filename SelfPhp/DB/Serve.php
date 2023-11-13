<?php

namespace SelfPhp\DB;

use SelfPhp\SP;
use SelfPhp\DB\SPQueryBuilder as DB; 

/**
 * Class Serve
 * 
 * Handles database operations such as saving, updating, and fetching rows.
 */
class Serve extends DB
{
    /**
     * The model object. 
     * @var object
     */
    private $model;

    /**
     * @var string The table name to perform operations on.
     */
    private $table;

    /**
     * @var array The parameters for the SQL query.
     */
    private $final_params; 

    /**
     * The active database connection resource.
     *
     * @var resource|MongoDB\Client|PDO
     */
    private $connection;

    /**
     * Serve constructor.
     * 
     * @param string $table The table name to perform operations on.
     */
    public function __construct($model = null)
    {   
        $this->row = null;
        $this->rows = null; 
        $this->model = $model; 
        $this->connection = DB::connect();
    }

    /**
     * Sets the table name to perform operations on.
     * 
     * @param string $table The table name to perform operations on.
     * @return Serve The Serve object.
     */
    public function table()
    { 
        if (is_null($this->model)) {
            throw new \Exception("No model specified.");
        }

        $table = $this->model::$table;

        if (isset($table) && !empty($table)) {
            $this->table = $table;
        } else {
            throw new \Exception("No table name specified in the " . get_class($this->model) . " model.");
        }

        return $this->table;
    }

    /**
     * Saving of the post object is done here. 
     * 
     * An SQL post request is executed on this function.
     * Extract keys passed on the post object.
     * Extract key values passed on the post object 
     * DB Query(INSERT)
     * 
     * @param array $post_object A post object that is passed as a parameter for 
     *                      execution by the SQL post request.
     * @return bool 
     */
    public function save(array $post_object)
    {
        $this->table = $this->table(); 
        try {
            $table_column_keys = array_keys($post_object);

            $new_table_column_keys = [];
            foreach ($table_column_keys as $key => $value) {
                array_push($new_table_column_keys, "`$value`");
            }
            
            $table_column_keys = $new_table_column_keys;
            $table_column_keys = implode(", ", $table_column_keys);  

            $key_values = array_values($post_object);

            $new_key_values = array();
            foreach ($key_values as $key => $value) {
                array_push($new_key_values, ($value ? str_replace("'", "`", $value) : null));
            }

            $key_values = $new_key_values; 

            $key_values = implode("', '", $key_values);

            $query = "INSERT INTO $this->table($table_column_keys) VALUES('$key_values')";
            $result = mysqli_query($this->connection, $query); 

            if ($result == true or is_object($result)) {
                return true;
            } else { 
                return false;
            }
        } catch (\Throwable $error) {
            SP::debugBacktraceShow($error);
        } 
    }

    /**
     * Updates rows based on specified conditions.
     * 
     * @param array $post_object The values to be updated.
     * @param array $params_array The conditions for the update.
     * @return bool 
     */
    public function update_on_condition($post_object = [], $params_array = []) { 
        $this->table = $this->table(); 

        // Where clause params
        $appendable_query_string = null; 
        foreach ($params_array as $key => $value) {
            if (!empty($value)) {
                $command = $key . ' = ' . "$value";
                $appendable_query_string .= $command;
            } 
        } 
        // End of where clause params

        // Params with  update values
        $final_params = array(); 
        foreach ($post_object as $col_key_name => $col_key_value) { 
            if (!empty($col_key_value)) {
                array_push($final_params, $col_key_name . ' = ' . "'" . ($col_key_value ? str_replace("'", "`", $col_key_value) : null) . "'" );  
            }
        }

        $this->final_params = implode(",", $final_params); 

        if (empty($appendable_query_string)) {
            $query = "UPDATE $this->table SET " . $this->final_params; 
        }
        else { 
            $query = "UPDATE $this->table SET $this->final_params WHERE " . $appendable_query_string; 
        } 
        
        $result = mysqli_query($this->connection, $query);

        if ($result == true or is_object($result)) {
            return true;
        } else { 
            return false;
        }
    }

    /**
     * Fetches all rows from the specified table.
     * 
     * @return array An array of rows fetched.
     * @return false If an error and if debug is set to true, then,
     *                  a debug error will be returned.
     */
    public function fetchAll()
    { 
        $this->table = $this->table(); 

        try {
            $query = "SELECT * FROM $this->table";
            $result = mysqli_query($this->connection, $query);

            $row_array = array();
            
            if ($result) {
                while($rows = mysqli_fetch_assoc($result)){
                    array_push($row_array, $rows);
                } 
            }

            return current($row_array);
        } catch (\Throwable $error) {
            SP::debugBacktraceShow($error); 
        }
    } 
    
    /**
     * Fetches all rows from the specified table in descending
     * Order while ordered by creation time.
     * 
     * @return array An array of rows fetched.
     * @return false If an error and if debug is set to true, then,
     *                  a debug error will be returned.
     */
    public function fetchAllInDescOrder() {
        $this->table = $this->table(); 

        try {
            $query = "SELECT * FROM $this->table ORDER BY $this->table.created_at DESC";
            $result = mysqli_query($this->connection, $query);

            $row_array = array();

            if ($result == true or is_object($result)) { 

                while($rows = mysqli_fetch_assoc($result)){
                    array_push($row_array, $rows);
                }
            }

            return $row_array[0];
        } catch (\Throwable $error) {
            SP::debugBacktraceShow($error); 
        } 
    }

    /**
     * Fetches all rows from the specified table in ascending
     * Order while ordered by creation time.
     * 
     * @return array An array of rows fetched.
     * @return false If an error and if debug is set to true, then,
     *                  a debug error will be returned.
     */
    public function fetchAllInAscOrder() {
        $this->table = $this->table(); 

        try {
            $query = "SELECT * FROM $this->table ORDER BY $this->table.created_at ASC";
            $result = mysqli_query($this->connection, $query);

            $row_array = array();

            if ($result == true or is_object($result)) { 

                while($rows = mysqli_fetch_assoc($result)){
                    array_push($row_array, $rows);
                }
            }

            return $row_array[0];
        } catch (\Throwable $error) {
            SP::debugBacktraceShow($error); 
        } 
    }

    /**
     * Fetches a row from the specified table based on a specified
     * unique row's primary key id.
     * 
     * @param int $id The primary key id.
     * @return array An array of rows fetched.
     * @return false If an error and if debug is set to true, then,
     *                  a debug error will be returned.
     */
    public function FetchById(int $id) {
        $this->table = $this->table(); 
        try {
            $row = array();

            $query = "SELECT * FROM $this->table WHERE id='" . $id . "'";
            $result = mysqli_query($this->connection, $query); 

            if ($result == true or is_object($result)) {
                $row = mysqli_fetch_assoc($result);
            }

            return $row;
        } catch (\Throwable $error) {
            SP::debugBacktraceShow($error); 
        } 
    }

    /**
     * Checks if a user with the specified email exists in the table.
     * 
     * @param array $post_object The post object containing the email.
     * @return bool True if the user exists, false otherwise.
     * @return false If an error and if debug is set to true, then,
     *                  a debug error will be returned.
     */
    public function user_exists_on_condition(array $post_object = [])
    {
        $this->table = $this->table(); 

        $exists = false;

        try {
            $query = "SELECT * FROM $this->table WHERE email='" . $post_object['email'] . "'";
            $result = mysqli_query($this->connection, $query);
            
            $row_count = mysqli_num_rows($result);

            if ($row_count > 0) {
                $exists = true;

                return $exists;
            } else {
                return $exists;
            }
        } catch (\Throwable $error) {
            SP::debugBacktraceShow($error); 
        }  
    }

    /**
     * Fetches rows from the table based on specified conditions.
     * 
     * @param array $post_object The conditions for the query.
     * @return Serve The Serve object.
     */
    public function query_by_condition(array $post_object = [])
    {  
        $this->table = $this->table(); 
        
        try {
            $appendable_query_string = null;

            $post = $post_object;
            foreach ($post as $key => $value) {
                if (!empty($value)) {
                    $command = $key . '=' . '"' . $value . '"';

                    if ($appendable_query_string == null) {
                        $appendable_query_string .= $command;
                    } else {
                        $appendable_query_string .= ' AND ' . $command;
                    } 
                } 
            } 

            if (empty($appendable_query_string)) {
                $query = "SELECT * FROM $this->table"; 
            }
            else {
                $query = "SELECT * FROM $this->table WHERE " . $appendable_query_string; 
            } 

            $result = mysqli_query($this->connection, $query);
            
            $row_array = array();

            if ($result) {
                while($rows = mysqli_fetch_assoc($result)){
                    array_push($row_array, $rows);
                }
            }
            $this->rows = $row_array;

            return $this;
        } catch (\Throwable $error) {
            SP::debugBacktraceShow($error);  
        }
    }

    /**
     * Gets the first row from the fetched rows.
     * 
     * @return Serve The Serve object.
     */
    public function first() {
        if (! is_null($this->rows)) {
            $this->row = current($this->rows);
        } 

        return $this->row;
    } 

    /**
     * Gets all the fetched rows.
     * 
     * @return array The fetched rows.
     */
    public function get() {
        return $this->rows;
    }

    /**
     * Fetches a row from the specified table based on an email.
     * 
     * @param array $post_object The post object containing the email.
     * @return array|null An array of rows fetched or null if not found.
     * @return false If an error and if debug is set to true, then,
     *                  a debug error will be returned.
     */
    public function getUserByEmail(array $post_object = [])
    { 
        $this->table = $this->table(); 

        try {
            $query = "SELECT * FROM $this->table WHERE email='" . $post_object['email'] . "'";
            $result = mysqli_query($this->connection, $query); 

            $row_array = array();

            if ($result) {
                while($rows = mysqli_fetch_assoc($result)){
                    array_push($row_array, $rows);
                }
            }

            return isset($row_array[0]) ? $row_array[0] : null; 
        } catch (\Throwable $error) {
            SP::debugBacktraceShow($error);  
        }
    }

    /**
     * Deletes a row from the table based on an id.
     * 
     * @param int $id The primary key id.
     * @return bool True if successful, false otherwise.
     * @return false If an error and if debug is set to true, then,
     *                  a debug error will be returned.
     */
    public function TrashBasedOnId(int $id) {
        $this->table = $this->table(); 
        
        try {
            $query = "DELETE FROM $this->table WHERE id ='" . $id . "'";
            $result = mysqli_query($this->connection, $query); 

            if ($result) {
                return true;
            } else { 
                return false;
            }
        } catch (\Throwable $error) {
            SP::debugBacktraceShow($error); 
        } 
    }
}
