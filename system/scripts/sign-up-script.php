<?php

    include "core.php";

    try
    {

        $system = Query_Client::get_system_instance();

        $member_fname = $_POST['member_fname'];
        $member_lname = $_POST['member_lname'];
        $member_email = $_POST['member_email'];
        $hashed_member_password = $_POST['hashed_member_password'];
        $member_DOB = $_POST['member_DOB'];
        $member_gender = $_POST['member_gender'];
        $club_ID = $_POST['club_ID'];

        $create_member = null;

        if (Validation::check_valid_email($member_email))
        {
            $create_member = Members::create_member($system, $club_ID, $member_fname, $member_lname, $member_DOB, $member_gender, $member_email, $hashed_member_password);
        }

        if ($create_member?->check_query_success())
        {
            $success = ["success" => $member_DOB];
            echo json_encode($success);
        }
        else
        {
            $error = ["error" => "Oh snap! Sign up failed."];
            echo json_encode($error);
            throw new System_Error(0, "Sign up failed.", __LINE__);
        }

    }
    catch (Throwable $error)
    {
        new Error_Handler($error);
    }

?>