<?php

    include "core.php";

    $system = Query_Client::get_system_instance();

    $member_email = $_POST['member_email'];
    $hashed_member_password = $_POST['hashed_member_password'];

    $member_query = Members::member_login($system, $member_email, $hashed_member_password);

    $member_info = $member_query->get_result_as_assoc_array()[0];

    var_dump($member_info);
?>