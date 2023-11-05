<?php
global $current_page;
$current_page = get_site_url().'/'.basename(get_permalink());
$link = mysqli_connect(DB_HOST,DB_USER,DB_PASSWORD,DB_NAME);
function connect()
{
    if ($link->connect_error) {
        die('Connection failed: ' . $link->connect_error);
    }
}
function get_enum_values($connection, $table, $column )
{
    $query = " SHOW COLUMNS FROM `$table` LIKE '$column' ";
    $result = mysqli_query($connection, $query );
    $row = mysqli_fetch_array($result , MYSQLI_NUM );
    $regex = "/'(.*?)'/";
    preg_match_all( $regex , $row[1], $enum_array );
    $enum_fields = $enum_array[1];
    return( $enum_fields );
}
function voltaatras()
{
    echo "<script type='text/javascript'>document.write(\"<a href='javascript:history.back()' class='backLink' title='Voltar atr&aacute;s'>Voltar atr&aacute;s</a>\");</script>
<noscript>
<a href='" . $_SERVER['HTTP_REFERER'] . "‘ class='backLink' title='Voltar atr&aacute;s'>Voltar atr&aacute;s</a>
</noscript>";
}
?>