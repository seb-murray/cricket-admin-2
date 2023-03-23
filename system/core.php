<?php
    
    error_reporting(E_ALL);

    function custom_error_handler($error_code, $error_message, $error_file, $error_line)
    {
        $error = new Exception($error_message, $error_code);

        new Error_Handler($error);
    }

    set_error_handler("custom_error_handler");

    class Database_Credentials
    {
        const SERVERNAME = "localhost";
        const USERNAME = "wyvernsi_sebMurray";
        const PASSWORD = "L0n3someP0l3cat";
        const DATABASE = "wyvernsi_sebM";
    }

    class Database_Connection
    {
        private static $instance = null;
        private $connection;

        private function __construct()
        {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

            try
            {
                $this->connection = new mysqli(Database_Credentials::SERVERNAME, Database_Credentials::USERNAME, Database_Credentials::PASSWORD, Database_Credentials::DATABASE);
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
            }
        }

        //Implementation of singleton class design
        public static function get_instance()
        {
            try
            {
                if (self::$instance == null)
                {
                    self::$instance = new Database_Connection;
                }

                return self::$instance->connection;
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
            }
        }

    }

    class Query
    {
        private $database;
        private $query;
        private $result;
        private $query_executed = false;
        private const FIELD_HEADINGS = 
        [
            "availability_ID" => "Availability ID",
            "available" => "Available?",
            "COLUMN_NAME" => "Column Name",
            "club_ID" => "Club ID",
            "club_name" => "Club Name",
            "error_ID" => "Error ID",
            "error_message" => "Error Message",
            "error_time" => "Time",
            "event_ID" => "Event ID",
            "event_name" => "Event Name",
            "event_date" => "Date",
            "event_meet_time" => "Meet Time",
            "event_start_time" => "Start Time",
            "event_description" => "Event Description",
            "event_team_ID" => "Team ID",
            "event_type_ID" => "Event Type ID",
            "event_type_name" => "Event Type",
            "event_type_description" => "Description",
            "event_gender_restriction" => "Gender Restriction",
            "min_age" => "Min Age",
            "max_age" => "Max Age",
            "guardianship_ID" => "Guardianship ID",
            "parent_ID" => "Parent ID",
            "child_ID" => "Child ID",
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
            "team_ID" => "Team ID",
            "team_name" => "Teamname",
            "team_nickname" => "Team Nickname",
            "team_member_ID" => "Team Member ID"
        ];

        public function __construct(string $sql, array $params = [], string $param_types = "")
        {
            try
            {
                $this->database = Database_Connection::get_instance();

                $this->execute_query($sql, $params, $param_types);
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
            }
        }

        private function execute_query(string $sql, array $params, string $param_types)
        {
            try
            {
                if ($this->query_executed == false)
                {

                    $this->query = $this->database->prepare($sql);

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

                        $this->query->execute();

                        $this->query_executed = true;
                        $this->result = $this->query->get_result();
                    }
                }
                else
                {
                    throw new System_Error(0, "Query has already been executed.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
            }
        }

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

        public function get_result_as_plain()
        {
            try
            {
                if ($this->query_executed)
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

        public function get_result_as_assoc_array()
        {
            try
            {
                if ($this->query_executed)
                {
                    return $this->result->fetch_all(MYSQL_ASSOC);
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

        public function get_result_as_indexed_array()
        {
            try
            {
                if ($this->query_executed)
                {
                    return $this->result->fetch_array(MYSQLI_NUM);
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

        public function get_result_as_string()
        {
            try
            {
                if ($this->query_executed)
                {
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

        public function get_result_as_HTML_table()
        {
            try
            {
                if ($this->query_executed)
                {
                    $HTML_table = "";

                    //Fetch number of rows in $this->result
                    if ($this->result->num_rows)
                    {
                        $row_count = $this->result->num_rows;

                        //fetch_fields() returns an array of objects, containing info about each field
                        $fields = $this->result->fetch_fields();

                        //$data = $this->result;

                        $HTML_table .= '<table class="table table-striped table-bordered">';
                        $HTML_table .= '<thead>';
                        $HTML_table .= '<tr>';

                        for ($x = 0; $x < count($fields); $x++)
                        {
                            $heading = $this->get_heading_from_fieldname($fields[$x]->name);

                            $HTML_table .= '<th scope="col">' . $heading . '</th>';
                        }

                        $HTML_table .= '</thead>';
                        $HTML_table .= '</tr>';

                        $HTML_table .= '<tbody>';

                        while ($row = $this->result->fetch_object()) 
                        {
                            $HTML_table .= '<tr>';
            
                            foreach ($fields as $field) 
                            {
                                $HTML_table .= '<td>' . $row->{$field->name} . '</td>';
                            }
            
                            $HTML_table .= '</tr>';
                        }

                        $HTML_table .= '</tbody>';

                        $HTML_table .= '</table>';

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

        //private function get_result_as_feed_item(){}
    }

    class Error_Handler
    {
        private $error;
        private $error_type;
        private $error_code;
        private $error_message;
        private $error_line;
        
        public function __construct(Throwable $error, bool $error_fail = false)
        {
            $this->error = $error;

            $this->error_message = $error->getMessage();
            $this->error_code = $error->getCode();
            $this->error_type = get_class($error);
            $this->error_line = null;

            switch (true)
            {
                case $error_fail:
                    $this->display_error();
                case $this->error instanceof mysqli_sql_exception:
                    $this->error_line = $error->getLine();
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
                    $this->error_line = $error->getLine();
                    $this->insert_error_to_db();
                    break;
                case $this->error instanceof System_Error:
                    $this->error_line = $error->getLine();
                    $this->insert_error_to_db();
                    break;
                default:
                    $this->display_error();
                    break;
            }
        }

        private function insert_error_to_db()
        {
            try
            {
                $connection = new mysqli(Database_Credentials::SERVERNAME, Database_Credentials::USERNAME, Database_Credentials::PASSWORD, Database_Credentials::DATABASE);

                $sql = 
                "INSERT INTO `ERRORS`
                (`error_type`, `error_code`, `error_message`, `error_line`, `error_time`) 
                VALUES (?, ?, ?, ?, NOW());";

                $query = $connection->prepare($sql);

                $params = [$this->error_type, $this->error_code, $this->error_message, $this->error_line];
                $param_types = "sssi";

                $query->bind_param($param_types, ...$params);

                $query->execute();
            }
            catch(Throwable $error)
            {
                new Error_Handler($error, true);
            }
        }

        private function display_error()
        {
            try
            {
                echo $this->error_code;
                echo $this->error_message;
                echo $this->error_type;
                echo strval($this->error_line);
            }
            catch(Throwable $error)
            {
                new Error_Handler($error, true);
            }
        }
    }

    class System_Error extends Exception
    {
        protected $line;

        public function __construct(int $error_code, string $error_message, int $error_line)
        {
            parent::__construct($error_message, $error_code);

            $this->line = $error_line;
        }
    }

    class Client_Type
    {
        const USER = "User";
        const SYSTEM = "System";
    }

    class Query_Client
    {
        private $client_type;
        private $member_ID = null;
        private $club_ID = null;
        private static $system_instance = null;
        private static $user_instance = null;

        private function __construct($client_type, $member_ID)
        {
            try
            {
                $this->client_type = $client_type;

                if ($this->client_type == Client_Type::USER)
                {
                    if ($member_ID != null)
                    {
                        $this->member_ID = $member_ID;

                        $club_ID = Clubs::read_club_from_member(Query_Client::get_system_instance(), $this->member_ID);

                        if ($club_ID != false)
                        {
                            $club_ID = $club_ID->get_result_as_string();
                            $club_ID = intval($club_ID);

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

        public function __destruct(){}

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
            }
        }

        public static function get_user_instance(int $member_ID)
        {
            try
            {
                if (self::$user_instance == null)
                {
                    if (Validation::member_ID_exists($member_ID))
                    {
                        self::$user_instance = new Query_Client(Client_Type::USER, $member_ID);
                        return self::$user_instance;
                    }
                    else
                    {
                        throw new System_Error(0, "Query_Client->member_ID not found in MEMBERS table.", __LINE__);
                    }
                }
                else if ($member_ID == self::$user_instance->get_member_ID())
                {
                    return self::$user_instance;
                }
                else
                {
                    throw new System_Error(0, "member_ID passed to get_user_instance doesn't match current USER instance.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        public function check_club_admin()
        {
            try
            {
                $admin = false;

                if ($this->client_type == Client_Type::SYSTEM)
                {
                    $admin = true;
                    return $admin;
                }
                else
                {
                    $sql = 
                    "SELECT admin 
                    FROM `MEMBERS`
                    WHERE member_ID = ?;";

                    $params = [$this->member_ID];
                    $param_types = "i";

                    $is_club_admin = new Query($sql, $params, $param_types);
                    switch (intval($is_club_admin->get_result_as_string()))
                    {
                        case 0:
                            return $admin;
                        case 1:
                            $admin = true;
                            return $admin;
                        default:
                            throw new System_Error(0, "Query_Client::check_club_admin() result is not of type bool.", __LINE__);
                    }
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        public function check_team_admin($team_ID)
        {
            try
            {
                $team_admin = false;

                if ($this->client_type == Client_Type::SYSTEM)
                {
                    $team_admin = true;
                    return $team_admin;
                }
                else
                {
                    $sql = 
                    "SELECT ROLES.team_admin 
                    FROM `TEAM_MEMBERS`
                    JOIN `ROLES` 
                    ON TEAM_MEMBERS.role_ID = ROLES.role_ID 
                    WHERE member_ID = ? 
                    AND team_ID = ?;";

                    $params = [$this->member_ID, $team_ID];
                    $param_types = "ii";

                    $is_team_admin = new Query($sql, $params, $param_types);
                    switch (intval($is_team_admin->get_result_as_string()))
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
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        public function get_client_type()
        {
            try
            {
                return $this->client_type;
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        public function get_member_ID()
        {
            try
            {
                return $this->member_ID;
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        public function get_club_ID()
        {
            try
            {
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

    class Availability
    {
        const USER_READ_SQL = 
            "SELECT 
            CONCAT(MEMBERS.member_fname, ' ', MEMBERS.member_lname) AS member_whole_name,     
                TEAMS.team_name, EVENTS.event_name,  
            CONCAT(DATE_FORMAT(event_date, '%d'), '/',
                DATE_FORMAT(event_date, '%m'), '/',
                DATE_FORMAT(event_date, '%Y')) AS event_date,
            CONCAT(DATE_FORMAT(event_meet_time, '%H'), ':',
                DATE_FORMAT(event_meet_time, '%i')) AS event_meet_time,
            CONCAT(DATE_FORMAT(event_start_time, '%H'), ':',
                    DATE_FORMAT(event_start_time, '%i')) AS event_start_time, 
            AVAILABILITY.available 
            FROM `AVAILABILITY` 
            JOIN `TEAM_MEMBERS` 
                ON AVAILABILITY.team_member_ID = TEAM_MEMBERS.team_member_ID 
            JOIN `MEMBERS` 
                ON TEAM_MEMBERS.member_ID = MEMBERS.member_ID 
            JOIN `EVENTS` 
                ON AVAILABILITY.event_ID = EVENTS.event_ID 
            JOIN `TEAMS` 
                ON EVENTS.event_team_ID = TEAMS.team_ID ";

        //CRUD SQL Functions

        public static function create_availability(Query_Client $client, int $team_member_ID, int $event_ID, int $available)
        {
            try
            {
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

                    $create_availability = new Query($sql, $params, $param_types);
                    return $create_availability;
                }
                else
                {
                    throw new System_Error(0, "Query_Client passed as arg to create_availability() has unrecognised Client_Type.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        public static function read_availability(Query_Client $client, int $availability_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::USER)
                {
                    //Check if event_ID from AVAILABILITY belongs to the $client's club

                    $sql = 
                        Availability::USER_READ_SQL . 
                        "WHERE (AVAILABILITY.availability_ID = ?);";

                    $params = [$availability_ID];
                    $param_types = "i";

                    $read_availability = new Query($sql, $params, $param_types);
                    return $read_availability;
                }
                elseif ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "SELECT AVAILABILITY.availability_ID 
                        FROM `AVAILABILITY` 
                        WHERE (availability_ID = ?);";

                    $params = [$availability_ID];
                    $param_types = "i";

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

        public static function update_availability(Query_Client $client, int $availability_ID, int $available)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::USER)
                {
                    //Check if availability belongs to the client

                    $sql = 
                        "UPDATE `AVAILABILITY`
                        SET `available` = ? 
                        WHERE (availability_ID = ?);";

                    $params = [$available, $availability_ID];
                    $param_types = "ii";

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

        //delete_availability() does not exist, as once created an availability should not be removed

        //Specialised SQL Functions

        //$is_available is used to filter to only available/unavailable teammembers
        public static function read_availabilities_from_event(Query_Client $client, int $event_ID, int $is_available = null)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::USER)
                {
                    //Check if availability belongs to the client's club

                    $sql = Availability::USER_READ_SQL;
                    
                    if ($is_available != null)
                    {
                        $sql .= "
                            WHERE (AVAILABILITY.event_ID = ? AND AVAILABILITY.available = ?);";

                        $params = [$event_ID, $is_available];
                        $param_types = "ii";
                    }
                    else
                    {
                        $sql .= "
                            WHERE (AVAILABILITY.event_ID = ?);";

                        $params = [$event_ID];
                        $param_types = "i";
                    }

                    $read_availability = new Query($sql, $params, $param_types);
                    return $read_availability;
                }
                elseif ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "SELECT availability_ID 
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
                    throw new System_Error(0, "Query_Client passed as arg to read_availabilities_from_event() has unrecognised Client_Type.", __LINE__);
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
                return null;
            }
        }

        public static function read_availabilities_from_member(Query_Client $client, $member_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::USER)
                {
                    //Check if availability belongs to the client's club

                    $sql = 
                        Availability::USER_READ_SQL . 
                        "WHERE MEMBERS.member_ID = ?;";

                    $params = [$member_ID];
                    $param_types = "i";

                    $read_availability = new Query($sql, $params, $param_types);
                    return $read_availability;
                }
                elseif ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "SELECT AVAILABILITY.availability_ID 
                        FROM `AVAILABILITY` 
                        JOIN `TEAM_MEMBERS` 
                            ON AVAILABILITY.team_member_ID = TEAM_MEMBERS.team_member_ID 
                        JOIN `MEMBERS` 
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

        public static function read_availabilities_from_parent()
        {

        }

        public static function read_availabilities_from_team(Query_Client $client, $team_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::USER)
                {
                    //Check if availability belongs to the client's club

                    $sql = 
                        Availability::USER_READ_SQL . 
                        "WHERE TEAMS.team_ID = ?;";

                    $params = [$team_ID];
                    $param_types = "i";

                    $read_availability = new Query($sql, $params, $param_types);
                    return $read_availability;
                }
                elseif ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "SELECT AVAILABILITY.availability_ID 
                        FROM `AVAILABILITY` 
                        JOIN `TEAM_MEMBERS` 
                            ON AVAILABILITY.team_member_ID = TEAM_MEMBERS.team_member_ID 
                        JOIN `MEMBERS` 
                            ON TEAM_MEMBERS.member_ID = MEMBERS.member_ID 
                        JOIN `EVENTS` 
                            ON AVAILABILITY.event_ID = EVENTS.event_ID 
                        JOIN `TEAMS` 
                            ON EVENTS.event_team_ID = TEAMS.team_ID 
                        WHERE TEAMS.team_ID = ?;";

                    $params = [$team_ID];
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

        public static function read_availabilities_from_club()
        {

        }
    }

    class Clubs
    {
        //CRUD SQL Functions

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
                        if (!Validation::club_ID_exists($club_ID))
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

        public static function update_club(Query_Client $client, int $club_ID, string $club_name)
        {
            try
            {
                //Only the system and club admins can update a club

                //read_club() returns different outputs depending on Query_Client->Client_Type
                if ($client->get_client_type() == Client_Type::USER)
                {
                    //First check client is a member of the club_ID given
                    if ($club_ID == $client->get_club_ID())
                    {
                        if ($client->check_club_admin())
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
                        if (!Validation::club_ID_exists($club_ID))
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
    }

    class Events
    {
        public static function create_event()
        {

        }

        public static function read_event(Query_Client $client, int $event_ID)
        {

        }

        public static function update_event()
        {

        }

        public static function delete_event()
        {

        }

        //Specialised SQL functions

        public static function read_events_from_club()
        {

        }

        public static function read_events_from_team()
        {

        }
    }

    class Event_Types
    {
        public static function create_event_type()
        {

        }

        public static function read_event_type()
        {

        }

        public static function update_event_type()
        {

        }

        public static function delete_event_type()
        {

        }

        //Custom SQL functions

        public static function read_event_types_from_team()
        {

        }

    }

    class Guardianships
    {
        public static function create_guardianship()
        {

        }

        public static function read_guardianship()
        {

        }

        public static function update_guardianship()
        {

        }

        public static function delete_guardianship()
        {

        }

        //Custom SQL functions

        public static function read_parent_from_child()
        {

        }

        public static function read_children_from_parent()
        {

        }
    }

    class Members
    {
        public static function create_member()
        {

        }

        public static function read_member()
        {

        }

        public static function update_member()
        {

        }

        public static function delete_member()
        {

        }

        //Custom SQL functions

        public static function read_members_from_club()
        {

        }

        public static function read_members_from_team()
        {

        }

    }

    class Participants
    {
        public static function create_participant()
        {

        }

        public static function read_participant()
        {

        }

        public static function update_participant()
        {

        }

        public static function delete_participant()
        {

        }

        //Custom SQL function

        public static function read_participants_from_event()
        {

        }

        public static function read_participants_from_parent()
        {

        }

        public static function read_participants_from_member()
        {

        } 

        public static function read_participants_from_team()
        {

        }
    }

    class Roles
    {
        //Roles should not be read directly
        //or created/updated/deleted from the PHP script
    }

    class Teams
    {
        //Current project scope does not allow for user creation or deletion of clubs

        public static function read_team()
        {

        }

        public static function update_team()
        {

        }

        //Custom SQL functions

        public static function read_teams_from_member()
        {

        }

        public static function read_teams_from_club()
        {

        }

    }

    class Team_Members
    {
        public static function create_team_member()
        {

        }

        public static function read_team_member()
        {

        }
        
        public static function update_team_member()
        {

        }

        public static function delete_team_member()
        {

        }

        //Custom SQL functions

        public static function read_team_from_team_member()
        {

        }

        public static function read_member_from_team_member()
        {

        }

        public static function read_role_from_team_member()
        {

        }
    }

    class Validation
    {
        //Function needs writing
        public static function club_ID_exists($club_ID)
        {
            try
            {
                $sql = 
                    "SELECT `club_ID` 
                    FROM `CLUBS` 
                    WHERE `club_ID` = ?";

                $params = [$club_ID];
                $param_types = "i";

                $check_club_ID = new Query($sql, $params, $param_types);

                if($check_club_ID = $check_club_ID->get_result_as_indexed_array())
                {
                    if ($check_club_ID[0] == $club_ID)
                    {
                        return true;
                    }
                    else
                    {
                        return false;
                    }
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
            }
        }

        public static function member_ID_exists($member_ID)
        {
            try
            {
                $sql = 
                    "SELECT `member_ID` 
                    FROM `MEMBERS` 
                    WHERE `member_ID` = ?";

                $params = [$member_ID];
                $param_types = "i";

                $check_member_ID = new Query($sql, $params, $param_types);

                if($check_member_ID = $check_member_ID->get_result_as_indexed_array())
                {
                    if ($check_member_ID[0] == $member_ID)
                    {
                        return true;
                    }
                    else
                    {
                        return false;
                    }
                }
            }
            catch(Throwable $error)
            {
                new Error_Handler($error);
            }
        }
    }

?>