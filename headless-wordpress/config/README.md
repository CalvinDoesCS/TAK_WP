# Configuration Directory

This directory contains configuration files for your headless WordPress installation.

## Files

### `php.ini`

PHP configuration settings for the WordPress container.

**Current settings:**

- `upload_max_filesize = 64M`
- `post_max_size = 64M`
- `memory_limit = 256M`
- `max_execution_time = 300`
- `max_input_vars = 3000`

### `.env.example`

Template for environment variables.

**To use:**

1. Copy to `.env`: `cp config/.env.example .env`
2. Update values as needed
3. Never commit `.env` to version control

**Contains:**

- Database credentials
- WordPress admin credentials
- Site configuration
