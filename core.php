<?php

    class Database_Connection
    {
        private static $instance = null;
        private $connection;

        private function __construct()
        {
            //Database connection credentials
            $servername = "localhost";
            $username = "wyvernsi_sebMurray";
            $password = "L0n3someP0l3cat";
            $database = "wyvernsi_sebM";

            $this->connection = new mysqli($servername, $username, $password, $database);

            if ($this->connection->connect_error) {
                //Log error: DB_CONNECT error
            }
        }

        //Implementation of singleton class design
        public static function get_instance()
        {
            if (self::$instance == null)
            {
                self::$instance = new Database_Connection;
            }

            return self::$instance->connection;
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
            $this->database = Database_Connection::get_instance();

            $this->execute_query($sql, $params, $param_types);
        }

        private function execute_query(string $sql, array $params, string $param_types)
        {
            if ($this->query_executed == false)
            {

                $this->query = $this->database->prepare($sql);

                if ($this->query != false)
                {
                    if (count($params) > 0)
                    {
                        //Splat operator '...' splits array into individual function params
                        $this->query->bind_param($param_types, ...$params);
                    }

                    if ($this->query->execute())
                    {
                        $this->query_executed = true;
                        $this->result = $this->query->get_result();
                    }
                    else
                    {
                        //Log error: DB_QUERY
                        return null;
                    }
                }
                else
                {
                    //Log error: DB_QUERY
                    return null;
                }
            }
            else
            {
                //Log error: GENERAL, "Query already executed."
                return null;
            }
        }

        private function get_heading_from_fieldname($fieldname)
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
                    //Log error, $fieldname is not a key in Query::FIELD_HEADINGS
                    return $fieldname;
                }
            }
            else
            {
                //Log error, $fieldname not passed as string
            }
        }

        //Get result methods

        public function get_result_as_plain()
        {
            if ($this->query_executed)
            {
                return $this->result;
            }
            else
            {
                //Log error: get_result() attempted when query failed
                return null;
            } 
        }

        public function get_result_as_assoc_array()
        {
            if ($this->query_executed)
            {
                return $this->result->fetch_all(MYSQL_ASSOC);
            }
            else
            {
                //Log error: get_result() attempted when query failed
                return null;
            } 
        }

        public function get_result_as_indexed_array()
        {
            if ($this->query_executed)
            {
                return $this->result->fetch_all(MYSQL_NUM);
            }
            else
            {
                //Log error: get_result() attempted when query failed
                return null;
            } 
        }

        public function get_result_as_string()
        {
            if ($this->query_executed)
            {
                $result_string = "";
                $row_count = $this->result->num_rows;

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
                //Log error: get_result() attempted when query failed
                return null;
            }
        }

        public function get_result_as_HTML_table()
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
                //Log error: get_result() attempted when query failed
                return null;
            }
        }

        //private function get_result_as_feed_item(){}
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

        public function __construct($client_type, $member_ID = null)
        {
            $this->client_type = $client_type;

            if ($this->client_type == Client_Type::USER)
            {
                if ($member_ID != null)
                {
                    $this->member_ID = $member_ID;

                    //Set $club_ID using database query
                }
                else
                {
                    //Log error to DB: $member_ID not provided
                }
            }
        }

        public function check_club_admin()
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
                        //Log error - DB query error
                        return null;
                }
            }
        }

        public function check_team_admin($team_ID)
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
                        //Log error - DB query error
                        return null;
                }
            }
        }

        public function get_client_type()
        {
            return $this->client_type;
        }

        public function get_member_ID()
        {
            return $this->member_ID;
        }

        public function get_club_ID()
        {
            return $this->club_ID;
        }
    }

    //CRUD operations for each database class

    class Availability
    {

        public static function create_availability(Query_Client $client, int $team_member_ID, int $event_ID, int $available)
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
                //Log error: unrecognised Client_Type
            }
        }

        public static function read_availability(Query_Client $client, int $availability_ID)
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

        public static function update_availability(Query_Client $client, $availability_ID, $available)
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

        public static function delete_availability()
        {
            
        }
    }

    //CRUD functions repeated for all database classes

    class Clubs
    {
        public static function create_club()
        {
            //Only the system can create a new club
        }

        public static function read_club()
        {
            //read_club() returns different outputs depending on Access_Level
            //e.g. all data is returned to system and club admins, only some data returned to normal User
        }

        public static function update_club()
        {
            //Only the system and club admins can update a club
        }

        public static function delete_club()
        {
            //Only the system can delete a club
        }
    }

    class Error_Types
    {
        const DB_CONNECT = "Database Connection";
        const DB_QUERY = "Database Query";
        const USER = "User";
    }

    class Error_Handling
    {
        public static function handle_error($error_type, $error_code, $error_message)
        {
            if ($error_type == Error_Types::USER)
            {
                //Return error to user page
            }
            else
            {
                Error_Handling::log_error($error_type, $error_code, $error_message);
            }
        }

        private static function log_error($error_type, $error_code, $error_message)
        {
            $db = Database_Connection::get_instance();


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

?>