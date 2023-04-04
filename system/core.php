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
                    return $this->result->fetch_all(MYSQLI_ASSOC);
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
                    return $this->result->fetch_all(MYSQLI_NUM);
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

        public function check_null_result()
        {
            try
            {
                if ($this->result->num_rows)
                {
                    return false;                   
                }
                else
                {
                    return true;
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
                case $this->error instanceof Exception:
                    $this->error_type = "PHP_Exception";
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
                echo implode(", ", [$this->error_code, $this->error_message, $this->error_type, $this->error_line]);
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
                    if (Validation::check_member_ID_exists($member_ID))
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
                    switch ($is_club_admin->get_result_as_indexed_array()[0][0])
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
                    $client_club_ID = $client->get_club_ID();

                    $sql = 
                        self::USER_READ_SQL . 
                        "WHERE (AVAILABILITY.availability_ID = ? AND CLUBS.club_ID = ?);";

                    $params = [$availability_ID, $client_club_ID];
                    $param_types = "ii";

                    $read_availability = new Query($sql, $params, $param_types);
                    return $read_availability;
                }
                elseif ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "SELECT AVAILABILITY.* 
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

                    $sql = self::USER_READ_SQL;

                    $club_ID = $client->get_club_ID();

                    $sql .= "
                            WHERE (AVAILABILITY.event_ID = ? AND CLUBS.club_ID = ? ";
                    
                    if ($is_available != null)
                    {
                        $sql .= "
                            AND AVAILABILITY.available = ?);";

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
                    $sql = 
                        self::USER_READ_SQL . 
                        "WHERE (MEMBERS.member_ID = ? AND CLUBS.club_ID = ?);";

                    $params = [$member_ID, $client->get_club_ID()];
                    $param_types = "ii";

                    $read_availability = new Query($sql, $params, $param_types);
                    return $read_availability;
                }
                elseif ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "SELECT AVAILABILITY.availability_ID 
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

        /*public static function read_availabilities_from_parent()
        {
            
        }*/
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
        const USER_READ_SQL = 
            "SELECT EVENTS.event_name, EVENT_TYPES.event_type_name, 
            CONCAT(DATE_FORMAT(event_date, '%d'), '/',
                DATE_FORMAT(event_date, '%m'), '/',
                DATE_FORMAT(event_date, '%Y')) AS event_date,
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

        public static function create_event(Query_Client $client, string $event_name, int $team_ID, int $event_type_ID, string $event_date, string $event_meet_time, string $event_start_time, string $event_description)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::USER)
                {
                    if (Validation::check_team_member($client, $client->get_member_ID(), $team_ID))
                    {
                        if (Validation::check_team_admin($client, $client->get_member_ID(), $team_ID))
                        {
                            $sql = 
                                "INSERT INTO `EVENTS` 
                                (`event_name`, `team_ID`, `event_type_ID`, `event_date`, `event_meet_time`, `event_start_time`, `event_description`) 
                                VALUES (?, ?, ?, ?, ?, ?, ?);";

                            $params = [$event_name, $team_ID, $event_type_ID, $event_date, $event_meet_time, $event_start_time, $event_description];
                            $param_types = "siissss";

                            $create_event = new Query($sql, $params, $param_types);
                            return $create_event;
                        }
                        else
                        {
                            throw new System_Error(0, "Client passed as arg to create_event() is not an admin of the team passed as an arg.", __LINE__);
                        }
                    }
                    else
                    {
                        throw new System_Error(0, "Client passed as arg to create_event() is not a member of the team passed as an arg.", __LINE__);
                    }
                }
                elseif ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "INSERT INTO `EVENTS` 
                        (`event_name`, `team_ID`, `event_type_ID`, `event_date`, `event_meet_time`, `event_start_time`, `event_description`) 
                        VALUES (?, ?, ?, ?, ?, ?, ?);";

                    $params = [$event_name, $team_ID, $event_type_ID, $event_date, $event_meet_time, $event_start_time, $event_description];
                    $param_types = "siissss";

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

        public static function update_event(Query_Client $client, int $event_ID, string $event_name, int $event_type_ID, string $event_date, string $event_meet_time, string $event_start_time, string $event_description)
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
                        `event_description` = ? 
                        WHERE (EVENTS.event_ID = ? AND ROLES.team_admin = 1);";

                        //Currently only lets admins update, which is correct
                        //However if this is not the case there is no reporting back to user

                    $params = [$client->get_member_ID(), $event_name, $event_type_ID, $event_date, $event_meet_time, $event_start_time, $event_description, $event_ID];
                    $param_types = "isissssi";

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
                        `event_description` = ? 
                        WHERE (EVENTS.event_ID = ?);";

                    $params = [$event_name, $event_type_ID, $event_date, $event_meet_time, $event_start_time, $event_description, $event_ID];
                    $param_types = "sissssi";

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

        public static function delete_event(Query_Client $client, int $event_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::USER)
                {
                    $sql = 
                        "DELETE FROM `EVENTS` 
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
                        "SELECT `event_ID`
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

        public static function read_events_from_member(Query_Client $client, int $member_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::USER)
                {
                    if ($member_ID == $client->get_member_ID())
                    {
                        if (Validation::check_is_parent($client, $client->get_member_ID()))
                        {
                            $sql = 
                                "SELECT EVENTS.event_name, EVENT_TYPES.event_type_name, 
                                CONCAT(DATE_FORMAT(event_date, '%d'), '/',
                                    DATE_FORMAT(event_date, '%m'), '/',
                                    DATE_FORMAT(event_date, '%Y')) AS event_date,
                                CONCAT(DATE_FORMAT(event_meet_time, '%H'), ':',
                                    DATE_FORMAT(event_meet_time, '%i')) AS event_meet_time,
                                CONCAT(DATE_FORMAT(event_start_time, '%H'), ':',
                                        DATE_FORMAT(event_start_time, '%i')) AS event_start_time, 
                                TEAMS.team_name, 
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
                                WHERE (TEAM_MEMBERS.member_ID = ? OR GUARDIANSHIP.parent_ID = ?)
                                ORDER BY EVENTS.event_date, EVENTS.event_meet_time ASC;";

                            $params = [$member_ID, $member_ID];
                            $param_types = "ii";
                        }
                        else
                        {
                            $sql = 
                                "SELECT EVENTS.event_name, EVENT_TYPES.event_type_name, 
                                CONCAT(DATE_FORMAT(event_date, '%d'), '/',
                                    DATE_FORMAT(event_date, '%m'), '/',
                                    DATE_FORMAT(event_date, '%Y')) AS event_date,
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
                        }

                        $read_event = new Query($sql, $params, $param_types);
                        return $read_event;
                    }
                }
                elseif ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "SELECT EVENTS.event_ID 
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
                        "SELECT EVENTS.event_ID 
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

    class Event_Types
    {
        const USER_READ_SQL = 
            "SELECT `event_type_name`, `event_gender_restriction`, `min_age`, `max_age`, `event_type_description` 
            FROM `EVENT_TYPES` ";

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

        //Custom SQL functions

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

    class Guardianships
    {
        public static function create_guardianship(Query_Client $client, int $parent_ID, int $child_ID)
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
        public static function check_club_ID_exists(int $club_ID)
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
            }
        }

        public static function check_member_ID_exists(int $member_ID)
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
            }
        }

        public static function check_team_member(Query_Client $client, int $member_ID, int $team_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::USER)
                {
                    if ($member_ID == $client->get_member_ID())
                    {
                        $sql = 
                            "SELECT DISTINCT `team_member_ID` 
                                FROM `TEAM_MEMBERS` 
                            WHERE (member_ID = ? 
                                AND team_ID = ?);";

                        $params = [$member_ID, $team_ID];
                        $param_types = "ii";

                        $check_team_member = new Query($sql, $params, $param_types);

                        switch ($check_team_member->check_null_result())
                        {
                            case false:
                                return true;
                            case true:
                                return false;
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
                    $sql = 
                        "SELECT DISTINCT `team_member_ID` 
                        FROM `TEAM_MEMBERS` 
                        WHERE (member_ID = ? AND team_ID = ?);";

                    $params = [$member_ID, $team_ID];
                    $param_types = "ii";

                    $check_team_member = new Query($sql, $params, $param_types);

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

        public static function check_team_admin(Query_Client $client, int $member_ID, int $team_ID)
        {
            try
            {
                $team_admin = false;

                if ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $team_admin = true;
                    return $team_admin;
                }
                else if ($client->get_client_type() == Client_Type::USER)
                {
                    $sql = 
                    "SELECT ROLES.team_admin 
                        FROM `TEAM_MEMBERS`
                    INNER JOIN `ROLES` 
                        ON TEAM_MEMBERS.role_ID = ROLES.role_ID 
                    WHERE member_ID = ? 
                        AND team_ID = ?;";

                    $params = [$member_ID, $team_ID];
                    $param_types = "ii";

                    $is_team_admin = new Query($sql, $params, $param_types);

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

        public static function check_club_admin(Query_Client $client, int $member_ID)
        {
            try
            {
                $admin = false;

                if ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $admin = true;
                    return $admin;
                }
                else if ($client->get_client_type() == Client_Type::USER)
                {
                    if ($member_ID == $client->get_member_ID())
                    {
                        $sql = 
                            "SELECT admin 
                            FROM `MEMBERS`
                            WHERE member_ID = ?;";

                        $params = [$client->get_member_ID()];
                        $param_types = "i";

                        $is_club_admin = new Query($sql, $params, $param_types);
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

        public static function check_is_parent(Query_Client $client, int $parent_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::USER)
                {
                    if ($parent_ID == $client->get_member_ID())
                    {
                        $sql = 
                            "SELECT `guardianship_ID` 
                            FROM `GUARDIANSHIP` 
                            WHERE (parent_ID = ?);";
        
                        $params = [$parent_ID];
                        $param_types = "i";
        
                        $check_is_parent = new Query($sql, $params, $param_types);
        
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
                    $sql = 
                        "SELECT `guardianship_ID` 
                        FROM `GUARDIANSHIP` 
                        WHERE (parent_ID = ?);";

                    $params = [$parent_ID];
                    $param_types = "i";

                    $check_is_parent = new Query($sql, $params, $param_types);

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

        public static function check_is_child(Query_Client $client, int $child_ID)
        {
            try
            {
                if ($client->get_client_type() == Client_Type::USER)
                {
                    if ($child_ID == $client->get_member_ID())
                    {
                        $sql = 
                            "SELECT `guardianship_ID` 
                            FROM `GUARDIANSHIP` 
                            WHERE (child_ID = ?);";

                        $params = [$child_ID];
                        $param_types = "i";

                        $check_is_parent = new Query($sql, $params, $param_types);

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
                    $sql = 
                        "SELECT `guardianship_ID` 
                        FROM `GUARDIANSHIP` 
                        WHERE (child_ID = ?);";

                    $params = [$child_ID];
                    $param_types = "i";

                    $check_is_parent = new Query($sql, $params, $param_types);

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
    }

?>