<!DOCTYPE html>
<html lang="en">

<head>

    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Test</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/stylesheet.css">

    <!-- Custom JS -->
    <script src="scripts/script.js"></script>

</head>

<body>
    <?php
    
		include "scripts/core.php";

		try 
        {
            $user = Query_Client::get_user_instance(4);
            $system = Query_Client::get_system_instance();

            echo Events::read_events_from_member($user, 4)->get_result_as_HTML_feed();
		} 
        catch (Throwable $error) 
        {
			new Error_Handler($error);
		}

    ?>
</body>

</html>