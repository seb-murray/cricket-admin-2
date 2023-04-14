<?php

    include "core.php";

    try
    {
        session_start();
        
        session_destroy();

        exit();

    }
    catch(Throwable $error)
    {
        new Error_Handler($error);
    }

?>