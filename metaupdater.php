<?php
/*
Plugin Name: Meta Updater
Plugin URI: 
Description: Bulk insert and update custom fields
Version: 0.1
Author: Adrian Short
Author URI: https://adrianshort.org/
License: CC0/public domain
*/

add_action( 'admin_menu', 'as_metaupdater_menu' );

// Add submenu to Tools menu
function as_metaupdater_menu() {
  add_submenu_page(
    'tools.php', // top-level handle
    'Meta Updater', // page title 
    'Meta Updater', // submenu title
    'manage_options', // capabilities
    'as_metaupdater', // submenu handle
    'as_metaupdater_page' //function
  );
}

function as_metaupdater_page() {
  if ( ! current_user_can( 'manage_options' ) ) {
    wp_die("You do not have sufficient permissions to access this page.");
  }
  ?>
  <div class="wrap">
    <h2>Meta Updater</h2>

  <?php
  if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
    foreach( explode( PHP_EOL, $_POST['data'] ) as $row ) {
      $data[] = str_getcsv( $row );
    }
    echo "<pre>";
    print_r( $data );
    echo "</pre>";

    $header_row = array_shift( $data );
    $search_field = $header_row[0];
    $replace_field = $header_row[1];

    print_r( $header_row );
    print_r( $data );

    echo "Search: $search_field, replace: $replace_field";

    echo "<h3>Results</h3>";

    $success = 0;

    foreach( $data as $row ) {

      $args = array(
        'meta_key'   => $search_field,
        'post_type' => 'site'
      );

      if ( is_string( $row[0] ) ) {
        $args['meta_value'] = $row[0]; 
      } elseif ( is_numeric( $row[0] ) ) {
        $args['meta_value_num'] = $row[0];
      }

      echo "<p>";
      print_r( $args );

      $query = new WP_Query( $args );

      if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
          $query->the_post();
          echo get_the_title();

          if ( $_POST['dry_run'] != '1' ) {
            echo "Updating meta for real";
            if ( update_post_meta( get_the_ID(), $replace_field, $row[1] ) ) {
              $success++;
            }
          }

        }
      } else {
        echo "No results<br>";
      }

    }

    echo "<h3>Saved $success terms.</h3>";
    
  }
  ?>
  </div>

  <form method="POST" action="">
  <p>
    Paste in CSV data: column 1 holds the key field and column 2 holds the value. The first row must be a header row. Every post with the field set to the value in column 1 will have a field added or updated with the value in column 2.
  </p>

  <textarea name="data" rows="15" cols="60">
openlylocal_id,area_covered
2192,"All of London"
2191,"Greater Manchester"
  </textarea>
  <p><input type="checkbox" name="dry_run" value="1" checked="checked" /> <label for="dry_run">Dry run - leave the database unchanged</label></p>
  <p><input class="button-primary" type="submit" value="Update Custom Fields" /></p>
  </form>

  <?php
}
