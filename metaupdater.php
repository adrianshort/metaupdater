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
    foreach( explode( PHP_EOL, stripslashes( $_POST['data'] ) ) as $row ) {
      $data[] = str_getcsv( $row, ",", '"' );
    }

    // Get header row
    list( $search_field, $replace_field ) = array_shift( $data );


    echo "<h3>Results</h3>";

    $success = 0;
    $not_found = [];

    foreach( $data as $row ) {

      list( $key, $value ) = $row;

      if ( $key == '' ) continue; // skip blank lines

      $args = array(
        'meta_key'   => $search_field,
        'post_type' => 'site'
      );

      // if ( is_string( $key ) ) {
        $args['meta_value'] = $key; 
      // } elseif ( is_numeric( $key ) ) {
      //   $args['meta_value_num'] = $key;
      // }

      echo "<p>";

      $query = new WP_Query( $args );

      if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
          $query->the_post();
          if ( $_POST['dry_run'] != '1' ) {
            if ( update_post_meta( get_the_ID(), $replace_field, trim( $value ) ) === true ) {
              echo "<pre>";
              print_r( $args );
              echo sprintf( "ID: %d<br>%s: %s<br>Title: %s<br>%s: %s", get_the_ID(), $search_field, $key, get_the_title(), $replace_field, $value );
              $success++;
            }
          }
        }
      } else {
        $not_found[]= $key;
        echo "No results<br>";
      }
    }

    echo "<h3>Updated $success custom fields OK.</h3><hr>";

    echo "<h3>Errors</h3>";
    if ( count( $not_found) > 0 ) {
      echo "<p>Posts with the custom field <strong>$search_field</strong> with these values could not be found:</p>\n<ul>";
      foreach( $not_found as $error ) {
        echo "<li>$error</li>\n";
      }
      echo "</ul>";
    } else {
      echo "<p>None.</p>";
    }
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
99999,dummy
  </textarea>
  <p><input type="checkbox" name="dry_run" value="1" checked="checked" /> <label for="dry_run">Dry run - leave the database unchanged</label></p>
  <p><input class="button-primary" type="submit" value="Update Custom Fields" /></p>
  </form>

  <?php
}
