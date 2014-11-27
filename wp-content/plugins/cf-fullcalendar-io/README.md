# FullCalendar field plugin

### Getting the calendar functional

#### To get calendar info to view on FullCalendar, you'll need to do the following:

 * On the sidebar, click on the disclosure triangle next to your calendar's name. 
 * Go to "Share this Calendar." Make sure that
     * "Make this calendar public" is checked
     * "Share only my free/busy" is NOT checked
     * The gservice account is added in the People section and has "Make changes and manage sharing" as its permissions. Mine is 
    661144412361-38darqei4feaq1v1nser6uicujaq3afr@developer.gserviceaccount.com
 * Under "Calendar details," go down to Calendar address and copy whatever it has as your Calendar ID in the parentheses.

#### If my API key doesn't work for you, getting a key:
 * Go to the Google Developers API.
 * Make a new project. The name and ID don't matter.
 * In APIs, turn on the Calendar API.
 * In Credentials, go to Public API access and press "Create new Key." Select "Browser key," and you don't need to set the referrers just yet.
 * It should generate a new API key for you, which is what you'll use for FullCalendar.
 * To test if your calendar has the right settings and that your public key is working, fill your calendar ID and API key into the URL below:

	https://www.googleapis.com/calendar/v3/calendars/CALENDAR_ID/events?key=API_KEY

If you don't get a 403 Forbidden error, FullCalendar should be able to display your calendar with no issues!