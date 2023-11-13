<?php

namespace SelfPhp\DB;

use SelfPhp\DB\DatabaseManager as DB; 

/**
 * Database Query Builder Class
 *
 * The SPQueryBuilder class provides a robust and secure mechanism for constructing SQL queries
 * within the selfPHP framework. Emphasizing the use of prepared statements, this class helps mitigate
 * SQL injection vulnerabilities, ensuring a high level of security when interacting with the database.
 *
 * This class is an integral part of the SelfPHP framework's database abstraction layer, offering a fluent
 * interface for composing SELECT, UPDATE, DELETE, and JOIN operations with ease. The generated queries
 * adhere to best practices, promoting maintainability and readability in large-scale applications.
 *
 * @package SelfPHP
 * @category Database
 * @version 1.0.0
 * @author Giceha Junior: https://github.com/Gicehajunior
 * @link https://github.com/Gicehajunior/selfphp-framework
 */
class SPQueryBuilder extends DB
{
    /** @var string The generated SQL query. */
    private $query;

    /** @var array The parameters for the prepared statement. */
    private $params = [];

    /** @var mysqli The mysqli instance. */
    private $mysqli;

    /**
     * QueryBuilder constructor.
     *
     * @param mysqli $mysqli The mysqli instance.
     */
    public function __construct()
    {
        $mysqli = DB::connect();
        $this->mysqli = $mysqli;
    }

    public function _select($table, $columns = ['*']) {
        $this->query = "SELECT " . implode(', ', $columns);
        $this->query .= " FROM $table";
        return $this;
    }

    /**
     * Select columns from a table.
     *
     * @param string $table    The table name.
     * @param array  $columns  An array of columns to select. Default is ['*'] (all columns).
     * @return QueryBuilder    Returns $this for method chaining.
     */
    public static function select($table, $columns = ['*'])
    {
        return (new SPQueryBuilder())->_select($table, $columns = ['*']); 
    }

    /**
     * Add a WHERE clause to the query.
     *
     * @param string $condition The condition for the WHERE clause.
     * @return QueryBuilder     Returns $this for method chaining.
     */
    public function where($condition)
    { 
        // if this query has no WHERE clause, add one
        if (strpos($this->query, 'WHERE') === false) {
            $this->query .= " WHERE $condition";
        } else {
            // if this query already has a WHERE clause, add an AND clause
            $this->query .= " AND $condition";
        }
        
        return $this;
    }

    /**
     * Add a NOT WHERE clause to the query.
     *
     * @param string $condition The condition for the NOT WHERE clause.
     * @return QueryBuilder     Returns $this for method chaining.
     */
    public function whereNot($condition)
    {
        $this->query .= " AND NOT $condition";
        return $this;
    }

    /**
     * Add a WHERE IN clause to the query.
     *
     * @param string $column The column for the WHERE IN clause.
     * @param array  $values An array of values for the WHERE IN clause.
     * @return QueryBuilder Returns $this for method chaining.
     */
    public function whereIn($column, $values)
    {
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->query .= " AND $column IN ($placeholders)";
        $this->params = array_merge($this->params, $values);
        return $this;
    }

    /**
     * Add an ORDER BY clause to the query.
     *
     * @param string $column    The column to order by.
     * @param string $direction The order direction (ASC or DESC). Default is 'ASC'.
     * @return QueryBuilder     Returns $this for method chaining.
     */
    public function orderBy($column, $direction = 'ASC')
    {
        $this->query .= " ORDER BY $column $direction";
        return $this;
    }

    /**
     * Update data in a table.
     *
     * @param string $table The table name.
     * @param array  $data  An associative array of column-value pairs to update.
     * @return QueryBuilder Returns $this for method chaining.
     */
    public function update($table, $data)
    {
        $setClause = [];
        foreach ($data as $column => $value) {
            $setClause[] = "$column = ?";
            $this->params[] = $value;
        }

        $this->query = "UPDATE $table SET " . implode(', ', $setClause);
        return $this;
    }

    /**
     * Delete data from a table.
     *
     * @param string $table The table name.
     * @return QueryBuilder Returns $this for method chaining.
     */
    public function delete($table)
    {
        $this->query = "DELETE FROM $table";
        return $this;
    }

    /**
     * Join another table in the query.
     *
     * @param string $table       The table name to join.
     * @param string $onCondition The condition for the join operation.
     * @param string $type        The type of join (INNER, LEFT, RIGHT). Default is 'INNER'.
     * @return QueryBuilder       Returns $this for method chaining.
     */
    public function join($table, $onCondition, $type = 'INNER')
    {
        $this->query .= " $type JOIN $table ON $onCondition";
        return $this;
    }

    /**
     * Execute the prepared statement using mysqli_query.
     * Note: This is a simple example. Actual implementation may vary based on your database connection setup.
     *
     * @return mixed The result of the executed query.
     */
    private function execute()
    {
        // Prepare the statement
        $stmt = $this->mysqli->prepare($this->query);

        // Bind parameters
        if (!empty($this->params)) {
            $types = str_repeat('s', count($this->params)); // Assuming all parameters are strings for simplicity
            $stmt->bind_param($types, ...$this->params);
        }

        // Execute the statement
        $stmt->execute();

        // Get the result
        $result = $stmt->get_result();

        // Fetch the result as an associative array
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        // Close the statement
        $stmt->close();

        return $data;
    }

    /**
     * Get the generated SQL query.
     *
     * @return array An associative array containing the query and parameters.
     */
    public function get()
    {
        $result = $this->execute();

        // Reset query and parameters for potential reuse
        $this->query = '';
        $this->params = [];

        return $result;
    }
}