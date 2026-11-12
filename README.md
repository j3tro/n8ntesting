# NOC HTTP Trigger

A Network Operations Center HTTP Trigger Tester - a web interface for triggering n8n workflows and displaying HTTP GET/POST results.

## Overview

This application provides a clean, dark-themed interface for:
- Pasting text and submitting it to an n8n webhook
- Executing HTTP GET and POST requests to httpbin.org
- Displaying structured responses above the input textbox
- Preventing duplicate submissions and handling errors gracefully

## Architecture

```
Browser
  |
  v
PHP Frontend (index.php) - Handles UI and user interactions
  |
  v
PHP Backend (index.php) - Server-side cURL communication with n8n
  |
  v
n8n Webhook - Processes the trigger and executes the workflow
  |
  +---- HTTP GET ---> https://httpbin.org/get?message={{input}}
  |
  +---- HTTP POST --> https://httpbin.org/post (JSON body)
  |
  v
Format Response & Return JSON
```

## Files Structure

```
noc-trigger/
├── index.php          # Main PHP file (UI + backend logic)
├── config.php         # Configuration settings
├── assets/
│   ├── css/
│   │   └── style.css  # Custom NOC theme styles
│   └── js/
│       └── app.js     # Vanilla JavaScript (embedded in index.php)
└── README.md          # This file
```

## Installation

### Prerequisites

- XAMPP or PHP's built-in development server
- n8n instance running locally (port 5678)
- n8n workflow "NOC - HTTP Request Trigger Tester" deployed and active

### Step 1: Install XAMPP

Download and install XAMPP from https://www.apachefriends.org/
- Start Apache and MySQL services
- Place the `noc-trigger` folder in `C:\xampp\htdocs\`

### Step 2: Configure n8n Webhook

Ensure your n8n workflow "NOC - HTTP Request Trigger Tester" is:
1. Active (or at least deployed)
2. Using a Webhook Trigger node with path: `noc-http-trigger`
3. Set the webhook URL in `config.php`:

```php
define('N8N_WEBHOOK_URL', 'http://localhost:5678/webhook/noc-http-trigger');
```

### Step 3: Place Files

```bash
# Using XAMPP
cd C:\xampp\htdocs
# Or using PHP built-in server
cd /path/to/noc-trigger

# Or using the opencode workspace
# Files are already at: C:\Users\Andy\Documents\opencode\noc-trigger\
```

### Step 4: Access the Application

Open your browser and navigate to:

```
http://localhost/noc-trigger/
# Or if using PHP built-in server:
# php -S localhost:8000 -t /path/to/noc-trigger
```

Then visit: `http://localhost:8000` or `http://localhost/noc-trigger/`

## Usage

### Basic Workflow

1. **Paste text** into the large multiline textbox
2. **Wait 500ms** after pasting (auto-submit) or click the **Submit** button
3. **Loading indicator** appears while the request is being processed
4. **Results appear above the textbox** showing:
   - GET request status and response
   - POST request status and response
   - Execution timestamp
   - Original input text preserved
5. **Clear** the input using the Clear button when done

### Auto-Submit Feature

- When you paste text into the textbox, a timer starts
- After 500ms of no additional typing, the form automatically submits
- This prevents duplicate submissions from rapid typing
- You can also manually click the **Submit** button

### Supported Scenarios

| Test | Input | Expected |
|------|-------|----------|
| TEST 1 | "Hello NOC" | GET succeeds, POST succeeds, both results display |
| TEST 2 | "OLT003 - Palo - Temperature Alarm" | Text preserved, passed to both requests |
| TEST 3 | (empty) | Submission prevented, error shown |
| TEST 4 | (GET failure) | POST result remains visible |
| TEST 5 | (POST failure) | GET result remains visible |
| TEST 6 | Rapid consecutive pastes | Latest result overwrites previous, no duplicates |

## Configuration

### Edit `config.php`

```php
<?php
// n8n Webhook URL - must match your deployed workflow
define('N8N_WEBHOOK_URL', 'http://localhost:5678/webhook/noc-http-trigger');

// How long to wait for n8n response (seconds)
define('REQUEST_TIMEOUT', 30);

// Application name displayed in header
define('APP_NAME', 'NOC HTTP Trigger');

// Enable debug mode (logs to server error log)
define('DEBUG_MODE', false);
?>
```

### n8n Webhook URL Pattern

The webhook URL follows this pattern:
```
http://{n8n-host}:{n8n-port}/webhook/{workflow-trigger-path}
```

For this application, the workflow trigger path is `noc-http-trigger`, so:
- Localhost: `http://localhost:5678/webhook/noc-http-trigger`
- Custom host/port: adjust accordingly

## Development

### Running with PHP Built-in Server

```bash
cd /path/to/noc-trigger
php -S localhost:8000
```

Then visit: `http://localhost:8000`

### Customizing the NOC Theme

The application uses a dark NOC dashboard theme with these custom properties:
- `--noc-bg`: Background color
- `--noc-surface`: Card/surface background
- `--noc-border`: Border color
- `--noc-accent`: Accent color (green for success)
- `--noc-error`: Error color (red)
- `--noc-success`: Success color
- `--noc-warning`: Warning color
- `--noc-text`: Main text color
- `--noc-text-muted`: Secondary text color

Modify these in `index.php` within the `:root` CSS block, or in `assets/css/style.css`.

### Adding New Features

1. **New API endpoints**: Add routes in `index.php` above the HTML
2. **New UI elements**: Extend the Tailwind classes or add CSS in `style.css`
3. **Additional validation**: Add PHP validation before the n8n webhook call
4. **Error logging**: Enable `DEBUG_MODE` and check server error logs

## Testing

### Test Scenarios

The application has been designed to handle these test scenarios:

**TEST 1: Hello NOC**
- Input: "Hello NOC"
- Expected: GET and POST both succeed, results display above textbox

**TEST 2: OLT003 - Palo - Temperature Alarm**
- Input: "OLT003 - Palo - Temperature Alarm"
- Expected: Original text preserved and passed to both HTTP requests

**TEST 3: Empty Input**
- Input: (empty)
- Expected: Submission prevented, error message displayed

**TEST 4: Simulated GET Failure**
- The application handles cases where GET fails but POST succeeds
- POST result remains visible, GET shows error state

**TEST 5: Simulated POST Failure**
- The application handles cases where POST fails but GET succeeds
- GET result remains visible, POST shows error state

**TEST 6: Rapid Consecutive Pastes**
- No duplicate or outdated response overwrites the latest result
- Auto-debounce ensures only the latest submission processes

### Manual Testing Steps

1. Start XAMPP (Apache running)
2. Visit `http://localhost/noc-trigger/`
3. Paste "Hello NOC" into the textbox
4. Wait for auto-submit or click Submit
5. Observe the results panel appearing above the textbox
6. Check that both GET and POST results are displayed
7. Use the Clear button to reset
8. Test with different inputs

## Credentials and Security

- **Do not expose** n8n webhook credentials in frontend JavaScript
- The webhook URL is configured server-side in `config.php`
- All communication between PHP and n8n happens on the server
- Input text is the only data sent to the webhook
- XSS prevention: All user-displayed content is properly escaped
- CSRF: The application uses same-origin form submissions

### PHP Backend Security

- cURL requests use timeout protection
- Input validation prevents empty submissions
- Error messages are sanitized before display
- No secrets or tokens are hardcoded in JavaScript

## License

This project is for internal Network Operations Center use.