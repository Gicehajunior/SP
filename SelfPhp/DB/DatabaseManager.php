<?php

namespace SelfPhp\DB;

use SelfPhp\SP;
use \mysqli;
use \PDO;
use \SQLite3; 
use \MongoDB\Driver\Manager;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Class DatabaseManager
 * 
 * The DatabaseManager class handles database spconnections for various database systems
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
trait DatabaseManager {
    /**
     * The database driver used for the spconnection (e.g., mysql, postgresql, mongodb, sqlite, sqlsrv).
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
     * Additional options for configuring the database spconnection.
     *
     * @var array
     */
    private $options;

    /**
     * The character set used for MySQL database spconnections.
     *
     * @var string
     */
    private $charset;

    /**
     * The collation used for MySQL database spconnections.
     *
     * @var string
     */
    private $collation;

    /**
     * The table prefix for MySQL database spconnections.
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
     * The storage engine used for MySQL database spconnections.
     *
     * @var string
     */
    private $engine;

    /**
     * The default schema for PostgreSQL database spconnections.
     *
     * @var string
     */
    private $schema;

    /**
     * The SSL mode used for PostgreSQL database spconnections.
     *
     * @var string
     */
    private $sslmode;

    /**
     * The active database spconnection resource.
     *
     * @var resource|MongoDB\Client|PDO
     */
    private $spconnection;

    /**
     * The default database type (e.g., mysql, postgresql, mongodb, sqlite, sqlsrv).
     *
     * @var string
     */
    private $defaultDB;

    /**
     * The foreign key constraints setting for SQLite database spconnections.
     *
     * @var bool
     */
    private $foreign_key_constraints;

    /**
     * Holds any spconnection error that occurred during the database spconnection.
     *
     * @var string|null
     */
    private $spconnection_error = null;

    /** 
     * The data to be saved. 
     * 
     * @var array
     * */
    private $data;

    /** 
     * The parameters for the prepared statement.
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
    public function __construct() {
        // Set database spconnection common configurations
        $this->driver = env("DB_CONNECTION") ? env("DB_CONNECTION") : $appDbConfigurations[$this->defaultDB]['driver'];
        $this->host = env("DB_HOST") ? env("DB_HOST") : $appDbConfigurations[$this->defaultDB]['host'];
        $this->port = env("DB_PORT") ? env("DB_PORT") : $appDbConfigurations[$this->defaultDB]['port']; 
        $this->username = env("DB_USERNAME") ? env("DB_USERNAME") : $appDbConfigurations[$this->defaultDB]['username'];
        $this->password = (env("DB_NAME")) ? env("DB_PASSWORD") : $appDbConfigurations[$this->defaultDB]['password'];
        $this->database = env("DB_NAME") ? env("DB_NAME") : $appDbConfigurations[$this->defaultDB]['database']; 
    }

    /**
     * Set the database configurations based on the application settings.
     * 
     * This method determines the default database type (e.g., MySQL, PostgreSQL),
     * reads the corresponding configuration values, and sets the appropriate properties
     * for the DatabaseManager instance. It also establishes the initial database spconnection.
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
        
        // Set database spconnection common configurations
        $this->driver = env("DB_CONNECTION") ? env("DB_CONNECTION") : $appDbConfigurations[$this->defaultDB]['driver'];
        $this->host = env("DB_HOST") ? env("DB_HOST") : $appDbConfigurations[$this->defaultDB]['host'];
        $this->port = env("DB_PORT") ? env("DB_PORT") : $appDbConfigurations[$this->defaultDB]['port']; 
        $this->username = env("DB_USERNAME") ? env("DB_USERNAME") : $appDbConfigurations[$this->defaultDB]['username'];
        $this->password = (env("DB_NAME")) ? env("DB_PASSWORD") : $appDbConfigurations[$this->defaultDB]['password'];
        $this->database = env("DB_NAME") ? env("DB_NAME") : $appDbConfigurations[$this->defaultDB]['database']; 
    } 

    public function addDBManager() {  
        $capsule = new Capsule;

        $capsule->addConnection([
            'driver'    => $this->driver,
            'host'      => $this->host,
            'database'  => $this->database,
            'username'  => $this->username,
            'password'  => env("DB_PASSWORD"),
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ]); 

        // Make this Capsule instance available globally.
        $capsule->setAsGlobal();

        // Setup the Eloquent ORM.
        $capsule->bootEloquent();
    }
    
    /**
     * Establishes a spconnection to a MySQL database.
     * 
     * This method uses the configured parameters to establish a spconnection to a MySQL
     * database and performs additional setup, such as setting character set, collation,
     * and other options.
     * 
     * @return resource|false The MySQL database spconnection resource or false on failure.
     */
    public function mysqlConnect() {  
        // Check if the MySQLi extension is installed
        if (! extension_loaded('mysqli')) {
            throw new \Exception("The MySQLi extension is not installed!");
        }

        // Establishing a mysql database spconnection 
        $this->spconnection = mysqli_connect(
            $this->host,
            $this->username,
            $this->password,
            $this->database,
            $this->port
        );  

        if (!$this->spconnection) {
            $this->spconnection_error = mysqli_connect_error();

            die("Connection failed: " . $this->spconnection_error);
        }
        
        // Set character set
        mysqli_set_charset($this->spconnection, $this->charset);

        // Set collation
        $collationQuery = "SET collation_spconnection=$this->collation";
        mysqli_query($this->spconnection, $collationQuery);

        // set prefix
        if ($this->prefix !== null || !empty($this->prefix)) {
            $this->prefix = $this->prefix;
        }

        // Additional configuration options
        if ($this->strict) {
            $strictQuery = "SET SESSION sql_mode='STRICT_ALL_TABLES'";
            mysqli_query($this->spconnection, $strictQuery);
        }

        if ($this->engine !== null || !empty($this->engine)) {
            $engineQuery = "SET storage_engine=$this->engine";
            mysqli_query($this->spconnection, $engineQuery);
        }

        // Set any additional options
        if (!empty($this->options)) {
            foreach ($this->options as $option => $value) {
                if (!empty($value)) {
                    $optionQuery = "SET $option=$value"; 
                    mysqli_query($this->spconnection, $optionQuery); 
                } 
            }
        } 

        return $this->spconnection;
    }

