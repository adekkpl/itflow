<?php

// API related functions

function createAPIKey($secret, $name, $expire, $client) {
    global $mysqli, $name, $ip, $user_agent, $user_id;

    mysqli_query($mysqli,"INSERT INTO api_keys SET api_key_name = '$name', api_key_secret = '$secret', api_key_expire = '$expire', api_key_client_id = $client");
    $api_key_id = mysqli_insert_id($mysqli);

    // Logging
    mysqli_query($mysqli,"INSERT INTO logs SET log_type = 'API', log_action = 'Create', log_description = '$name created API Key $name set to expire on $expire', log_ip = '$ip', log_user_agent = '$user_agent',  log_client_id = $client, log_user_id = $user_id, log_entity_id = $api_key_id");

    return $api_key_id;
}

function deleteAPIKey($api_key_id) {
    global $mysqli, $name, $ip, $user_agent, $user_id;

    // Get API Key Name
    $row = mysqli_fetch_array(mysqli_query($mysqli,"SELECT * FROM api_keys WHERE api_key_id = $api_key_id"));
    $name = sanitizeInput($row['api_key_name']);

    mysqli_query($mysqli,"DELETE FROM api_keys WHERE api_key_id = $api_key_id");

    // Logging
    mysqli_query($mysqli,"INSERT INTO logs SET log_type = 'API Key', log_action = 'Delete', log_description = '$name deleted API key $name', log_ip = '$ip', log_user_agent = '$user_agent', log_user_id = $user_id, log_entity_id = $api_key_id");
}

function getAPIKey($api_key_id) {
    global $mysqli;

    $row = mysqli_fetch_array(mysqli_query($mysqli,"SELECT * FROM api_keys WHERE api_key_id = $api_key_id"));
    return $row;
}

function tryAPIKey($api_key_secret) {
    
    global $mysqli, $ip, $user_agent;

    $row = mysqli_fetch_array(mysqli_query($mysqli,"SELECT * FROM api_keys WHERE api_key_secret = '$api_key_secret'"));

    if($row) {
        $api_key_id = intval($row['api_key_id']);
        $api_key_expire = sanitizeInput($row['api_key_expire']);
        $api_client_id = intval($row['api_key_client_id']);
        $api_key_name = sanitizeInput($row['api_key_name']);

        // Check if the key has expired
        if(strtotime($api_key_expire) < time()) {
            // Log expired Key
            mysqli_query($mysqli,"INSERT INTO logs SET log_type = 'API', log_action = 'Failed', log_description = 'Expired key: ', log_ip = '$ip', log_user_agent = '$user_agent'");
            echo json_encode(['status' => 'error', 'message' => 'Expired API Key']);
            exit;
        }

        $return_data = [
            'api_key_id' => $api_key_id,
            'api_key_client_id' => $api_client_id,
            'api_key_name' => $api_key_name,
            'api_key_expire' => $api_key_expire,
        ];

        if ($api_client_id) {
            $return_data['api_client_id'] = $api_client_id;
        }

        return $return_data;
    } else {
        // Log invalid Key
        mysqli_query($mysqli,"INSERT INTO logs SET log_type = 'API', log_action = 'Failed', log_description = 'Incorrect or expired key: ', log_ip = '$ip', log_user_agent = '$user_agent'");
        echo json_encode(['status' => 'error', 'message' => 'Invalid API Key']);
        exit;
    }
}

function getAPIWhereClause(
    $obj,
    $obj_id,
    $api_client_id
){

    //if object is not integer, exit
    if (!is_numeric($obj_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid Object ID']);
        exit;
    }

    $where_clause = "";
    // If asset_id is all, check if client_id is set


    if ($obj_id == '0') {
        if ($api_client_id != '0') {
            // If client_id is set, get all assets for that client
            $where_clause = "WHERE  " . $obj . "_client_id = $api_client_id";
        }else {   // If client_id is not set, get all assets
            $where_clause = "";
        }

    } else {
        if ($api_client_id != '0') {
            // If client_id is set, get the asset only if the client matches
            $where_clause = "WHERE " . $obj . "_id = $obj_id AND asset_client_id = $api_client_id";
        } else {
            // If client_id is not set, get the asset
            $where_clause = "WHERE " . $obj . "_id = $obj_id";
        }
    }
    return $where_clause;
}