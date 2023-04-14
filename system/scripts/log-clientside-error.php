<?php

    include "core.php";

    try
    {
        $error_code = strval($_POST['error_code']);
        $error_message = strval($_POST['error_message']);
        $error_line = intval($_POST['error_line']);
        $error_file = strval($_POST['error_file']);

        $error = new Clientside_Error($error_code, $error_message, $error_line, $error_file);

        new Error_Handler($error);
    }
    catch (Throwable $error)
    {
        new Error_Handler($error);
    }
?>