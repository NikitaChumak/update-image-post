# Featured Image Synchronization for WordPress

## Overview
This WordPress plugin allows you to automatically sync featured images for posts from an external WordPress site. It provides an admin panel where you can manually trigger the synchronization process and view logs of past sync operations.

## Features
- Syncs featured images from a remote WordPress site using REST API.
- Batches the synchronization process to avoid performance issues.
- Provides an admin page to trigger synchronization manually.
- Displays a log of recent synchronization events.
- Allows clearing of logs from the admin interface.

## Installation
1. Download and place the PHP file in your WordPress theme's `functions.php` file or create a custom plugin.
2. Ensure that the source WordPress site has the REST API enabled.
3. Activate the plugin if added as a custom plugin.

## Usage
### Admin Menu
- Navigate to **Sync Featured Images** in the WordPress admin panel.
- Click **Update Images** to start the synchronization process.
- View logs to check the sync status.
- Click **Clear Logs** to remove past log records.

### Automatic Sync (Cron Job)
- The function `sync_featured_images_cron()` is hooked to a scheduled event.
- You can set up a WP-Cron job or a real cron job to trigger `sync_featured_images_event` periodically.

## Customization
- Modify `$source_domain` in `sync_all_featured_images()` to match the source WordPress site.
- Change the post type or batch size by adjusting the `get_posts()` query inside `sync_all_featured_images()`.

## Requirements
- WordPress 5.0+
- PHP 7.4+
- REST API enabled on the source WordPress site

## Troubleshooting
- If featured images are not syncing, ensure the source site has public API access.
- Check the WordPress logs for any errors related to `wp_remote_get()`.
- Increase the timeout value if API requests fail due to slow responses.

## License
This plugin is open-source and can be modified or distributed freely.

