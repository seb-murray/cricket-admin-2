<?php
    
    // Set the PHP error reporting level to show all errors and warnings
    error_reporting(E_ALL);

    /**
     * Handles PHP errors by logging them to the database. 
     * php_custom_error_handler() captures PHP error details as parameters, and creates a new instance of the custom 'PHP_Error' class. 
     * This object is then passed as a parameter to a new instance of the 'Error_Handler' class, which is responsible for logging the error to the database.
     * 
     * @param int $error_code PHP error code
     * @param string $error_message PHP error message
     * @param string $error_file File in which the error occured
     * @param int $error_line Line on which the error occured
     * 
     * @return void Function does not return a value
     */
    function php_custom_error_handler(int $error_code, string $error_message, string $error_file, int $error_line): void
    {
        // Instantiate the custom Exception object with the error message, error code and error line
        $error = new PHP_Error($error_code, $error_message, $error_line, $error_file);

        // Pass the Exception object to the Error_Handler class to log the error to the database
        new Error_Handler($error);
    }

    // Set the PHP error handler to be the php_custom_error_handler() function defined above
    set_error_handler("php_custom_error_handler");

    /**
     * Static class containing database connection credentials as constants.
     */
    class Database_Credentials
    {
        /**
         *
         */
        const SERVERNAME = "localhost";
        /**
         *
         */
        const USERNAME = "wyvernsi_sebMurray";
        /**
         *
         */
        const PASSWORD = "L0n3someP0l3cat";
        /**
         *
         */
        const DATABASE = "wyvernsi_sebM";
    }

    /**
     * Class implementing the Singleton design pattern, meaning only one instance of the 'Database_Connection' class is ever created.
     * 
     * This ensures only one database connection is made throughout the lifetime of the application.
     */
    class Database_Connection
    {
        /**
         * Contains the singular instance of the class, only accessible through the 'get_instance()' method.
         * 
         * @var Database_Connection|null 
         */
        private static $instance = null;

        /**
         * Contains the MySQLi connection object.
         * 
         * @var mysqli
         */
        private $connection;

        // Constructor which creates a new MySQLi object, and stores it in the private variable $connection
        // Private so the __construct() function can only be called within the class (get_instance())
        /**
         * Private constructor which initializes a new MySQLi connection.
         * 
         * The function is private so it can only be called from within the class (public 'get_instance()' method).
         */
        private function __construct()
        {
            // By enabling MySQLi error reporting, a my_sql_exception object will be thrown and substequently handled by the Error_Handler function (from within a try...catch block) upon encountering an error, e.g. error in SQL syntax.
            // This means non-volatile my_sql_exception objects can be logged to the database, not bothering the user.
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

            try
            {
                // MySQLi connection made using credentials from 'Database_Credentials' class.
                $this->connection = new mysqli(Database_Credentials::SERVERNAME, Database_Credentials::USERNAME, Database_Credentials::PASSWORD, Database_Credentials::DATABASE);
            }
            catch(Throwable $error)
            {
                // Catch and handle any exceptions thrown during the connection process by creating a new instance of the Error_Handler class.
                new Error_Handler($error);
            }
        }

        /**
         * Returns the MySQLi connection from the singular instance of the 'Database_Connection' class.
         * 
         * If an instance does not already exist, it will instantiate one, and return the MySQLi connection from there.
         * 
         * @return mysqli|null Returns the MySQLi connection belonging to the singular instance of the 'Database_Connection' class
         */
        public static function get_instance()
        {
            try
            {
                // If the class has not yet been instantiated, create an instance of the class
                if (self::$instance == null)
                {
                    self::$instance = new Database_Connection;
                }

                // Return the MySQLi connection
                return self::$instance->connection;
            }
            catch(Throwable $error)
            {
                // Catch and handle any errors thrown by creating a new instance of the Error_Handler class.
                // Then return a null value.
                new Error_Handler($error);
                return null;
            }
        }

    }

    /**
     * Class which provides a simplified way to execute SQL queries using prepared statements.
     * 
     * Responsible for preparing, executing and retrieving results in different formats from SQL queries.
     */
    class Query
    {
        /**
         * Contains a MySQLi database connection object.
         * 
         * @var mysqli 
         */
        private $database_connection;
        
        /**
         * Contains the prepared statement object to be executed.
         * 
         * @var mysqli_stmt 
         */
        private $query;

        /**
         * Contains the result of the query as a mysqli_result object.
         * 
         * @var mysqli_result 
         */
        private $result;

        /**
         * Contains a boolean flag to indicate whether the SQL query was successful or not.
         * 
         * @var bool
         */
        private $query_success = false;

        /**
         * Constant array containing database fields as keys, and corresponding English headings as values (e.g. "club_name" -> "Club").
         */
        private const FIELD_HEADINGS = 
        [
            "availability_ID" => "Availability ID",
            "available" => "Available?",
            "COLUMN_NAME" => "Column Name",
            "club_ID" => "Club ID",
            "club_name" => "Club",
            "error_ID" => "Error ID",
            "error_message" => "Error Message",
            "error_time" => "Time",
            "event_ID" => "Event ID",
            "event_name" => "Event",
            "event_date" => "Date",
            "event_location" => "Location",
            "event_meet_time" => "Meet Time",
            "event_start_time" => "Start Time",
            "event_description" => "Event Description",
            "team_ID" => "Team ID",
            "event_type_ID" => "Event Type ID",
            "event_type_name" => "Event Type",
            "event_type_description" => "Description",
            "event_gender_restriction" => "Gender Restriction",
            "min_age" => "Min Age",
            "max_age" => "Max Age",
            "guardianship_ID" => "Guardianship ID",
            "parent_ID" => "Parent ID",
            "child_ID" => "Child ID",
            "valid" => "Valid?", 
            "member_ID" => "Member ID",
            "member_fname" => "First Name",
            "member_lname" => "Last Name",
            "member_DOB" => "Date of Birth",
            "member_gender" => "Gender",
            "member_email" => "Email",
            "member_whole_name" => "Name",
            "member_password" => "Password",
            "admin" => "Admin?",
            "participant_ID" => "Participant ID",
            "role_ID" => "Role ID",
            "role_name" => "Role",
            "team_name" => "Team",
            "team_nickname" => "Nickname",
            "team_member_ID" => "Team Member ID"
        ];

        /**
         * Creates a Query instance.
         * 
         * Fetches the database connection object, and calls the 'execute_query' method using the provided SQL query and optional parameters.
         * 
         * @param string $sql The SQL query to be executed.
         * @param array $params An array of parameters to be used in the prepared statement. Defaults to an empty array.
         * @param string $param_types A string indicating the type of each value in the $params array. 's' for string, 'i' for int. Note: more complex numeral values, such as dates should be of type string; and boolean values should of type int (0/1). Defaults to an empty string.
         */
        public function __construct(string $sql, array $params = [], string $param_types = "")
        {
            try
            {
                $this->database_connection = Database_Connection::get_instance();

                $this->execute_query($sql, $params, $param_types);
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
            }
        }

        /** 
         * Executes a given SQL query with provided parameters and types.
         * 
         * @param string $sql The SQL query to be executed.
         * @param array $params An array of parameters to be used in the prepared statement. Defaults to an empty array.
         * @param string $param_types A string indicating the type of each value in the $params array. 's' for string, 'i' for int. Note: more complex numeral values, such as dates should be of type string; and boolean values should of type int (0/1). Defaults to an empty string.
         */
        private function execute_query(string $sql, array $params, string $param_types)
        {
            try
            {
                if ($this->query_success == false)
                {

                    $this->query = $this->database_connection->prepare($sql);

                    if ($this->query != false)
                    {
                        if (count($params) > 0)
                        {
                            if (substr_count($sql, "?") == count($params))
                            {
                                //Splat operator '...' splits array into individual function params
                                $this->query->bind_param($param_types, ...$params);
                            }
                            else
                            {
                                throw new System_Error(0, "Number of expected params did not match number of params passed in array.", __LINE__);
                            }
                        }

                        //Execute query
                        $this->query->execute();

                        //Code will not reach this point if the query failed (try...catch)
                        $this->query_success = true;
                        //Set object result property to query result
                        $this->result = $this->query->get_result();
                    }
                }
                else
                {
                    //throw new System_Error manually throws an error to try...catch - this will be logged to the database
                    throw new System_Error(0, "Query has already been executed.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
            }
        }

        /**
         * Retrieves an English heading corresponding to a database fieldname. Uses FIELD_HEADINGS array.
         * 
         * @param string $fieldname The database fieldname for which the heading should be retrieved.
         * 
         * @return string The corresponding heading if the fieldname exists in the FIELD_HEADING array. Otherwise, the original $fieldname parameter is returned.
         */
        private function get_heading_from_fieldname(string $fieldname)
        {
            try
            {
                //If $fieldname is a key in Query::FIELD_HEADINGS
                if (array_key_exists($fieldname, Query::FIELD_HEADINGS))
                {
                    return Query::FIELD_HEADINGS[$fieldname];
                }
                else
                {
                    throw new System_Error(0, "fieldname passed into get_heading_from_fieldname() not found in array FIELD_HEADINGS.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return $fieldname;
            }
        }

        //Get result methods

        /**
         * Retrieves the result of the executed SQL query as a mysqli_result object.
         * 
         * @return mysqli_result|null The query result if the query was successful. Otherwise, returns null.
         */
        public function get_result_as_plain()
        {
            try
            {
                if ($this->query_success)
                {
                    return $this->result;
                }
                else
                {
                    throw new System_Error(0, "get_result_as_plain() attempted on failed SQL query.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        /**
         * Retrieves the result of the executed SQL query as an associative array.
         * 
         * @return array|null The query result as an associative array the query it was successful. Otherwise, returns null.
         */
        public function get_result_as_assoc_array()
        {
            try
            {
                if ($this->query_success)
                {
                    $assoc_array = $this->result->fetch_all(MYSQLI_ASSOC);

                    //Reset result pointer after fetching so a get_result...() method can be called more than once on one query.
                    $this->result->data_seek(0);

                    return $assoc_array;
                }
                else
                {
                    throw new System_Error(0, "get_result_as_assoc_array() attempted on failed SQL query.", __LINE__);
                } 
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        /**
         * Retrieves the result of the executed SQL query as an indexed array.
         * 
         * @return array|null The query result as an indexed array if the query was successful. Otherwise, returns null.
         */
        public function get_result_as_indexed_array()
        {
            try
            {
                if ($this->query_success)
                {
                    $indexed_array = $this->result->fetch_all(MYSQLI_NUM);

                    //Reset result pointer after fetching so a get_result...() method can be called more than once on one query.
                    $this->result->data_seek(0);

                    return $indexed_array;
                }
                else
                {
                    throw new System_Error(0, "get_result_as_indexed_array() attempted on failed SQL query.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        /**
         * Retrieves the result of the executed SQL query as a string.
         * 
         * @return string|null The query result as a string if the query was successful. Otherwise, returns null.
         */
        public function get_result_as_string()
        {
            try
            {
                if ($this->query_success)
                {
                    // Variable which will contain string to return.
                    $result_string = "";

                    if ($row_count = $this->result->num_rows)
                    {
                        if ($row_count > 0)
                        {
                            $fields = $this->result->fetch_fields();

                            while ($row = $this->result->fetch_assoc()) {
                                $data_row = "";
                                foreach ($fields as $field) {
                                    $data_row .= sprintf("%-20s", $row[$field->name]);
                                }
                            }
                        }

                        //Reset result pointer after fetching so a get_result...() method can be called more than once on one query.
                        $this->result->data_seek(0);

                        if (strlen($result_string) > 0)
                        {
                            return $result_string;
                        }
                        else
                        {
                            return null;
                        }
                    }
                    else
                    {
                        throw new System_Error(0, "get_result_as_string() attempted on query containing null result.", __LINE__);
                    }
                }
                else
                {
                    throw new System_Error(0, "get_result_as_string() attempted on failed SQL query.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        /**
         * Retrieves the result of the executed SQL query as HTML table code. If you are including a checkbox field, the first column of data must contain the relevant ID for the row, and the second must be a boolean value for the checkbox.
         * 
         * @param string|null $checkbox_heading The heading for a checkbox field within the table. If left null, no checkbox field will be added.
         * @param string|null $click_action The action which will be performed on checkbox click (only included if $checkbox_heading is not null).
         * @param string|null $sum Boolean flag stating whether a counter for how many checkboxes have been selected should be included below the table (only included if $checkbox_heading is not null).
         * 
         * @return string|null The query result as HTML table code if the query successful. Otherwise, returns null.
         */
        public function get_result_as_HTML_table(string $click_action = null, string $checkbox_heading = null, bool $sum = false)
        {
            try
            {
                // Only attempt if query was successful
                if ($this->query_success)
                {
                    // Variable which will contain HTML to return.
                    $HTML_table = "";

                    // Fetch number of rows in $this->result
                    if ($this->result->num_rows)
                    {
                        $row_count = $this->result->num_rows;

                        // fetch_fields() returns an array of objects, containing info about each field
                        $fields = $this->result->fetch_fields();

                        $HTML_table .= '<table class="table table-striped table-bordered">';
                        $HTML_table .= '<thead>';
                        $HTML_table .= '<tr>';

                        // If there is a checkbox field, add a heading for the column.
                        if ($checkbox_heading != null)
                        {
                            $HTML_table .= "<th scope='col'>$checkbox_heading</th>";

                            // Including a checkbox field removes the first column which should contain a database ID
                            // Therefore when the data is written into the table, it should be offset by two columns
                            $offset = 2;

                            // Variable which stores the number of rows in the table data (body)
                            // This is used for checkbox HTML IDs
                            $row_count = 0;

                            // Variable which stores the number of pre-checked rows in the table data
                            $checked_count = 0;
                        }
                        else
                        {
                            // If there is no checkbox, there is no offset for the table data
                            $offset = 0;
                        }

                        // Loop through the table fields (headings), and write them into the HTML code
                        for ($x = 0; $x < count($fields); $x++)
                        {
                            if (!($x < $offset))
                            {
                                $heading = $this->get_heading_from_fieldname($fields[$x]->name);

                                $HTML_table .= '<th scope="col">' . $heading . '</th>';
                            }
                        }

                        $HTML_table .= '</thead>';
                        $HTML_table .= '</tr>';

                        $HTML_table .= '<tbody>';

                        while ($row = $this->result->fetch_object()) 
                        {
                            $row_count++;

                            $HTML_table .= '<tr>';

                            // If there is a checkbox field, a checkbox is written to the table HTML
                            if ($checkbox_heading != null) 
                            {
                                // The checkbox ID in the database
                                $ID = $row->{$fields[0]->name};
                                // This is encrypted to hid the real value from the end user (security)
                                $encrypted_ID = System_Utility::encrypt($ID);

                                // If the checkbox boolean value in the database is already true, it needs to show as checked when the page loads
                                if ($row->{$fields[1]->name} == 1)
                                {
                                    $is_checked = 'checked';
                                    $checked_count++;
                                }
                                else
                                {
                                    $is_checked = '';
                                }

                                //Checkbox which has its own ID (containing row on table), and encrypted database ID
                                $HTML_table .= "<td class='text-center align-middle'><div class='form-check d-flex justify-content-center'><input class='form-check-input' onchange='$click_action' type='checkbox' id='row_$row_count' db_ID='$encrypted_ID' $is_checked></div></td>";
                            }

                            // If there is a checkbox, the first two columns contain the ID for the checkbox, and its value
                            // These should not be included in the table, so are skipped (if there is a checkbox)
                            $skip_fields = ($checkbox_heading != null) ? 2 : 0;

                            // Runs through the rest of the table row, adding the data
                            // If $skip_fields = 2, the first two fields are removed from the array
                            foreach (array_slice($fields, $skip_fields) as $field) 
                            {
                                $HTML_table .= '<td>' . $row->{$field->name} . '</td>';
                            }

                            $HTML_table .= '</tr>';
                        }

                        $HTML_table .= '</tbody>';

                        $HTML_table .= '</table>';

                        // If the sum boolean flag (param) is true, output the total number of checked fields
                        // This has its own ID, so it can be updated using JS
                        if ($sum)
                        {
                            $HTML_table .= "<h6 class='fw-bold'>Total selected: <span class='fw-normal' id='table-selected-sum'>$checked_count</span></h6>";
                        }

                        //Reset result pointer after fetching so a get_result...() method can be called more than once on one query.
                        $this->result->data_seek(0);

                        return $HTML_table;
                    }
                }
                else
                {
                    throw new System_Error(0, "get_result_as_HTML_table() attempted on failed SQL query.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        /**
         * Retrieves the result of an executed SQL query (event) as HTML feed code.
         * 
         * @param array $team_admins Array of teams which the current user is an admin of. A select team button will be shown on events which users are an admin of.
         * 
         * @return string|null The query result as HTML feed code if the query successful. Otherwise, returns null.
         */
        public function get_result_as_HTML_feed(array $team_admins = [])
        {
            try 
            {
                // Only run if the SQL query was successful
                if ($this->query_success) 
                {
                    // Ensure the SQL query returned some rows of data
                    if (!($this->check_null_result()))
                    {
                        // Get the number of rows in the query result
                        $row_count = $this->result->num_rows;
                        // Get the names of the columns in the query result
                        $result_info = $this->result->fetch_fields();

                        // Initialize an empty array to store field names
                        $fields = [];

                        // Decrypt the team_IDs in the $team_admins array
                        foreach ($team_admins as &$team)
                        {
                            $team = System_Utility::decrypt($team['team_ID']);
                        }

                        // Store field names in the $fields array
                        for ($x = 0; $x < count($result_info); $x++)
                        {
                            array_push($fields, $result_info[$x]->name);
                        }

                        // Required fields for the HTML feed
                        $req_fields = ["availability_ID", "team_name", "event_name", "event_type_name", "event_date", "event_meet_time", "event_start_time", "event_location", "event_description", "available", "member_whole_name", "member_ID", "team_ID", "event_ID"];

                        // Determine whether there are any differences between the columns provided in the SQL result and the columns required to generate a feed item
                        $array_diff = array_diff($req_fields, $fields);

                        // Check if all requires fields are present in the query result
                        if (count($array_diff) == 0)
                        {
                            if ($row_count > 0) 
                            {
                                // Convert the query result to an associative array
                                $result_assoc_array = self::get_result_as_assoc_array();
                                // Initialize an empty array to store feed items
                                $feed_items = [];

                                // Loop through each row in the query result
                                for ($item_index = 0; $item_index < $row_count; $item_index++)
                                {
                                    // Generate an HTML feed item for the current row
                                    $HTML = $this->generate_HTML_feed_item($item_index, $result_assoc_array[$item_index], $team_admins);

                                    // Check if the generated HTML feed item is not null
                                    if ($HTML != null)
                                    {
                                        // Add the generated HTML feed item to the feed_items array
                                        array_push($feed_items, $HTML);
                                    }
                                } 

                                // Combine all HTML feed items into a single string
                                $HTML_feed = implode("", $feed_items);

                                // Reset the result pointer to the first row so another get_result_as...() method can be called.
                                $this->result->data_seek(0);

                                // Return the combined HTML feed
                                return $HTML_feed;
                            }
                        }
                    }
                    else
                    {
                        $system = Query_Client::get_system_instance();

                        return '<div class="container d-flex align-items-center" style="max-width: 600px; height: 100vh;">
                        <div class="shadow-lg p-3 m-4 bg-white rounded">
                        <div class="row">
                            <div class="col d-flex justify-content-center m-4">
                                <div class="d-flex align-items-start me-3 mt-0">
                                    <img src="https://wyvernsite.net/sebMurray/system/assets/grimacing.png" alt="Description"
                                        style="width: auto; height: 6vh;">
                                </div>
                                <div class="d-flex flex-column align-items-center">
                                    <div class="text-center text-part-1">
                                        <h1 class="fw-bold text-dark mb-0"
                                            style="line-height: 1.2em; font-size: 5vh;">Sorry!</h1>
                                    </div>
                                </div>
                            </div>
                        </div>
                          <p class="text-center m-4 mt-2 text-muted fs-4 fw-normal lh-sm">When a club admin adds you to a team, upcoming events will show here.</p>
                        </div>
                      </div>';
                    }
                }
            } 
            catch (Throwable $error) 
            {
                new Error_Handler($error);
                return null;
            }
        }

        /**
         * Generates an HTML feed item for a single event using the provided event data.
         *
         * @param int $item_index The index of the current event item.
         * @param array $feed_data The event data as an associative array.
         * @param array $team_admins Array of team IDs for which the current user is an admin.
         *
         * @return string|null The generated HTML feed item if successful. Otherwise, returns null.
         */
        private function generate_HTML_feed_item(int $item_index, array $feed_data, array $team_admins)
        {
            try 
            {
                // Encrypt availability_ID, event_ID, and team_ID for security purposes
                $encrypted_availability_ID = System_Utility::encrypt($feed_data["availability_ID"]);
                $encrypted_event_ID = System_Utility::encrypt($feed_data["event_ID"]);
                $encrypted_team_ID = System_Utility::encrypt($feed_data["team_ID"]);

                // Extract rest of event data from the $feed_data array
                $event_name = $feed_data["event_name"];
                $event_type_name = $feed_data["event_type_name"];

                $date_time = DateTime::createFromFormat('d/m/Y', $feed_data["event_date"]);
                $event_date = System_Utility::get_date_as_string($date_time);

                $event_meet_time = System_Utility::get_meet_time_as_string($feed_data["event_meet_time"]);
                $event_start_time = System_Utility::get_start_time_as_string($feed_data["event_start_time"]);
                $event_location = $feed_data["event_location"];
                $event_description = $feed_data["event_description"];
                $member_whole_name = $feed_data["member_whole_name"];
                $team_name = $feed_data["team_name"];
                $member_ID = $feed_data["member_ID"];
                
                // Determine the availability status and corresponding label
                switch ($feed_data["available"])
                {
                    case 1:
                        $available = "<input class='form-check-input availability-switch me-2' type='checkbox' role='switch' id='available_switch_$item_index' availability_ID='$encrypted_availability_ID' onclick='update_availability(event)' checked>";
                        $available_label = "Available";
                        break;
                    default:
                        $available = "<input class='form-check-input availability-switch me-2' type='checkbox' role='switch' id='available_switch_$item_index' availability_ID='$encrypted_availability_ID' onclick='update_availability(event)'>";
                        $available_label = "Not available";
                        break;
                }

                // Begin constructing the HTML feed item
                $feed_item_HTML = "<div class='feed-item' id='feed_item_$item_index' availability_ID='$encrypted_availability_ID' team_ID='$encrypted_team_ID'>";
                
                // Add event details to the HTML feed item
                $feed_item_HTML .= "<h3 class='mb-0' id='event_name_$item_index' availability_ID='$encrypted_availability_ID'>$event_name</h3>";
                $feed_item_HTML .= "<p id='event_type_name_$item_index' availability_ID='$encrypted_availability_ID' class='text-muted mb-3'>$event_type_name</p>";

                $feed_item_HTML .= "<div class='d-flex align-items-center icon-info'>";
                $feed_item_HTML .= "<img src='assets/calendar.png' alt='Time icon' class='icon'>";
                $feed_item_HTML .= "<div class='icon-text'>";
                $feed_item_HTML .= "<h6 id='event_date_$item_index' availability_ID='$encrypted_availability_ID'>$event_date</h6>";
                $feed_item_HTML .= "</div>";
                $feed_item_HTML .= "</div>";

                $feed_item_HTML .= "<div class='d-flex align-items-center icon-info'>";
                $feed_item_HTML .= "<img src='assets/time-clock.png' alt='Time icon' class='icon'>";
                $feed_item_HTML .= "<div class='icon-text'>";
                $feed_item_HTML .= "<h6 id='event_meet_time_$item_index' availability_ID='$encrypted_availability_ID' class='mb-0'>$event_meet_time</h6>";
                $feed_item_HTML .= "<p id='event_start_time_$item_index' availability_ID='$encrypted_availability_ID' class='text-muted mb-0'>$event_start_time</p>";
                $feed_item_HTML .= "</div>";
                $feed_item_HTML .= "</div>";

                $feed_item_HTML .= "<div class='d-flex align-items-center icon-info'>";
                $feed_item_HTML .= "<img src='assets/location-pin.png' alt='Time icon' class='icon'>";
                $feed_item_HTML .= "<div class='icon-text'>";
                $feed_item_HTML .= "<h6 id='event_location_$item_index' availability_ID='$encrypted_availability_ID'>$event_location</h6>";
                $feed_item_HTML .= "</div>";
                $feed_item_HTML .= "</div>";

                $feed_item_HTML .= "<p id='event_description_$item_index' availability_ID='$encrypted_availability_ID' class='text-muted'>$event_description</p>";

                $feed_item_HTML .= "<div class='form-check form-switch d-flex align-items-center availability-container mb-3'>";
                $feed_item_HTML .= $available;
                $feed_item_HTML .= "<label class='form-check-label mb-0 availability-label' for='available_switch_$item_index' id='label_available_switch_$item_index' availability_ID='$encrypted_availability_ID'>$available_label</label>";
                $feed_item_HTML .= "</div>";

                $user = Query_Client::get_user_instance($member_ID);

                $read_team = Availability::read_participants_from_event($user, $feed_data["event_ID"]);
                $read_team_assoc = $read_team->get_result_as_assoc_array();

                // Check if the event participants list is not empty
                if (!$read_team->check_null_result())
                {
                    // Add a div to display participants
                    $feed_item_HTML .= "<div class='mb-4 mt-4'>";
                    $feed_item_HTML .= "<h6>Team</h6>";
                    $feed_item_HTML .= "<ul class='list-group'>";

                    // Initialize the player count for displaying player numbers
                    $player_count = 1;

                    // Iterate through the participants and add them to the feed item
                    foreach ($read_team_assoc as $player)
                    {
                        $name = $player['member_whole_name'];

                        // Add the player's name with the player count as a prefix
                        $feed_item_HTML .= "<li class='list-group-item d-flex align-items-start'><span class='mr-2 fw-semibold' style='width: 1.5em;'>$player_count.</span>$name</li>";
                        $player_count++;
                    }

                    $feed_item_HTML .= "</ul>";
                    $feed_item_HTML .= "</div>";
                }

                // Add the member's whole name to the feed item
                $feed_item_HTML .= "<h6 id='member_whole_name_$item_index' availability_ID='$encrypted_availability_ID' class='mt-2 mb-0'>$member_whole_name</h6>";
                // Add the team name to the feed item
                $feed_item_HTML .= "<p id='team_name_$item_index' availability_ID='$encrypted_availability_ID' class='text-muted mb-0'>$team_name</p>";

                // Check if the current user is a team admin
                if (in_array($feed_data['team_ID'], $team_admins))
                {
                    // Add a button for the admin to select a team
                    $feed_item_HTML .= "<button onclick='select_team(event)' id='select_team_$item_index' event_ID='$encrypted_event_ID' class='btn btn-primary w-100 mb-1 mt-3 fw-semibold fs-6'>Select team</button>";
                }

                $feed_item_HTML .= "</div>";

                // Return the generated HTML feed item
                return $feed_item_HTML;
            }
            catch (Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        
        /**
         * Checks whether the executed SQL query returned any rows.
         * 
         * @return bool|null False if there are any rows in the result; true if the result is empty; null in the case of an error within the function.
         */
        public function check_null_result()
        {
            try
            {
                // Check if the number of rows in the result is greater than 0
                if ($this->result->num_rows)
                {
                    // If there are rows in the result, return false (not a null result)
                    return false;                   
                }
                else
                {
                    // If there are no rows in the result, return true (null result)
                    return true;
                }
            }
            catch(Throwable $error)
            {
                // Handle the error and return null
                new Error_Handler($error);
                return null;
            }
        }

        /**
         * Checks if the SQL query execution was successful or not.
         *
         * @return bool|null True if the query was successful; false if it was not successful; null in case of an error.
         */
        public function check_query_success()
        {
            try
            {
                // Check the value of the query_success property
                switch ($this->query_success)
                {
                    case true:
                        // If the query was successful, return true
                        return true;
                    default:
                        // If the query was unsuccessful, return false
                        return false;
                }
            }
            catch (Throwable $error)
            {
                // Handle the error and return null
                new Error_Handler($error);
                return null;
            }
        }

        
    }

    /**
     * Error_Handler class to handle various types of errors; either logs them to the database, or displays on page (specific to error);
     */
    class Error_Handler
    {
        /**
         * @var Throwable
         */
        private $error;
        /**
         * @var string
         */
        private $error_type;
        /**
         * @var int
         */
        private $error_code;
        /**
         * @var string
         */
        private $error_message;
        /**
         * @var int
         */
        private $error_line;
        /**
         * @var string|null
         */
        private $error_file;

        /**
         * Constructs a new Error_Handler object.
         *
         * @param Throwable $error The error object to handle.
         * @param bool $error_fail Indicates if the error should be displayed immediately. This should only be called if an error occurs when logging an error to the database within the Error_Handler class. Defaults to false.
         */
        public function __construct(Throwable $error, bool $error_fail = false)
        {
            // Assign the error object
            $this->error = $error;

            // Extract properties from error object
            $this->error_message = $error->getMessage();
            $this->error_code = $error->getCode();
            $this->error_type = get_class($error);
            $this->error_line = null;
            $this->error_file = null;

            // Handling different types of errors
            switch (true)
            {
                // If $error_fail is true, display the error immediately
                case $error_fail:
                    $this->display_error();
                    
                case $this->error instanceof mysqli_sql_exception:
                    $this->error_line = $error->getLine();
                    // Handling different MySQL error codes
                    switch ($this->error_code)
                    {
                        case 1044:
                            $this->display_error();
                            break;
                        case 1045:
                            $this->display_error();
                            break;
                        case 1049:
                            $this->display_error();
                            break;
                        case 2002:
                            $this->display_error();
                            break;
                        case 2003:
                            $this->display_error();
                            break;
                        case 2006:
                            $this->display_error();
                            break;
                        case 2013:
                            $this->display_error();
                            break;
                        case 2054:
                            $this->display_error();
                            break;
                        default:
                            $this->insert_error_to_db();
                            break;            
                    }

                    break;

                case $this->error instanceof Error:
                    $this->error_type = "PHP_Error";
                    $this->error_file = $error->getFile();
                    $this->error_line = $error->getLine();
                    $this->insert_error_to_db();
                    break;

                case $this->error instanceof System_Error:
                    $this->error_line = $error->getLine();
                    $this->insert_error_to_db();
                    break;

                case $this->error instanceof Clientside_Error:
                    $this->error_line = $error->getLine();
                    $this->error_file = $error->getFile();
                    $this->insert_error_to_db();
                    break;

                // Insert the error into the database for unknown types of error
                // If insertion fails, this will be displayed
                default:
                    $this->error_line = $error->getLine();
                    $this->error_file = $error->getFile();
                    $this->insert_error_to_db();
                    break;
            }
        }

        /**
         * Inserts the error information into the database using a prepared statement to prevent SQL injection.
         * 
         * This uses seperate code to the Query class, incase of errors in that code.
         */
        private function insert_error_to_db()
        {
            try
            {
                // Connect to the database using the defined credentials (Database_Credentials class)
                $connection = new mysqli(Database_Credentials::SERVERNAME, Database_Credentials::USERNAME, Database_Credentials::PASSWORD, Database_Credentials::DATABASE);

                // Prepare the SQL query to insert the error information into the database
                $sql = 
                "INSERT INTO `ERRORS`
                (`error_type`, `error_code`, `error_message`, `error_line`, `error_time`, `error_file`) 
                VALUES (?, ?, ?, ?, NOW(), ?);";

                $query = $connection->prepare($sql);

                // Prepare the parameters for the query
                $params = [$this->error_type, $this->error_code, $this->error_message, $this->error_line, $this->error_file];
                $param_types = "sssis";

                // Bind the parameters to the query and execute it
                $query->bind_param($param_types, ...$params);
                $query->execute();
            }
            catch(Throwable $error)
            {
                // If an error occurs while displaying the error, display straight away (don't log to DB).
                new Error_Handler($error, true);
            }
        }

        /**
         * Displays the error information in a comma-separated format. This is not ideal, but is used as last-resort.
         */
        private function display_error()
        {
            try
            {
                echo implode(", ", [$this->error_code, $this->error_message, $this->error_type, $this->error_line]);
            }
            catch(Throwable $error)
            {
                new Error_Handler($error, true);
            }
        }
    }

    /**
     * Class System_Error extends the built-in Exception class.
     * 
     * This class is used to represent warnings or issues in the application.
     * 
     * The parent constructor does not have line attribute, so it is set manually.
     * 
     * It is thrown manually in a try...catch block.
     */
    class System_Error extends Exception
    {
        /**
         * @var int
         */
        protected $line;

        /**
         * Constructs a new System_Error object.
         *
         * @param int $error_code The error code.
         * @param string $error_message The error message.
         * @param int $error_line The line number where the error occurred.
         */
        public function __construct(int $error_code, string $error_message, int $error_line)
        {
            // Call the parent constructor to set the error code and message
            parent::__construct($error_message, $error_code);

            // Set the error line
            $this->line = $error_line;
        }
    }

    /**
     * Class PHP_Error extends the built-in Exception class.
     * 
     * This class is used to represent PHP errors in the application.
     * 
     * It is specifically designed to be used with the set_custom_error_handler() PHP function.
     * 
     * The parent constructor does not have line and file attributes, so they are set manually.
     */
    class PHP_Error extends Exception
    {
        /**
         * @var int
         */
        protected $line;
        /**
         * @var string
         */
        protected $file;

        /**
         * Constructs a new PHP_Error object.
         *
         * @param string $error_code The error code as a string. This is how it is represented PHP-side.
         * @param string $error_message The error message.
         * @param int $error_line The line number where the error occurred.
         * @param string $error_file The file where the error occurred.
         */
        public function __construct(string $error_code, string $error_message, int $error_line, string $error_file)
        {
            // Call the parent constructor to set the error code and message
            // Convert the error code to an integer
            parent::__construct($error_message, intval($error_code));

            // Set the error line and file
            $this->line = $error_line;
            $this->file = $error_file;
        }
    }

    /**
     * Class Clientside_Error extends the built-in Exception class.
     * 
     * This class is used to represent client-side errors in the application.
     * 
     * The errors are caught by JavaScript and then logged by PHP.
     * 
     * The parent constructor does not have line and file attributes, so they are set manually.
     */
    class Clientside_Error extends Exception
    {
        /**
         * @var int
         */
        protected $line;
        /**
         * @var string
         */
        protected $file;

        /**
         * Constructs a new Clientside_Error object.
         *
         * @param string $error_code The error code as a string.
         * @param string $error_message The error message.
         * @param int $error_line The line number where the error occurred.
         * @param string $error_file The file where the error occurred.
         */
        public function __construct(string $error_code, string $error_message, int $error_line, string $error_file)
        {
            // Call the parent constructor to set the error code and message
            // Convert the error code to an integer
            parent::__construct($error_message, intval($error_code));

            // Set the error line and file
            $this->line = $error_line;
            $this->file = $error_file;
        }
    }

/**
 *
 */
class Client_Type
    {
    /**
     *
     */
    const USER = "User";
    /**
     *
     */
    const SYSTEM = "System";
    }

    /**
     * Class Query_Client represents a client performing an SQL query.
     * 
     * The client can either be the system or a user.
     * 
     * The class is used for identity checking, restricting query results or actions based on the client type.
     * 
     * It ensures the proper client type is used for each query, and implements a singleton pattern for system and user instances.
     */
    class Query_Client
    {
        // Client type (either system or user)
        /**
         * @var string|null
         */
        private $client_type;
        
        // member_ID, only used for user clients
        /**
         * @var int|null
         */
        private $member_ID = null;

        // club_ID, only used for user clients
        /**
         * @var mixed|null
         */
        private $club_ID = null;

        // Singleton instances of system and user clients
        /**
         * @var null
         */
        private static $system_instance = null;
        /**
         * @var null
         */
        private static $user_instance = null;

        /**
         * Private constructor to ensure only the static singleton methods for getting instances are used.
         * 
         * Sets the client type and initializes the member_ID and club_ID if the client type is USER.
         *
         * @param string|null $client_type The type of client (SYSTEM or USER).
         * @param int|null $member_ID The member ID for the user client (only applicable for user clients).
         */
        private function __construct($client_type, $member_ID)
        {
            try
            {
                // Set client type attribute from param
                $this->client_type = $client_type;

                // If the client type is USER, validate and set the member_ID and club_ID
                if ($this->client_type == Client_Type::USER)
                {
                    if ($member_ID != null)
                    {
                        // Set the member_ID for the user client
                        $this->member_ID = $member_ID;

                        // Retrieve the club_ID associated with the member_ID
                        $club_ID = Clubs::read_club_from_member(Query_Client::get_system_instance(), $this->member_ID);

                        // Check if the club_ID was found and set it, otherwise throw an error
                        if ($club_ID->check_null_result())
                        {
                            throw new System_Error(0, "club_ID not found from member_ID", __LINE__);
                        }
                        else
                        {
                            $this->club_ID = $club_ID->get_result_as_indexed_array()[0][0];
                        }
                    }
                    else
                    {
                        throw new System_Error(0, "member_ID not provided for Query_Client of type USER", __LINE__);
                    }
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                $this->__destruct();
            }
        }


        /**
         * Destructor, used to clean up resources when the object is destroyed.
         */
        public function __destruct(){}

        /**
         * Returns a singleton instance of the system client.
         *
         * @return Query_Client|null The singleton instance of the system client; null if error occurs.
         */
        public static function get_system_instance()
        {
            try
            {
                //Ensure user cannot get_system_instance() via injection

                if (self::$system_instance == null)
                {
                    self::$system_instance = new Query_Client(Client_Type::SYSTEM, null);
                }

                return self::$system_instance;
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        /**
         * @param int $member_ID
         * @return Query_Client|null
         */
        public static function get_user_instance(int $member_ID)
        {
            // Get a singleton instance of the system client
            $system = Query_Client::get_system_instance();


            try
            {
                // If a user instance doesn't exist yet, create one
                if (self::$user_instance == null)
                {
                    // Check that the member ID exists in the MEMBERS table
                    if (Validation::check_member_ID_exists($member_ID))
                    {
                        // Create a new user instance and return it
                        self::$user_instance = new Query_Client(Client_Type::USER, $member_ID);
                        return self::$user_instance;
                    }
                    else
                    {
                        // Throw an error if the member ID does not exist
                        throw new System_Error(0, "Query_Client->member_ID not found in MEMBERS table.", __LINE__);
                    }
                }
                else
                {
                    // Get an array of children from the parent user instance (this can be empty)
                    $children = Guardianships::read_children_from_parent($system, self::$user_instance->get_member_ID())->get_result_as_assoc_array();

                    // Get only the child IDs from the array of children
                    foreach ($children as &$child)
                    {
                        $child = $child["child_ID"];
                    }

                    // If the member ID is the same as the current user instance or is a child of the parent user instance, return the user instance
                    if (($member_ID == self::$user_instance->get_member_ID()) or (in_array($member_ID, $children)))
                    {
                        return self::$user_instance;
                    }
                    else
                    {
                        // Throw an error if the member ID passed in does not match the current user instance or its children
                        throw new System_Error(0, "member_ID passed to get_user_instance doesn't match current USER instance.", __LINE__);
                    }
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        /**
         * @return string|null
         */
        public function get_client_type()
        {
            try
            {
                // Return the value of the client_type property of the object instance
                return $this->client_type;
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        /**
         * @return int|null
         */
        public function get_member_ID()
        {
            try
            {
                // Return the value of the member_ID property of the object instance
                return $this->member_ID;
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        /**
         * @return mixed|null
         */
        public function get_club_ID()
        {
            try
            {
                // Return the value of the club_ID property of the object instance
                return $this->club_ID;
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }
    }

    //CRUD operations for each database class

    /** 
     * This class provides static methods to create, read, update and delete availability records in the database. 
     */
    class Availability
    {
        // Start of SQL query used for selecting availability records as Client_Type::USER
        // This is for consistency across methods, so the same fields are retrieved
        /**
         *
         */
        const USER_READ_SQL =
            "SELECT AVAILABILITY.availability_ID, AVAILABILITY.participating, 
            CONCAT(MEMBERS.member_fname, ' ', MEMBERS.member_lname) AS member_whole_name,     
                TEAMS.team_name, EVENTS.event_name,  
            CONCAT(DATE_FORMAT(event_date, '%d'), '/',
                DATE_FORMAT(event_date, '%m'), '/',
                DATE_FORMAT(event_date, '%Y')) AS event_date, 
            EVENTS.event_location, 
            CONCAT(DATE_FORMAT(event_meet_time, '%H'), ':',
                DATE_FORMAT(event_meet_time, '%i')) AS event_meet_time,
            CONCAT(DATE_FORMAT(event_start_time, '%H'), ':',
                    DATE_FORMAT(event_start_time, '%i')) AS event_start_time 
            FROM `AVAILABILITY` 
            INNER JOIN `TEAM_MEMBERS` 
                ON AVAILABILITY.team_member_ID = TEAM_MEMBERS.team_member_ID 
            INNER JOIN `MEMBERS` 
                ON TEAM_MEMBERS.member_ID = MEMBERS.member_ID 
            INNER JOIN `EVENTS` 
                ON AVAILABILITY.event_ID = EVENTS.event_ID 
            INNER JOIN `TEAMS` 
                ON EVENTS.team_ID = TEAMS.team_ID 
            INNER JOIN `CLUBS` 
                ON TEAMS.club_ID = CLUBS.club_ID 
            INNER JOIN `EVENT_TYPES` 
                ON EVENTS.event_type_ID = EVENT_TYPES.event_type_ID ";

        /**
         * Creates an availability record for a team member for a given event.
         *
         * @param Query_Client $client The client object used to connect to the database.
         * @param int $team_member_ID
         * @param int $event_ID
         * @param int $available
         * @return Query|null
         */
        public static function create_availability(Query_Client $client, int $team_member_ID, int $event_ID, int $available)
        {
            try
            {
                // Check client type
                if ($client->get_client_type() == Client_Type::USER)
                {
                    //Check if team_member_ID belongs to the $client
                    //If not return null;
                    
                    $sql = 
                        "INSERT INTO `AVAILABILITY` 
                        (`team_member_ID`, `event_ID`, `available`) 
                        VALUES (?, ?, ?);";

                    $params = [$team_member_ID, $event_ID, $available];
                    $param_types = "iii";

                    // Create and return query object
                    $create_availability = new Query($sql, $params, $param_types);
                    return $create_availability;
                }
                elseif ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "INSERT INTO `AVAILABILITY` 
                        (`team_member_ID`, `event_ID`, `available`) 
                        VALUES (?, ?, ?);";

                    $params = [$team_member_ID, $event_ID, $available];
                    $param_types = "iii";

                    // Create and return query object
                    $create_availability = new Query($sql, $params, $param_types);
                    return $create_availability;
                }
                else
                {
                    // Throw error if client type is unrecognized
                    throw new System_Error(0, "Query_Client passed as arg to create_availability() has unrecognised Client_Type.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        /**
         * Reads the availability of a team member for a specific event.
         *
         * @param Query_Client $client The client object used to connect to the database.
         * @param int $team_member_ID
         * @param int $event_ID
         * @return Query|null Returns the Query object if successful; if an error occurs returns null.
         */
        public static function read_availability(Query_Client $client, int $team_member_ID, int $event_ID)
        {
            try
            {
                // USER Client_Type
                if ($client->get_client_type() == Client_Type::USER)
                {
                    // Get the club ID of the client
                    $client_club_ID = $client->get_club_ID();

                    // Construct SQL query to read availability record and check that the club ID matches the client's club ID
                    $sql = 
                        self::USER_READ_SQL . 
                        "WHERE (AVAILABILITY.team_member_ID = ? AND AVAILABILITY.event_ID = ? AND CLUBS.club_ID = ?);";

                    $params = [$team_member_ID, $event_ID, $client_club_ID];
                    $param_types = "iii";

                    // Create and return query object
                    $read_availability = new Query($sql, $params, $param_types);
                    return $read_availability;
                }
                elseif ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "SELECT * 
                        FROM `AVAILABILITY` 
                        WHERE (team_member_ID = ? AND event_ID = ?);";

                    $params = [$team_member_ID, $event_ID];
                    $param_types = "ii";

                    $read_availability = new Query($sql, $params, $param_types);
                    return $read_availability;
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg to read_availability() has unrecognised Client_Type.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        /**
         * Update the availabile column of an availability record.
         *
         * @param Query_Client $client The client object used to connect to the database.
         * @param int $availability_ID
         * @param int $available
         * @return Query|null Returns the Query object if successful; if an error occurs returns null.
         */
        public static function update_availability(Query_Client $client, int $availability_ID, int $available)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::USER)
                {
                    $member_ID = $client->get_member_ID();

                    $sql = 
                        "UPDATE `AVAILABILITY` 
                        INNER JOIN `TEAM_MEMBERS` 
                            ON AVAILABILITY.team_member_ID = TEAM_MEMBERS.team_member_ID 
                        INNER JOIN `MEMBERS` 
                            ON TEAM_MEMBERS.member_ID = MEMBERS.member_ID 
                        SET `available` = ? 
                        WHERE (AVAILABILITY.availability_ID = ? AND MEMBERS.member_ID = ?);";

                    $params = [$available, $availability_ID, $member_ID];
                    $param_types = "iii";

                    $read_availability = new Query($sql, $params, $param_types);
                    return $read_availability;
                }
                elseif ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "UPDATE `AVAILABILITY`
                        SET `available` = ? 
                        WHERE (availability_ID = ?);";

                    $params = [$available, $availability_ID];
                    $param_types = "ii";

                    $read_availability = new Query($sql, $params, $param_types);
                    return $read_availability;
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg to update_availability() has unrecognised Client_Type.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        /**
         * Update the participating column of an availability record.
         *
         * @param Query_Client $client The client object used to connect to the database.
         * @param int $availability_ID availability_ID of availability record.
         * @param int $participating Boolean flag for 
         * @return Query|null Returns the Query object if successful; if an error occurs returns null.
         */
        public static function update_participation(Query_Client $client, int $availability_ID, int $participating)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::USER)
                {
                    $member_ID = $client->get_member_ID();

                    $sql = 
                        "UPDATE `AVAILABILITY` 
                        INNER JOIN `TEAM_MEMBERS` 
                            ON AVAILABILITY.team_member_ID = TEAM_MEMBERS.team_member_ID 
                        INNER JOIN `MEMBERS` 
                            ON TEAM_MEMBERS.member_ID = MEMBERS.member_ID 
                        SET `participating` = ? 
                        WHERE (AVAILABILITY.availability_ID = ? AND MEMBERS.member_ID = ?);";

                    $params = [$participating, $availability_ID, $member_ID];
                    $param_types = "iii";

                    $update_participation = new Query($sql, $params, $param_types);
                    return $update_participation;
                }
                elseif ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "UPDATE `AVAILABILITY`
                        SET `participating` = ? 
                        WHERE (availability_ID = ?);";

                    $params = [$participating, $availability_ID];
                    $param_types = "ii";

                    $update_participation = new Query($sql, $params, $param_types);
                    return $update_participation;
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg to update_availability() has unrecognised Client_Type.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        //delete_availability() does not exist, as once created an availability should not be removed.

        //Specialised SQL Functions

        //$is_available is used to filter to only available/unavailable teammembers
        /**
         * Reads the availabilities from the event for the given client.
         *
         * @param Query_Client $client
         * @param int $event_ID
         * @param int|null $is_available
         * @return Query|null
         * @throws System_Error
         */
        public static function read_availabilities_from_event(Query_Client $client, int $event_ID, int $is_available = null)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::USER)
                {
                    //Check if availability belongs to the client's club

                    $club_ID = $client->get_club_ID();

                    $sql = "   SELECT AVAILABILITY.availability_ID, AVAILABILITY.participating, 
                                CONCAT(MEMBERS.member_fname, ' ', MEMBERS.member_lname) AS member_whole_name 
                                FROM `AVAILABILITY` 
                                INNER JOIN `TEAM_MEMBERS` 
                                    ON AVAILABILITY.team_member_ID = TEAM_MEMBERS.team_member_ID 
                                INNER JOIN `MEMBERS` 
                                    ON TEAM_MEMBERS.member_ID = MEMBERS.member_ID 
                                INNER JOIN `EVENTS` 
                                    ON AVAILABILITY.event_ID = EVENTS.event_ID 
                                INNER JOIN `TEAMS` 
                                    ON EVENTS.team_ID = TEAMS.team_ID 
                                INNER JOIN `CLUBS` 
                                    ON TEAMS.club_ID = CLUBS.club_ID 
                                INNER JOIN `EVENT_TYPES` 
                                    ON EVENTS.event_type_ID = EVENT_TYPES.event_type_ID 
                                WHERE (AVAILABILITY.event_ID = ? AND CLUBS.club_ID = ? ";
                    
                    if ($is_available != null)
                    {
                        $sql .= "AND AVAILABILITY.available = ?);";

                        $params = [$event_ID, $club_ID, $is_available];
                        $param_types = "iii";
                    }
                    else
                    {
                        $sql .= ");";

                        $params = [$event_ID, $club_ID];
                        $param_types = "ii";
                    }

                    $read_availability = new Query($sql, $params, $param_types);
                    return $read_availability;
                }
                elseif ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "SELECT * 
                        FROM `AVAILABILITY` ";
                    
                    if ($is_available != null)
                    {
                        $sql .= "
                            WHERE (event_ID = ? AND available = ?);";

                        $params = [$event_ID, $is_available];
                        $param_types = "ii";
                    }
                    else
                    {
                        $sql .= "
                            WHERE (event_ID = ?);";

                        $params = [$event_ID];
                        $param_types = "i";
                    }

                    $read_availability = new Query($sql, $params, $param_types);
                    return $read_availability;
                }
                else
                {
        /**
         * Reads availabilities from a member using the provided Query_Client and member ID.
         *
         * @param Query_Client $client The Query_Client to use for the query.
         * @param int $member_ID The ID of the member to retrieve availabilities for.
         * @return mixed|null Returns the result of the query or null if an error occurred.
         * @throws System_Error If the Query_Client passed as an argument has an unrecognized Client_Type.
         */
                    throw new System_Error(0, "Query_Client passed as arg to read_availabilities_from_event() has unrecognised Client_Type.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        /**
         * @param Query_Client $client
         * @param $member_ID
         * @return Query|null
         */
        public static function read_availabilities_from_member(Query_Client $client, $member_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::USER)
                {
                    $sql = 
                        "SELECT 
                        CONCAT(MEMBERS.member_fname, ' ', MEMBERS.member_lname) AS member_whole_name,     
                            TEAMS.team_name, EVENTS.event_name,  
                        CONCAT(DATE_FORMAT(event_date, '%d'), '/',
                            DATE_FORMAT(event_date, '%m'), '/',
                            DATE_FORMAT(event_date, '%Y')) AS event_date, 
                        EVENTS.event_location, 
                        CONCAT(DATE_FORMAT(event_meet_time, '%H'), ':',
                            DATE_FORMAT(event_meet_time, '%i')) AS event_meet_time,
                        CONCAT(DATE_FORMAT(event_start_time, '%H'), ':',
                                DATE_FORMAT(event_start_time, '%i')) AS event_start_time, 
                        AVAILABILITY.available 
                        FROM `AVAILABILITY` 
                        INNER JOIN `TEAM_MEMBERS` 
                            ON AVAILABILITY.team_member_ID = TEAM_MEMBERS.team_member_ID 
                        INNER JOIN `MEMBERS` 
                            ON TEAM_MEMBERS.member_ID = MEMBERS.member_ID 
                        INNER JOIN `EVENTS` 
                            ON AVAILABILITY.event_ID = EVENTS.event_ID 
                        INNER JOIN `TEAMS` 
                            ON EVENTS.team_ID = TEAMS.team_ID 
                        INNER JOIN `CLUBS` 
                            ON TEAMS.club_ID = CLUBS.club_ID 
                        INNER JOIN `EVENT_TYPES` 
                            ON EVENTS.event_type_ID = EVENT_TYPES.event_type_ID 
                        WHERE (MEMBERS.member_ID = ? AND CLUBS.club_ID = ?);";

                    $params = [$member_ID, $client->get_club_ID()];
                    $param_types = "ii";

                    $read_availability = new Query($sql, $params, $param_types);
                    return $read_availability;
                }
                elseif ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "SELECT * 
                        FROM `AVAILABILITY` 
                        INNER JOIN `TEAM_MEMBERS` 
                            ON AVAILABILITY.team_member_ID = TEAM_MEMBERS.team_member_ID 
                        INNER JOIN `MEMBERS` 
                            ON TEAM_MEMBERS.member_ID = MEMBERS.member_ID 
                        WHERE MEMBERS.member_ID = ?;";

                    $params = [$member_ID];
                    $param_types = "i";

                    $read_availability = new Query($sql, $params, $param_types);
                    return $read_availability;
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg to read_availabilities_from_member() has unrecognised Client_Type.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        /**
         * @param Query_Client $client
         * @param int $event_ID
         * @return Query|null
         */
        public static function read_participants_from_event(Query_Client $client, int $event_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::USER)
                {
                    //Check if availability belongs to the client's club

                    $sql = self::USER_READ_SQL;

                    $club_ID = $client->get_club_ID();

                    $sql .= "
                            WHERE (AVAILABILITY.event_ID = ? AND CLUBS.club_ID = ? AND AVAILABILITY.participating = 1) ORDER BY AVAILABILITY.modified ASC;";
                    
                    $params = [$event_ID, $club_ID];
                    $param_types = "ii";

                    $read_availability = new Query($sql, $params, $param_types);
                    return $read_availability;
                }
                elseif ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "SELECT * 
                        FROM `AVAILABILITY` 
                        WHERE (AVAILABILITY.event_ID = ? AND AVAILABILITY.participating = 1)
                        ORDER BY AVAILABILITY.modified ASC;";

                    $params = [$event_ID];
                    $param_types = "i";

                    $read_availability = new Query($sql, $params, $param_types);
                    return $read_availability;
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg to read_availabilities_from_event() has unrecognised Client_Type.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }
    }

/**
 *
 */
class Clubs
    {
        //CRUD SQL Functions

    /**
     * @param Query_Client $client
     * @param string $club_name
     * @return Query|null
     */
    public static function create_club(Query_Client $client, string $club_name)
        {
            try
            {
                //Only the system can create a new club
                if ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "INSERT INTO `CLUBS`
                        (`club_name`) 
                        VALUES (?);";

                    $params = [$club_name];
                    $param_types = "s";

                    $read_availability = new Query($sql, $params, $param_types);
                    return $read_availability;
                }
                elseif ($client->get_client_type() == Client_Type::USER)
                {
                    throw new System_Error(0, "Query_Client passed as arg to create_club() has Client_Type::USER. Only SYSTEM can create a club.", __LINE__);
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg to create_club() has unrecognised Client_Type.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

    /**
     * @param Query_Client $client
     * @param int $club_ID
     * @return Query|null
     */
    public static function read_club(Query_Client $client, int $club_ID)
        {
            try
            {
                //read_club() returns different outputs depending on Query_Client->Client_Type
                if ($client->get_client_type() == Client_Type::USER)
                {
                    //First check client is a member of the club_ID given
                    if ($club_ID == $client->get_club_ID())
                    {
                        $sql = 
                            "SELECT `club_name` 
                            FROM `CLUBS`
                            WHERE (`club_ID` = ?);";

                        $params = [$club_ID];
                        $param_types = "i";

                        $read_availability = new Query($sql, $params, $param_types);
                        return $read_availability;
                    }
                    else
                    {
                        if (!Validation::check_club_ID_exists($club_ID))
                        {
                            throw new System_Error(0, "club_ID passed to read_club() does not exist in table CLUBS.", __LINE__);
                        }
                        else
                        {
                            throw new System_Error(0, "Query_Client passed as arg as an arg to read_club() is not a member of the club_ID passed as an arg.", __LINE__);
                        }
                    }
                }
                elseif ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                            "SELECT * 
                            FROM `CLUBS`
                            WHERE (`club_ID` = ?);";

                        $params = [$club_ID];
                        $param_types = "i";

                        $read_availability = new Query($sql, $params, $param_types);
                        return $read_availability;
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg to read_club() has unrecognised Client_Type.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

    /**
     * @param Query_Client $client
     * @param int $club_ID
     * @param string $club_name
     * @return Query|null
     */
    public static function update_club(Query_Client $client, int $club_ID, string $club_name)
        {
            try
            {
                //Only the system and club admins can update a club

                if ($client->get_client_type() == Client_Type::USER)
                {
                    //First check client is a member of the club_ID given
                    if ($club_ID == $client->get_club_ID())
                    {
                        if (Validation::check_club_admin($client, $client->get_member_ID()))
                        {
                            $sql = 
                                "UPDATE `CLUBS` SET 
                                `club_name` = ? 
                                WHERE (`club_ID` = ?);";

                            $params = [$club_name, $club_ID];
                            $param_types = "si";

                            $read_availability = new Query($sql, $params, $param_types);
                            return $read_availability;
                        }
                        else
                        {
                            throw new System_Error(0, "Query_Client passed as arg to update_club() is not an admin of their club.", __LINE__);
                        }
                    }
                    else
                    {
                        if (!Validation::check_club_ID_exists($club_ID))
                        {
                            throw new System_Error(0, "club_ID passed to update_club() does not exist in table CLUBS.", __LINE__);
                        }
                        else
                        {
                            throw new System_Error(0, "Query_Client passed as arg as an arg to update_club() is not a member of the club_ID passed as an arg.", __LINE__);
                        }
                    }
                }
                elseif ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "UPDATE `CLUBS` SET 
                        `club_name` = ? 
                        WHERE (`club_ID` = ?);";

                    $params = [$club_name, $club_ID];
                    $param_types = "si";

                    $read_availability = new Query($sql, $params, $param_types);
                    return $read_availability;
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg to update_club() has unrecognised Client_Type.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

    /**
     * @param Query_Client $client
     * @param $club_ID
     * @return Query|null
     */
    public static function delete_club(Query_Client $client, $club_ID)
        {
            try
            {
                //Only the system can delete a club
                if ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "DELETE FROM `CLUBS` 
                        WHERE (`club_ID` = ?);";

                    $params = [$club_ID];
                    $param_types = "i";

                    $read_availability = new Query($sql, $params, $param_types);
                    return $read_availability;
                }
                elseif ($client->get_client_type() == Client_Type::USER)
                {
                    throw new System_Error(0, "Query_Client passed as arg to delete_club() has Client_Type::USER. Only SYSTEM can delete a club.", __LINE__);
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg to delete_club() has unrecognised Client_Type.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        //Specialised SQL Functions

    /**
     * @param Query_Client $client
     * @param int $member_ID
     * @return Query|null
     */
    public static function read_club_from_member(Query_Client $client, int $member_ID)
        {
            try
            {
                //Only the system can view external clubs
                if ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "SELECT `club_ID` 
                        FROM `MEMBERS` 
                        WHERE (`member_ID` = ?);";

                    $params = [$member_ID];
                    $param_types = "i";

                    $read_availability = new Query($sql, $params, $param_types);
                    return $read_availability;
                }
                elseif ($client->get_client_type() == Client_Type::USER)
                {
                    throw new System_Error(0, "Attempt to call read_club_from_member() as Query_Client with Client_Type::USER. Users cannot view external clubs.", __LINE__);
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg to read_club_from_member() has unrecognised Client_Type.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

    /**
     * @param Query_Client $client
     * @param int $team_member_ID
     * @return Query|null
     */
    public static function read_club_from_team_member(Query_Client $client, int $team_member_ID)
        {
            try
            {
                //Only the system can view external clubs
                if ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "SELECT MEMBERS.club_ID 
                            FROM `MEMBERS` 
                        INNER JOIN `TEAM_MEMBERS` 
                            ON MEMBERS.member_ID = TEAM_MEMBERS.club_ID 
                        WHERE (TEAM_MEMBERS.team_member_ID = ?);";

                    $params = [$team_member_ID];
                    $param_types = "i";

                    $read_club = new Query($sql, $params, $param_types);
                    return $read_club;
                }
                elseif ($client->get_client_type() == Client_Type::USER)
                {
                    throw new System_Error(0, "Attempt to call read_club_from_team_member() as Query_Client with Client_Type::USER. Users cannot view external clubs.", __LINE__);
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg to read_club_from_team_member() has unrecognised Client_Type.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

    /**
     * @param Query_Client $client
     * @param int $team_ID
     * @return Query|null
     */
    public static function read_club_from_team(Query_Client $client, int $team_ID)
        {
            try
            {
                //Only the system can view external clubs
                if ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "SELECT `club_ID` 
                            FROM `TEAMS` 
                        WHERE (`team_ID` = ?);";

                    $params = [$team_ID];
                    $param_types = "i";

                    $read_club = new Query($sql, $params, $param_types);
                    return $read_club;
                }
                elseif ($client->get_client_type() == Client_Type::USER)
                {
                    throw new System_Error(0, "Attempt to call read_club_from_team_member() as Query_Client with Client_Type::USER. Users cannot view external clubs.", __LINE__);
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg to read_club_from_team_member() has unrecognised Client_Type.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

    /**
     * @param Query_Client $client
     * @return Query|null
     */
    public static function read_all_clubs(Query_Client $client)
        {
            try
            {
                //read_club() returns different outputs depending on Query_Client->Client_Type
                if ($client->get_client_type() == Client_Type::USER)
                {
                    throw new System_Error(0, "Client does not have sufficient permissions to perform this action.", __LINE__);
                }
                elseif ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "SELECT * 
                        FROM `CLUBS`;";

                    $params = [];
                    $param_types = "";

                    $read_clubs = new Query($sql, $params, $param_types);
                    return $read_clubs;
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg to read_club() has unrecognised Client_Type.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }
    }

/**
 *
 */
class Events
    {
    /**
     *
     */
    const USER_READ_SQL =
            "SELECT EVENTS.event_name, EVENT_TYPES.event_type_name, 
            CONCAT(DATE_FORMAT(event_date, '%d'), '/',
                DATE_FORMAT(event_date, '%m'), '/',
                DATE_FORMAT(event_date, '%Y')) AS event_date, 
            event_location, 
            CONCAT(DATE_FORMAT(event_meet_time, '%H'), ':',
                DATE_FORMAT(event_meet_time, '%i')) AS event_meet_time,
            CONCAT(DATE_FORMAT(event_start_time, '%H'), ':',
                    DATE_FORMAT(event_start_time, '%i')) AS event_start_time, 
            TEAMS.team_name 
            FROM `EVENTS` 
            INNER JOIN `TEAMS` 
                ON EVENTS.team_ID = TEAMS.team_ID 
            INNER JOIN `EVENT_TYPES` 
                ON EVENTS.event_type_ID = EVENT_TYPES.event_type_ID 
            INNER JOIN `CLUBS` 
                ON TEAMS.club_ID = CLUBS.club_ID ";

    /**
     * @param Query_Client $client
     * @param string $event_name
     * @param int $team_ID
     * @param int $event_type_ID
     * @param string $event_date
     * @param string $event_meet_time
     * @param string $event_start_time
     * @param string $event_location
     * @param string $event_description
     * @return Query|null
     */
    public static function create_event(Query_Client $client, string $event_name, int $team_ID, int $event_type_ID, string $event_date, string $event_meet_time, string $event_start_time, string $event_location, string $event_description)
        {
            try
            {
                $system = Query_Client::get_system_instance();

                if ($client->get_client_type() == Client_Type::USER)
                {
                    if ((Validation::check_team_member($client, $client->get_member_ID(), $team_ID) and Validation::check_team_admin($client, $client->get_member_ID(), $team_ID)) or Validation::check_club_admin($system, $client->get_member_ID()))
                    {
                        $sql = 
                            "INSERT INTO `EVENTS` 
                            (`event_name`, `team_ID`, `event_type_ID`, `event_date`, `event_meet_time`, `event_start_time`, `event_location`, `event_description`) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?);";

                        $params = [$event_name, $team_ID, $event_type_ID, $event_date, $event_meet_time, $event_start_time, $event_location, $event_description];
                        $param_types = "siisssss";

                        $create_event = new Query($sql, $params, $param_types);

                        return $create_event;
                    }
                    else
                    {
                        throw new System_Error(0, "Client passed as arg to create_event() does not have permissions to edit team passed as arg.", __LINE__);
                    }
                }
                elseif ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "INSERT INTO `EVENTS` 
                        (`event_name`, `team_ID`, `event_type_ID`, `event_date`, `event_meet_time`, `event_start_time`, `event_location`, `event_description`) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?);";

                    $params = [$event_name, $team_ID, $event_type_ID, $event_date, $event_meet_time, $event_start_time, $event_location, $event_description];
                    $param_types = "siisssss";

                    $create_event = new Query($sql, $params, $param_types);
                    return $create_event;
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg to read_event() has unrecognised Client_Type.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

    /**
     * @param Query_Client $client
     * @param int $event_ID
     * @return Query|null
     */
    public static function read_event(Query_Client $client, int $event_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::USER)
                {

                    $sql = 
                        self::USER_READ_SQL . 
                        "WHERE (EVENTS.event_ID = ? AND CLUBS.club_ID = ?);";

                    $params = [$event_ID, $client->get_club_ID()];
                    $param_types = "ii";

                    $read_event = new Query($sql, $params, $param_types);
                    return $read_event;
                }
                elseif ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "SELECT EVENTS.* 
                        FROM `EVENTS` 
                        WHERE (`event_ID` = ?);";

                    $params = [$event_ID];
                    $param_types = "i";

                    $read_event = new Query($sql, $params, $param_types);
                    return $read_event;
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg to read_event() has unrecognised Client_Type.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

    /**
     * @param Query_Client $client
     * @param int $event_ID
     * @param string $event_name
     * @param int $event_type_ID
     * @param string $event_date
     * @param string $event_meet_time
     * @param string $event_start_time
     * @param string $event_location
     * @param string $event_description
     * @return Query|null
     */
    public static function update_event(Query_Client $client, int $event_ID, string $event_name, int $event_type_ID, string $event_date, string $event_meet_time, string $event_start_time, string $event_location, string $event_description)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::USER)
                {
                    $sql = 
                        "UPDATE `EVENTS` 
                        INNER JOIN `TEAM_MEMBERS` 
                            ON EVENTS.team_ID = TEAM_MEMBERS.team_ID 
                            AND TEAM_MEMBERS.member_ID = ? 
                        INNER JOIN `ROLES` 
                            ON TEAM_MEMBERS.role_ID = ROLES.role_ID 
                        SET `event_name` = ?, 
                        `event_type_ID` = ?, 
                        `event_date` = ?, 
                        `event_meet_time` = ?, 
                        `event_start_time` = ?, 
                        `event_location` = ?, 
                        `event_description` = ? 
                        WHERE (EVENTS.event_ID = ? AND ROLES.team_admin = 1);";

                        //Currently only lets admins update, which is correct
                        //However if this is not the case there is no reporting back to user

                    $params = [$client->get_member_ID(), $event_name, $event_type_ID, $event_date, $event_meet_time, $event_start_time, $event_location, $event_description, $event_ID];
                    $param_types = "isisssssi";

                    $update_event = new Query($sql, $params, $param_types);
                    return $update_event;
                }
                elseif ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "UPDATE `EVENTS` 
                        SET `event_name` = ?, 
                        `event_type_ID` = ?, 
                        `event_date` = ?, 
                        `event_meet_time` = ?, 
                        `event_start_time` = ?, 
                        `event_location` = ?, 
                        `event_description` = ? 
                        WHERE (EVENTS.event_ID = ?);";

                    $params = [$event_name, $event_type_ID, $event_date, $event_meet_time, $event_start_time, $event_location, $event_description, $event_ID];
                    $param_types = "sisssssi";

                    $update_event = new Query($sql, $params, $param_types);
                    return $update_event;
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg to update_availability() has unrecognised Client_Type.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

    /**
     * @param Query_Client $client
     * @param int $event_ID
     * @return Query|null
     */
    public static function delete_event(Query_Client $client, int $event_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::USER)
                {
                    $sql = 
                        "DELETE `EVENTS`
                        FROM `EVENTS` 
                        INNER JOIN `TEAM_MEMBERS` 
                            ON EVENTS.team_ID = TEAM_MEMBERS.team_ID 
                                AND TEAM_MEMBERS.member_ID = ? 
                        WHERE (EVENTS.event_ID = ? AND ROLES.team_admin = 1);";

                        //Currently only lets admins update, which is correct
                        //However if this is not the case there is no reporting back to user

                    $params = [$client->get_member_ID(), $event_ID];
                    $param_types = "ii";

                    $delete_event = new Query($sql, $params, $param_types);
                    return $delete_event;
                }
                elseif ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "DELETE FROM `EVENTS` 
                        WHERE (EVENTS.event_ID = ?);";

                    $params = [$event_ID];
                    $param_types = "i";

                    $delete_event = new Query($sql, $params, $param_types);
                    return $delete_event;
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg to update_availability() has unrecognised Client_Type.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        //Specialised SQL functions

    /**
     * @param Query_Client $client
     * @param int $team_ID
     * @return Query|null
     */
    public static function read_events_from_team(Query_Client $client, int $team_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::USER)
                {
                    if (Validation::check_team_member($client, $client->get_member_ID(), $team_ID))
                    {
                        $sql = 
                            self::USER_READ_SQL . 
                            "WHERE (EVENTS.team_ID = ?)
                            ORDER BY EVENTS.event_date, EVENTS.event_meet_time ASC;";

                        $params = [$team_ID];
                        $param_types = "i";

                        $read_event = new Query($sql, $params, $param_types);
                        return $read_event;
                    }
                    else
                    {
                        throw new System_Error(0, "Client is not a member of the team passed as arg.", __LINE__);
                    }
                }
                elseif ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "SELECT *
                        FROM `EVENTS` 
                        WHERE (`team_ID` = ?)
                        ORDER BY EVENTS.event_date, EVENTS.event_meet_time ASC;";

                    $params = [$team_ID];
                    $param_types = "i";

                    $read_event = new Query($sql, $params, $param_types);
                    return $read_event;
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg to read_events_from_team() has unrecognised Client_Type.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

    /**
     * @param Query_Client $client
     * @param int $member_ID
     * @return Query|void|null
     */
    public static function read_events_from_member(Query_Client $client, int $member_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::USER)
                {
                    if ($member_ID == $client->get_member_ID())
                    {
                        $sql = 
                            "SELECT EVENTS.event_name, EVENTS.event_location, EVENTS.event_description, EVENT_TYPES.event_type_name, EVENTS.event_ID, 
                            CONCAT(DATE_FORMAT(event_date, '%d'), '/',
                                DATE_FORMAT(event_date, '%m'), '/',
                                DATE_FORMAT(event_date, '%Y')) AS event_date,
                            CONCAT(DATE_FORMAT(event_meet_time, '%H'), ':',
                                DATE_FORMAT(event_meet_time, '%i')) AS event_meet_time,
                            CONCAT(DATE_FORMAT(event_start_time, '%H'), ':',
                                    DATE_FORMAT(event_start_time, '%i')) AS event_start_time, 
                            TEAMS.team_name, AVAILABILITY.availability_ID, AVAILABILITY.available, TEAMS.team_ID, MEMBERS.member_ID, 
                            CONCAT(MEMBERS.member_fname, ' ', MEMBERS.member_lname) AS member_whole_name 
                            FROM `EVENTS` 
                            INNER JOIN `TEAMS` 
                                ON EVENTS.team_ID = TEAMS.team_ID 
                            INNER JOIN `TEAM_MEMBERS` 
                                ON TEAMS.team_ID = TEAM_MEMBERS.team_ID 
                            INNER JOIN `MEMBERS` 
                                ON TEAM_MEMBERS.member_ID = MEMBERS.member_ID 
                            LEFT JOIN `GUARDIANSHIP` 
                                ON MEMBERS.member_ID = GUARDIANSHIP.child_ID 
                            INNER JOIN `EVENT_TYPES` 
                                ON EVENTS.event_type_ID = EVENT_TYPES.event_type_ID 
                            INNER JOIN `CLUBS` 
                                ON TEAMS.club_ID = CLUBS.club_ID 
                            INNER JOIN `AVAILABILITY` 
                                ON EVENTS.event_ID = AVAILABILITY.event_ID 
                                    AND TEAM_MEMBERS.team_member_ID = AVAILABILITY.team_member_ID 
                            WHERE ((TEAM_MEMBERS.member_ID = ? OR GUARDIANSHIP.parent_ID = ?) AND EVENTS.event_date >= DATE(NOW()))
                            ORDER BY EVENTS.event_date, EVENTS.event_meet_time ASC;";

                        $params = [$member_ID, $member_ID];
                        $param_types = "ii";

                        $read_event = new Query($sql, $params, $param_types);
                        return $read_event;
                    }
                }
                elseif ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "SELECT * 
                        FROM `EVENTS` 
                        INNER JOIN `TEAMS` 
                            ON EVENTS.team_ID = TEAMS.team_ID 
                        INNER JOIN `TEAM_MEMBERS` 
                            ON TEAMS.team_ID = TEAM_MEMBERS.team_ID 
                        INNER JOIN `MEMBERS` 
                            ON TEAM_MEMBERS.member_ID = MEMBERS.member_ID 
                        LEFT JOIN `GUARDIANSHIP` 
                            ON MEMBERS.member_ID = GUARDIANSHIP.child_ID 
                        WHERE (TEAM_MEMBERS.member_ID = ? OR GUARDIANSHIP.parent_ID = ?)
                        ORDER BY EVENTS.event_date, EVENTS.event_meet_time ASC;";

                    $params = [$member_ID, $member_ID];
                    $param_types = "ii";

                    $read_event = new Query($sql, $params, $param_types);
                    return $read_event;
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg to read_event() has unrecognised Client_Type.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        //read_events_from_member_explicit only gets the events for that member, not including children

    /**
     * @param Query_Client $client
     * @param int $member_ID
     * @return Query|void|null
     */
    public static function read_events_from_member_explicit(Query_Client $client, int $member_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::USER)
                {
                    if ($member_ID == $client->get_member_ID())
                    {
                        $sql = 
                            "SELECT EVENTS.event_name, EVENT_TYPES.event_type_name, 
                            CONCAT(DATE_FORMAT(event_date, '%d'), '/',
                                DATE_FORMAT(event_date, '%m'), '/',
                                DATE_FORMAT(event_date, '%Y')) AS event_date, 
                            EVENTS.event_location, 
                            CONCAT(DATE_FORMAT(event_meet_time, '%H'), ':',
                                DATE_FORMAT(event_meet_time, '%i')) AS event_meet_time,
                            CONCAT(DATE_FORMAT(event_start_time, '%H'), ':',
                                    DATE_FORMAT(event_start_time, '%i')) AS event_start_time, 
                            TEAMS.team_name 
                            FROM `EVENTS` 
                            INNER JOIN `TEAMS` 
                                ON EVENTS.team_ID = TEAMS.team_ID 
                            INNER JOIN `TEAM_MEMBERS` 
                                ON TEAMS.team_ID = TEAM_MEMBERS.team_ID 
                            INNER JOIN `MEMBERS` 
                                ON TEAM_MEMBERS.member_ID = MEMBERS.member_ID 
                            INNER JOIN `EVENT_TYPES` 
                                ON EVENTS.event_type_ID = EVENT_TYPES.event_type_ID 
                            INNER JOIN `CLUBS` 
                                ON TEAMS.club_ID = CLUBS.club_ID 
                            WHERE (TEAM_MEMBERS.member_ID = ?)
                            ORDER BY EVENTS.event_date, EVENTS.event_meet_time ASC;";

                        $params = [$member_ID];
                        $param_types = "i";

                        $read_event = new Query($sql, $params, $param_types);
                        return $read_event;
                    }
                }
                elseif ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "SELECT * 
                        FROM `EVENTS` 
                        INNER JOIN `TEAMS` 
                            ON EVENTS.team_ID = TEAMS.team_ID 
                        INNER JOIN `TEAM_MEMBERS` 
                            ON TEAMS.team_ID = TEAM_MEMBERS.team_ID 
                        WHERE (TEAM_MEMBERS.member_ID = ?)
                        ORDER BY EVENTS.event_date, EVENTS.event_meet_time ASC;";

                    $params = [$member_ID, $member_ID];
                    $param_types = "ii";

                    $read_event = new Query($sql, $params, $param_types);
                    return $read_event;
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg to read_event() has unrecognised Client_Type.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }
    }

/**
 *
 */
class Event_Types
    {
    /**
     *
     */
    const USER_READ_SQL =
            "SELECT `event_type_name`, `event_gender_restriction`, `min_age`, `max_age`, `event_type_description` 
            FROM `EVENT_TYPES` ";

    /**
     * @param Query_Client $client
     * @param string $event_type_name
     * @param int $club_ID
     * @param string $event_gender_restriction
     * @param int $min_age
     * @param int $max_age
     * @param string $event_type_description
     * @return Query|null
     */
    public static function create_event_type(Query_Client $client, string $event_type_name, int $club_ID, string $event_gender_restriction, int $min_age, int $max_age, string $event_type_description)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::USER)
                {
                    if ($club_ID == $client->get_club_ID())
                    {
                        if (Validation::check_club_admin($client, $club_ID))
                        {
                            $sql = 
                                "INSERT INTO `EVENT_TYPES` 
                                (`event_type_name`, `club_ID`, `event_gender_restriction`, `min_age`, `max_age`, `event_type_description`) 
                                VALUES (?, ?, ?, ?, ?, ?);";

                            $params = [$event_type_name, $club_ID, $event_gender_restriction, $min_age, $max_age, $event_type_description];
                            $param_types = "sisiis";

                            $create_event_type = new Query($sql, $params, $param_types);
                            return $create_event_type;
                        }
                        else
                        {
                            throw new System_Error(0, "Client passed as arg is not an admin of the club passed as an arg.", __LINE__);
                        }
                    }
                    else
                    {
                        throw new System_Error(0, "Client passed as arg is not a member of the club_ID passed as an arg.", __LINE__);
                    }
                }
                elseif ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "INSERT INTO `EVENT_TYPES` 
                        (`event_type_name`, `club_ID`, `event_gender_restriction`, `min_age`, `max_age`, `event_type_description`) 
                        VALUES (?, ?, ?, ?, ?, ?);";

                    $params = [$event_type_name, $club_ID, $event_gender_restriction, $min_age, $max_age, $event_type_description];
                    $param_types = "sisiis";

                    $create_event_type = new Query($sql, $params, $param_types);
                    return $create_event_type;
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg has unrecognised Client_Type.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

    /**
     * @param Query_Client $client
     * @param int $event_type_ID
     * @return Query|null
     */
    public static function read_event_type(Query_Client $client, int $event_type_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::USER)
                {
                    $sql = 
                        self::USER_READ_SQL . 
                        "WHERE (`event_type_ID` = ? 
                            AND `club_ID` = ?);";

                    $params = [$event_type_ID, $client->get_club_ID()];
                    $param_types = "ii";

                    $read_event = new Query($sql, $params, $param_types);
                    return $read_event;
                }
                elseif ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "SELECT * 
                        FROM `EVENT_TYPES` 
                        WHERE (`event_type_ID` = ?);";

                    $params = [$event_type_ID];
                    $param_types = "i";

                    $read_event = new Query($sql, $params, $param_types);
                    return $read_event;
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg has unrecognised Client_Type.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

    /**
     * @param Query_Client $client
     * @param int $event_type_ID
     * @param string $event_type_name
     * @param string $event_gender_restriction
     * @param int $min_age
     * @param int $max_age
     * @param string $event_type_description
     * @return Query|null
     */
    public static function update_event_type(Query_Client $client, int $event_type_ID, string $event_type_name, string $event_gender_restriction, int $min_age, int $max_age, string $event_type_description)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::USER)
                {
                    $sql = 
                        "UPDATE `EVENT_TYPES` 
                        INNER JOIN `MEMBERS` 
                            ON EVENT_TYPES.club_ID = MEMBERS.club_ID 
                                AND MEMBERS.member_ID = ? 
                        SET `event_type_name` = ?, 
                        `event_gender_restriction` = ?, 
                        `min_age` = ?, 
                        `max_age` = ?, 
                        `event_type_description` = ? 
                        WHERE (EVENT_TYPES.event_type_ID = ? AND MEMBERS.admin = 1);";

                        //Currently only lets admins update, which is correct
                        //However if this is not the case there is no reporting back to user

                    $params = [$client->get_member_ID(), $event_type_name, $event_gender_restriction, $min_age, $max_age, $event_type_description, $event_type_ID];
                    $param_types = "issiisi";

                    $update_event = new Query($sql, $params, $param_types);
                    return $update_event;
                }
                elseif ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "UPDATE `EVENT_TYPES` 
                        SET `event_type_name` = ?, 
                        `event_gender_restriction` = ?, 
                        `min_age` = ?, 
                        `max_age` = ?, 
                        `event_type_description` = ? 
                        WHERE (EVENT_TYPES.event_type_ID = ?);";

                    $params = [$event_type_name, $event_gender_restriction, $min_age, $max_age, $event_type_description, $event_type_ID];
                    $param_types = "ssiisi";

                    $update_event = new Query($sql, $params, $param_types);
                    return $update_event;
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg to update_availability() has unrecognised Client_Type.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

    /**
     * @param Query_Client $client
     * @param $event_type_ID
     * @return Query|null
     */
    public static function delete_event_type(Query_Client $client, $event_type_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::USER)
                {
                    $sql = 
                        "DELETE `EVENT_TYPES` 
                        FROM `EVENT_TYPES` 
                        INNER JOIN `MEMBERS` 
                            ON EVENT_TYPES.club_ID = MEMBERS.club_ID 
                                AND MEMBERS.member_ID = ? 
                        WHERE (EVENT_TYPES.event_type_ID = ? AND MEMBERS.admin = 1);";

                        //Currently only lets admins update, which is correct
                        //However if this is not the case there is no reporting back to user

                    $params = [$client->get_member_ID(), $event_type_ID];
                    $param_types = "ii";

                    $update_event = new Query($sql, $params, $param_types);
                    return $update_event;
                }
                elseif ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "DELETE FROM `EVENT_TYPES` 
                        WHERE (event_type_ID = ?);";

                    $params = [$event_type_ID];
                    $param_types = "i";

                    $delete_event_type = new Query($sql, $params, $param_types);
                    return $delete_event_type;
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg has unrecognised Client_Type.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        //Custom SQL functions

    /**
     * @param Query_Client $client
     * @param int $club_ID
     * @return Query|null
     */
    public static function read_event_types_from_club(Query_Client $client, int $club_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::USER)
                {
                    if ($club_ID == $client->get_club_ID())
                    {
                        $sql = 
                            self::USER_READ_SQL . 
                            "WHERE (`club_ID` = ?);";

                        $params = [$club_ID];
                        $param_types = "i";

                        $read_event = new Query($sql, $params, $param_types);
                        return $read_event;
                    }
                    else
                    {
                        throw new System_Error(0, "Client club_ID does not match club_ID passed as arg.", __LINE__);
                    }
                }
                elseif ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "SELECT * 
                        FROM `EVENT_TYPES` 
                        WHERE (`club_ID` = ?);";

                    $params = [$club_ID];
                    $param_types = "i";

                    $read_event = new Query($sql, $params, $param_types);
                    return $read_event;
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg has unrecognised Client_Type.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }   
        }

    }

/**
 *
 */
class Guardianships
    {
    /**
     * @param Query_Client $client
     * @param int $parent_ID
     * @param int $child_ID
     * @return Query|null
     */
    public static function create_guardianship(Query_Client $client, int $parent_ID, int $child_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::USER)
                {
                    if (Validation::check_club_admin($client, $client->get_member_ID()))
                    {
                        $sql = 
                            "INSERT INTO `GUARDIANSHIP` 
                            (`parent_ID`, `child_ID`) 
                            VALUES (?, ?);";

                        $params = [$parent_ID, $child_ID];
                        $param_types = "ii";

                        $create_guardianship = new Query($sql, $params, $param_types);
                        return $create_guardianship;
                    }
                    else
                    {
                        throw new System_Error(0, "Client passed as arg is not an admin of their club.", __LINE__);
                    }
                }
                elseif ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "INSERT INTO `GUARDIANSHIP` 
                        (`parent_ID`, `child_ID`) 
                        VALUES (?, ?);";

                    $params = [$parent_ID, $child_ID];
                    $param_types = "ii";

                    $create_guardianship = new Query($sql, $params, $param_types);
                    return $create_guardianship;
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg has unrecognised Client_Type.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

    /**
     * @param Query_Client $client
     * @param int $guardianship_ID
     * @return Query|null
     */
    public static function read_guardianship(Query_Client $client, int $guardianship_ID)
        {
            //Users do not need to be able to view guardianship pairs
            try
            {
                if ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                            "SELECT * 
                            FROM `GUARDIANSHIP` 
                            WHERE (`guardianship_ID` = ?);";

                        $params = [$guardianship_ID];
                        $param_types = "i";

                        $read_guardianship = new Query($sql, $params, $param_types);
                        return $read_guardianship;
                }
                else if ($client->get_client_type() == Client_Type::USER)
                {
                    throw new System_Error(0, "Query_Client of type USER does not have access to view guardianships.", __LINE__);
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg has unrecognised client type.", __LINE__);
                }
            }
            catch (Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

    /**
     * @param Query_Client $client
     * @param int $guardianship_ID
     * @param int $valid
     * @return Query|null
     */
    public static function update_guardianship(Query_Client $client, int $guardianship_ID, int $valid)
        {
            //Only system may need to update valid field in a GUARDIANSHIP

            //Users do not need to be able to view guardianship pairs
            try
            {
                if ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "UPDATE `GUARDIANSHIP` 
                        SET `valid` = ? 
                        WHERE (`guardianship_ID` = ?);";

                    $params = [$valid, $guardianship_ID];
                    $param_types = "ii";

                    $read_guardianship = new Query($sql, $params, $param_types);
                    return $read_guardianship;
                }
                else if ($client->get_client_type() == Client_Type::USER)
                {
                    throw new System_Error(0, "Query_Client of type USER does not have access to update guardianships.", __LINE__);
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg has unrecognised client type.", __LINE__);
                }
            }
            catch (Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

    /**
     * @param Query_Client $client
     * @param int $guardianship_ID
     * @return Query|null
     */
    public static function delete_guardianship(Query_Client $client, int $guardianship_ID)
        {
            //Only system should be able to delete a guardianship
            //In normal circumstances the guardianship should just be invalidated

            try
            {
                if ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "DELETE FROM `GUARDIANSHIP` 
                        WHERE (`guardianship_ID` = ?);";

                    $params = [$guardianship_ID];
                    $param_types = "i";

                    $delete_guardianship = new Query($sql, $params, $param_types);
                    return $delete_guardianship;
                }
                else if ($client->get_client_type() == Client_Type::USER)
                {
                    throw new System_Error(0, "Query_Client of type USER does not have access to delete guardianships.", __LINE__);
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg has unrecognised client type.", __LINE__);
                }
            }
            catch (Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        //Custom SQL functions

    /**
     * @param Query_Client $client
     * @param int $child_ID
     * @return Query|null
     */
    public static function read_parent_from_child(Query_Client $client, int $child_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "SELECT `parent_ID` 
                        FROM `GUARDIANSHIP` 
                        WHERE (`child_ID` = ?);";

                    $params = [$child_ID];
                    $param_types = "i";

                    $read_guardianship = new Query($sql, $params, $param_types);
                    return $read_guardianship;
                }
                else if ($client->get_client_type() == Client_Type::USER)
                {
                    $sql = 
                        "SELECT CONCAT(MEMBERS.member_fname, ' ', MEMBERS.member_lname) AS member_whole_name 
                            FROM `GUARDIANSHIP` 
                        INNER JOIN MEMBERS 
                            ON GUARDIANSHIP.parent_ID = MEMBERS.member_ID 
                        WHERE (GUARDIANSHIP.child_ID = ? AND MEMBERS.club_ID = ?);";

                    $params = [$child_ID, $client->get_club_ID()];
                    $param_types = "ii";

                    $read_guardianship = new Query($sql, $params, $param_types);
                    return $read_guardianship;
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg has unrecognised client type.", __LINE__);
                }
            }
            catch (Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

    /**
     * @param Query_Client $client
     * @param int $parent_ID
     * @return Query|null
     */
    public static function read_children_from_parent(Query_Client $client, int $parent_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "SELECT `child_ID` 
                        FROM `GUARDIANSHIP` 
                        WHERE (`parent_ID` = ?);";

                    $params = [$parent_ID];
                    $param_types = "i";

                    $read_guardianship = new Query($sql, $params, $param_types);
                    return $read_guardianship;
                }
                else if ($client->get_client_type() == Client_Type::USER)
                {
                    $sql = 
                        "SELECT CONCAT(MEMBERS.member_fname, ' ', MEMBERS.member_lname) AS member_whole_name 
                            FROM `GUARDIANSHIP` 
                        INNER JOIN MEMBERS 
                            ON GUARDIANSHIP.child_ID = MEMBERS.member_ID 
                        WHERE (GUARDIANSHIP.parent_ID = ? AND MEMBERS.club_ID = ?);";

                    $params = [$parent_ID, $client->get_club_ID()];
                    $param_types = "ii";

                    $read_guardianship = new Query($sql, $params, $param_types);
                    return $read_guardianship;
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg has unrecognised client type.", __LINE__);
                }
            }
            catch (Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

    /**
     * @param Query_Client $client
     * @param int $child_ID
     * @param int $parent_ID
     * @return Query|null
     */
    public static function update_parent_from_child(Query_Client $client, int $child_ID, int $parent_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "UPDATE `GUARDIANSHIP` 
                        SET `parent_ID` = ? 
                        WHERE (`child_ID` = ?);";

                    $params = [$parent_ID, $child_ID];
                    $param_types = "ii";

                    $update_guardianship = new Query($sql, $params, $param_types);
                    return $update_guardianship;
                }
                else if ($client->get_client_type() == Client_Type::USER)
                {
                    throw new System_Error(0, "Query_Client passed as arg does not have permission to perform this action.", __LINE__);
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg has unrecognised client type.", __LINE__);
                }
            }
            catch (Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

    /**
     * @param Query_Client $client
     * @param int $child_ID
     * @return Query|null
     */
    public static function delete_guardianship_from_child(Query_Client $client, int $child_ID)
        {
            //Only system should be able to delete a guardianship
            //In normal circumstances the guardianship should just be invalidated

            try
            {
                if ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "DELETE FROM `GUARDIANSHIP` 
                        WHERE (`child_ID` = ?);";

                    $params = [$child_ID];
                    $param_types = "i";

                    $delete_guardianship = new Query($sql, $params, $param_types);
                    return $delete_guardianship;
                }
                else if ($client->get_client_type() == Client_Type::USER)
                {
                    throw new System_Error(0, "Query_Client of type USER does not have access to delete guardianships.", __LINE__);
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg has unrecognised client type.", __LINE__);
                }
            }
            catch (Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }
    }

/**
 *
 */
class Members
    {
    /**
     * @param Query_Client $client
     * @param int $club_ID
     * @param string $member_fname
     * @param string $member_lname
     * @param string $member_DOB
     * @param string $member_gender
     * @param string $member_email
     * @param string $hashed_member_password
     * @param int|null $admin
     * @return Query|null
     */
    public static function create_member(Query_Client $client, int $club_ID, string $member_fname, string $member_lname, string $member_DOB, string $member_gender, string $member_email, string $hashed_member_password, int $admin = null)
        {
            //Only system can create a member (through sign-up form)

            try
            {
                if ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "INSERT INTO `MEMBERS` 
                        (`club_ID`, `member_fname`, `member_lname`, `member_DOB`, `member_gender`, `member_email`, `member_password`, `admin`) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, IFNULL(?, 0));";

                    $params = [$club_ID, $member_fname, $member_lname, $member_DOB, $member_gender, $member_email, $hashed_member_password, $admin];
                    $param_types = "issssssi";

                    $create_member = new Query($sql, $params, $param_types);
                    return $create_member;
                }
                elseif ($client->get_client_type() == Client_Type::USER)
                {
                    throw new System_Error(0, "Query_Client of type USER does not have permission to create a member.", __LINE__);
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg has unrecognised Client_Type.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

    /**
     * @param Query_Client $client
     * @param int $member_ID
     * @return Query|null
     */
    public static function read_member(Query_Client $client, int $member_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "SELECT * 
                        FROM `MEMBERS` 
                        WHERE (`member_ID` = ?);";

                    $params = [$member_ID];
                    $param_types = "i";

                    $read_member = new Query($sql, $params, $param_types);
                    return $read_member;
                }
                else if ($client->get_client_type() == Client_Type::USER)
                {
                    $sql = 
                        "SELECT CONCAT(MEMBERS.member_fname, ' ', MEMBERS.member_lname) AS member_whole_name, 
                        CLUBS.club_name, MEMBERS.member_DOB, MEMBERS.member_gender, MEMBERS.member_email, MEMBERS.admin
                        FROM `MEMBERS` 
                        INNER JOIN `CLUBS` 
                            ON MEMBERS.club_ID = CLUBS.club_ID
                        WHERE (MEMBERS.member_ID = ? AND MEMBERS.club_ID = ?);";

                    $params = [$member_ID, $client->get_club_ID()];
                    $param_types = "ii";

                    $read_member = new Query($sql, $params, $param_types);
                    return $read_member;
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg has unrecognised client type.", __LINE__);
                }
            }
            catch (Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

    /**
     * @param Query_Client $client
     * @param int $member_ID
     * @param int $club_ID
     * @param string $member_fname
     * @param string $member_lname
     * @param string $member_DOB
     * @param string $member_gender
     * @param string $member_email
     * @param int $member_admin
     * @param string $hashed_member_password
     * @return Query|null
     */
    public static function update_member(Query_Client $client, int $member_ID, int $club_ID, string $member_fname, string $member_lname, string $member_DOB, string $member_gender, string $member_email, int $member_admin, string $hashed_member_password)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "UPDATE `MEMBERS` 
                            SET `club_ID` = ?, 
                            `member_fname` = ?, 
                            `member_lname` = ?, 
                            `member_DOB` = ?, 
                            `member_gender` = ?, 
                            `member_email` = ?, 
                            `member_admin` = ?, 
                            `member_password` = ? 
                        WHERE (`member_ID` = ?);";

                    $params = [$club_ID, $member_fname, $member_lname, $member_DOB, $member_gender, $member_email, $member_admin, $hashed_member_password, $member_ID];
                    $param_types = "isssssisi";

                    $update_member = new Query($sql, $params, $param_types);
                    return $update_member;
                }
                else if ($client->get_client_type() == Client_Type::USER)
                {
                    if ($client->get_member_ID() == $member_ID or Validation::check_guardianship_exists($client, $client->get_member_ID(), $member_ID))
                    {
                        //Can update email and password

                        $sql = 
                            "UPDATE `MEMBERS` 
                                SET `member_fname` = ?, 
                                `member_lname` = ?, 
                                `member_DOB` = ?, 
                                `member_gender` = ?, 
                                `member_email` = ?, 
                                `member_password` = ? 
                            WHERE (`member_ID` = ?);";

                        $params = [$member_fname, $member_lname, $member_DOB, $member_gender, $member_email, $hashed_member_password];
                        $param_types = "ssssssi";

                        $update_member = new Query($sql, $params, $param_types);
                        return $update_member;
                    }
                    else if (Validation::check_club_admin($client, $client->get_member_ID()))
                    {
                        //Cannot update email and password

                        $sql = 
                            "UPDATE `MEMBERS` 
                                SET `member_fname` = ?, 
                                `member_lname` = ?, 
                                `member_DOB` = ?, 
                                `member_gender` = ? 
                            WHERE (`member_ID` = ?);";

                        $params = [$club_ID, $member_fname, $member_lname, $member_DOB, $member_gender, $member_email, $member_admin, $hashed_member_password];
                        $param_types = "isssssis";

                        $update_member = new Query($sql, $params, $param_types);
                        return $update_member;
                    }
                    else
                    {
                        throw new System_Error(0, "Client passed as arg does not have the permissions to update the member_ID.", __LINE__);
                    }
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg has unrecognised client type.", __LINE__);
                }
            }
            catch (Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

    /**
     * @param Query_Client $client
     * @param int $member_ID
     * @return Query|null
     */
    public static function delete_member(Query_Client $client, int $member_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::USER)
                {
                    $system = Query_Client::get_system_instance();

                    $member = self::read_member($system, $member_ID)->get_result_as_assoc_array();
                    
                    $check_club = $member[0]["club_ID"];

                    if ($check_club == $client->get_club_ID())
                    {
                        if (Validation::check_club_admin($system, $client->get_member_ID()))
                        {
                            $sql = 
                                "DELETE FROM `MEMBERS` 
                                WHERE (member_ID = ?);";

                            $params = [$member_ID];
                            $param_types = "i";

                            $delete_member = new Query($sql, $params, $param_types);
                            return $delete_member;
                        }
                        else
                        {
                            throw new System_Error(0, "Client is not a club admin.", __LINE__);
                        }
                    }
                    else
                    {
                        throw new System_Error(0, "Client is not a member of the same club as the member_ID passed as arg.", __LINE__);
                    }
                }
                elseif ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "DELETE FROM `MEMBERS` 
                        WHERE (member_ID = ?);";

                    $params = [$member_ID];
                    $param_types = "i";

                    $delete_member = new Query($sql, $params, $param_types);
                    return $delete_member;
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg has unrecognised Client_Type.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        //Custom SQL functions

    /**
     * @param Query_Client $client
     * @param int $club_ID
     * @return Query|null
     */
    public static function read_members_from_club(Query_Client $client, int $club_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "SELECT MEMBERS.*, CONCAT(MEMBERS.member_fname, ' ', MEMBERS.member_lname) AS member_whole_name 
                        FROM `MEMBERS` 
                        WHERE (`club_ID` = ?);";

                    $params = [$club_ID];
                    $param_types = "i";

                    $read_member = new Query($sql, $params, $param_types);
                    return $read_member;
                }
                else if ($client->get_client_type() == Client_Type::USER)
                {
                    if ($club_ID == $client->get_club_ID())
                    {
                        $system = Query_Client::get_system_instance();

                        if (Validation::check_club_admin($client, $client->get_member_ID()) or Validation::check_team_admin($system, $client->get_member_ID()))
                        {
                            $sql = 
                                "SELECT CONCAT(MEMBERS.member_fname, ' ', MEMBERS.member_lname) AS member_whole_name, member_ID  
                                FROM `MEMBERS` 
                                WHERE (`club_ID` = ?);";

                            $params = [$club_ID];
                            $param_types = "i";

                            $read_member = new Query($sql, $params, $param_types);
                            return $read_member;
                        }
                        else
                        {
                            throw new System_Error(0, "Client is not an admin of the club passed as an arg.", __LINE__);
                        }
                    }
                    else
                    {
                        throw new System_Error(0, "Client is not a member of the club passed as an arg.", __LINE__);
                    }
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg has unrecognised client type.", __LINE__);
                }
            }
            catch (Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

    /**
     * @param Query_Client $client
     * @param int $team_ID
     * @return Query|null
     */
    public static function read_members_from_team(Query_Client $client, int $team_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "SELECT `member_ID` 
                        FROM `TEAM_MEMBERS` 
                        WHERE (`team_ID` = ?);";

                    $params = [$team_ID];
                    $param_types = "i";

                    $read_members = new Query($sql, $params, $param_types);
                    return $read_members;
                }
                else if ($client->get_client_type() == Client_Type::USER)
                {
                    if (Validation::check_team_member($client, $client->get_member_ID(), $team_ID))
                    {
                        $sql = 
                            "SELECT CONCAT(MEMBERS.member_fname, ' ', MEMBERS.member_lname) AS member_whole_name 
                                FROM `MEMBERS` 
                            INNER JOIN `TEAM_MEMBERS` 
                                ON MEMBERS.member_ID = TEAM_MEMBERS.member_ID 
                            WHERE (TEAM_MEMBERS.team_ID = ?);";

                        $params = [$team_ID];
                        $param_types = "i";

                        $read_members = new Query($sql, $params, $param_types);
                        return $read_members;
                    }
                    else
                    {
                        throw new System_Error(0, "Client is not a member of the team passed as an arg.", __LINE__);
                    }
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg has unrecognised client type.", __LINE__);
                }
            }
            catch (Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

    /**
     * @param Query_Client $client
     * @param string $member_email
     * @param string $hashed_member_password
     * @return Query|null
     */
    public static function member_login(Query_Client $client, string $member_email, string $hashed_member_password)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "SELECT `member_ID`, `club_ID`, `member_fname`, `member_lname`, `admin` 
                        FROM `MEMBERS` 
                        WHERE (`member_email` = ? and `member_password` = ?);";

                    $params = [$member_email, $hashed_member_password];
                    $param_types = "ss";

                    $read_member = new Query($sql, $params, $param_types);
                    return $read_member;
                }
                else if ($client->get_client_type() == Client_Type::USER)
                {
                    throw new System_Error(0, "Query_Client passed as arg has does not have permission to perform this action.", __LINE__);
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg has unrecognised client type.", __LINE__);
                }
            }
            catch (Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }
    }


/**
 *
 */
class Roles
    {
    /**
     * @return Query|null
     */
    public static function read_all_roles()
        {
            try
            {
                $sql = 
                    "SELECT * 
                    FROM `ROLES`;";

                $params = [];
                $param_types = "";

                $read_roles = new Query($sql, $params, $param_types);
                return $read_roles;
            }
            catch (Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }
    }

/**
 *
 */
class Teams
    {

    /**
     * @param Query_Client $client
     * @param string $team_name
     * @param int $club_ID
     * @return Query|void|null
     */
    public static function create_team(Query_Client $client, string $team_name, int $club_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "INSERT INTO `TEAMS` 
                        (`team_name`, `club_ID`) 
                        VALUES (?, ?);";

                    $params = [$team_name, $club_ID];
                    $param_types = "si";

                    $create_team = new Query($sql, $params, $param_types);
                    return $create_team;
                }
                elseif ($client->get_client_type() == Client_Type::USER)
                {
                    $system = Query_Client::get_system_instance();

                    if (Validation::check_club_admin($system, $client->get_member_ID()))
                    {
                        $sql = 
                        "INSERT INTO `TEAMS` 
                        (`team_name`, `club_ID`) 
                        VALUES (?, ?);";

                        $params = [$team_name, $client->get_club_ID()];
                        $param_types = "si";

                        $create_team = new Query($sql, $params, $param_types);
                        return $create_team;
                    }
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg has unrecognised Client_Type.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

    /**
     * @param Query_Client $client
     * @param string $team_ID
     * @return Query|null
     */
    public static function read_team(Query_Client $client, string $team_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "SELECT * 
                        FROM `TEAMS` 
                        WHERE (`team_ID` = ?);";

                    $params = [$team_ID];
                    $param_types = "i";

                    $read_team = new Query($sql, $params, $param_types);
                    return $read_team;
                }
                else if ($client->get_client_type() == Client_Type::USER)
                {
                    $sql = 
                        "SELECT `team_name`, `team_nickname` 
                        FROM `TEAMS` 
                        WHERE (`team_ID` = ? AND `club_ID` = ?);";

                    $params = [$team_ID, $client->get_club_ID()];
                    $param_types = "ii";

                    $read_team = new Query($sql, $params, $param_types);
                    return $read_team;
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg has unrecognised client type.", __LINE__);
                }
            }
            catch (Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

    /**
     * @param Query_Client $client
     * @param int $team_ID
     * @param string $team_name
     * @param string $team_nickname
     * @return Query|null
     */
    public static function update_team(Query_Client $client, int $team_ID, string $team_name, string $team_nickname)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "UPDATE `TEAMS` 
                            SET `team_name` = ?, 
                            `team_nickname` = ? 
                        WHERE (`team_ID` = ?);";

                    $params = [$team_name, $team_nickname, $team_ID];
                    $param_types = "ssi";

                    $update_team = new Query($sql, $params, $param_types);
                    return $update_team;
                }
                else if ($client->get_client_type() == Client_Type::USER)
                {
                    $system = Query_Client::get_system_instance();

                    if (Validation::check_club_admin($system, $client->get_member_ID()))
                    {
                        $sql = 
                        "UPDATE `TEAMS` 
                            SET `team_name` = ?, 
                            `team_nickname` = ? 
                        WHERE (`team_ID` = ?);";

                    $params = [$team_name, $team_nickname, $team_ID];
                    $param_types = "ssi";

                    $update_team = new Query($sql, $params, $param_types);
                    return $update_team;
                    }
                    else
                    {
                        throw new System_Error(0, "Client passed as arg does not have the permissions to update the club.", __LINE__);
                    }
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg has unrecognised client type.", __LINE__);
                }
            }
            catch (Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

    /**
     * @param Query_Client $client
     * @param int $team_ID
     * @return Query|null
     */
    public static function delete_team(Query_Client $client, int $team_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::USER)
                {
                    throw new System_Error(0, "Query_Client does not have permission to perform this action.", __LINE__);
                }
                elseif ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "DELETE FROM `TEAMS` 
                        WHERE (team_ID = ?);";

                    $params = [$team_ID];
                    $param_types = "i";

                    $delete_team = new Query($sql, $params, $param_types);
                    return $delete_team;
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg has unrecognised Client_Type.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        //Custom SQL functions

    /**
     * @param Query_Client $client
     * @param int $member_ID
     * @return Query|null
     */
    public static function read_teams_from_member(Query_Client $client, int $member_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "SELECT * 
                            FROM `TEAM_MEMBERS` 
                        INNER JOIN `TEAMS` 
                            ON TEAM_MEMBERS.team_ID = TEAMS.team_ID 
                        WHERE (TEAM_MEMBERS.member_ID = ?);";

                    $params = [$member_ID];
                    $param_types = "i";

                    $read_team = new Query($sql, $params, $param_types);
                    return $read_team;
                }
                else if ($client->get_client_type() == Client_Type::USER)
                {
                    $sql = 
                        "SELECT TEAMS.team_name, TEAMS.team_nickname 
                            FROM `TEAM_MEMBERS` 
                        INNER JOIN `TEAMS` 
                            ON TEAM_MEMBERS.team_ID = TEAMS.team_ID 
                        WHERE (TEAM_MEMBERS.member_ID = ? AND TEAMS.club_ID = ?);";

                    $params = [$member_ID, $client->get_club_ID()];
                    $param_types = "ii";

                    $read_team = new Query($sql, $params, $param_types);
                    return $read_team;
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg has unrecognised client type.", __LINE__);
                }
            }
            catch (Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

    /**
     * @param Query_Client $client
     * @param int $member_ID
     * @return Query|null
     */
    public static function read_teams_from_team_admin(Query_Client $client, int $member_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "SELECT * 
                            FROM `TEAM_MEMBERS` 
                        INNER JOIN `TEAMS` 
                            ON TEAM_MEMBERS.team_ID = TEAMS.team_ID 
                        INNER JOIN `ROLES` 
                            ON TEAM_MEMBERS.role_ID = ROLES.role_ID 
                        INNER JOIN `MEMBERS` 
                            ON TEAM_MEMBERS.member_ID = MEMBERS.member_ID 
                        WHERE (TEAM_MEMBERS.member_ID = ? AND (ROLES.team_admin = 1 OR MEMBERS.admin = 1));";

                    $params = [$member_ID];
                    $param_types = "i";

                    $read_team = new Query($sql, $params, $param_types);
                    return $read_team;
                }
                else if ($client->get_client_type() == Client_Type::USER)
                {
                    $sql = 
                        "SELECT TEAMS.team_name, TEAMS.team_nickname 
                        FROM `TEAM_MEMBERS` 
                        INNER JOIN `TEAMS` 
                            ON TEAM_MEMBERS.team_ID = TEAMS.team_ID 
                        INNER JOIN `ROLES` 
                            ON TEAM_MEMBERS.role_ID = ROLES.role_ID 
                        INNER JOIN `MEMBERS` 
                            ON TEAM_MEMBERS.member_ID = MEMBERS.member_ID 
                        WHERE (TEAM_MEMBERS.member_ID = ? AND (ROLES.team_admin = 1 OR MEMBERS.admin = 1));";

                    $params = [$member_ID];
                    $param_types = "i";

                    $read_team = new Query($sql, $params, $param_types);
                    return $read_team;
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg has unrecognised client type.", __LINE__);
                }
            }
            catch (Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

    /**
     * @param Query_Client $client
     * @param int $club_ID
     * @return Query|null
     */
    public static function read_teams_from_club(Query_Client $client, int $club_ID)
        {  
            try
            {
                if ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "SELECT * 
                            FROM `TEAMS` 
                        WHERE (`club_ID` = ?);";

                    $params = [$club_ID];
                    $param_types = "i";

                    $read_team = new Query($sql, $params, $param_types);
                    return $read_team;
                }
                else if ($client->get_client_type() == Client_Type::USER)
                {
                    if ($club_ID == $client->get_club_ID())
                    {
                        $sql = 
                            "SELECT `team_name`, `team_nickname` 
                                FROM `TEAMS` 
                            WHERE (`club_ID` = ?);";

                        $params = [$club_ID];
                        $param_types = "i";

                        $read_team = new Query($sql, $params, $param_types);
                        return $read_team;
                    }
                    else
                    {
                        throw new System_Error(0, "Client is not a member of the club passed as an arg.", __LINE__);
                    }
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg has unrecognised client type.", __LINE__);
                }
            }
            catch (Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

    /**
     * @param Query_Client $client
     * @param int $event_ID
     * @return Query|null
     */
    public static function read_team_from_event(Query_Client $client, int $event_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "SELECT `team_ID`
                            FROM `EVENTS` 
                        WHERE (`event_ID` = ?);";

                    $params = [$event_ID];
                    $param_types = "i";

                    $read_team = new Query($sql, $params, $param_types);
                    return $read_team;
                }
                else if ($client->get_client_type() == Client_Type::USER)
                {
                    $sql = 
                        "SELECT TEAMS.team_name, TEAMS.team_nickname 
                            FROM `TEAMS` 
                        INNER JOIN `EVENTS` 
                            ON TEAMS.team_ID = EVENTS.team_ID 
                        INNER JOIN `TEAM_MEMBERS`
                            ON TEAMS.team_ID = TEAM_MEMBERS.team_ID 
                        WHERE (EVENTS.event_ID = ? AND TEAM_MEMBERS.member_ID = ?);";

                    $params = [$event_ID, $client->get_member_ID()];
                    $param_types = "ii";

                    $read_team = new Query($sql, $params, $param_types);
                    return $read_team;
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg has unrecognised client type.", __LINE__);
                }
            }
            catch (Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

    /**
     * @param Query_Client $client
     * @param int $availability_ID
     * @return Query|null
     */
    public static function read_team_from_availability(Query_Client $client, int $availability_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "SELECT EVENTS.team_ID 
                            FROM `AVAILABILITY` 
                        INNER JOIN `EVENTS` 
                            ON AVAILABILITY.event_ID = EVENTS.event_ID 
                        WHERE (AVAILABILITY.participant_ID = ?);";

                    $params = [$availability_ID];
                    $param_types = "i";

                    $read_team = new Query($sql, $params, $param_types);
                    return $read_team;
                }
                else if ($client->get_client_type() == Client_Type::USER)
                {
                    throw new System_Error(0, "Client of type USER does not have access to this function.", __LINE__);
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg has unrecognised client type.", __LINE__);
                }
            }
            catch (Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

    /**
     * @param Query_Client $client
     * @param int $participant_ID
     * @return Query|null
     */
    public static function read_team_from_participation(Query_Client $client, int $participant_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "SELECT EVENTS.team_ID 
                            FROM `PARTICIPANTS` 
                        INNER JOIN `EVENTS` 
                            ON PARTICIPANTS.event_ID = EVENTS.event_ID 
                        WHERE (PARTICIPANTS.participant_ID = ?);";

                    $params = [$participant_ID];
                    $param_types = "i";

                    $read_team = new Query($sql, $params, $param_types);
                    return $read_team;
                }
                else if ($client->get_client_type() == Client_Type::USER)
                {
                    throw new System_Error(0, "Client of type USER does not have access to this function.", __LINE__);
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg has unrecognised client type.", __LINE__);
                }
            }
            catch (Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }
    }

/**
 *
 */
class Team_Members
    {
    /**
     * @param Query_Client $client
     * @param int $member_ID
     * @param int $team_ID
     * @param int $role_ID
     * @return Query|null
     */
    public static function create_team_member(Query_Client $client, int $member_ID, int $team_ID, int $role_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "INSERT INTO `TEAM_MEMBERS` 
                        (`member_ID`, `team_ID`, `role_ID`) 
                        VALUES (?, ?, ?);";

                    $params = [$member_ID, $team_ID, $role_ID];
                    $param_types = "iii";

                    $create_team_member = new Query($sql, $params, $param_types);
                    return $create_team_member;
                }
                elseif ($client->get_client_type() == Client_Type::USER)
                {
                    $system = Query_Client::get_system_instance();

                    if (Validation::check_club_admin($system, $client->get_member_ID()))
                    {
                        $club_ID = Clubs::read_club_from_member($system, $member_ID)->get_result_as_indexed_array()[0][0];

                        if ($club_ID == $client->get_club_ID())
                        {
                            $sql = 
                                "INSERT INTO `TEAM_MEMBERS` 
                                (`member_ID`, `team_ID`, `role_ID`) 
                                VALUES (?, ?, ?);";

                            $params = [$member_ID, $team_ID, $role_ID];
                            $param_types = "iii";

                            $create_team_member = new Query($sql, $params, $param_types);
                            return $create_team_member;
                        }
                        else
                        {
                            throw new System_Error(0, "Query_Client is not a member of the same club as the member_ID passed as an arg.", __LINE__);
                        }
                    }
                    else
                    {
                        throw new System_Error(0, "Query_Client passed as arg has insufficient permissions (not a club admin).", __LINE__);
                    }
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg has unrecognised Client_Type.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

    /**
     * @param Query_Client $client
     * @param int $team_member_ID
     * @return Query|null
     */
    public static function read_team_member(Query_Client $client, int $team_member_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "SELECT * 
                        FROM `TEAM_MEMBERS` 
                        WHERE (`team_member_ID` = ?);";

                    $params = [$team_member_ID];
                    $param_types = "i";

                    $read_team_member = new Query($sql, $params, $param_types);
                    return $read_team_member;
                }
                elseif ($client->get_client_type() == Client_Type::USER)
                {
                    $system = Query_Client::get_system_instance();

                    $club_ID = Clubs::read_club_from_team_member($system, $team_member_ID)->get_result_as_assoc_array()[0]["club_ID"];

                    if ($club_ID == $client->get_club_ID())
                    {
                        $sql = 
                            "SELECT CONCAT(MEMBERS.member_fname, ' ', MEMBERS.member_lname) AS member_whole_name, 
                            TEAMS.team_name, ROLES.role_name 
                                FROM `TEAM_MEMBERS` 
                            INNER JOIN `MEMBERS` 
                                ON TEAM_MEMBERS.member_ID = MEMBERS.member_ID 
                            INNER JOIN `TEAMS` 
                                ON TEAM_MEMBERS.team_ID = TEAMS.team_ID 
                            INNER JOIN `ROLES` 
                                ON TEAM_MEMBERS.role_ID = ROLES.role_ID 
                            WHERE (`team_member_ID` = ?);";

                        $params = [$team_member_ID];
                        $param_types = "i";

                        $read_team_member = new Query($sql, $params, $param_types);
                        return $read_team_member;
                    }
                    else
                    {
                        throw new System_Error(0, "Query_Client is not a member of the same club as the member_ID passed as an arg.", __LINE__);
                    }
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg has unrecognised Client_Type.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

    /**
     * @param Query_Client $client
     * @param int $team_member_ID
     * @param int $role_ID
     * @return Query|null
     */
    public static function update_team_member(Query_Client $client, int $team_member_ID, int $role_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "UPDATE `TEAM_MEMBERS` 
                            SET `role_ID` = ? 
                        WHERE (`team_member_ID` = ?);";

                    $params = [$team_member_ID];
                    $param_types = "i";

                    $update_team_member = new Query($sql, $params, $param_types);
                    return $update_team_member;
                }
                else if ($client->get_client_type() == Client_Type::USER)
                {
                    $system = Query_Client::get_system_instance();

                    $club_ID = Clubs::read_club_from_team_member($system, $team_member_ID)->get_result_as_assoc_array()[0]["club_ID"];

                    if ($client->get_club_ID() == $club_ID)
                    {
                        if (Validation::check_club_admin($system, $client->get_member_ID()))
                        {
                            $sql = 
                                "UPDATE `TEAM_MEMBERS` 
                                    SET `role_ID` = ? 
                                WHERE (`team_member_ID` = ?);";

                            $params = [$team_member_ID];
                            $param_types = "ii";

                            $update_team_member = new Query($sql, $params, $param_types);
                            return $update_team_member;
                        }
                        else
                        {
                            throw new System_Error(0, "Client passed as arg does not have the permissions to update team_members.", __LINE__);
                        }
                    }
                    else
                    {
                        throw new System_Error(0, "Query_Client passed as arg is not a member of the same club as the team_member_ID.", __LINE__);
                    }
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg has unrecognised client type.", __LINE__);
                }
            }
            catch (Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

    /**
     * @param Query_Client $client
     * @param int $team_member_ID
     * @return Query|null
     */
    public static function delete_team_member(Query_Client $client, int $team_member_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "DELETE FROM `TEAM_MEMBERS` 
                        WHERE (`team_member_ID` = ?);";

                    $params = [$team_member_ID];
                    $param_types = "i";

                    $delete_team_member = new Query($sql, $params, $param_types);
                    return $delete_team_member;
                }
                else if ($client->get_client_type() == Client_Type::USER)
                {
                    $system = Query_Client::get_system_instance();

                    $club_ID = Clubs::read_club_from_team_member($system, $team_member_ID)->get_result_as_assoc_array()[0]["club_ID"];

                    if ($client->get_club_ID() == $club_ID)
                    {
                        if (Validation::check_club_admin($system, $client->get_member_ID()))
                        {
                            $sql = 
                                "DELETE FROM `TEAM_MEMBERS` 
                                WHERE (`team_member_ID` = ?);";

                            $params = [$team_member_ID];
                            $param_types = "i";

                            $delete_team_member = new Query($sql, $params, $param_types);
                            return $delete_team_member;
                        }
                        else
                        {
                            throw new System_Error(0, "Client passed as arg does not have the permissions to delete team_members.", __LINE__);
                        }
                    }
                    else
                    {
                        throw new System_Error(0, "Query_Client passed as arg is not a member of the same club as the team_member_ID.", __LINE__);
                    }
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg has unrecognised client type.", __LINE__);
                }
            }
            catch (Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        //Custom SQL functions

    /**
     * @param Query_Client $client
     * @param int $team_member_ID
     * @return Query|null
     */
    public static function read_team_from_team_member(Query_Client $client, int $team_member_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "SELECT `team_ID` 
                        FROM `TEAM_MEMBERS` 
                        WHERE (`team_member_ID` = ?);";

                    $params = [$team_member_ID];
                    $param_types = "i";

                    $read_team = new Query($sql, $params, $param_types);
                    return $read_team;
                }
                else if ($client->get_client_type() == Client_Type::USER)
                {
                    $system = Query_Client::get_system_instance();

                    $club_ID = Clubs::read_club_from_team_member($system, $team_member_ID)->get_result_as_assoc_array()[0]["club_ID"];

                    if ($client->get_club_ID() == $club_ID)
                    {
                        $sql = 
                            "SELECT TEAMS.team_name`
                                FROM `TEAM_MEMBERS` 
                            INNER JOIN `TEAMS` 
                                ON TEAM_MEMBERS.team_ID = TEAMS.team_ID 
                            WHERE (`team_member_ID` = ?);";

                        $params = [$team_member_ID];
                        $param_types = "i";

                        $read_team = new Query($sql, $params, $param_types);
                        return $read_team;
                    }
                    else
                    {
                        throw new System_Error(0, "Query_Client is not a member of the same club as the team_member passed as arg.", __LINE__);
                    }
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg has unrecognised client type.", __LINE__);
                }
            }
            catch (Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

    /**
     * @param Query_Client $client
     * @param int $member_ID
     * @return Query|null
     */
    public static function read_team_members_from_member(Query_Client $client, int $member_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "SELECT * 
                        FROM `TEAM_MEMBERS` 
                        WHERE (`member_ID` = ?);";

                    $params = [$member_ID];
                    $param_types = "i";

                    $read_team_members = new Query($sql, $params, $param_types);
                    return $read_team_members;
                }
                else if ($client->get_client_type() == Client_Type::USER)
                {
                    throw new System_Error(0, "Client does not have the sufficient permissions to perform this action.", __LINE__);
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg has unrecognised client type.", __LINE__);
                }
            }
            catch (Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

    /**
     * @param Query_Client $client
     * @param int $team_member_ID
     * @return Query|null
     */
    public static function read_member_from_team_member(Query_Client $client, int $team_member_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "SELECT `member_ID` 
                        FROM `TEAM_MEMBERS` 
                        WHERE (`team_member_ID` = ?);";

                    $params = [$team_member_ID];
                    $param_types = "i";

                    $read_team = new Query($sql, $params, $param_types);
                    return $read_team;
                }
                else if ($client->get_client_type() == Client_Type::USER)
                {
                    $system = Query_Client::get_system_instance();

                    $club_ID = Clubs::read_club_from_team_member($system, $team_member_ID)->get_result_as_assoc_array()[0]["club_ID"];

                    if ($client->get_club_ID() == $club_ID)
                    {
                        $sql = 
                            "SELECT CONCAT(MEMBERS.member_fname, ' ', MEMBERS.member_lname) AS member_whole_name 
                                FROM `TEAM_MEMBERS` 
                            INNER JOIN `MEMBERS` 
                                ON TEAM_MEMBERS.memvber_ID = MEMBERS.member_ID 
                            WHERE (TEAM_MEMBERS.team_member_ID = ?);";

                        $params = [$team_member_ID];
                        $param_types = "i";

                        $read_team = new Query($sql, $params, $param_types);
                        return $read_team;
                    }
                    else
                    {
                        throw new System_Error(0, "Query_Client is not a member of the same club as the team_member passed as arg.", __LINE__);
                    }
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg has unrecognised client type.", __LINE__);
                }
            }
            catch (Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

    /**
     * @param Query_Client $client
     * @param int $member_ID
     * @param int $team_ID
     * @return Query|null
     */
    public static function read_team_member_from_member_and_team(Query_Client $client, int $member_ID, int $team_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "SELECT * 
                        FROM `TEAM_MEMBERS` 
                        WHERE (`member_ID` = ? and `team_ID` = ?);";

                    $params = [$member_ID, $team_ID];
                    $param_types = "ii";

                    $read_team_members = new Query($sql, $params, $param_types);
                    return $read_team_members;
                }
                else if ($client->get_client_type() == Client_Type::USER)
                {
                    throw new System_Error(0, "Client does not have the sufficient permissions to perform this action.", __LINE__);
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg has unrecognised client type.", __LINE__);
                }
            }
            catch (Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

    /**
     * @param Query_Client $client
     * @param int $member_ID
     * @param int $team_ID
     * @param int $role_ID
     * @return Query|null
     */
    public static function update_team_member_from_member_and_team(Query_Client $client, int $member_ID, int $team_ID, int $role_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "UPDATE `TEAM_MEMBERS` 
                            SET `role_ID` = ? 
                        WHERE (`member_ID` = ? and `team_ID` = ?);";

                    $params = [$role_ID, $member_ID, $team_ID];
                    $param_types = "iii";

                    $update_team_member = new Query($sql, $params, $param_types);
                    return $update_team_member;
                }
                else if ($client->get_client_type() == Client_Type::USER)
                {
                    $system = Query_Client::get_system_instance();

                    $club_ID = Clubs::read_club_from_member($system, $member_ID)->get_result_as_assoc_array()[0]["club_ID"];

                    if ($client->get_club_ID() == $club_ID)
                    {
                        if (Validation::check_club_admin($system, $client->get_member_ID()))
                        {
                            $sql = 
                                "UPDATE `TEAM_MEMBERS` 
                                    SET `role_ID` = ? 
                                WHERE (`member_ID` = ? and `team_ID` = ?);";

                            $params = [$role_ID, $member_ID, $team_ID];
                            $param_types = "iii";

                            $update_team_member = new Query($sql, $params, $param_types);
                            return $update_team_member;
                        }
                        else
                        {
                            throw new System_Error(0, "Client passed as arg does not have the permissions to update team_members.", __LINE__);
                        }
                    }
                    else
                    {
                        throw new System_Error(0, "Query_Client passed as arg is not a member of the same club as the team_member_ID.", __LINE__);
                    }
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg has unrecognised client type.", __LINE__);
                }
            }
            catch (Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

    /**
     * @param Query_Client $client
     * @param int $member_ID
     * @param int $team_ID
     * @return Query|null
     */
    public static function delete_team_member_from_member_and_team(Query_Client $client, int $member_ID, int $team_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "DELETE FROM `TEAM_MEMBERS` 
                        WHERE (`member_ID` = ? and `team_ID` = ?);";

                    $params = [$member_ID, $team_ID];
                    $param_types = "ii";

                    $delete_team_member = new Query($sql, $params, $param_types);
                    return $delete_team_member;
                }
                else if ($client->get_client_type() == Client_Type::USER)
                {
                    $system = Query_Client::get_system_instance();

                    $club_ID = Clubs::read_club_from_member($system, $member_ID)->get_result_as_assoc_array()[0]["club_ID"];

                    if ($client->get_club_ID() == $club_ID)
                    {
                        if (Validation::check_club_admin($system, $client->get_member_ID()))
                        {
                            $sql = 
                                "DELETE FROM `TEAM_MEMBERS` 
                                WHERE (`member_ID` = ? and `team_ID` = ?);";

                            $params = [$member_ID, $team_ID];
                            $param_types = "ii";

                            $delete_team_member = new Query($sql, $params, $param_types);
                            return $delete_team_member;
                        }
                        else
                        {
                            throw new System_Error(0, "Client passed as arg does not have the permissions to delete team_members.", __LINE__);
                        }
                    }
                    else
                    {
                        throw new System_Error(0, "Query_Client passed as arg is not a member of the same club as the team_member_ID.", __LINE__);
                    }
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg has unrecognised client type.", __LINE__);
                }
            }
            catch (Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

    }

    /**
     * Class containing static methods for consistent validation across the database CRUD methods.
     * 
     * It is important to note there are numerous cases where it is only possible to provide validation within the SQL query itself, however these static methods should be used wherever possible.
     */
    class Validation
    {
        /**
         * Check if a club_ID exists in the database.
         * 
         * @param int $club_ID The club_ID to check.
         * 
         * @return bool|null True if club_ID exists; false if club_ID does not exist; null if an error occurs.
         */
        public static function check_club_ID_exists(int $club_ID)
        {
            try
            {
                // SQL query to select club_ID from CLUBS
                $sql = 
                    "SELECT `club_ID` 
                    FROM `CLUBS` 
                    WHERE `club_ID` = ?";

                $params = [$club_ID];
                $param_types = "i";

                $check_club_ID = new Query($sql, $params, $param_types);

                // Check if the result is null
                // If the result is null, the club_ID cannot exist in the database
                // In default case return null (unknown case)
                switch ($check_club_ID->check_null_result())
                {
                    case false:
                        return true;
                    case true:
                        return false;
                    default:
                        return null;
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        /**
         * Check if a member ID exists in the database.
         *
         * @param int $member_ID The member ID to check.
         * @return bool|null True if the member ID exists; false if it does not; null if error occurs.
         */
        public static function check_member_ID_exists(int $member_ID)
        {
            try
            {
                // SQL query to select member_ID from MEMBERS
                $sql = 
                    "SELECT `member_ID` 
                    FROM `MEMBERS` 
                    WHERE `member_ID` = ?";

                $params = [$member_ID];
                $param_types = "i";

                $check_member_ID = new Query($sql, $params, $param_types);

                // Check if the result is null
                // If the result is null, the member_ID cannot exist in the database
                // In default case return null (unknown case)
                switch ($check_member_ID->check_null_result())
                {
                    case false:
                        return true;
                    case true:
                        return false;
                    default:
                        return null;
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        /**
         * Check if a member is part of a team.
         *
         * @param Query_Client $client The client performing the action (must be of type SYSTEM).
         * @param int $member_ID The member_ID to check.
         * @param int $team_ID The team_ID to check.
         * 
         * @return bool|null True if team_member record exists; false if team_member record does not exist; null if error occurs.
         */
        public static function check_team_member(Query_Client $client, int $member_ID, int $team_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::SYSTEM) 
                {
                    // SQL query to select team_member_ID from TEAM_MEMBERS table where member_ID and team_ID match the given IDs
                    $sql = 
                        "SELECT DISTINCT `team_member_ID` 
                        FROM `TEAM_MEMBERS` 
                        WHERE (member_ID = ? AND team_ID = ?);";

                    $params = [$member_ID, $team_ID];
                    $param_types = "ii";

                    $check_team_member = new Query($sql, $params, $param_types);

                    // Check if the result is null
                    // If the result is null, the team_member_ID cannot exist in the database
                    // In default case return null (unknown case)
                    switch ($check_team_member->check_null_result())
                    {
                        case true:
                            return false;
                        case false:
                            return true;
                        default:
                            return null;
                    }
                }
                else if ($client->get_client_type() == Client_Type::USER)
                {
                    throw new System_Error(0, "Query_Client does not have permission to perform this action.", __LINE__);
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg has unrecognised Client_Type.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        /**
         * Check if a given member is a team admin. 
         * 
         * If $team_ID is left null, the function checks if the member is a team admin of any team.
         * 
         * If $team_ID is assigned a value, the function checks if the member is a team admin of that specific team.
         *
         * @param Query_Client $client The client performing the query.
         * 
         * @param int $member_ID The member ID to check for admin status.
         * @param int|null $team_ID The team ID to check for admin status (optional).
         * 
         * @return bool|null True if the member is a team admin, false if not, and null in case of an error.
         */
        public static function check_team_admin(Query_Client $client, int $member_ID, int $team_ID = null)
        {
            try
            {
                // Initialize the boolean flag team_admin variable 
                $team_admin = false;

                if ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    // If the client is SYSTEM, consider the member as an automatic team admin
                    $team_admin = true;
                    return $team_admin;
                }
                else if ($client->get_client_type() == Client_Type::USER)
                {
                    // If the team_ID is not provided, search for admin status in any team
                    if ($team_ID == null)
                    {
                        // SQL query to select team_admin status from TEAM_MEMBERS and ROLES tables where member_ID matches the given member_ID
                        $sql = 
                            "SELECT DISTINCT ROLES.team_admin 
                                FROM `TEAM_MEMBERS`
                            INNER JOIN `ROLES` 
                                ON TEAM_MEMBERS.role_ID = ROLES.role_ID 
                            WHERE member_ID = ?;";

                        $params = [$member_ID];
                        $param_types = "i";
                    }
                    // If the team_ID is provided, search for admin status in the specific team
                    else
                    {
                        // SQL query to select team_admin status from TEAM_MEMBERS and ROLES tables where member_ID and team_ID match the given IDs
                        $sql = 
                            "SELECT DISTINCT ROLES.team_admin 
                                FROM `TEAM_MEMBERS`
                            INNER JOIN `ROLES` 
                                ON TEAM_MEMBERS.role_ID = ROLES.role_ID 
                            WHERE member_ID = ? 
                                AND team_ID = ?;";

                        $params = [$member_ID, $team_ID];
                        $param_types = "ii";
                    }

                    $is_team_admin = new Query($sql, $params, $param_types);

                    // As only one value can be selected, check first item in multi-dimensional result array
                    switch ($is_team_admin->get_result_as_indexed_array()[0][0])
                    {
                        case 0:
                            return $team_admin;
                        case 1:
                            $team_admin = true;
                            return $team_admin;
                        default:
                            throw new System_Error(0, "Query_Client::check_team_admin() result is not of type bool.", __LINE__);
                    }
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg has unrecognised Client_Type.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        /**
         * Check if a member is a club admin.
         *
         * @param Query_Client $client The client performing the query.
         * 
         * @param int $member_ID The member ID to check for club admin status.
         * 
         * @return bool|null True if the member is a club admin; false if member is not a club admin; null if an error occurs.
         */
        public static function check_club_admin(Query_Client $client, int $member_ID)
        {
            try
            {
                // Initialize the boolean flag club admin variable
                $admin = false;

                if ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    // If the client is of type SYSTEM, consider the member as an automatic club admin
                    $admin = true;
                    return $admin;
                }
                else if ($client->get_client_type() == Client_Type::USER)
                {
                    // Check if the member_ID of the client matches the provided member_ID (identity checking)
                    if ($member_ID == $client->get_member_ID())
                    {
                        // SQL query to select admin status from MEMBERS table
                        $sql = 
                            "SELECT DISTINCT admin 
                            FROM `MEMBERS`
                            WHERE member_ID = ?;";

                        $params = [$client->get_member_ID()];
                        $param_types = "i";

                        $is_club_admin = new Query($sql, $params, $param_types);

                        // As only one value can be selected, check first item in multi-dimensional result array
                        switch ($is_club_admin->get_result_as_indexed_array()[0][0])
                        {
                            case 0:
                                return $admin;
                            case 1:
                                $admin = true;
                                return $admin;
                            default:
                                throw new System_Error(0, "check_club_admin() result is not of type bool.", __LINE__);
                        }
                    }
                    else
                    {
                        throw new System_Error(0, "Client member_ID does not match the arg member_ID.", __LINE__);
                    }
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        /**
         * Check if a guardianship relationship exists between a parent and a child.
         *
         * @param Query_Client $client The client performing the query.
         * 
         * @param int $parent_ID The parent's member ID.
         * @param int $child_ID The child's member ID.
         * 
         * @return bool|null True if the guardianship record exists; false if no guardianship record exists; null if an error occurs.
         */
        public static function check_guardianship_exists(Query_Client $client, int $parent_ID, int $child_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::USER)
                {
                    // Ensure the client's member ID matches the parent ID OR the child_ID
                    if (($parent_ID == $client->get_member_ID()) or ($child_ID == $client->get_member_ID()))
                    {
                        // SQL query which selects the guardianship_ID from a given parent_ID and child_ID
                        $sql = 
                            "SELECT DISTINCT `guardianship_ID` 
                            FROM `GUARDIANSHIP` 
                            WHERE (`parent_ID` = ? AND `child_ID` = ?);";
        
                        $params = [$parent_ID, $child_ID];
                        $param_types = "ii";
        
                        $check_is_parent = new Query($sql, $params, $param_types);

                        // Check if the result is null
                        // If the result is null, the guardianship_ID cannot exist in the database
                        // In default case return null (unknown case)
                        switch ($check_is_parent->check_null_result())
                        {
                            case true:
                                return false;
                            case false:
                                return true;
                            default:
                                return null;
                        }
                    }
                    else
                    {
                        throw new System_Error(0, "Client member_ID does not match the arg member_ID.", __LINE__);
                    }
                }
                else if ($client->get_client_type() == Client_Type::SYSTEM) 
                {
                    // SQL query which selects the guardianship_ID from a given parent_ID and child_ID
                    $sql = 
                        "SELECT `guardianship_ID` 
                        FROM `GUARDIANSHIP` 
                        WHERE (`parent_ID` = ? AND `child_ID` = ?);";

                    $params = [$parent_ID, $child_ID];
                    $param_types = "ii";

                    $check_is_parent = new Query($sql, $params, $param_types);

                    // Check if the result is null
                    // If the result is null, the guardianship_ID cannot exist in the database
                    // In default case return null (unknown case)
                    switch ($check_is_parent->check_null_result())
                    {
                        case true:
                            return false;
                        case false:
                            return true;
                        default:
                            return null;
                    }
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg has unrecognised Client_Type.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        /**
         * Check if a member is a parent by checking if they have any guardianship records as a parent.
         *
         * @param Query_Client $client The client performing the query.
         * 
         * @param int $parent_ID The parent's member ID.
         * 
         * @return bool|null True if the member is a parent; false if member is not a parent; null if an error occurs.
         */
        public static function check_is_parent(Query_Client $client, int $parent_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::USER)
                {
                    // Ensure the client's member ID matches the parent ID.
                    if ($parent_ID == $client->get_member_ID())
                    {
                        // Check if parent_ID param exists within a record in the GUARDIANSHIP table
                        $sql = 
                            "SELECT DISTINCT `guardianship_ID` 
                            FROM `GUARDIANSHIP` 
                            WHERE (parent_ID = ?);";
        
                        $params = [$parent_ID];
                        $param_types = "i";
        
                        $check_is_parent = new Query($sql, $params, $param_types);
        
                        // Check if the result is null
                        // If the result is null, the guardianship_ID cannot exist in the database
                        // In default case return null (unknown case)
                        switch ($check_is_parent->check_null_result())
                        {
                            case true:
                                return false;
                            case false:
                                return true;
                            default:
                                return null;
                        }
                    }
                    else
                    {
                        throw new System_Error(0, "Client member_ID does not match the arg member_ID.", __LINE__);
                    }
                }
                else if ($client->get_client_type() == Client_Type::SYSTEM) 
                {
                    // Check if parent_ID param exists within a record in the GUARDIANSHIP table
                    $sql = 
                        "SELECT DISTINCT `guardianship_ID` 
                        FROM `GUARDIANSHIP` 
                        WHERE (parent_ID = ?);";

                    $params = [$parent_ID];
                    $param_types = "i";

                    $check_is_parent = new Query($sql, $params, $param_types);

                    // Check if the result is null
                    // If the result is null, the guardianship_ID cannot exist in the database
                    // In default case return null (unknown case)
                    switch ($check_is_parent->check_null_result())
                    {
                        case true:
                            return false;
                        case false:
                            return true;
                        default:
                            return null;
                    }
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg has unrecognised Client_Type.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        /**
         * Check if a member is a child by checking if they have any guardianship records as a child.
         *
         * @param Query_Client $client The client performing the query.
         * 
         * @param int $child_ID The child's member ID.
         * 
         * @return bool|null True if the member is a child; false if member is not a child; null if an error occurs.
         */
        public static function check_is_child(Query_Client $client, int $child_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::USER)
                {
                    // Ensure the client's member ID matches the child ID (identity checking)
                    if ($child_ID == $client->get_member_ID())
                    {
                        // Check if child_ID param exists within a record in the GUARDIANSHIP table
                        $sql = 
                            "SELECT DISTINCT `guardianship_ID` 
                            FROM `GUARDIANSHIP` 
                            WHERE (child_ID = ?);";

                        $params = [$child_ID];
                        $param_types = "i";

                        $check_is_parent = new Query($sql, $params, $param_types);

                        // Check if the result is null
                        // If the result is null, the guardianship_ID cannot exist in the database
                        // In default case return null (unknown case)
                        switch ($check_is_parent->check_null_result())
                        {
                            case true:
                                return false;
                            case false:
                                return true;
                            default:
                                return null;
                        }
                    }
                    else
                    {
                        throw new System_Error(0, "Client member_ID does not match the arg member_ID.", __LINE__);
                    }
                }
                else if ($client->get_client_type() == Client_Type::SYSTEM) 
                {
                    // Check if child_ID param exists within a record in the GUARDIANSHIP table
                    $sql = 
                        "SELECT DISTINCT `guardianship_ID` 
                        FROM `GUARDIANSHIP` 
                        WHERE (child_ID = ?);";

                    $params = [$child_ID];
                    $param_types = "i";

                    $check_is_parent = new Query($sql, $params, $param_types);

                    // Check if the result is null
                    // If the result is null, the guardianship_ID cannot exist in the database
                    // In default case return null (unknown case)
                    switch ($check_is_parent->check_null_result())
                    {
                        case true:
                            return false;
                        case false:
                            return true;
                        default:
                            return null;
                    }
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg has unrecognised Client_Type.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        /**
         * Uses PHP filter_var() function to check if an email is valid.
         * 
         * @param string $email The email to validate.
         * 
         * @return bool|null True for valid email; false for invalid email; null if an error occurs.
         */
        public static function check_valid_email(string $email)
        {
            try
            {
                switch (filter_var($email, FILTER_VALIDATE_EMAIL))
                {
                    case false:
                        return false;
                    default:
                        return true;
                }
            }
            catch (Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }
    }

    /**
     * A utility class that provides common functions for typical system function.
     */
    class System_Utility
    {
        /**
         * Converts a DateTime object into a string in the format "weekday day month".
         * 
         * @param DateTime $date The date to convert.
         * 
         * @return string|null The formatted date string; null if error occurs.
         */
        public static function get_date_as_string(DateTime $date)
        {
            try
            {
                // Format the date as a string in the format of "weekday day month"
                $string = date_format($date, 'l jS F');
                
                return $string;
            }
            catch (Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        /**
         * Converts a time value into a string containing the phrase "Meet at " followed by the time value.
         * 
         * @param mixed $meet_time The time value to convert.
         * 
         * @return string|null The formatted time string, or null if an error occurred.
         */
        public static function get_meet_time_as_string($meet_time)
        {
            try
            {
                // If the input is not a string, convert it to a string
                switch (false)
                {
                    case is_string($meet_time):
                        $meet_time = strval($meet_time);
                        break;
                    default:
                        break;
                }

                $string = "Meet at " . $meet_time;
                
                return $string;
            }
            catch (Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        /**
         * Converts a time value into a string containing the phrase "Starts at " followed by the time value.
         * 
         * @param mixed $start_time The time value to convert.
         * 
         * @return string|null The formatted time string, or null if an error occurred.
         */
        public static function get_start_time_as_string($start_time)
        {
            try
            {
                // If the input is not a string, convert it to a string
                switch (false)
                {
                    case is_string($start_time):
                        $start_time = strval($start_time);
                        break;
                    default:
                        break;
                }

                $string = "Starts at " . $start_time;
                
                return $string;
            }
            catch (Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        /**
         * Generates a SHA-3-224 hash of a given input string.
         * 
         * @param mixed $data The input data to hash.
         * 
         * @return string|null The hash value as a hexadecimal string; null if an error occurs.
         */
        public static function hash($data)
        {
            try
            {
                // If the input is not a string, convert it to a string
                switch (false)
                {
                    case is_string($data):
                        $data = strval($data);
                        break;
                    default:
                        break;
                }

                $hash = hash("sha3-224", $data);

                return $hash;
            }
            catch (Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        /**
         * Encrypts input data using base64 encoding.
         * 
         * @param mixed $data The data to encrypt.
         * 
         * @return string|null The encrypted data as a base64-encoded string; null if an error occurs.
         */
        public static function encrypt($data)
        {
            try
            {
                switch (true)
                {
                    // If the input is an array, escape it first using the escape_array method
                    case is_array(($data)):
                        $data = self::escape_array($data);
                    // If the input is not a string, convert it to a string
                    case !(is_string($data)):
                        $data = strval($data);
                        break;
                    default:
                        break;
                }

                $encrypted_data = base64_encode($data);

                return $encrypted_data;
            }
            catch (Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        /**
         * Decrypts data that has been encoded using base64 encoding.
         * 
         * @param mixed $data The encoded data to decrypt.
         * 
         * @return string|null The decrypted data as a string; null if an error occurs.
         */
        public static function decrypt($data)
        {
            try
            {
                // If the input is not a string, convert it to a string
                switch (true)
                {
                    case !(is_string($data)):
                        $data = strval($data);
                        break;
                    default:
                        break;
                }

                $decrypted_data = base64_decode($data);

                return $decrypted_data;
            }
            catch (Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        /**
         * Recursively combines all the elements of a multi-dimensional array into a single string.
         * 
         * @param array $array The input array to escape.
         * 
         * @return string|null The escaped string, or null if an error occurred.
         */
        public static function escape_array($array)
        {
            try
            {
                while (is_array($array))
                {
                    $array = implode("", $array);
                }

                return $array;
            }
            catch (Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        /**
         * Prints the navbar for the website. 
         * 
         * Calling from a function ensures consistency across the site.
         * 
         * @param string $club_name The name of the club being displayed.
         * 
         * @param bool $club_admin Whether the user is a club admin or not.
         * 
         * @param bool $team_admin Whether the user is a team admin or not.
         * 
         */
        public static function print_navbar(string $club_name, bool $club_admin, bool $team_admin)
        {


            $navbar = "<nav class='navbar sticky-top navbar-expand-lg navbar-dark bg-dark'>
            <div class='container-fluid bg-transparent'>
                <a href='home.php' class='navbar-brand h1 m-2 me-4'>
                    &#127968; &thinsp;<span class='d-none d-md-inline'>$club_name</span><span class='d-inline d-md-none'>Home</span>
                </a>
                <button class='navbar-toggler' type='button' data-bs-toggle='collapse' data-bs-target='#navbarSupportedContent' aria-controls='navbarSupportedContent' aria-expanded='false' aria-label='Toggle navigation'>
                <span class='navbar-toggler-icon'></span>
                </button>
                <div class='collapse navbar-collapse' id='navbarSupportedContent'>
                <ul class='navbar-nav me-auto mb-2 mb-lg-0'>
                    <li class='nav-item'>
                    <a class='nav-link active' aria-current='page' href='home.php'>Home</a>
                    </li>
                    <li class='nav-item'>
                    <a class='nav-link active' aria-current='page' href='schedule.php'>Schedule</a>
                    </li>";

                    // Only Team Admins and Club Admins have permission to use these pages
                    if ($team_admin or $club_admin)
                    {
                        $navbar .= "<li class='nav-item'>
                        <a class='nav-link active' aria-current='page' href='create-event.php'>Create Event</a>
                        </li><li class='nav-item'>
                        <a class='nav-link active' aria-current='page' href='manage-members.php'>Manage Members</a>
                        </li>";
                    }
                    
                    // Only Club Admins have permission to use these pages
                    if ($club_admin)
                    {
                        $navbar .= "<li class='nav-item'>
                            <a class='nav-link active' aria-current='page' href='manage-teams.php'>Manage Teams</a>
                            </li>";
                    }


                    $navbar .= "</ul>
                            <form class='d-flex' action='javascript:;' onsubmit='sign_out()'>
                                <button class='btn btn-outline-danger' type='submit'>Sign out</button>
                            </form>
                            </div>
                        </div>
                        </nav>";

                // Return navbar HTML
                echo $navbar;
        }
    }

?>