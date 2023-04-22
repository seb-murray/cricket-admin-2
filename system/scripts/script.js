async function server_encrypt(data)
{
    try
    {
        data = String(data);

        let url = 'https://wyvernsite.net/sebMurray/system/scripts/encrypt.php';

        let form_data = new FormData();
        form_data.append("data", data);
        
        //Use fetch API with POST method. Then turn JSON object containing data into a string to be read in PHP.

        let response = await fetch(url, { method: 'POST', body: form_data });
        let result = await response.text();
    
        return result;
    }
    catch (error)
    {
        log_error_to_db(error);
    }
}

async function log_error_to_db(Error)
{
    let url = "https://wyvernsite.net/sebMurray/system/scripts/log-clientside-error.php"

    let error_code = -1;
    let error_message = Error.message;
    let error_line = -1;
    let error_file = window.location.pathname;

    let form_data = new FormData();

    form_data.append("error_code", error_code);
    form_data.append("error_message", error_message);
    form_data.append("error_line", error_line);
    form_data.append("error_file", error_file);
    
    //Use fetch API with POST method. Then turn JSON object containing data into a string to be read in PHP.

    let response = await fetch(url, { method: 'POST', body: form_data });
}

async function update_availability(event)
{
    
    let url = 'https://wyvernsite.net/sebMurray/system/scripts/update-availability.php';

    let element = event.target;

    let label_element_ID = "label_" + element.id;
    let label_element = document.getElementById(label_element_ID);

    let available;

    switch(element.checked) 
    {
        case true:
            available = 1;
            label_element.innerHTML = "Available";
            break;
        default:
            available = 0;
            label_element.innerHTML = "Not available";
            break;
    }

    let encrypted_availability_ID = element.getAttribute("availability_ID");

    //let client = logged in user;

    let form_data = new FormData();

    //form_data.append("client", client);
    form_data.append("encrypted_availability_ID", encrypted_availability_ID);
    form_data.append("available", available);

    let response = await fetch(url, { method: 'POST', body: form_data });
    let result = await response.text();

    switch(result) 
    {
        case "1":
            break;
        // If PHP fails
        default:
            switch(available) 
            {
                case true:
                    label_element.innerHTML = "Not available";
                    element.setAttribute(checked, false);
                    break;
                default:
                    label_element.innerHTML = "Available";
                    element.setAttribute(checked, true);
                    break;
            }
            break;
    }
}

async function create_event() 
{
	try 
	{
		let url = 'https://wyvernsite.net/sebMurray/system/scripts/create-event-script.php';

		let event_name = document.getElementById("event_name").value;

		let event_team = document.getElementById("event_team").value;
        

		let event_type = document.getElementById("event_type").value;

		let unformatted_event_date = new Date(document.getElementById("event_date").value);
		let event_date = unformatted_event_date.toISOString().split("T")[0];

		let event_location = document.getElementById("event_location").value;
		let event_meet_time = document.getElementById("event_meet_time").value;
        let event_start_time = document.getElementById("event_start_time").value;

        let event_description = document.getElementById("event_description").value;

		let form_data = new FormData();

		let alert_element = document.getElementById("invalid_input");

		form_data.append("event_name", event_name);
		form_data.append("team_ID", event_team);
		form_data.append("event_type_ID", event_type);
		form_data.append("event_date", event_date);
		form_data.append("event_location", event_location);
		form_data.append("event_meet_time", event_meet_time);
		form_data.append("event_start_time", event_start_time);
        form_data.append("event_description", event_description);

		let response = await fetch(url, { method: 'POST', body: form_data });

		let result = await response.text();
		let JSON_result = JSON.parse(result);

		if (JSON_result.error) 
		{
			alert_element.innerHTML = JSON_result.error;
            alert_element.classList.remove("alert-success");
            alert_element.classList.add("alert-danger")
			alert_element.classList.remove("invisible");
		}
		else 
		{
            alert_element.innerHTML = "Event created successfully. <a href='schedule.php'>See it here.</a>";
            alert_element.classList.remove("alert-danger");
            alert_element.classList.add("alert-success")
			alert_element.classList.remove("invisible");

            document.getElementById("create-event-form").reset();
		}
	}
	catch (error) 
	{
		log_error_to_db(error);
	}
}

