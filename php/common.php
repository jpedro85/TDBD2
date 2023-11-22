<?php
global $link;
$link = createDatabaseConnection();
global $current_page;
$current_page = get_site_url() . '/' . basename(get_permalink());
global $editDataPage;
$editDataPage = get_site_url() . '/edicao-de-dados';

/**
 * The function creates a database connection using the provided host, user, password, and database
 * name.
 *
 * @return mysqli database connection object.
 */
function createDatabaseConnection()
{
    $dbConnection = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    if ($dbConnection->connect_error) {
        die('Connection failed: ' . $dbConnection->connect_error);
    }

    return $dbConnection;
}

/**
 * The function retrieves the values of an ENUM column in a MySQL database table.
 *
 * @param mysqli $connection The connection object that is used to connect to the database
 * @param string $table The name of the table in the database from which you want to retrieve the enum values.
 * @param string $column The name of the column in the table for which you want to retrieve the enum values.
 * @return array of values from the specified column in the specified table.
 */
function get_enum_values($connection, $table, $column)
{
    $query = " SHOW COLUMNS FROM `$table` LIKE '$column' ";
    $result = mysqli_query($connection, $query);
    $row = mysqli_fetch_array($result, MYSQLI_NUM);
    #extract the values
    #the values are enclosed in single quotes
    #and separated by commas
    $regex = "/'(.*?)'/";
    preg_match_all($regex, $row[1], $enum_array);
    $enum_fields = $enum_array[1];
    return ($enum_fields);
}

/**
 * Checks whether the user that's accessing the page has the needed permissions to see its contents
 * @param string $permission name of the permission needed
 * @return bool true if the user has the needed permissions, false if the user doesn't have the needed permissions
 */
function doesUserHavePermission($permission)
{
    return is_user_logged_in() && current_user_can($permission);
}

/**
 * Checks whether there exists a certain value in a given column in given table
 * @param mysqli $connection The connection object that is used to connect to the database
 * @param string $value The value you want to check inside the table and column
 * @param string $table The name of the table in the database from which you want to check the field
 * @param string $column The name of the column you want to check the value
 * @return bool
 */
function checkFieldExistsOnDatabase($connection, $value, $table, $column)
{
    $query = "SELECT {$column} FROM {$table} WHERE {$column} = '{$value}'";
    $result = mysqli_query($connection, $query);
    $numRows = mysqli_num_rows($result);

    return ($numRows > 0);
}

function voltar_atras()
{

    echo "<script type='text/javascript'>document.write(\"<a href='javascript:history.back()'> <button class='' >Voltar Atrás</button> </a>\");</script>
    <noscript>
    <a href='" . $_SERVER['HTTP_REFERER'] . "</a>
    </noscript>";

}

?>