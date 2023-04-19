<?php

    include "core.php";

    try
    {
        // When login system complete, replace this to use currently logged in user
        $system = Query_Client::get_system_instance();

        $encrypted_team_ID = $_POST['encrypted_team_ID'];
        $team_name = $_POST['team_name'];

        $team_ID = intval(System_Utility::decrypt($encrypted_team_ID));

        $update_team = Teams::update_team($system, $team_ID, $team_name, "");

        switch ($update_team->check_query_success())
        {
            case true:
                echo "1";
                break;
            default:
                echo "0";
                break;
        }
    }
    catch (throwable $error)
    {
        new Error_Handler($error);
    }

?>