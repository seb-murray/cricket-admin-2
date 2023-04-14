<?php

    include "core.php";

    try
    {
        echo System_Utility::encrypt($_POST);
    }
    catch (throwable $error)
    {
        new Error_Handler($error);
    }

?>