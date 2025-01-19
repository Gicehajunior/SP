<?php

namespace SelfPhp\DB;

use SelfPhp\SP;
use SelfPhp\DB\DatabaseManager as DB;

/**
 * Class Serve
 * 
 * This class serves as a base for interacting with the database. 
 * It utilizes functionalities provided by the `Illuminate\Database\Eloquent\Model` 
 * class and additional features from custom classes like `SelfPhp\SP` and 
 * `SelfPhp\DB\DatabaseManager` (aliased as `DB`).
 * 
 * Through inheritance and composition, `Serve` aims to streamline 
 * common database operations like saving, updating, and fetching data rows.
 */
class Serve extends DB
{ 
    /**
     * The model object. 
     * @var object
     */
    private $model;

    /**
     * A query row from the database.
     *
     * @var array
     */
    private $row;

    /**
     * An array of query rows from the database.
     *
     * @var array
     */
    private $rows;

    /**
     * Gets the first row from the fetched rows.
     * 
     * @return Serve The Serve object.
     */
    public function first()
    {
        if (!is_null($this->rows)) {
            $this->row = current($this->rows);
        }

        return $this->row;
    }

    /**
     * Gets all the fetched rows.
     * 
     * @return array The fetched rows.
     */
    public function get()
    {
        return $this->rows;
    }
}
