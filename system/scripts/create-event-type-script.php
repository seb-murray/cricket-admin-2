<?php

    include "core.php";

    try
    {
        // When login system complete, replace this to use currently logged in user
        $system = Query_Client::get_system_instance();

        $event_type_name = $_POST['event_type_name'];
        $gender_restriction = $_POST['gender_restriction'];
        $min_age = $_POST['min_age'];
        $max_age = $_POST['max_age'];
        $event_type_description = $_POST['event_type_description'];
        $encrypted_club_ID = $_POST['encrypted_club_ID'];

        $club_ID = intval(System_Utility::decrypt($encrypted_club_ID));

        $create_event_type = Event_Types::create_event_type($system, $event_type_name, $club_ID, $gender_restriction, $min_age, $max_age, $event_type_description);

        switch ($create_event_type->check_query_success())
        {
            case true:
                //echo "1";
                break;
            default:
                //echo "0";
                break;
        }
    }
    catch (throwable $error)
    {
        new Error_Handler($error);
    }

?>