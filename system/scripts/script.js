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