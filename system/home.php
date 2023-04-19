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

    $_SESSION["team_admins"] = [];

    foreach ($team_admin_assoc as $team_ID)
    {
        $team_ID = System_Utility::encrypt($team_ID);
    }

    $_SESSION["team_admins"] = $team_admin_assoc;

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Home</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/stylesheet.css">

    <!-- Custom JS -->
    <script src="scripts/script.js" type="application/javascript"></script>

    <style>
        body::before {
            content: "";
            background-image: url("assets/cricket-bg.jpg");
            filter: blur(10px);
            -webkit-filter: blur(10px);

            width: 110%;
            height: 110%;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: -1;

            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
        }
    </style>

</head>

<body>

    <?php System_Utility::print_navbar($_SESSION['club_name'], $_SESSION['club_admin'], Validation::check_team_admin(Query_Client::get_system_instance(), System_Utility::decrypt($_SESSION['member_ID']))); ?>

    <div class="container d-flex align-items-center bg-transparent" style="max-width: 700px; height: 90vh;">
        <div class="shadow-lg p-3 m-4 bg-white rounded">
        <div class="row">
            <div class="col d-flex justify-content-center m-4">
                <h1 class="fw-bold text-dark d-flex align-items-center">&#127951;&nbsp; Welcome, <?php echo $_SESSION['member_fname'] ?>.</h1>
            </div>
        </div>
            <p class="text-center m-4 mt-2 text-muted fs-4 fw-normal lh-sm">Head to the <?php echo $_SESSION['club_name'] ?> <a class="fw-semibold text-decoration-none text-muted" href="schedule.php">Schedule</a> tab to see upcoming events.</p>
        </div>
    </div>
</body>

</html>