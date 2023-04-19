<?php

    include "core.php";

    try
    {
        // When login system complete, replace this to use currently logged in user
        $system = Query_Client::get_system_instance();

        $encrypted_availability_ID = $_POST['encrypted_availability_ID'];
        $participating = intval($_POST['participating']);

        $availability_ID = intval(System_Utility::decrypt($encrypted_availability_ID));

        $update_participation = Availability::update_participation($system, $availability_ID, $participating);

        switch ($update_participation->check_query_success())
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