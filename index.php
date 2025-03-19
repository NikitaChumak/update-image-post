function sync_featured_images_cron() {
  sync_all_featured_images(10);
}
add_action('sync_featured_images_event', 'sync_featured_images_cron');

function register_featured_image_sync_menu() {
  add_menu_page(
      'Image Synchronization',
      'Sync Featured Images',
      'manage_options',
      'featured-image-sync',
      'render_featured_image_sync_page',
      'dashicons-update',
      25
  );
}
add_action('admin_menu', 'register_featured_image_sync_menu');

function render_featured_image_sync_page() {
  echo '<div class="wrap">
      <h1>Featured Image Synchronization</h1>
      <p>Click the button below to update all featured images from <strong>mtchdevimport.wpenginepowered.com</strong>.</p>
      <form method="post">
          <input type="hidden" name="sync_featured_images" value="1">
          <button type="submit" class="button button-primary">Update Images</button>
      </form>
      <hr>
      <h2>Sync Logs</h2>';
  
  display_featured_image_logs();
  echo '</div>';

  if (!empty($_POST['sync_featured_images'])) {
      sync_all_featured_images();
  }
}

function sync_all_featured_images($batch_size = 10) {
  $source_domain = 'https://mtchdevimport.wpenginepowered.com';

  if (!function_exists('media_sideload_image')) {
      require_once ABSPATH . 'wp-admin/includes/media.php';
      require_once ABSPATH . 'wp-admin/includes/file.php';
      require_once ABSPATH . 'wp-admin/includes/image.php';
  }

  $posts = get_posts([
      'post_type'      => 'post',
      'posts_per_page' => $batch_size,
      'meta_query'     => [['key' => '_thumbnail_id', 'compare' => 'NOT EXISTS']],
  ]);

  if (empty($posts)) {
      add_featured_image_log(0, '🎉 All images are already synced.');
      return;
  }

  foreach ($posts as $post) {
      $post_id = $post->ID;
      $slug = $post->post_name;
      add_featured_image_log($post_id, '⏳ Processing post: ' . $slug);

      $response = wp_remote_get("$source_domain/wp-json/wp/v2/posts?slug=$slug", ['timeout' => 15]);
      if (is_wp_error($response)) {
          add_featured_image_log($post_id, '❌ API error: ' . $response->get_error_message());
          continue;
      }

      $post_data = json_decode(wp_remote_retrieve_body($response), true);
      if (empty($post_data)) {
          add_featured_image_log($post_id, '❌ Post not found on source site.');
          continue;
      }

      $featured_image_id = $post_data[0]['featured_media'] ?? null;
      if (!$featured_image_id) {
          add_featured_image_log($post_id, '❌ No featured image available on source site.');
          continue;
      }

      $media_response = wp_remote_get("$source_domain/wp-json/wp/v2/media/$featured_image_id", ['timeout' => 15]);
      if (is_wp_error($media_response)) {
          add_featured_image_log($post_id, '❌ Image request error: ' . $media_response->get_error_message());
          continue;
      }

      $media_data = json_decode(wp_remote_retrieve_body($media_response), true);
      $image_url = $media_data['source_url'] ?? null;
      if (!$image_url) {
          add_featured_image_log($post_id, '❌ Could not retrieve image URL.');
          continue;
      }

      $image_id = media_sideload_image($image_url, $post_id, '', 'id');
      if (is_wp_error($image_id)) {
          add_featured_image_log($post_id, '❌ Image upload error: ' . $image_id->get_error_message());
          continue;
      }

      set_post_thumbnail($post_id, $image_id);
      add_featured_image_log($post_id, '✅ Successfully updated (' . $image_url . ')');
  }
}

function add_featured_image_log($post_id, $message) {
  $logs = get_option('featured_image_logs', []);
  $logs[] = ['post_id' => $post_id, 'title' => get_the_title($post_id), 'message' => $message, 'time' => current_time('mysql')];
  update_option('featured_image_logs', $logs);
}

function display_featured_image_logs() {
  $logs = get_option('featured_image_logs', []);

  if (empty($logs)) {
      echo '<p>No logs available.</p>';
      return;
  }

  echo '<table class="widefat fixed">';
  echo '<thead><tr><th>Time</th><th>Post</th><th>Message</th></tr></thead><tbody>';

  foreach (array_reverse($logs) as $log) {
      echo '<tr>';
      echo '<td>' . esc_html($log['time']) . '</td>';
      echo '<td><a href="' . get_edit_post_link($log['post_id']) . '">' . esc_html($log['title']) . '</a></td>';
      echo '<td>' . esc_html($log['message']) . '</td>';
      echo '</tr>';
  }
  echo '</tbody></table>';

  echo '<form method="post" style="margin-top: 20px;">';
  echo '<input type="hidden" name="clear_logs" value="1">';
  echo '<button type="submit" class="button button-secondary">Clear Logs</button>';
  echo '</form>';

  if (!empty($_POST['clear_logs'])) {
      update_option('featured_image_logs', []);
      echo '<p><strong>Logs cleared!</strong></p>';
  }
}
