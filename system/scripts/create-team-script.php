<?php

    include "core.php";

    try
    {
        // When login system complete, replace this to use currently logged in user
        $system = Query_Client::get_system_instance();

        $team_name = $_POST['team_name'];
        $encrypted_member_ID = $_POST['encrypted_member_ID'];
        $member_ID = intval(System_Utility::decrypt($encrypted_member_ID));

        $user = Query_Client::get_user_instance($member_ID);

        $create_team = Teams::create_team($user, $team_name, $user->get_club_ID());

        switch ($create_team->check_query_success())
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