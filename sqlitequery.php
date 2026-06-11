<?php
// SQLite Query Extension for Datenstrom Yellow CMS
// https://github.com/goehte/yellow-sqlitequery
// Usage in markdown: [sqlitequery filename.sqlite "SELECT * FROM users WHERE active = 1"] or [sqlitequery filename.sqlite view_query.sql]

class YellowSqliteQuery {
    const VERSION = "0.0.2";
    public $yellow; // Wrapped CMS API object
    

    // Handle initialisation
    public function onLoad($yellow) {
        $this->yellow = $yellow;
        // Directory for storing form definition files
        $this->yellow->system->setDefault("SQLiteDatabaseDirectory", "media/databases/");
        $this->yellow->system->setDefault("SQLiteSQLQueryDirectory", "media/databases/sql/");
    }


    // Handle page content element
    public function onParseContentElement($page, $name, $text, $attributes, $type) {
        $output = "";
        if ($name == "sqlitequery" && ($type == "block" || $type == "inline")) {
            // Grab the arguments
            list($dbFile, $sqlStatement) = $this->yellow->toolbox->getTextArguments($text); 

            if (empty($dbFile) || empty($sqlStatement)) {
                return "<em class=\"error\">[sqlitequery: Missing database file or SQL statement]</em>";
            }
     
            // SQLite Database File
            $dbFile = basename(trim($dbFile));
            
            // SQLite Database Path
            $dbPath = realpath($this->yellow->system->get("SQLiteDatabaseDirectory") . $dbFile);
 
            // Normalize the input by trimming any accidental whitespace
            $sqlStatement = trim($sqlStatement);

            // Check if the string ends with the .sql extension
            if (pathinfo($sqlStatement, PATHINFO_EXTENSION) === 'sql') {
                // It's a file reference
                $sqlName = basename(trim($sqlStatement));
                $sqlPath = realpath($this->yellow->system->get("SQLiteSQLQueryDirectory") . $sqlName);
                if (!file_exists($sqlPath)) {

                } else {
                    $sqlStatement = $this->yellow->toolbox->readFile($sqlPath);
                    $output = $this->query_sql($dbFile, $dbPath, $sqlStatement);
                }
            } else {
                // It's an inline SQL statement
                $output = $this->query_sql($dbFile, $dbPath, $sqlStatement);
            }
        } 

        return $output;
    } 
    
    // Query the SQL statment
    private function query_sql($dbFile, $dbPath, $sqlStatement) {
        if (!file_exists($dbPath)) {
            return "<em class=\"error\">[sqlitequery: Database file not found at " . htmlspecialchars($dbPath) . "]</em>";
        }

        try {
            // Initialize SQLite connection in read-only mode for safety with "?mode=ro"
            $db = new PDO("sqlite:file:" . $dbPath . "?mode=ro", null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);

            // Run the custom query statement
            $stmt = $db->query($sqlStatement);
            $rows = $stmt->fetchAll();

            $output = ""; // Initialized to prevent PHP string concatenation warnings

            if (count($rows) > 0) {
                // Start building HTML output
                $output .= "<div class=\"sqlite-table-wrapper\">\n";
                $output .= "<table>\n";
                
                // Render Table Headers dynamically using array keys
                $output .= "<thead>\n<tr>\n";
                foreach (array_keys($rows[0]) as $columnName) {
                    $output .= "<th>" . htmlspecialchars($columnName) . "</th>\n";
                }
                $output .= "</tr>\n</thead>\n";

                // Render Table Body
                $output .= "<tbody>\n";
                foreach ($rows as $row) {
                    $output .= "<tr>\n";
                    foreach ($row as $value) {
                        $output .= "<td>" . htmlspecialchars($value ?? '') . "</td>\n";
                    }
                    $output .= "</tr>\n";
                }
                $output .= "</tbody>\n";
                $output .= "</table>\n";
                $output .= "</div>\n";
            } else {
                $output = "<p class=\"sqlite-table-empty\">No data returned from query.</p>";
            }

            // Close database connection
            $db = null;

        } catch (PDOException $e) {
            $output = "<em class=\"error\">[sqlitequery error: " . htmlspecialchars($e->getMessage()) . "]</em>";
        }

        return $output;
    }
}
