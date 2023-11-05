<?php
global $link;
$link= createDatabaseConnection();
global $current_page;
$current_page = get_site_url().'/'.basename(get_permalink());

function createDatabaseConnection() {
    $dbConnection = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    if ($dbConnection->connect_error) {
        die('Connection failed: ' . $dbConnection->connect_error);
    }

    return $dbConnection;
}

function get_enum_values($connection, $table, $column )
{
    $query = " SHOW COLUMNS FROM `$table` LIKE '$column' ";
    $result = mysqli_query($connection, $query );
    $row = mysqli_fetch_array($result , MYSQLI_NUM );
    #extract the values
    #the values are enclosed in single quotes
    #and separated by commas
    $regex = "/'(.*?)'/";
    preg_match_all( $regex , $row[1], $enum_array );
    $enum_fields = $enum_array[1];
    return( $enum_fields );
}

function doesUserHavePermission($permission)
{
    return is_user_logged_in() && current_user_can($permission);
}

?>