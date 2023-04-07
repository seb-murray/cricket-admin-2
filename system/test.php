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

			$start = hrtime(true);

			$user = Query_Client::get_user_instance(24);
			$system = Query_Client::get_system_instance();

			//$output = Participants::read_participant($user, 24);

			Participants::create_participant($system, 18, 35);
			Participants::create_participant($system, 18, 36);
			Participants::create_participant($system, 18, 37);
			Participants::create_participant($system, 18, 38);

			Participants::create_participant($system, 19, 35);
			Participants::create_participant($system, 19, 36);
			Participants::create_participant($system, 19, 37);
			Participants::create_participant($system, 19, 38);
			
			//echo $output->get_result_as_HTML_table();
			//var_dump(json_encode($output->get_result_as_assoc_array()[0]));

			
			$end = hrtime(true);   

			echo ($end - $start) / 1000000000;   // Seconds
		}
		catch(Throwable $error)
		{
			new Error_Handler($error);
		}

	?>
</body>

</html>