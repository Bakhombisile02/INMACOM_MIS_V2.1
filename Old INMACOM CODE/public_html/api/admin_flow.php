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

if (isset($_POST['action'])) {
    if ($_POST['action'] == 'fetch') {
        $order_column = ['station_id', 'value', 'unit', 'date', 'name'];

        $main_query = '
        SELECT flow_levels.station_id, flow_levels.value, flow_levels.unit, flow_levels.date, station.name
        FROM flow_levels INNER JOIN station ON flow_levels.station_id = station.code
        ';

        $search_query = 'WHERE station_id = "'.mysqli_real_escape_string($con, $_POST['station_id']).'" AND date <= "'.date('Y-m-d').'" AND ';

        if (isset($_POST['start_date'], $_POST['end_date']) && $_POST['start_date'] != '' && $_POST['end_date'] != '') {
            $search_query .= 'date >= "'.mysqli_real_escape_string($con, $_POST['start_date']).'" AND date <= "'.mysqli_real_escape_string($con, $_POST['end_date']).'" AND ';
        }

        if (isset($_POST['search']['value'])) {
            $search_query .= '(station_id LIKE "%'.mysqli_real_escape_string($con, $_POST['search']['value']).'%" OR value LIKE "%'.mysqli_real_escape_string($con, $_POST['search']['value']).'%" OR date LIKE "%'.mysqli_real_escape_string($con, $_POST['search']['value']).'%")';
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
            $limit_query = 'LIMIT '.mysqli_real_escape_string($con, $_POST['start']).', '.mysqli_real_escape_string($con, $_POST['length']);
        }

        // Prepare the final query
        $query = $main_query.$search_query.$group_by_query.$order_by_query.$limit_query;

        // Execute the query
        $result = mysqli_query($con, $query);

        // Fetch total rows
        $total_rows_query = mysqli_query($con, $main_query.$group_by_query);
        $total_rows = mysqli_num_rows($total_rows_query);

        // Fetch filtered rows
        $filtered_rows = mysqli_num_rows($result);

        $data = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $sub_array = [];

            $sub_array[] = $row['station_id'];
            $sub_array[] = $row['name'];
            $sub_array[] = $row['value'];
            $sub_array[] = $row['unit'];
            $sub_array[] = $row['date'];

            $data[] = $sub_array;
        }

        $output = [
            'draw' => intval($_POST['draw']),
            'recordsTotal' => $total_rows,
            'recordsFiltered' => $filtered_rows,
            'data' => $data,
        ];

        echo json_encode($output);
    }
}

// Close connection
mysqli_close($con);
