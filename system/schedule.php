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
    $_SESSION["team_admins"] = $team_admin_query->get_result_as_assoc_array();

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

            echo '<div class="form-group"><label for="team-filter" class="form-label fw-medium">Team</label><select class="form-select mb-4" id="team-filter" required onchange="sort_teams()"><option selected value="all">All teams</option>';

            if (Validation::check_is_parent($system, $user->get_member_ID()))
            {
                $children = Guardianships::read_children_from_parent($system, $user->get_member_ID())->get_result_as_assoc_array();

                $family = [$user->get_member_ID()];

                for ($i = 0; $i < count($children); $i++)
                {
                    array_push($family, $children[$i]["child_ID"]);
                }

                $family_team_info = [];

                for ($i = 0; $i < count($family); $i++)
                {
                    $all_team_info = Teams::read_teams_from_member($system, $family[$i])->get_result_as_assoc_array();

                    for ($x = 0; $x < count($all_team_info); $x++)
                    {
                        $team_ID = System_Utility::encrypt($all_team_info[$x]['team_ID']);
                        $team_name = $all_team_info[$x]['team_name'];

                        array_push($family_team_info, [$team_ID, $team_name]);
                    }
                }

                $team_data = array_map("unserialize", array_unique(array_map("serialize", $family_team_info)));

            }
            else
            {
                $team_data = [];
                
                $all_team_info = Teams::read_teams_from_member($system, $user->get_member_ID())->get_result_as_assoc_array();

                for ($x = 0; $x < count($all_team_info); $x++)
                {
                    $team_ID = System_Utility::encrypt($all_team_info[$x]['team_ID']);
                    $team_name = $all_team_info[$x]['team_name'];

                    array_push($team_data, [$team_ID, $team_name]);
                }
            }

            for ($i = 0; $i < count($team_data); $i++)
            {
                $team_ID = $team_data[$i][0];
                $team_name = $team_data[$i][1];

                echo "<option value='$team_ID'>$team_name</option>";
            }

            echo '</select></div>';

            $events = Events::read_events_from_member($user, $user->get_member_ID());
            $feed = $events->get_result_as_HTML_feed();

            if ($events->check_null_result())
            {   
                if (Team_Members::read_team_members_from_member($system, $user->get_member_ID())->check_null_result())
                {
                    echo    '<div class="container d-flex align-items-center" style="max-width: 600px; height: 90vh;">
                            <div class="shadow-lg p-3 m-4 bg-white rounded">
                            <div class="row">
                                <div class="col d-flex justify-content-center m-4">
                                    <div class="d-flex align-items-start me-3 mt-0">
                                        <img src="https://wyvernsite.net/sebMurray/system/assets/grimacing.png" alt="Description"
                                            style="width: auto; height: 6vh;">
                                    </div>
                                    <div class="d-flex flex-column align-items-center">
                                        <div class="text-center text-part-1">
                                            <h1 class="fw-bold text-dark mb-0"
                                                style="line-height: 1.2em; font-size: 5vh;">Sorry!</h1>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <p class="text-center m-4 mt-2 text-muted fs-4 fw-normal lh-sm">When a club admin adds you to a team, upcoming events will show here.</p>
                            </div>
                        </div>';
                }
                else
                {
                    echo    '<div class="container d-flex align-items-center" style="max-width: 600px; height: 90vh;">
                            <div class="shadow-lg p-3 m-4 bg-white rounded">
                            <div class="row">
                                <div class="col d-flex justify-content-center m-4">
                                    <div class="d-flex align-items-start me-3 mt-0">
                                        <img src="https://wyvernsite.net/sebMurray/system/assets/neutral.png" alt="Description"
                                            style="width: auto; height: 6vh;">
                                    </div>
                                    <div class="d-flex flex-column align-items-center">
                                        <div class="text-center text-part-1">
                                            <h1 class="fw-bold text-dark mb-0"
                                                style="line-height: 1.2em; font-size: 5vh;">Oh...</h1>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <p class="text-center m-4 mt-2 text-muted fs-4 fw-normal lh-sm">It\'s a bit empty in here... You have no upcoming events.</p>
                            </div>
                        </div>';
                }
            }
            else
            {
                echo $feed;
            }

            echo '</div></div></div>';
		} 
        catch (Throwable $error) 
        {
			new Error_Handler($error);
		}

    ?>
</body>

</html>