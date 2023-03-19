<?php

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
            try
            {
                $this->connection = new mysqli(Database_Credentials::SERVERNAME, Database_Credentials::USERNAME, Database_Credentials::PASSWORD, Database_Credentials::DATABASE);

                if ($this->connection->connect_error) 
                {
                    //Handle error
                }
            }
            catch(Exception $error)
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
            catch(Exception $error)
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
            catch(Exception $error)
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
                                new System_Error(0, "Number of expected params did not match number of params passed in array.");
                            }
                        }

                        if ($this->query->execute())
                        {
                            $this->query_executed = true;
                            $this->result = $this->query->get_result();
                        }
                        else
                        {
                            new System_Error(0, "Query failed to execute: $sql.");
                            return null;
                        }
                    }
                }
                else
                {
                    new System_Error(0, "Query has already been executed.");
                    return null;
                }
            }
            catch(Exception $error)
            {
                new Error_Handler($error);
            }
        }

        private function get_heading_from_fieldname($fieldname)
        {
            try
            {
                //If $fieldname is a string
                if (is_string($fieldname))
                {
                    //If $fieldname is a key in Query::FIELD_HEADINGS
                    if (array_key_exists($fieldname, Query::FIELD_HEADINGS))
                    {
                        return Query::FIELD_HEADINGS[$fieldname];
                    }
                    else
                    {
                        new System_Error(0, "fieldname passed into get_heading_from_fieldname() not found in array FIELD_HEADINGS.");
                        return $fieldname;
                    }
                }
                else
                {
                    new System_Error(0, "fieldname passed into get_heading_from_fieldname() is not of type string.");
                }
            }
            catch(Exception $error)
            {
                new Error_Handler($error);
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
                    new System_Error(0, "get_result_as_plain() attempted on failed SQL query.");
                    return null;
                }
            }
            catch(Exception $error)
            {
                new Error_Handler($error);
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
                    new System_Error(0, "get_result_as_assoc_array() attempted on failed SQL query.");
                    return null;
                } 
            }
            catch(Exception $error)
            {
                new Error_Handler($error);
            }
        }

        public function get_result_as_indexed_array()
        {
            try
            {
                if ($this->query_executed)
                {
                    return $this->result->fetch_all(MYSQL_NUM);
                }
                else
                {
                    new System_Error(0, "get_result_as_indexed_array() attempted on failed SQL query.");
                    return null;
                }
            }
            catch(Exception $error)
            {
                new Error_Handler($error);
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
                                $result_string .= $data_row . "\n";
                            }
                        }
                        return $result_string;
                    }
                    else
                    {
                        new System_Error(0, "get_result_as_string() attempted on query containing null result.");
                        return null;
                    }
                }
                else
                {
                    new System_Error(0, "get_result_as_string() attempted on failed SQL query.");
                    return null;
                }
            }
            catch(Exception $error)
            {
                new Error_Handler($error);
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
                    new System_Error(0, "get_result_as_HTML_table() attempted on failed SQL query.");
                    return null;
                }
            }
            catch(Exception $error)
            {
                new Error_Handler($error);
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
        
        public function __construct(Exception $error, bool $error_fail = false)
        {
            $this->error = $error;

            $this->error_message = $error->getMessage();
            $this->error_code = strval($error->getCode());
            $this->error_type = get_class($error);

            switch (true)
            {
                case $error_fail:
                    $this->display_error();
                case $this->error instanceof mysqli_sql_exception:
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
                    $this->insert_error_to_db();
                    break;
                case $this->error instanceof System_Error:
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
                (`error_type`, `error_code`, `error_message`, `error_time`) 
                VALUES (?, ?, ?, NOW());";

                $query = $connection->prepare($sql);

                $params = [$this->error_type, $this->error_code, $this->error_message];
                $param_types = "sss";

                $query->bind_param($param_types, ...$params);

                $query->execute();
            }
            catch(Exception $error)
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
            }
            catch(Exception $error)
            {
                new Error_Handler($error, true);
            }
        }
    }

    class System_Error extends Exception
    {
        public function __construct(int $error_code, string $error_message)
        {
            parent::__construct($error_message, $error_code);

            new Error_Handler($this);
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
                        new System_Error(0, "member_ID not provided for Query_Client of type USER");
                    }
                }
            }
            catch(Exception $error)
            {
                new Error_Handler($error);
            }
        }

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
            catch(Exception $error)
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
                        new System_Error(0, "Query_Client->member_ID not found in MEMBERS table.");
                        return null;
                    }
                }
                else if ($member_ID == self::$user_instance->get_member_ID())
                {
                    return self::$user_instance;
                }
                else
                {
                    new System_Error(0, "member_ID passed to get_user_instance doesn't match current USER instance.");
                    return null;
                }
            }
            catch(Exception $error)
            {
                new Error_Handler($error);
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
                    switch ($is_club_admin->get_result_as_string())
                    {
                        case "0":
                            return $admin;
                        case "1":
                            $admin = true;
                            return $admin;
                        default:
                            return null;
                    }
                }
            }
            catch(Exception $error)
            {
                new Error_Handler($error);
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
                    switch ($is_team_admin->get_result_as_string())
                    {
                        case "0":
                            return $team_admin;
                        case "1":
                            $team_admin = true;
                            return $team_admin;
                        default:
                            return null;
                    }
                }
            }
            catch(Exception $error)
            {
                new Error_Handler($error);
            }
        }

        public function get_client_type()
        {
            try
            {
                return $this->client_type;
            }
            catch(Exception $error)
            {
                new Error_Handler($error);
            }
        }

        public function get_member_ID()
        {
            try
            {
                return $this->member_ID;
            }
            catch(Exception $error)
            {
                new Error_Handler($error);
            }
        }

        public function get_club_ID()
        {
            try
            {
                return $this->club_ID;
            }
            catch(Exception $error)
            {
                new Error_Handler($error);
            }
        }
    }

    //CRUD operations for each database class

    class Availability
    {
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
                    new System_Error(0, "Unrecognised Client_Type passed to create_availability()");
                    return null;
                }
            }
            catch(Exception $error)
            {
                new Error_Handler($error);
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
                        JOIN `TEAMS` 
                        ON TEAM_MEMBERS.team_ID = TEAMS.team_ID 
                        JOIN `EVENTS` 
                        ON AVAILABILITY.event_ID = EVENTS.event_ID 
                        WHERE (AVAILABILITY.availability_ID = ?);";

                    $params = [$availability_ID];
                    $param_types = "i";

                    $read_availability = new Query($sql, $params, $param_types);
                    return $read_availability;
                }
                elseif ($client->get_client_type() == Client_Type::SYSTEM)
                {
                    $sql = 
                        "SELECT * 
                        FROM `AVAILABILITY` 
                        WHERE (availability_ID = ?);";

                    $params = [$availability_ID];
                    $param_types = "i";

                    $read_availability = new Query($sql, $params, $param_types);
                    return $read_availability;
                }
                else
                {
                    //Log error: unrecognised Client_Type
                }
            }
            catch(Exception $error)
            {
                new Error_Handler($error);
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
                    //Log error: unrecognised Client_Type
                }
            }
            catch(Exception $error)
            {
                new Error_Handler($error);
            }
        }

        //delete_availability() does not exist, as once created an availability should not be removed

        //Specialised SQL Functions

        //$is_available is used to filter to only available/unavailable teammembers

        public static function read_availabilities_from_event(Query_Client $client, $event_ID, $is_available)
        {

        }

        public static function read_availabilities_from_member()
        {

        }

        public static function read_availabilities_from_team()
        {

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
                    //Log Error: Query_Client of Client_Type USER cannot create a club
                }
                else
                {
                    //Log error: unrecognised Client_Type
                }
            }
            catch(Exception $error)
            {
                new Error_Handler($error);
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
                            //Log error: club_ID does not exist
                        }
                        else
                        {
                            //Log error: Query_Client not a member or club_ID passed into function
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
                    //Log error: unrecognised Client_Type
                }
            }
            catch(Exception $error)
            {
                new Error_Handler($error);
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
                            //Log error: Query_Client is not an admin of their club
                        }
                    }
                    else
                    {
                        if (!Validation::club_ID_exists($club_ID))
                        {
                            //Log error: club_ID does not exist
                        }
                        else
                        {
                            //Log error: Query_Client not a member or club_ID passed into function
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
                    //Log error: unrecognised Client_Type
                }
            }
            catch(Exception $error)
            {
                new Error_Handler($error);
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
                    //Log error: USERS cannot delete clubs
                }
                else
                {
                    //Log error: unrecognised Client_Type
                }
            }
            catch(Exception $error)
            {
                new Error_Handler($error);
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
                    //Log error: USERS cannot view external clubs
                }
                else
                {
                    //Log error: unrecognised Client_Type
                }
            }
            catch(Exception $error)
            {
                new Error_Handler($error);
            }
        }
    }

    class Events
    {

    }

    class Event_Types
    {

    }

    class Guardianships
    {

    }

    class Members
    {

    }

    class Participants
    {

    }

    class Roles
    {

    }

    class Teams
    {

    }

    class Team_Members
    {

    }

    class Validation
    {
        //Function needs writing
        public static function club_ID_exists($club_ID)
        {
            try
            {
                return true;
            }
            catch(Exception $error)
            {
                new Error_Handler($error);
            }
        }
        public static function member_ID_exists($member_ID)
        {
            try
            {
                return true;
            }
            catch(Exception $error)
            {
                new Error_Handler($error);
            }
        }
    }

?>