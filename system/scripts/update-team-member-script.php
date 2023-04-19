<?php

    include "core.php";

    try
    {
        // When login system complete, replace this to use currently logged in user
        $system = Query_Client::get_system_instance();

        $encrypted_member_ID = $_POST['encrypted_member_ID'];
        $encrypted_team_ID = $_POST['encrypted_team_ID'];
        $encrypted_role_ID = $_POST['encrypted_role_ID'];

        $member_ID = intval(System_Utility::decrypt($encrypted_member_ID));
        $team_ID = intval(System_Utility::decrypt($encrypted_team_ID));
        $role_ID = intval(System_Utility::decrypt($encrypted_role_ID));

        if ($encrypted_role_ID == 0)
        {
            $update_team_member = Team_Members::delete_team_member_from_member_and_team($system, $member_ID, $team_ID);
        }
        elseif (Team_Members::read_team_member_from_member_and_team($system, $member_ID, $team_ID)->check_null_result())
        {
            $update_team_member = Team_Members::create_team_member($system, $member_ID, $team_ID, $role_ID);
        }
        else
        {
            $update_team_member = Team_Members::update_team_member_from_member_and_team($system, $member_ID, $team_ID, $role_ID);
        }

        switch ($update_team_member->check_query_success())
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