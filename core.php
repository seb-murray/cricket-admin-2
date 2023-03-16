<?php

    class Database_Connection
    {
        private $instance = null;

        private function __construct()
        {

        }

        //Implementation of singleton class design
        public static function get_instance()
        {

        }

    }

    class Query
    {
        private $database;

        public function __construct(Database_Connection $database)
        {
            $this->database = $database;
        }

        public function execute_query()
        {

        }

        //Get result methods

        private function get_result_as_plain($result)
        {

        }

        private function get_result_as_assoc_array($result)
        {

        }

        private function get_result_as_indexed_array($result)
        {

        }

        private function get_result_as_string($result)
        {

        }

        private function get_result_as_HTML_table($result)
        {

        }

        private function get_result_as_feed_item($result)
        {

        }
    }


    class Access_Level
    {
        public $system = null;
        public $member_ID = null;
        public $club_ID = null;

        public function __construct($member_ID, $club_ID, $system = null)
        {
            $this->system = $system;
            $this->member_ID = $member_ID;
            $this->club_ID = $club_ID;
        }
    }

    //CRUD operations for each database class

    class Availability
    {
        public function __construct(Access_Level $user)
        {

        }

        public function create_availability()
        {

        }

        public function read_availability()
        {

        }

        public function update_availability()
        {
            
        }

        public function delete_availability()
        {
            
        }
    }

    //CRUD functions repeated for all database classes

    class Clubs
    {

    }

    class Log_Error
    {

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