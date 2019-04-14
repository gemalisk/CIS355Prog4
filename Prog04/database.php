<?php
/**
 * File:   database.php
 * Author: George Corser (gpcorser@svsu.edu)
 * 
 * Description: Uninstantiated class, Database, 
 * contains functions used for database operations,
 * like connecting to the database (Database::connect()) .
 * 
 */
class Database {
    // class Database code below copied from...
    // https://www.startutorial.com/articles/view/php-crud-tutorial-part-1
    
    private static $dbName          = "customers" ;
    private static $dbHost          = "localhost" ;
    private static $dbUsername      = "root";
    private static $dbUserPassword  = "";
     
    private static $cont            = null; // pdo object
     
    public function __construct() {
        // do not allow class to be instantiated
        die("Init function is not allowed"); 
    }
     
    public static function connect() {
       // One connection through whole application
       if ( null == self::$cont ) {     
            try {
                self::$cont =  new PDO( "mysql:host=".self::$dbHost.";"."dbname=".self::$dbName, self::$dbUsername, self::$dbUserPassword); 
            }
            catch(PDOException $e) {
                die($e->getMessage()); 
            }
       }
       return self::$cont;
    }
     
    public static function disconnect() {
        self::$cont = null;
    }
    
    // tableExists() function code copied from...
    // https://stackoverflow.com/questions/1717495/check-if-a-database-table-exists-using-php-pdo
    
    /** Check if a table exists in the current database.
    *
    * @param PDO $pdo PDO instance connected to a database.
    * @param string $table Table to search for.
    * @return bool TRUE if table exists, FALSE if no table found.
    */
    function tableExists($pdo, $table) {
        
        // Try a select statement against the table
        // Run it in try/catch in case PDO is in ERRMODE_EXCEPTION.
        try {
            $result = $pdo->query("SELECT 1 FROM $table LIMIT 1");
        } catch (Exception $e) {
            // We got an exception == table not found
            return FALSE;
        }

        // Result is either boolean FALSE (no table found) or PDOStatement Object (table found)
        return $result !== FALSE;
        
    } // end function tableExists()
    
} //end class Database
?>