async function sign_out()
{
    let url = 'https://wyvernsite.net/sebMurray/system/scripts/sign-out-script.php';

    let response = await fetch(url, { method: 'POST' });

    window.location.replace("https://wyvernsite.net/sebMurray/system/sign-in.html");
}

function sort_teams()
{
    team_selector = document.getElementById('team-filter');

    team = team_selector.value;

    const events = document.querySelectorAll('.feed-item');

    for (let i = 0; i < events.length; i++)
    {
        const event = events[i];

        let event_team_ID = event.getAttribute('team_ID');

        if ((team_selector.value == 'all') || (event_team_ID == team_selector.value))
        {
            event.style.display = 'block';
        }
        else
        {
            event.style.display = 'none';
        }
    }
}

function select_team(e)
{
    const button = e.target;

    let var_event_ID = button.getAttribute('event_ID');

    let event = {
        event_ID: var_event_ID
    }

    var GET_data = Object.keys(event).map(key => key + '=' + encodeURIComponent(event[key])).join('&');

    var url = 'https://wyvernsite.net/sebMurray/system/select-participants.php?' + GET_data;

    window.location.href = url;
}

async function select_participant(event) {
    
    const element = event.target;

    var participating;

    if (element.checked) 
    {
        const selected_sum = parseInt(document.getElementById('table-selected-sum').innerHTML);

        document.getElementById('table-selected-sum').innerHTML = (selected_sum + 1).toString();

        participating = 1;
    } 
    else 
    {
        const selected_sum = parseInt(document.getElementById('table-selected-sum').innerHTML);

        document.getElementById('table-selected-sum').innerHTML = (selected_sum - 1).toString();

        participating = 0;
    }

    const url = 'https://wyvernsite.net/sebMurray/system/scripts/create-participant.php';

    const availability_ID = element.getAttribute('db_ID');

    let form_data = new FormData();

    //form_data.append("client", client);
    form_data.append("encrypted_availability_ID", availability_ID);
    form_data.append("participating", participating);

    let response = await fetch(url, { method: 'POST', body: form_data });
    let result = await response.text();

    console.log(result);

}

function edit_team(e)
{
    const button = e.target;

    let team_ID = button.getAttribute('team_ID');

    let team = {
        team_ID: team_ID
    }

    var GET_data = Object.keys(team).map(key => key + '=' + encodeURIComponent(team[key])).join('&');

    var url = 'https://wyvernsite.net/sebMurray/system/edit-team.php?' + GET_data;

    window.location.href = url;
}

async function delete_team(e)
{
    const url = "https://wyvernsite.net/sebMurray/system/scripts/delete-team.php";

    const delete_btn = e.target;

    let team_ID = delete_btn.getAttribute('team_ID');

    let form_data = new FormData();

    //form_data.append("client", client);
    form_data.append("encrypted_team_ID", team_ID);

    let response = await fetch(url, { method: 'POST', body: form_data });
    let result = await response.text();

    switch(result) 
    {
        case "1":
            window.location.href = "https://wyvernsite.net/sebMurray/system/manage-teams.php";
            break;
        // If PHP fails
        default:
            break;
    }
}

async function update_team(e)
{
    const button = e.target;
    const url = "https://wyvernsite.net/sebMurray/system/scripts/update-team-script.php";

    let team_name_textbox = document.getElementById('team-name');

    let team_name = team_name_textbox.value;
    let encrypted_team_ID = button.getAttribute('team_ID')

    let form_data = new FormData();

    //form_data.append("client", client);
    form_data.append("team_name", team_name);
    form_data.append("encrypted_team_ID", encrypted_team_ID);

    let response = await fetch(url, { method: 'POST', body: form_data });
    let result = await response.text();

    console.log(result);

    switch(result) 
    {
        case "1":
            window.location.reload(true);
            break;
        // If PHP fails
        default:
            break;
    }
}

