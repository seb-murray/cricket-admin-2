<?php

    include "core.php";

    try
    {

        $system = Query_Client::get_system_instance();

        $event_name = $_POST['event_name'];
        $event_type_ID = System_Utility::decrypt($_POST['event_type_ID']);
        $event_date = $_POST['event_date'];
        $event_meet_time = $_POST['event_meet_time'];
        $event_start_time = $_POST['event_start_time'];
        $event_location = $_POST['event_location'];
        $event_description = $_POST['event_description'];
        $team_ID = System_Utility::decrypt($_POST['team_ID']);

        $create_event = Events::create_event($system, $event_name, $team_ID, $event_type_ID, $event_date, $event_meet_time, $event_start_time, $event_location, $event_description);

        if ($create_event->check_query_success())
        {
            $success = ["success" => 1];
            echo json_encode($success);
        }
        else
        {
            $error = ["error" => "Oh snap! Event creation failed."];
            echo json_encode($error);
            throw new System_Error(0, "Event creation failed.", __LINE__);
        }

    }
    catch (Throwable $error)
    {
        new Error_Handler($error);
    }

?>