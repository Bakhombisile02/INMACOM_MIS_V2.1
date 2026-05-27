<?php

// Database connection details
$host = 'localhost';
$username = 'u550237388_inmacomadmin';
$password = 'AccessInmacom2046';
$database = 'u550237388_inmacom_db1';

// Establish connection
$con = mysqli_connect($host, $username, $password, $database);

// Check connection
if (! $con) {
    // If connection fails, return an error
    echo json_encode(['error' => 'Database connection failed: '.mysqli_connect_error()]);
    exit(); // Stop further execution if connection fails
}

// Check if the action is set and is 'fetch'
if (isset($_POST['action']) && $_POST['action'] == 'fetch') {
    $order_column = ['station_id', 'value', 'date'];

    $main_query = '
    SELECT station_id, value, date 
    FROM '.$_POST['table'].'
    ';

    $search_query = 'WHERE station_id = "'.$_POST['station_id'].'" AND date <= "'.date('Y-m-d').'" AND ';

    if (isset($_POST['start_date'], $_POST['end_date']) && $_POST['start_date'] != '' && $_POST['end_date'] != '') {
        $search_query .= 'date >= "'.$_POST['start_date'].'" AND date <= "'.$_POST['end_date'].'" AND ';
    }

    if (isset($_POST['search']['value'])) {
        $search_query .= '(station_id LIKE "%'.$_POST['search']['value'].'%" OR value LIKE "%'.$_POST['search']['value'].'%" OR date LIKE "%'.$_POST['search']['value'].'%")';
    }

    $group_by_query = ' GROUP BY date ';

    $order_by_query = '';

    if (isset($_POST['order'])) {
        $order_by_query = 'ORDER BY '.$order_column[$_POST['order']['0']['column']].' '.$_POST['order']['0']['dir'].' ';
    } else {
        $order_by_query = 'ORDER BY date ASC ';
    }

    $limit_query = '';

    if ($_POST['length'] != -1) {
        $limit_query = 'LIMIT '.$_POST['start'].', '.$_POST['length'];
    }

    // Full query for fetching data
    $query = $main_query.$search_query.$group_by_query.$order_by_query.$limit_query;
    $result = mysqli_query($con, $query);

    // Get the number of filtered rows
    $filtered_rows = mysqli_num_rows($result);

    // Query for total rows
    $total_query = $main_query.$group_by_query;
    $total_result = mysqli_query($con, $total_query);
    $total_rows = mysqli_num_rows($total_result);

    $data = [];

    // Fetch the data from the result
    while ($row = mysqli_fetch_assoc($result)) {
        $sub_array = [];
        $sub_array[] = $row['station_id'];
        $sub_array[] = $row['value'];
        $sub_array[] = $row['date'];

        $data[] = $sub_array;
    }

    // Prepare the output
    $output = [
        'draw' => intval($_POST['draw']),
        'recordsTotal' => $total_rows,
        'recordsFiltered' => $filtered_rows,
        'data' => $data,
    ];

    // Return the result as JSON
    echo json_encode($output);
}
