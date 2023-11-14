<?php

namespace SelfPhp\DB;

use SelfPhp\SP;
use \mysqli;
use \PDO;
use \MongoDB\Driver\Manager;
use \SQLite3;
use SelfPhp\DB\SPQueryBuilder; 

/**
 * Class DatabaseManager
 * 
 * The DatabaseManager class handles database connections for various database systems
 * including MySQL, PostgreSQL, MongoDB, SQLite, and SQL Server in the SelfPhp framework.
 * It provides a unified interface for connecting to different databases based on
 * configuration settings, and also includes common database operations such as querying,
 * inserting, updating, and deleting data.
 * 
 * @package SelfPhp\Database
 * @version 1.0.0
 * @since 1.0.0 
 * @author Giceha Junior: https://github.com/Gicehajunior
 */
class DatabaseManager extends SPQueryBuilder {
    /**
     * The database driver used for the connection (e.g., mysql, postgresql, mongodb, sqlite, sqlsrv).
     *
     * @var string
     */
    private $driver;

    /**
     * The host or IP address of the database server.
     *
     * @var string
     */
    private $host;

    /**
     * The port number to use when connecting to the database server.
     *
     * @var int
     */
    private $port;

    /**
     * The username used for authenticating to the database server.
     *
     * @var string
     */
    private $username;

    /**
     * The password used for authenticating to the database server.
     *
     * @var string
     */
    private $password;

    /**
     * The name of the database to connect to.
     *
     * @var string
     */
    private $database;

    /**
     * Additional options for configuring the database connection.
     *
     * @var array
     */
    private $options;

    /**
     * The character set used for MySQL database connections.
     *
     * @var string
     */
    private $charset;

    /**
     * The collation used for MySQL database connections.
     *
     * @var string
     */
    private $collation;

    /**
     * The table prefix for MySQL database connections.
     *
     * @var string
     */
    private $prefix;

    /**
     * Flag indicating whether MySQL should operate in strict mode.
     *
     * @var bool
     */
    private $strict;

    /**
     * The storage engine used for MySQL database connections.
     *
     * @var string
     */
    private $engine;

    /**
     * The default schema for PostgreSQL database connections.
     *
     * @var string
     */
    private $schema;

    /**
     * The SSL mode used for PostgreSQL database connections.
     *
     * @var string
     */
    private $sslmode;

    /**
     * The active database connection resource.
     *
     * @var resource|MongoDB\Client|PDO
     */
    private $connection;

    /**
     * The default database type (e.g., mysql, postgresql, mongodb, sqlite, sqlsrv).
     *
     * @var string
     */
    private $defaultDB;

    /**
     * The foreign key constraints setting for SQLite database connections.
     *
     * @var bool
     */
    private $foreign_key_constraints;

    /**
     * Holds any connection error that occurred during the database connection.
     *
     * @var string|null
     */
    private $connection_error = null;

    /** 
     * The data to be saved. 
     * 
     * @var array
     * */
    private $data;

    /** 
     * The parameters to be used in the query.
     * 
     * @var array
     * */
    private $params;

    /**
     * Constructor for the DatabaseManager class.
     * 
     * Initializes the DatabaseManager instance and sets the database configurations
     * based on the application settings.
     */
    public function __construct() {}

    /**
     * Set the database configurations based on the application settings.
     * 
     * This method determines the default database type (e.g., MySQL, PostgreSQL),
     * reads the corresponding configuration values, and sets the appropriate properties
     * for the DatabaseManager instance. It also establishes the initial database connection.
     */
    public function setDbConfigurations($defaultDB = null, $appDbConfigurations = []) { 
        $this->defaultDB = $defaultDB;  

        if ($this->defaultDB == 'mysql') {  
            $this->charset = $appDbConfigurations['mysql']['charset'];
            $this->collation = $appDbConfigurations['mysql']['collation'];
            $this->prefix = $appDbConfigurations['mysql']['prefix'];
            $this->strict = $appDbConfigurations['mysql']['strict'];
            $this->engine = $appDbConfigurations['mysql']['engine'];
            $this->options = $appDbConfigurations['mysql']['options'];
        } elseif ($this->defaultDB == 'postgresql') {   
            $this->schema = $appDbConfigurations['postgresql']['schema'];
            $this->sslmode = $appDbConfigurations['postgresql']['sslmode'];
            $this->options = $appDbConfigurations['postgresql']['options'];
        } elseif ($this->defaultDB == 'mongodb') { 
            $this->options = $appDbConfigurations['mongodb']['options'];
        } elseif ($this->defaultDB == 'sqlite') { 
            $this->driver = $appDbConfigurations['sqlite']['driver'];
            $this->database = $appDbConfigurations['sqlite']['database'];
            $this->prefix = $appDbConfigurations['sqlite']['prefix'];
            $this->foreign_key_constraints = $appDbConfigurations['sqlite']['foreign_key_constraints'];
        } elseif ($this->defaultDB == 'sqlsrv') { 
            $this->charset = $appDbConfigurations['sqlsrv']['charset'];
            $this->prefix = $appDbConfigurations['sqlsrv']['prefix'];
            $this->options = $appDbConfigurations['sqlsrv']['options'];
        }
        
        // Set database connection common configurations
        $this->host = env("DB_HOST") ? env("DB_HOST") : $appDbConfigurations[$this->defaultDB]['host'];
        $this->port = env("DB_PORT") ? env("DB_PORT") : $appDbConfigurations[$this->defaultDB]['port']; 
        $this->username = env("DB_USERNAME") ? env("DB_USERNAME") : $appDbConfigurations[$this->defaultDB]['username'];
        $this->password = (env("DB_NAME")) ? env("DB_PASSWORD") : $appDbConfigurations[$this->defaultDB]['password'];
        $this->database = env("DB_NAME") ? env("DB_NAME") : $appDbConfigurations[$this->defaultDB]['database']; 
    } 
    
