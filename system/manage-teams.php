<?php

    include "scripts/core.php";

    session_start();

    if (!isset($_SESSION['member_ID'])) 
    {
        header("Location: sign-in.html"); // Redirect the user to the sign-in page.
        exit();
    }

    $member_ID = System_Utility::decrypt($_SESSION["member_ID"]);
    $club_ID = System_Utility::decrypt($_SESSION["club_ID"]);

    $system = Query_Client::get_system_instance();
    
    $team_admin_query = Teams::read_teams_from_team_admin($system, $member_ID);
    $team_admin_assoc = $team_admin_query->get_result_as_assoc_array();

    foreach ($team_admin_assoc as &$team)
    {
        $team['team_ID'] = System_Utility::encrypt($team['team_ID']);
    }

    $_SESSION["team_admins"] = $team_admin_assoc;

    $_SESSION["club_teams"] = Teams::read_teams_from_club($system, $club_ID)->get_result_as_assoc_array();

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

<nav class="navbar sticky-top navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid bg-transparent">
        <h1 class="navbar-brand h1 m-2 me-4">
            <?php 

                $system = Query_Client::get_system_instance();
                $member_ID = System_Utility::decrypt($_SESSION['member_ID']);
                
                $club_ID = Members::read_member($system, $member_ID)?->get_result_as_assoc_array()[0]['club_ID'];
                $club_name = Clubs::read_club($system, $club_ID)?->get_result_as_assoc_array()[0]['club_name'];

                $_SESSION['club_name'] = $club_name;

                echo $club_name;
            ?>
        </h1>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            <li class="nav-item">
            <a class="nav-link active" aria-current="page" href="home.php">Home</a>
            </li>
            <li class="nav-item">
            <a class="nav-link active" aria-current="page" href="schedule.php">Schedule</a>
            </li>

            <?php

                if (count($_SESSION["team_admins"]) > 0)
                {
                    echo '<li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="create-event.php">Create Event</a>
                    </li>';
                }

                if ($_SESSION['club_admin'] == 1)
                {
                    echo '<li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="manage-members.php">Manage Members</a>
                    </li>';

                    echo '<li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="manage-teams.php">Manage Teams</a>
                    </li>';
                }
            ?>
        </ul>
        <form class="d-flex" action="javascript:;" onsubmit="sign_out()">
            <button class="btn btn-outline-danger" type="submit">Sign out</button>
        </form>
        </div>
    </div>
    </nav>

    <?php

		try 
        {
            $member_ID = intval(System_Utility::decrypt($_SESSION['member_ID']));
            $club_ID = intval(System_Utility::decrypt($_SESSION['club_ID']));

            $user = Query_Client::get_user_instance($member_ID);
            $system = Query_Client::get_system_instance();

            echo '<div class="container mt-4 mb-4"><div class="row"><div class="col-12 col-md-6 mx-auto">';

            foreach ($_SESSION['club_teams'] as $team)
            {
                $team_name = $team['team_name'];
                $encrypted_team_ID = System_Utility::encrypt($team['team_ID']);

                echo "<div class='feed-item'>";
                echo    "<div class='col d-flex'>
                            <h3 class='text-dark d-flex align-items-center'>&#128101;&thinsp; $team_name <button type='button' onclick='edit_team(event)' class='ms-2 btn btn-sm btn-outline-primary' team_ID='$encrypted_team_ID'>Edit</button></h3>
                        </div>";
                echo "</div>";
            }

            if (Validation::check_club_admin($system, $member_ID))
            {
                echo "<div class='d-flex justify-content-center'><a href='add-team.php' type='button' class='btn btn-primary mb-3 d-block w-100'>Add team</a></div>";
            }

            echo '</div></div></div>';

            unset($_SESSION['club_teams']);
		} 
        catch (Throwable $error) 
        {
			new Error_Handler($error);
		}

    ?>
</body>

</html>