<?php

    include "core.php";

    try
    {
        // When login system complete, replace this to use currently logged in user
        $system = Query_Client::get_system_instance();

        $encrypted_parent_ID = $_POST['parent_ID'];
        $encrypted_child_ID = $_POST['child_ID'];

        $parent_ID = intval(System_Utility::decrypt($encrypted_parent_ID));
        $child_ID = intval(System_Utility::decrypt($encrypted_child_ID));

        if($encrypted_parent_ID == 0)
        {
            $update_guardianship = Guardianships::delete_guardianship_from_child($system, $child_ID);
        }
        elseif (Guardianships::read_parent_from_child($system, $child_ID)->check_null_result())
        {
            $update_guardianship = Guardianships::create_guardianship($system, $parent_ID, $child_ID);
        }
        else
        {
            $update_guardianship = Guardianships::update_parent_from_child($system, $child_ID, $parent_ID);
        }

        switch ($update_guardianship->check_query_success())
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