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

    $_SESSION['team_ID'] = $_GET['team_ID'];
    $team_ID = System_Utility::decrypt($_GET['team_ID']);

    foreach ($team_admin_assoc as &$team)
    {
        $team = $team['team_ID'];
    }

    if ((!in_array($team_ID, $team_admin_assoc)) and (!$_SESSION['club_admin'] == 1))
    {
        header("Location: not-admin.html");
        exit();
    }

    foreach ($team_admin_assoc as &$team)
    {
        $team = System_Utility::encrypt($team);
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

        $team_ID = $_SESSION['team_ID'];

        $team = Teams::read_team($system, System_Utility::decrypt($team_ID));

        $team_name = $team->get_result_as_assoc_array()[0]['team_name'];

        echo '<div class="container mt-4 mb-4"><div class="row"><div class="col-12 col-md-6 mx-auto">';

        echo '<nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="manage-teams.php" class="text-decoration-none">Manage teams</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit team</li>
        </ol>
        </nav>';

        echo "<h1 class='fw-bold text-dark mb-4'>Edit $team_name</h1>";

        echo    '<div class="mb-3">
                    <label for="team-name" class="form-label">Team name</label>
                    <input type="text" class="form-control" id="team-name" value="' . $team_name . '">
                </div>';
        

        echo "<div class='d-flex align-items-center'>
                <button type='submit' class='btn btn-primary me-3' onclick='update_team(event)' team_ID='$team_ID'>Update</button>
                <div class='ms-auto'>
                    <button type='button' team_ID='$team_ID' class='btn btn-danger' data-bs-toggle='modal' data-bs-target='#delete-team-modal' id='delete-btn'>Delete team</button>
                </div>
            </div>";

        echo '<div class="modal" tabindex="-1" id="delete-team-modal">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Delete ' . $team_name . '</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <p>Are you sure you want to delete ' . $team_name . '?</p>
              <p class="fw-medium">This cannot be undone.</p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">No</button>
              <button type="button" class="btn btn-danger" team_ID="' . $team_ID . '" id="delete-modal-btn" onclick="delete_team(event)">Delete team</button>
            </div>
          </div>
        </div>
      </div>';

        unset($_SESSION['team_ID']);
    ?>
    
</body>

</html>

