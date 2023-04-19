<?php

    include "core.php";

    try
    {

        $system = Query_Client::get_system_instance();

        $member_email = $_POST['member_email'];
        $hashed_member_password = $_POST['hashed_member_password'];

        $member_query = Members::member_login($system, $member_email, $hashed_member_password);

        if (!$member_query->check_null_result())
        {
            $member_info = $member_query->get_result_as_assoc_array()[0];

            $member_ID_encrypted = System_Utility::encrypt($member_info["member_ID"]);
            $club_ID_encrypted = System_Utility::encrypt($member_info["club_ID"]);

            session_start();

            $_SESSION['member_ID'] = $member_ID_encrypted;
            $_SESSION['club_ID'] = $club_ID_encrypted;
            $_SESSION['club_name'] = Clubs::read_club($system, $member_info["club_ID"])->get_result_as_assoc_array()[0]['club_name'];
            $_SESSION['member_fname'] = $member_info['member_fname'];
            $_SESSION['member_lname'] = $member_info['member_lname'];
            $_SESSION['club_admin'] = $member_info['admin'];

            echo json_encode(["success" => 1]);

            exit();
        }
        else
        {
            $error = ["error" => "Oh snap! Incorrect email or password."];
            echo json_encode($error);
            throw new System_Error(0, "Incorrect email or password.", __LINE__);
        }

    }
    catch (Throwable $error)
    {
        new Error_Handler($error);
    }

?>