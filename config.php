<?php
/**
 * NOC HTTP Trigger Configuration
 * 
 * Edit these values to match your n8n installation.
 */

// n8n Webhook URL (do not expose credentials in frontend)
define('N8N_WEBHOOK_URL', 'http://localhost:5678/webhook/noc-http-trigger');

// Request timeout in seconds
define('REQUEST_TIMEOUT', 30);

// Application name
define('APP_NAME', 'NOC HTTP Trigger');

// Debug mode (enable for development, disable for production)
define('DEBUG_MODE', true);