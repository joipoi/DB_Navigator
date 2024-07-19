<?php
/**
 * Plugin Name:       DB Navigator
 * Description:       View your wordpress database, filter it by column and more
 * Version:           1.0
 * Author:            joelbyran
 *
 */
function DBN_load_scripts( $hook ) {

  //load javascript
wp_register_script( 'DBN_script', plugins_url('/script.js', __FILE__), array('jquery'));
wp_enqueue_script( 'DBN_script' );

// Load CSS
  wp_register_style(
    'DBN_style',
    plugins_url( 'style.css', __FILE__ ),
    array()
  );
  wp_enqueue_style( 'DBN_style' );
}
add_action( 'admin_enqueue_scripts', 'DBN_load_scripts' );
function DBN_enqueue_plugin_scripts()
{
    // Localize the script and pass the nonce and AJAX URL
    wp_localize_script('DBN_script', 'your_plugin_ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('filter_table_nonce') 
    ));
}
add_action('admin_enqueue_scripts', 'DBN_enqueue_plugin_scripts');



// Creates an admin menu for our plugin
  function DBN_admin_menu() {
    $user = wp_get_current_user();
    
    add_menu_page(
        __( 'DB Navigator', 'DB Navigator' ),
        __( 'DB Navigator', 'DB Navigator' ),
        'manage_options',
        'DB Navigator',
        function () {
          include('page.php');
        },
        'dashicons-schedule',
        3
    );

}
add_action( 'admin_menu', 'DBN_admin_menu' );

//ajax handlers
add_action( 'wp_ajax_getTable', 'DBN_ajax_getTable_handler' );
function DBN_ajax_getTable_handler() {
  $response = getDBTable($_POST['pageName']);

wp_send_json_success( $response );
    wp_die();
}

add_action( 'wp_ajax_filterTable', 'DBN_ajax_filterTable_handler' );
add_action('wp_ajax_nopriv_filterTable', 'DBN_ajax_filterTable_handler');
function DBN_ajax_filterTable_handler(){
 // Verify the nonce
 check_ajax_referer('filter_table_nonce', 'nonce');
 if (false === check_ajax_referer('filter_table_nonce', 'nonce', false)) {
  wp_send_json_error('Nonce verification failed.', 403);
}

  $response = getDBTableByColumn($_POST['tableName'],$_POST['columnName'], $_POST['columnValue'], $_POST['sortColumn'], $_POST['sortDirection'], $_POST['page'], 50);

  wp_send_json_success( $response );
  wp_die();
}

add_action( 'wp_ajax_downloadTable', 'DBN_ajax_downloadTable_handler' );
function DBN_ajax_downloadTable_handler(){
  $_SESSION['downloadingFile'] = true;
  $_SESSION['tableName'] = $_POST['tableName'];
  $_SESSION['columnName'] = $_POST['columnName'];
  $_SESSION['columnValue'] = $_POST['columnValue'];
  $_SESSION['sortColumn'] = $_POST['sortColumn'];
  $_SESSION['sortDirection'] = $_POST['sortDirection'];
}
//ajax handlers end

//gets database data based on what the user selected,
//builds up sql based on what was selected, values could be empty or -1
function getDBTableByColumn($tableName, $columnName, $columnValue, $sortColumn, $sortDirection, $page, $perPage) {
  global $wpdb;
  $sql = "SELECT COUNT(*) as total FROM $tableName"; //sql for getting $total pages

  //only adds a where clause if columnName and columnValue was selected
  if (!empty($columnValue) && $columnName != -1) {
    $sql .= " WHERE $columnName = %s";
    $sql = $wpdb->prepare($sql, $columnValue);
  }

  $totalResult = $wpdb->get_var($sql);
  $totalPages = ceil($totalResult / $perPage);
  $offset = ($page - 1) * $perPage;

  $sql = "SELECT * FROM $tableName"; //sql for getting $data

  if (!empty($columnValue) && $columnName != -1) {
    $sql .= " WHERE $columnName = %s";
    $sql = $wpdb->prepare($sql, $columnValue);
  }
  if (!empty($sortColumn)) {
    $sql .= " ORDER BY $sortColumn $sortDirection";
  }

  $sql .= " LIMIT %d OFFSET %d";

  $sql = $wpdb->prepare($sql, $perPage, $offset);

  $data = $wpdb->get_results($sql);

  return [
    'rows' => $data,
    'totalPages' => $totalPages
  ]; 
}
//gets just the column of the table with no data
function getDBTable($table_name){
  global $wpdb;
  $column_names = $wpdb->get_col("DESCRIBE $table_name", 0); 
  return $column_names;
}

function download_DB_CSV() {
  session_start();
  if (!isset($_SESSION['downloadingFile'])) {
    $_SESSION['downloadingFile'] = false;
  }
 
  if ($_SESSION['downloadingFile']) {
    $_SESSION['downloadingFile'] = false;
    $data = getDBTableByColumn($_SESSION['tableName'], $_SESSION['columnName'], $_SESSION['columnValue'], $_SESSION['sortColumn'], $_SESSION['sortDirection'], 1, 50)['rows'];

    $timestamp = gmdate('Ymd_His');
    $file_name = "wpDatabase_{$timestamp}.csv";
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="'.$file_name .'"');

    $output = fopen('php://output', 'w');
    
    if (!empty($data)) {
      // Get the column headers
      $headers = array_keys((array) $data[0]);
      fputcsv($output, $headers);
      
      // Iterate over the data rows
      foreach ($data as $row) {
        // Write each row to the CSV file
        fputcsv($output, (array) $row);
      }
    }

    fclose($output);
    exit(); // Stop further execution
  }
}

add_action('init', 'download_DB_CSV');