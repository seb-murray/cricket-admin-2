<!DOCTYPE html>
<html lang="en">

<head>

    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign up</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe"
        crossorigin="anonymous"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/stylesheet.css">

    <!-- Custom JS -->
    <script src="scripts/sign-up-script.js"></script>

</head>

<body>
    <section class="min-vh-100 d-flex align-items-center">
        <div class="container login">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card shadow-lg border-0">
                        <div class="card-body mt-3">
                            <div class="d-flex justify-content-center align-items-center mt-4">
                                <div class="row">
                                    <div class="col d-flex justify-content-center">
                                        <div class="d-flex align-items-start me-3 mt-1">
                                            <img src="assets/waving-hand.png" alt="Description"
                                                style="width: auto; height: 5vh;">
                                        </div>
                                        <div class="d-flex flex-column align-items-center">
                                            <div class="text-center text-part-1">
                                                <h1 class="fw-bold text-dark mb-0"
                                                    style="line-height: 1.2em; font-size: 5vh;">Hey there.</h1>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="w-100 text-center text-part-2">
                                <h1 class="fw-bold text-dark mb-0" style="line-height: 1.2em; font-size: 5vh;">Need to sign up?</h1>
                            </div>
                            <div class="fs-6 alert alert-danger mt-2 mb-2 fw-semibold invisible" id="invalid_input">Fill here</div>
                            <form class="row g-3 needs-validation" onsubmit="sign_up()" action='javascript:;'>
                                <div class="col-md-6 mb-3">
                                    <label for="member_fname" class="form-label fw-medium">First name<span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="member_fname" placeholder="Sachin" required>
                                    <div class="valid-feedback">
                                    Looks good!
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="member_lname" class="form-label fw-medium">Last name<span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="member_lname" placeholder="Tendulkar" required>
                                    <div class="valid-feedback">
                                    Looks good!
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <label for="member_email" class="form-label fw-medium">Email<span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="member_email" placeholder="sachintendulkar@cricket.com" required>
                                    <div class="invalid-feedback">
                                    Please provide a valid city.
                                    </div>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label for="member_password" class="form-label fw-medium">Password<span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="member_password" placeholder="•••••••••••" required>
                                    <div class="invalid-feedback">
                                    Please provide a valid city.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="member_DOB" class="form-label fw-medium">Date of birth<span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="member_DOB" required>
                                    <div class="invalid-feedback">
                                    Please provide a valid DOB.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="member_gender" class="form-label fw-medium">Gender<span class="text-danger">*</span></label>
                                    <select class="form-select" id="member_gender" required>
                                        <option selected disabled value="">Select your gender...</option>
                                        <option value="M">Male</option>
                                        <option value="F">Female</option>
                                        <option value="A">I'd prefer not to say</option>
                                    </select>
                                    <div class="invalid-feedback">
                                    Please provide a valid zip.
                                    </div>
                                </div>
                                <div class="col-md-12 mb-1">
                                    <label for="member_club" class="form-label fw-medium">Cricket club<span class="text-danger">*</span></label>
                                    <select class="form-select" id="member_club" required>
                                        <option selected disabled value="">Select your club...</option>

                                        <?php
                                            include "scripts/core.php";

                                            $system = Query_Client::get_system_instance();

                                            $all_clubs_info = Clubs::read_all_clubs($system)->get_result_as_assoc_array();

                                            for ($i = 0; $i < count($all_clubs_info); $i++) 
                                            {
                                                $club_ID = $all_clubs_info[$i]["club_ID"];
                                                $club_name = $all_clubs_info[$i]["club_name"];

                                                echo "<option enabled value='$club_ID'>$club_name</option>\n";
                                            }
                                        ?>
                                    </select>
                                    <div class="invalid-feedback">
                                    Please select a cricket club.
                                    </div>
                                </div>
                                <div class="col-12 mb-3">
                                    <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="ts_and_cs" required>
                                    <label class="form-check-label fw-semibold fs-6" for="ts_and_cs">
                                        I agree to the <a href="#">terms and conditions</a><span class="text-danger">*</span>
                                    </label>
                                    <div class="invalid-feedback">
                                        You must agree before submitting.
                                    </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button type="submit" id="form_submit" class="btn btn-primary w-100 mb-4 fw-semibold fs-6">Sign up</button>
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