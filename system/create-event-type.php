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

    if ($team_admin_query->check_null_result() and ($_SESSION["club_admin"] != 1))
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
    <title>Create event type</title>

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

    <?php
        System_Utility::print_navbar($_SESSION['club_name'], $_SESSION['club_admin'], Validation::check_team_admin(Query_Client::get_system_instance(), System_Utility::decrypt($_SESSION['member_ID'])));
    ?>

    <section class="min-vh-100 d-flex align-items-center">
        <div class="container login my-3 mx-auto">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card shadow-lg border-0">
                        <div class="card-body mt-3">
                            <div class="d-flex justify-content-center align-items-center mt-4">
                                <div class="row">
                                    <div class="col d-flex justify-content-center">
                                        <h1 class="fw-bold text-dark mb-2 d-flex align-items-center">&#128221;&nbsp; New event type</h1>
                                    </div>
                                </div>
                            </div>
                            <div class="fs-6 alert mt-2 mb-2 fw-semibold invisible" id="invalid_input">Fill here</div>
                            <form class="row g-3 needs-validation" onsubmit="create_event_type(event)" action='javascript:;' id="create-event-form">
                                <div class="col-md-6">
                                    <label for="event_type_name" class="form-label fw-medium">Event type name<span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="event_type_name" placeholder="Training" club_ID='<?php echo $_SESSION['club_ID'] ?>' required>
                                    <div class="valid-feedback">
                                        Looks good!
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="gender_restriction" class="form-label fw-medium">Gender restriction<span class="text-danger">*</span></label>
                                    <select class="form-select" id="gender_restriction" required>
                                        <option selected value="A">No restriction</option>
                                        <option value="F">Female only</option>
                                        <option value="M">Male only</option>
                                    </select>
                                    <div class="invalid-feedback">
                                        Please select a restriction.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="min_age" class="form-label fw-medium">Minimum age<span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="min_age" value="0" required>
                                    <div class="invalid-feedback">
                                        Please choose a minimum age.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="max_age" class="form-label fw-medium">Maximum age<span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="max_age" value="0" required>
                                    <div class="invalid-feedback">
                                        Please choose a minimum age.
                                    </div>
                                </div>
                                <p class="text-muted mb-2 mt-2 fw-normal">
                                    If you don't want an age restriction, leave both at zero.
                                </p>
                                <div class="col-md-12 mb-4">
                                    <label for="event_type_description" class="form-label fw-medium">Description</label>
                                    <textarea class="form-control" id="event_type_description" placeholder="Training session."></textarea>
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