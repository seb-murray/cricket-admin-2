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
            label_element.innerHTML = "Going";
            break;
        default:
            available = 0;
            label_element.innerHTML = "Not going";
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
                    label_element.innerHTML = "Not going";
                    element.setAttribute(checked, false);
                    break;
                default:
                    label_element.innerHTML = "Going";
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