    /**
     * Establishes a connection to a MySQL database.
     * 
     * This method uses the configured parameters to establish a connection to a MySQL
     * database and performs additional setup, such as setting character set, collation,
     * and other options.
     * 
     * @return resource|false The MySQL database connection resource or false on failure.
     */
    public function mysqlConnect() {  
        // Check if the MySQLi extension is installed
        if (! extension_loaded('mysqli')) {
            throw new \Exception("The MySQLi extension is not installed!");
        }

        // Establishing a mysql database connection 
        $this->connection = mysqli_connect(
            $this->host,
            $this->username,
            $this->password,
            $this->database,
            $this->port
        );  

        if (!$this->connection) {
            $this->connection_error = mysqli_connect_error();

            die("Connection failed: " . $this->connection_error);
        }
        
        // Set character set
        mysqli_set_charset($this->connection, $this->charset);

        // Set collation
        $collationQuery = "SET collation_connection=$this->collation";
        mysqli_query($this->connection, $collationQuery);

        // set prefix
        if ($this->prefix !== null || !empty($this->prefix)) {
            $this->prefix = $this->prefix;
        }

        // Additional configuration options
        if ($this->strict) {
            $strictQuery = "SET SESSION sql_mode='STRICT_ALL_TABLES'";
            mysqli_query($this->connection, $strictQuery);
        }

        if ($this->engine !== null || !empty($this->engine)) {
            $engineQuery = "SET storage_engine=$this->engine";
            mysqli_query($this->connection, $engineQuery);
        }

        // Set any additional options
        if (!empty($this->options)) {
            foreach ($this->options as $option => $value) {
                if (!empty($value)) {
                    $optionQuery = "SET $option=$value"; 
                    mysqli_query($this->connection, $optionQuery); 
                } 
            }
        } 

        return $this->connection;
    }

    /**
     * Establishes a connection to a PostgreSQL database.
     * 
     * This method uses the configured parameters to establish a connection to a PostgreSQL
     * database and performs additional setup, such as setting character set and options.
     * 
     * @return resource|false The PostgreSQL database connection resource or false on failure.
     */
    public function postgresqlConnect() {
        // Check if the pgsql extension is installed
        if (! extension_loaded('pgsql')) {
            throw new \Exception("The pgsql extension is not installed!");
        }

        // Establishing a PostgreSQL database connection
        $connectionString = "host={$this->host} port={$this->port} dbname={$this->database} user={$this->username} password={$this->password}";
        
        // Add additional PostgreSQL-specific connection parameters if needed
        if (!empty($this->schema)) {
            $connectionString .= " options=--search_path={$this->schema}";
        }
    
        if (!empty($this->sslmode)) {
            $connectionString .= " sslmode={$this->sslmode}";
        }
    
        $this->connection = pg_connect($connectionString);
    
        // Check if the connection was successful
        if (!$this->connection) {
            $this->connection_error = pg_last_error();

            die("Connection failed: " . $this->connection_error);
        }
    
        // Set client encoding (character set)
        pg_set_client_encoding($this->connection, $this->charset);
    
        // Additional configuration options
        if ($this->strict) {
            $strictQuery = "SET SESSION sql_mode='STRICT_ALL_TABLES'";
            pg_query($this->connection, $strictQuery);
        }
    
        // Set any additional options
        if (!empty($this->options)) {
            foreach ($this->options as $option => $value) {
                if (!empty($value)) {
                    $optionQuery = "SET $option=$value";
                    pg_query($this->connection, $optionQuery);
                }
            }
        }
    
        return $this->connection;  
    }

