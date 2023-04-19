<?php

    include "scripts/core.php";

    session_start();

    if (!isset($_SESSION['member_ID'])) 
    {
        header("Location: sign-in.html"); // Redirect the user to the sign-in page.
        exit();
    }

    $member_ID = System_Utility::decrypt($_SESSION["member_ID"]);
    $system = Query_Client::get_system_instance();
    
    $team_admin_query = Teams::read_teams_from_team_admin($system, $member_ID);
    $team_admin_assoc = $team_admin_query->get_result_as_assoc_array();

    foreach ($team_admin_assoc as &$team)
    {
        $team = $team['team_ID'];
    }

    if ((!in_array($team_ID, $team_admin_assoc)) and (!$_SESSION['club_admin'] == 1))
    {
        header("Location: not-admin.html");
        exit();
    }

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Schedule</title>

    <?php

        $member_ID = $_SESSION['member_ID'];
        $club_ID = $_SESSION['club_ID'];
        $member_fname = $_SESSION['member_fname'];
        $member_lname = $_SESSION['member_lname'];

        echo "<script type='application/javascript'>";
            echo "var member_ID = '$member_ID';";
            echo "var club_ID = '$club_ID';";
            echo "var member_fname = '$member_fname';";
            echo "var member_lname = '$member_lname';";
        echo "</script>";

    ?>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/stylesheet.css">

    <!-- Custom JS -->
    <script src="scripts/script.js" type="application/javascript"></script>

</head>

<body>

    <?php

		try 
        {
            $member_ID = intval(System_Utility::decrypt($_SESSION['member_ID']));
            $club_ID = intval(System_Utility::decrypt($_SESSION['club_ID']));

            $user = Query_Client::get_user_instance($member_ID);
            $system = Query_Client::get_system_instance();

            System_Utility::print_navbar($_SESSION['club_name'], $_SESSION['club_admin'], Validation::check_team_admin($system, $member_ID));

            $all_members = Members::read_members_from_club($user, $club_ID)->get_result_as_assoc_array();

            echo '<div class="container mt-4 mb-4"><div class="row"><div class="col-12 col-md-6 mx-auto">';

            echo '<h1 class="fw-bold text-dark mb-4">Manage members</h1>';
            
            echo '<ul class="list-group">';

            foreach ($all_members as $member)
            {
                $member_whole_name = $member['member_whole_name'];
                $member_ID = System_Utility::encrypt($member['member_ID']);

                echo "<li class='list-group-item d-flex align-items-center justify-content-between'><p class='my-auto'>$member_whole_name</p><button type='button' onclick='edit_member_teams(event)' member_ID='$member_ID' class='ms-2 btn btn-sm btn-outline-primary'>Edit</button></li>";
            }

            echo "</ul>";

            echo '</div></div></div>';

		} 
        catch (Throwable $error) 
        {
			new Error_Handler($error);
		}

    ?>
</body>

</html>