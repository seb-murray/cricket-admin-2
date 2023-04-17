<?php
    include "scripts/core.php";

    session_start();

    if (!isset($_SESSION['member_ID'])) 
    {
        header("Location: sign-in.html");
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

    if ($team_admin_query->check_null_result())
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
    <title>Create event</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe"
        crossorigin="anonymous"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/stylesheet.css">

    <!-- Custom JS -->
    <script src="scripts/script.js"></script>

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

    <section class="min-vh-100 d-flex align-items-center">
        <div class="container login my-3 mx-auto">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card shadow-lg border-0">
                        <div class="card-body mt-3">
                            <div class="d-flex justify-content-center align-items-center mt-4">
                                <div class="row">
                                    <div class="col d-flex justify-content-center">
                                        <div class="d-flex align-items-start me-3 mt-1">
                                            <img src="assets/cricket.png" alt="Description"
                                                style="width: auto; height: 5vh;">
                                        </div>
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="text-center text-part-1">
                                                <h1 class="fw-bold text-dark mb-0"
                                                    style="line-height: 1.2em; font-size: 5vh;">Lets create an event.</h1>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="fs-6 alert mt-2 mb-2 fw-semibold invisible" id="invalid_input">Fill here</div>
                            <form class="row g-3 needs-validation" onsubmit="create_event()" action='javascript:;' id="create-event-form">
                                <div class="col-md-6">
                                    <label for="event_name" class="form-label fw-medium">Event name<span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="event_name" placeholder="Winter nets" required>
                                    <div class="valid-feedback">
                                    Looks good!
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="event_team" class="form-label fw-medium">Team<span class="text-danger">*</span></label>
                                    <select class="form-select" id="event_team" required>
                                        <option selected disabled value="">Select team...</option>

                                        <?php 
                                            $system = Query_Client::get_system_instance();

                                            $teams = $_SESSION["team_admins"];


                                            for ($i = 0; $i < count($teams); $i++)
                                            {
                                                $team_ID = System_Utility::encrypt($teams[$i]['team_ID']);
                                                $team_name = $teams[$i]['team_name'];

                                                echo "<option value='$team_ID'>$team_name</option>";
                                            }

                                            unset($_SESSION['team_admins']);
                                        ?>

                                    </select>
                                    <div class="invalid-feedback">
                                        Please select a team.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="event_type" class="form-label fw-medium">Event type<span class="text-danger">*</span></label>
                                    <select class="form-select" id="event_type" required>
                                        <option selected disabled value="">Select event type...</option>

                                        <?php 

                                            $system = Query_Client::get_system_instance();

                                            $club_ID = System_Utility::decrypt($_SESSION["club_ID"]);

                                            $event_types = Event_Types::read_event_types_from_club($system, $club_ID)->get_result_as_assoc_array();

                                            for ($i = 0; $i < count($event_types); $i++)
                                            {
                                                $event_type_ID = System_Utility::encrypt($event_types[$i]['event_type_ID']);
                                                $event_type_name = $event_types[$i]['event_type_name'];

                                                echo "<option value='$event_type_ID'>$event_type_name</option>";
                                            }
                                        ?>

                                    </select>
                                    <div class="invalid-feedback">
                                        Please select an event type.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="event_date" class="form-label fw-medium">Date<span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="event_date" required>
                                    <div class="invalid-feedback">
                                        Please choose a date.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="event_location" class="form-label fw-medium">Location<span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="event_location" required>
                                    <div class="invalid-feedback">
                                        Please enter the event location.
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label for="event_meet_time" class="form-label fw-medium">Meet time<span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="event_meet_time" required>
                                    <div class="invalid-feedback">
                                        Please choose a meet time.
                                    </div>
                                </div>
                                <div class="col-md-3 mb-1">
                                    <label for="event_start_time" class="form-label fw-medium">Start time<span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="event_start_time" required>
                                    <div class="invalid-feedback">
                                        Please choose a start time.
                                    </div>
                                </div>
                                <div class="col-md-12 mb-4">
                                    <label for="member_DOB" class="form-label fw-medium">Description</label>
                                    <textarea class="form-control" id="event_description" placeholder="Midweek indoor training."></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" id="form_submit" class="btn btn-primary w-100 mb-4 fw-semibold fs-6">Create event</button>
                                </div>
                                </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

</body>

</html>