async function create_team()
{
    const url = "https://wyvernsite.net/sebMurray/system/scripts/create-team-script.php";

    team_name_textbox = document.getElementById('team-name');
    encrypted_member_ID = team_name_textbox.getAttribute('member_ID');
    team_name = team_name_textbox.value;

    let form_data = new FormData();

    //form_data.append("client", client);
    form_data.append("team_name", team_name);
    form_data.append("encrypted_member_ID", encrypted_member_ID);

    let response = await fetch(url, { method: 'POST', body: form_data });
    let result = await response.text();

    switch(result) 
    {
        case "1":
            window.location.href = "https://wyvernsite.net/sebMurray/system/manage-teams.php";
            break;
        // If PHP fails
        default:
            break;
    }
}

function edit_member_teams(e)
{
    const button = e.target;

    let member_ID = button.getAttribute('member_ID');

    let member = {
        member_ID: member_ID
    }

    var GET_data = Object.keys(member).map(key => key + '=' + encodeURIComponent(member[key])).join('&');

    var url = 'https://wyvernsite.net/sebMurray/system/edit-member.php?' + GET_data;

    window.location.href = url;
}

async function update_team_member(e)
{
    const url = "https://wyvernsite.net/sebMurray/system/scripts/update-team-member-script.php";

    const select = e.target;

    let encrypted_member_ID = select.getAttribute('member_ID');
    let encrypted_team_ID = select.getAttribute('team_ID');
    let encrypted_role_ID = select.value;

    let form_data = new FormData();

    //form_data.append("client", client);
    form_data.append("encrypted_member_ID", encrypted_member_ID);
    form_data.append("encrypted_team_ID", encrypted_team_ID);
    form_data.append("encrypted_role_ID", encrypted_role_ID);

    let response = await fetch(url, { method: 'POST', body: form_data });
    let result = await response.text();

    switch(result) 
    {
        case "1":
            window.location.reload(true);
            break;
        // If PHP fails
        default:
            break;
    }
}

async function create_event_type(e)
{
    try 
	{
		let url = 'https://wyvernsite.net/sebMurray/system/scripts/create-event-type-script.php';

        const form = e.target;

		let event_type_name = document.getElementById("event_type_name").value;
        let gender_restriction = document.getElementById("gender_restriction").value;
        let min_age = document.getElementById("min_age").value;
        let max_age = document.getElementById("max_age").value;
        let event_type_description = document.getElementById("event_type_description").value;
        let encrypted_club_ID = document.getElementById("event_type_name").getAttribute('club_ID');

		let form_data = new FormData();

		form_data.append("event_type_name", event_type_name);
		form_data.append("gender_restriction", gender_restriction);
		form_data.append("min_age", min_age);
		form_data.append("max_age", max_age);
		form_data.append("event_type_description", event_type_description);
        form_data.append("encrypted_club_ID", encrypted_club_ID);

		let response = await fetch(url, { method: 'POST', body: form_data });

		let result = await response.text();

        window.location.reload(true);
	}
	catch (error) 
	{
		log_error_to_db(error);
	}
}

async function update_guardianship(e)
{
    try 
	{
		let url = 'https://wyvernsite.net/sebMurray/system/scripts/update-guardianship-script.php';

        const select = e.target;

		let parent = select.value;
        let child = select.getAttribute('member_ID');

		let form_data = new FormData();

		form_data.append("parent_ID", parent);
		form_data.append("child_ID", child);

		let response = await fetch(url, { method: 'POST', body: form_data });

		let result = await response.text();

        console.log(result);
	}
	catch (error) 
	{
		log_error_to_db(error);
	}
}

