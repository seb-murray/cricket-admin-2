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
        $team = System_Utility::encrypt($team['team_ID']);
    }

    $_SESSION["team_admins"] = $team_admin_assoc;
    
    $event_ID = System_Utility::decrypt($_GET['event_ID']);

    $event_team_ID = Teams::read_team_from_event($system, $event_ID)->get_result_as_assoc_array()[0]['team_ID'];
    $encrypted_team_ID = System_Utility::encrypt($event_team_ID);

    if ((!in_array($encrypted_team_ID, $team_admin_assoc)) and (!$_SESSION['club_admin'] == 1))
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
    <title>Select participants</title>

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

        $system = Query_Client::get_system_instance();
        $member_ID = intval(System_Utility::decrypt($_SESSION['member_ID']));

        System_Utility::print_navbar($_SESSION['club_name'], $_SESSION['club_admin'], Validation::check_team_admin($system, $member_ID));

        echo '<div class="container mt-4 mb-4"><div class="row"><div class="mx-auto">';

        echo '<h1 class="fw-bold text-dark mb-4" style="line-height: 1.2em; font-size: 5vh;">Select team</h1>';

        $user = Query_Client::get_user_instance($member_ID);

        $event_ID = System_Utility::decrypt($_GET['event_ID']);
        
        $availabilities = Availability::read_availabilities_from_event($user, $event_ID, 1);

        echo $availabilities->get_result_as_HTML_table("select_participant(event)", "Selected", true);
        echo '<a class="btn btn-primary mt-3" href="schedule.php" role="button">Done</a>';
        echo '</div></div></div>';

    ?>
</body>

</html>