<?php

    include "core.php";

    try
    {
        // When login system complete, replace this to use currently logged in user
        $system = Query_Client::get_system_instance();

        $encrypted_availability_ID = $_POST['encrypted_availability_ID'];
        $available = intval($_POST['available']);

        $availability_ID = intval(System_Utility::decrypt($encrypted_availability_ID));

        $update_availability = Availability::update_availability($system, $availability_ID, $available);

        switch ($update_availability->check_query_success())
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