    /**
     * Establishes a spconnection to a PostgreSQL database.
     * 
     * This method uses the configured parameters to establish a spconnection to a PostgreSQL
     * database and performs additional setup, such as setting character set and options.
     * 
     * @return resource|false The PostgreSQL database spconnection resource or false on failure.
     */
    public function postgresqlConnect() {
        // Check if the pgsql extension is installed
        if (! extension_loaded('pgsql')) {
            throw new \Exception("The pgsql extension is not installed!");
        }

        // Establishing a PostgreSQL database spconnection
        $spconnectionString = "host={$this->host} port={$this->port} dbname={$this->database} user={$this->username} password={$this->password}";
        
        // Add additional PostgreSQL-specific spconnection parameters if needed
        if (!empty($this->schema)) {
            $spconnectionString .= " options=--search_path={$this->schema}";
        }
    
        if (!empty($this->sslmode)) {
            $spconnectionString .= " sslmode={$this->sslmode}";
        }
    
        $this->spconnection = pg_connect($spconnectionString);
    
        // Check if the spconnection was successful
        if (!$this->spconnection) {
            $this->spconnection_error = pg_last_error();

            die("Connection failed: " . $this->spconnection_error);
        }
    
        // Set client encoding (character set)
        pg_set_client_encoding($this->spconnection, $this->charset);
    
        // Additional configuration options
        if ($this->strict) {
            $strictQuery = "SET SESSION sql_mode='STRICT_ALL_TABLES'";
            pg_query($this->spconnection, $strictQuery);
        }
    
        // Set any additional options
        if (!empty($this->options)) {
            foreach ($this->options as $option => $value) {
                if (!empty($value)) {
                    $optionQuery = "SET $option=$value";
                    pg_query($this->spconnection, $optionQuery);
                }
            }
        }
    
        return $this->spconnection;  
    }