    /**
     * Establishes a connection to a MongoDB database.
     * 
     * This method uses the configured parameters to establish a connection to a MongoDB
     * database using the MongoDB\Client class.
     * 
     * @return MongoDB\Client The MongoDB client instance.
     */
    public function mongodbConnect() { 
        // Establishing a MongoDB connection
        $mongoConnectionOptions = [];
    
        // Add host and port to connection options
        if (!empty($this->host)) {
            $mongoConnectionOptions['host'] = $this->host;
        }
    
        if (!empty($this->port)) {
            $mongoConnectionOptions['port'] = $this->port;
        }
    
        // Add additional MongoDB-specific connection parameters if needed
        if (!empty($this->username) && !empty($this->password)) {
            $mongoConnectionOptions['username'] = $this->username;
            $mongoConnectionOptions['password'] = $this->password;
        }
    
        if (!empty($this->database)) {
            $mongoConnectionOptions['db'] = $this->database;
        }
    
        if (!empty($this->options)) {
            $mongoConnectionOptions += $this->options;
        }
    
        // Create MongoDB client
        $this->connection = new MongoDB\Client($mongoConnectionOptions);
    
        // Check if the connection was successful
        if (!$this->connection) {
            $this->connection_error = "Unable to connect to MongoDB!";
            die("Connection failed: " . $this->connection_error);
        }
    
        return $this->connection;
    }    

    /**
     * Establishes a connection to an SQLite database.
     * 
     * This method uses the configured parameters to establish a connection to an SQLite
     * database using the PDO extension.
     * 
     * @return PDO|false The SQLite database connection or false on failure.
     */
    public function sqliteConnect() {
        // Check if the pdo_sqlite extension is installed
        if (! extension_loaded('pdo_sqlite')) {
            throw new \Exception("The pdo_sqlite extension is not installed!");
        }

        // Establishing an SQLite database connection
        $dsn = "sqlite:" . $this->database;
    
        try {
            $this->connection = new PDO($dsn);
    
            // Set any additional options
            if (!empty($this->options)) {
                foreach ($this->options as $option => $value) {
                    $this->connection->setAttribute($option, $value);
                }
            }

            return $this->connection;
        } catch (PDOException $e) {
            $this->connection_error = $e->getMessage();
            die("Connection failed: " . $this->connection_error);
        }
    }

    /**
     * Establishes a connection to a SQL Server database.
     * 
     * This method uses the configured parameters to establish a connection to a SQL Server
     * database using the sqlsrv_connect function.
     * 
     * @return resource|false The SQL Server database connection resource or false on failure.
     */
    public function sqlsrvConnect() {
        // Check if the sqlsrv extension is installed
        if (! extension_loaded('sqlsrv')) {
            throw new \Exception("The sqlsrv extension is not installed!");
        }

        // Establishing a SQL Server database connection
        $connectionOptions = [
            'Database' => $this->database,
            'Uid' => $this->username,
            'PWD' => $this->password,
            'CharacterSet' => $this->charset,
        ];

        // Add additional SQL Server-specific connection parameters if needed
        if (!empty($this->host)) {
            $connectionOptions['Server'] = $this->host;
        }

        if (!empty($this->port)) {
            $connectionOptions['Port'] = $this->port;
        }

        // Create SQL Server connection
        $this->connection = sqlsrv_connect($this->host, $connectionOptions);

        // Check if the connection was successful
        if (!$this->connection) {
            $this->connection_error = sqlsrv_errors()[0]['message'];
            die("Connection failed: " . $this->connection_error);
        }

        // Set any additional options
        if (!empty($this->options)) {
            foreach ($this->options as $option => $value) {
                sqlsrv_query($this->connection, "SET $option=$value");
            }
        }

        return $this->connection;
    }

    /**
     * Selects the appropriate method to establish a database connection based on the
     * default database type.
     * 
     * @return resource|MongoDB\Client|PDO|false The database connection resource or false on failure.
     */
    public function _connect() {
        $sp = new SP();

        $appDbConfigurations = $sp->getAppDbConfigurations();

        $defaultDB = $appDbConfigurations['default']; 
        
        $this->setDbConfigurations($defaultDB, $appDbConfigurations); 
        
        if ($defaultDB == 'mysql') {
            return $this->mysqlConnect();
        } elseif ($defaultDB == 'postgresql') {
            return $this->postgresqlConnect();
        } elseif ($defaultDB == 'mongodb') {
            return $this->mongodbConnect();
        } elseif ($defaultDB == 'sqlite') {
            return $this->sqliteConnect();
        } elseif ($defaultDB == 'sqlsrv') {
            return $this->sqlsrvConnect();
        } else {
            // Default to MySQL if the database type is not recognized.
            return $mysqlConnect();
        }
    } 

    public static function connect() {

        return (new DatabaseManager())->_connect();
    }

    /**
     * Closes the active database connection.
     * 
     * This method closes the active database connection based on the default database type.
     */
    public function closeConnection() {
        if ($this->connection) {
            switch ($this->defaultDB) {
                case 'mysql':
                    mysqli_close($this->connection);
                    break;

                case 'postgresql':
                    pg_close($this->connection);
                    break;

                case 'mongodb':
                    $this->connection->close();
                    break;

                case 'sqlite':
                    $this->connection = null;
                    break;

                case 'sqlsrv':
                    sqlsrv_close($this->connection);
                    break; 
                default:
                    mysqli_close($this->connection);
            }

            // Set $this->connection to null after closing the connection
            $this->connection = null;
        }
    } 

}