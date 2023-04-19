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

    $_SESSION['edit_member_ID'] = $_GET['member_ID'];

    if ((!Validation::check_team_admin($system, $member_ID)) and (!$_SESSION['club_admin'] == 1))
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
        try
        {

            $system = Query_Client::get_system_instance();
            $member_ID = intval(System_Utility::decrypt($_SESSION['member_ID']));
            $user = Query_Client::get_user_instance($member_ID);

            System_Utility::print_navbar($_SESSION['club_name'], $_SESSION['club_admin'], Validation::check_team_admin($system, $member_ID));

            $encrypted_edit_member_ID = $_SESSION['edit_member_ID'];
            $edit_member_ID = intval(System_Utility::decrypt($encrypted_edit_member_ID));

            $member = Members::read_member($user, $edit_member_ID)->get_result_as_assoc_array()[0];

            $member_name = $member['member_whole_name'];

            echo '<div class="container mt-4 mb-4"><div class="row"><div class="col-12 col-md-6 mx-auto">';

            echo    "<nav aria-label='breadcrumb'>
                        <ol class='breadcrumb'>
                            <li class='breadcrumb-item'><a href='manage-members.php' class='text-decoration-none'>Manage members</a></li>
                            <li class='breadcrumb-item active' aria-current='page'>$member_name</li>
                        </ol>
                    </nav>";

            echo "<h1 class='fw-bold text-dark mb-4'>$member_name</h1>";

            echo "<p class='text-muted'>Captains, vice captains, and coaches all have team admin capabilities.</p>";

            echo "<ul class='list-group'>";

            if ($_SESSION['club_admin'] == 1)
            {
                $teams = Teams::read_teams_from_club($system, $user->get_club_ID())->get_result_as_assoc_array();
            }
            else
            {
                $teams = Teams::read_teams_from_team_admin($system, $member_ID)->get_result_as_assoc_array();
            }

            $edit_member_teams = Teams::read_teams_from_member($system, $edit_member_ID)->get_result_as_assoc_array();

            $roles = Roles::read_all_roles()->get_result_as_assoc_array();

            $roles_options = "";

            //Making team_ID the array key
            $edit_member_team_IDs = array_column($edit_member_teams, 'team_ID');
            $edit_member_teams = array_combine(array_map('ucfirst', $edit_member_team_IDs), $edit_member_teams);

            foreach ($teams as $team)
            {
                $team_ID = $team['team_ID'];
                $encrypted_team_ID = System_Utility::encrypt($team_ID);

                $team_name = $team['team_name'];

                echo "<li class='list-group-item d-flex align-items-end'><h6 class='d-inline fw-semibold flex-grow-1'>$team_name</h6><select class='form-select form-select-sm w-auto d-inline-flex ms-3' aria-label='Default select example' team_ID='$encrypted_team_ID' member_ID='$encrypted_edit_member_ID' onchange='update_team_member(event)'> <option selected value='0'>Not a member</option>";

                    foreach ($roles as $role)
                    {
                        $encrypted_role_ID = System_Utility::encrypt($role['role_ID']);
                        $role_name = $role['role_name'];

                        if (isset($edit_member_teams[$team_ID]['role_ID']) and ($edit_member_teams[$team_ID]['role_ID'] == $role['role_ID']))
                        {
                            $selected = ' selected';
                        }
                        else
                        {
                            $selected = '';
                        }

                        echo "<option value='$encrypted_role_ID'$selected>$role_name</option>";
                    }

                echo "</select></li>";
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

