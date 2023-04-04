<!DOCTYPE html>
<html lang="en">

<head>

	<link rel="icon" type="image/x-icon" href="../favicon.ico">
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Test</title>

	<!-- Bootstrap -->
	<link href="../css/bootstrap-4.4.1.css" rel="stylesheet">

</head>

<body>
	<?php
		include "core.php";

		try
		{
			$user = Query_Client::get_user_instance(2);

			Event_Types::delete_event_type($user, 8);
		}
		catch(Throwable $error)
		{
			new Error_Handler($error);
		}


	?>
</body>

</html>