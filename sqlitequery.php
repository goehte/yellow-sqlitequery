<?php
// SQLite Query Extension for Datenstrom Yellow CMS
// https://github.com/goehte/yellow-sqlitequery
// Usage in markdown: [sqlitequery filename.sqlite "SELECT * FROM users WHERE active = 1"]

class YellowSqliteQuery {
    const VERSION = "0.0.1";
    public $yellow; // Wrapped CMS API object
    

    // Handle initialisation
    public function onLoad($yellow) {
        $this->yellow = $yellow;
        // Directory for storing form definition files
        $this->yellow->system->setDefault("SQLiteDatabaseDirectory", "media/databases/");
    }


    // Handle page content element
    public function onParseContentElement($page, $name, $text, $attributes, $type) {
        $output = "";
        if ($name == "sqlitequery"&& ($type == "block" || $type == "inline")) {
            // Grab the rguments
            list($dbName, $sqlStatement) = $this->yellow->toolbox->getTextArguments($text); 

            if (empty($dbName) || empty($sqlStatement)) {
                return "<em class=\"error\">[sqlitequery: Missing database file or SQL statement]</em>";
            }
     
            // File
            $dbName = basename(trim($dbName));
            
            // Path
            $dbPath = realpath($this->yellow->system->get("SQLiteDatabaseDirectory") . $dbName);
 

            if (!file_exists($dbPath)) {
                return "<em class=\"error\">[sqlitequery: Database file not found at " . htmlspecialchars($dbPath) . "]</em>";
            }

            try {
                // Initialize SQLite connection in read-only mode for safety
                $db = new PDO("sqlite:" . $dbPath, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);

                // Run the custom query statement
                $stmt = $db->query($sqlStatement);
                $rows = $stmt->fetchAll();

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
        }
        return $output;
    }
}