    /**
     * Establishes a spconnection to a MongoDB database.
     * 
     * This method uses the configured parameters to establish a spconnection to a MongoDB
     * database using the MongoDB\Client class.
     * 
     * @return MongoDB\Client The MongoDB client instance.
     */
    public function mongodbConnect() { 
        // Establishing a MongoDB spconnection
        $mongoConnectionOptions = [];
    
        // Add host and port to spconnection options
        if (!empty($this->host)) {
            $mongoConnectionOptions['host'] = $this->host;
        }
    
        if (!empty($this->port)) {
            $mongoConnectionOptions['port'] = $this->port;
        }
    
        // Add additional MongoDB-specific spconnection parameters if needed
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
        $this->spconnection = new MongoDB\Client($mongoConnectionOptions);
    
        // Check if the spconnection was successful
        if (!$this->spconnection) {
            $this->spconnection_error = "Unable to connect to MongoDB!";
            die("Connection failed: " . $this->spconnection_error);
        }
    
        return $this->spconnection;
    }    

    /**
     * Establishes a spconnection to an SQLite database.
     * 
     * This method uses the configured parameters to establish a spconnection to an SQLite
     * database using the PDO extension.
     * 
     * @return PDO|false The SQLite database spconnection or false on failure.
     */
    public function sqliteConnect() {
        // Check if the pdo_sqlite extension is installed
        if (! extension_loaded('pdo_sqlite')) {
            throw new \Exception("The pdo_sqlite extension is not installed!");
        }

        // Establishing an SQLite database spconnection
        $dsn = "sqlite:" . $this->database;
    
        try {
            $this->spconnection = new PDO($dsn);
    
            // Set any additional options
            if (!empty($this->options)) {
                foreach ($this->options as $option => $value) {
                    $this->spconnection->setAttribute($option, $value);
                }
            }

            return $this->spconnection;
        } catch (PDOException $e) {
            $this->spconnection_error = $e->getMessage();
            die("Connection failed: " . $this->spconnection_error);
        }
    }

    /**
     * Establishes a spconnection to a SQL Server database.
     * 
     * This method uses the configured parameters to establish a spconnection to a SQL Server
     * database using the sqlsrv_connect function.
     * 
     * @return resource|false The SQL Server database spconnection resource or false on failure.
     */
    public function sqlsrvConnect() {
        // Check if the sqlsrv extension is installed
        if (! extension_loaded('sqlsrv')) {
            throw new \Exception("The sqlsrv extension is not installed!");
        }

        // Establishing a SQL Server database spconnection
        $spconnectionOptions = [
            'Database' => $this->database,
            'Uid' => $this->username,
            'PWD' => $this->password,
            'CharacterSet' => $this->charset,
        ];

        // Add additional SQL Server-specific spconnection parameters if needed
        if (!empty($this->host)) {
            $spconnectionOptions['Server'] = $this->host;
        }

        if (!empty($this->port)) {
            $spconnectionOptions['Port'] = $this->port;
        }

        // Create SQL Server spconnection
        $this->spconnection = sqlsrv_connect($this->host, $spconnectionOptions);

        // Check if the spconnection was successful
        if (!$this->spconnection) {
            $this->spconnection_error = sqlsrv_errors()[0]['message'];
            die("Connection failed: " . $this->spconnection_error);
        }

        // Set any additional options
        if (!empty($this->options)) {
            foreach ($this->options as $option => $value) {
                sqlsrv_query($this->spconnection, "SET $option=$value");
            }
        }

        return $this->spconnection;
    }

    /**
     * Selects the appropriate method to establish a database spconnection based on the
     * default database type.
     * 
     * @return resource|MongoDB\Client|PDO|false The database spconnection resource or false on failure.
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
     * Closes the active database spconnection.
     * 
     * This method closes the active database spconnection based on the default database type.
     */
    public function closeConnection() {
        if ($this->spconnection) {
            switch ($this->defaultDB) {
                case 'mysql':
                    mysqli_close($this->spconnection);
                    break;

                case 'postgresql':
                    pg_close($this->spconnection);
                    break;

                case 'mongodb':
                    $this->spconnection->close();
                    break;

                case 'sqlite':
                    $this->spconnection = null;
                    break;

                case 'sqlsrv':
                    sqlsrv_close($this->spconnection);
                    break; 
                default:
                    mysqli_close($this->spconnection);
            }

            // Set $this->spconnection to null after closing the spconnection
            $this->spconnection = null;
        }
    } 

}