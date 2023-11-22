<?php
require_once("custom/php/common.php");
// TODO check if its an allowed value to edit
// TODO check server-side check the new values for allowed values
if (arrayKeysExists(["estado", "tipo", "id"], $_REQUEST) && checkKeysValues(["estado", "tipo"], $_REQUEST, ["editar", "item"])) {
    // checking if the item is going to be updated or not
    if (array_key_exists("updateState", $_REQUEST) && $_REQUEST["updateState"] == "updating") {

        // Server-side verifications, can be tested with postman
        $validForm = true;
        $invalidFields = "";

        // Trim the received values, so it has no spaces in the edges
        $itemName = (isset($_REQUEST["itemName"])) ? trim($_REQUEST["itemName"]) : "";
        $typeId = (isset($_REQUEST["typeId"])) ? trim($_REQUEST["typeId"]) : "";

        // Check itemName received is empty or just numbers
        if (empty($itemName) || is_numeric($itemName)) {
            $validForm = false;
            $invalidFields .= "<p>Nome do item é invalido</p>";
        }
        // Checks whether the item type received is valid or not
        if (empty($typeId) || !is_numeric($typeId) || !checkFieldExistsOnDatabase($link, $_REQUEST["typeId"], "item_type", "id")) {
            $validForm = false;
            $invalidFields .= "<p>Id do tipo de item é invalido</p>";
        }
        // Checks if there were any errors in the server side verification
        if (!$validForm) {
            echo $invalidFields;
            voltar_atras();
        }// if there were no problems update the database
        else {
            if (!$_SESSION["itemUpdated"] && mysqli_begin_transaction($link)) {

                $updateItemQuery = "UPDATE item SET name = '$itemName', item_type_id = '$typeId' WHERE item.id = {$_REQUEST["id"]}";
                $updateItemResult = mysqli_query($link, $updateItemQuery);
                // checking whether the query was successful or not
                if (!$updateItemResult) {
                    mysqli_rollback($link);
                    echo "Ocorreu um erro na Atualização de dados nao conseguiu iniciar: " . mysqli_error($link);
                    voltar_atras();
                } else {

                    echo "<p>Atualizações realizadas com sucesso</p>
                          <p>Clique em continuar para voltar a pagina de gestao de itens</p>
                          <hr><a href='" . get_site_url() . "/gestao-de-itens'><button class=''>Continuar</button></a>";
                    // Commit the transaction
                    mysqli_commit($link);
                    $_SESSION["itemUpdated"] = true;
                }
            }// Checking if the item was already updated
            elseif ($_SESSION["itemUpdated"]) {
                echo "Os dados ja foram atualizados
                    <a href='" . get_site_url() . "/gestao-de-itens'><button class=''>Continuar</button></a>";
            } else {
                echo "Ocorreu um erro na Atualização de dados: " . mysqli_error($link);
                voltar_atras();
            }
        }
    } else {
        echo "<form method='post' action='" . get_permalink() . basename($_SERVER["REQUEST_URI"]) . "'>
              <table>";
        echo "
          <thead>
            <tr>
                <th>id</th>
                <th>name</th>
                <th>item_type_id</th>
                <th>state</th>
            </tr>
          </thead>";

        // Fetching the id, name, item_type, state for the item requested
        $itemQuery = "SELECT item.name AS itemName,item.item_type_id AS typeId , item.state FROM item WHERE item.id = {$_REQUEST["id"]}";
        $itemQueryResult = mysqli_query($link, $itemQuery);

        // Checking if the query was successful
        if (!$itemQueryResult) {
            echo "Ocorreu um erro na consulta:" . mysqli_error($link);
            voltar_atras();
        } else {
            $itemData = mysqli_fetch_assoc($itemQueryResult);

            // Fetching all item_type id for a dropdown box so we can choose the id more easily without needing mental mapping of the database
            $itemTypeQuery = "SELECT id as typeId FROM item_type";
            $itemTypeQueryResult = mysqli_query($link, $itemTypeQuery);

            if (!$itemTypeQueryResult) {
                echo "Ocorreu um erro na consulta:" . mysqli_error($link);
                voltar_atras();
            } else {

                // Starting to format the dropdown
                $dropdownTypeId = "<select name='typeId' id='typeId'>";
                while ($row = mysqli_fetch_assoc($itemTypeQueryResult)) {
                    if ($row["typeId"] == $itemData["typeId"]) {
                        $dropdownTypeId .= "<option value='{$row["typeId"]}' selected>{$row["typeId"]}</option>";
                    } else {
                        $dropdownTypeId .= "<option value='{$row["typeId"]}'>{$row["typeId"]}</option>";
                    }
                }
                $dropdownTypeId .= "</select>";

                echo "
            <tbody>
                <tr>
                    <td><strong>{$_REQUEST["id"]}</strong></td>
                    <td><input type='text' name='itemName' id='itemName' value='{$itemData["itemName"]}'></td>
                    <td>$dropdownTypeId</td>
                    <td><strong>{$itemData["state"]}</strong></td>
                </tr>
            </tbody>
            </table>
            <input type='hidden' name='updateState' value='updating'>
            <p>Clique em <strong>Submeter</strong> para atualizar os dados</p>
            <hr><button type='submit'>Submeter</button>";

                voltar_atras();

                $_SESSION["itemUpdated"] = false;
            }
        }
    }
} else if (arrayKeysExists(["estado", "tipo", "id"], $_REQUEST) && checkKeysValues(["estado", "tipo"], $_REQUEST, ["ativar", "item"])) {
    if (array_key_exists("updateState", $_REQUEST) && $_REQUEST["updateState"] == "activating") {
        // Server-side verifications, can be tested with postman
        $validForm = true;
        $invalidFields = "";

        // Checks whether the item type received is valid or not
        if (empty($_REQUEST["state"]) || $_REQUEST["state"] != "active") {
            $validForm = false;
            $invalidFields .= "<p>Estado do item é invalido</p>";
        }

        // Checks if there were any errors in the server side verification
        if (!$validForm) {
            echo $invalidFields;
            voltar_atras();
        } // if there were no problems update the database
        else {
            if (!$_SESSION["itemUpdated"] && mysqli_begin_transaction($link)) {

                $updateItemQuery = "UPDATE item SET state='active' WHERE item.id = {$_REQUEST["id"]}";
                $updateItemResult = mysqli_query($link, $updateItemQuery);

                // checking whether the query was successful or not
                if (!$updateItemResult) {
                    mysqli_rollback($link);
                    echo "Ocorreu um erro na Atualização de dados nao conseguiu iniciar: " . mysqli_error($link);
                    voltar_atras();
                } else {

                    echo "<p>Atualização realizada com sucesso</p>
                          <p>Clique em continuar para voltar a pagina de gestao de itens</p>
                          <hr><a href='" . get_site_url() . "/gestao-de-itens'><button class=''>Continuar</button></a>";

                    // Commit the transaction
                    mysqli_commit($link);
                    $_SESSION["itemUpdated"] = true;
                }
            }// Checking if the item was already updated
            else if ($_SESSION["itemUpdated"]) {
                echo "Os dados ja foram atualizados
                    <a href='" . get_site_url() . "/gestao-de-itens'><button class=''>Continuar</button></a>";
            } else {
                echo "Ocorreu um erro na Atualização de dados: " . mysqli_error($link);
                voltar_atras();
            }
        }
    } else {
        echo "<strong>Pretende ativar o item?</strong>
          <table>
          <thead>
            <tr>
                <th>id</th>
                <th>name</th>
                <th>item_type_id</th>
                <th>state</th>
            </tr>
          </thead>";

        // Fetching the id, name, item_type, state for the item requested
        $itemQuery = "SELECT item.name AS itemName,item.item_type_id AS typeId , item.state FROM item WHERE item.id = {$_REQUEST["id"]}";
        $itemQueryResult = mysqli_query($link, $itemQuery);

        if (!$itemQueryResult) {
            echo "Ocorreu um erro na consulta:" . mysqli_error($link);
            voltar_atras();
        } else {

            $itemData = mysqli_fetch_assoc($itemQueryResult);

            echo "
            <tbody>
                <tr>
                    <td><strong>{$_REQUEST["id"]}</strong></td>
                    <td><strong>{$itemData["itemName"]}</strong></td>
                    <td><strong>{$itemData["typeId"]}</strong></td>
                    <td><strong>{$itemData["state"]}</strong></td>
                </tr>
            </tbody>
            </table>
            <form method='post' action='" . get_permalink() . basename($_SERVER["REQUEST_URI"]) . "'>
            <input type='hidden' name='state' value='active'>
            <input type='hidden' name='updateState' value='activating'>
            <p>Clique em <strong>Submeter</strong> para atualizar os dados</p>
            <hr><button type='submit'>Submeter</button>";

            $_SESSION["itemUpdated"] = false;

        }
    }
} // Checking if the request is to deactivate the item
else if (arrayKeysExists(["estado", "tipo", "id"], $_REQUEST) && checkKeysValues(["estado", "tipo"], $_REQUEST, ["desativar", "item"])) {
    if (array_key_exists("updateState", $_REQUEST) && $_REQUEST["updateState"] == "deactivating") {
        // Server-side verifications, can be tested with postman
        $validForm = true;
        $invalidFields = "";

        // Checks whether the item type received is valid or not
        if (empty($_REQUEST["state"]) || $_REQUEST["state"] != "inactive") {
            $validForm = false;
            $invalidFields .= "<p>Estado do item é invalido</p>";
        }

        // Checks if there were any errors in the server side verification
        if (!$validForm) {
            echo $invalidFields;
            voltar_atras();
        } // if there were no problems update the database
        else {
            if (!$_SESSION["itemUpdated"] && mysqli_begin_transaction($link)) {

                $updateItemQuery = "UPDATE item SET state='inactive' WHERE item.id = {$_REQUEST["id"]}";
                $updateItemResult = mysqli_query($link, $updateItemQuery);

                // checking whether the query was successful or not
                if (!$updateItemResult) {
                    mysqli_rollback($link);
                    echo "Ocorreu um erro na Atualização de dados nao conseguiu iniciar: " . mysqli_error($link);
                    voltar_atras();
                } else {

                    echo "<p>Atualização realizada com sucesso</p>
                          <p>Clique em continuar para voltar a pagina de gestao de itens</p>
                          <hr><a href='" . get_site_url() . "/gestao-de-itens'><button class=''>Continuar</button></a>";

                    // Commit the transaction
                    mysqli_commit($link);
                    $_SESSION["itemUpdated"] = true;
                }
            }// Checking if the item was already updated
            else if ($_SESSION["itemUpdated"]) {
                echo "Os dados ja foram atualizados
                    <a href='" . get_site_url() . "/gestao-de-itens'><button class=''>Continuar</button></a>";
            } else {
                echo "Ocorreu um erro na Atualização de dados: " . mysqli_error($link);
                voltar_atras();
            }
        }
    } else {

        echo "<strong>Pretende desativar o item?</strong>
          <table>
          <thead>
            <tr>
                <th>id</th>
                <th>name</th>
                <th>item_type_id</th>
                <th>state</th>
            </tr>
          </thead>";

        // Fetching the id, name, item_type, state for the item requested
        $itemQuery = "SELECT item.name AS itemName,item.item_type_id AS typeId , item.state FROM item WHERE item.id = {$_REQUEST["id"]}";
        $itemQueryResult = mysqli_query($link, $itemQuery);
        if (!$itemQueryResult) {
            echo "Ocorreu um erro na consulta:" . mysqli_error($link);
            voltar_atras();
        } else {

            $itemData = mysqli_fetch_assoc($itemQueryResult);

            echo "
            <tbody>
                <tr>
                    <td><strong>{$_REQUEST["id"]}</strong></td>
                    <td><strong>{$itemData["itemName"]}</strong></td>
                    <td><strong>{$itemData["typeId"]}</strong></td>
                    <td><strong>{$itemData["state"]}</strong></td>
                </tr>
            </tbody>
            </table>
            <form method='post' action='" . get_permalink() . basename($_SERVER["REQUEST_URI"]) . "'>
            <input type='hidden' name='state' value='inactive'>
            <input type='hidden' name='updateState' value='deactivating'>
            <p>Clique em <strong>Submeter</strong> para atualizar os dados</p>
            <hr><button type='submit'>Submeter</button>";

            $_SESSION["itemUpdated"] = false;

        }
    }
} else if (arrayKeysExists(["estado", "tipo", "id"], $_REQUEST) && checkKeysValues(["estado", "tipo"], $_REQUEST, ["apagar", "item"])) {
    if (array_key_exists("updateState", $_REQUEST) && $_REQUEST["updateState"] == "deleting") {
        if (!$_SESSION["itemUpdated"] && mysqli_begin_transaction($link)) {

            $deleteItemQuery = "DELETE FROM item WHERE item.id = {$_REQUEST["id"]}";
            $deleteItemResult = mysqli_query($link, $deleteItemQuery);

            // checking whether the query was successful or not
            if (!$deleteItemResult) {
                mysqli_rollback($link);
                echo "Ocorreu um erro na Atualização de dados nao conseguiu iniciar: " . mysqli_error($link);
                voltar_atras();
            } else {

                echo "<p>Eliminições realizadas com sucesso</p>
                          <p>Clique em continuar para voltar a pagina de gestao de itens</p>
                          <hr><a href='" . get_site_url() . "/gestao-de-itens'><button class=''>Continuar</button></a>";

                // Commit the transaction
                mysqli_commit($link);
                $_SESSION["itemUpdated"] = true;
            }
        }// Checking if the item was already updated
        else if ($_SESSION["itemUpdated"]) {
            echo "Os dados ja foram atualizados
                    <a href='" . get_site_url() . "/gestao-de-itens'><button class=''>Continuar</button></a>";
        } else {
            echo "Ocorreu um erro na Atualização de dados: " . mysqli_error($link);
            voltar_atras();
        }
    } else {
        echo "<strong>Estamos prestes a apagar os dados abaixo da base de dados. Confirma que pertende apagar os mesmos?</strong>
          <table>
          <thead>
            <tr>
                <th>id</th>
                <th>name</th>
                <th>item_type_id</th>
                <th>state</th>
            </tr>
          </thead>";

        // Fetching the id, name, item_type, state for the item requested
        $itemQuery = "SELECT item.name AS itemName,item.item_type_id AS typeId , item.state FROM item WHERE item.id = {$_REQUEST["id"]}";
        $itemQueryResult = mysqli_query($link, $itemQuery);

        if (!$itemQueryResult) {
            echo "Ocorreu um erro na consulta:" . mysqli_error($link);
            voltar_atras();
        } else {

            $itemData = mysqli_fetch_assoc($itemQueryResult);

            echo "
            <tbody>
                <tr>
                    <td><strong>{$_REQUEST["id"]}</strong></td>
                    <td><strong>{$itemData["itemName"]}</strong></td>
                    <td><strong>{$itemData["typeId"]}</strong></td>
                    <td><strong>{$itemData["state"]}</strong></td>
                </tr>
            </tbody>
            </table>
            <form method='post' action='" . get_permalink() . basename($_SERVER["REQUEST_URI"]) . "'>
            <input type='hidden' name='updateState' value='deleting'>
            <p>Clique em <strong>Submeter</strong> para apagar os dados</p>
            <hr><button type='submit'>Submeter</button>";

            $_SESSION["itemUpdated"] = false;

        }
    }
}

// Adaptation of array_key_exists to be able to check if it contains multiples keys
function arrayKeysExists(array $keys, array $arr): bool
{
    // Using the array_flip we swap the keys of $keys array to values and values to keys
    // with that we now can use array_diff_keys to check if it contains the keys
    // so a normal array would look like this  [0 => "edit"] after array_flip it would look like this ["edit"] => 0
    return !array_diff_key(array_flip($keys), $arr);
}

// We are using this function much like checking the values of a dictionary so the values must be in the same index as
// the key you want to check its value
function checkKeysValues(array $keys, array $arrayToCheck, array $values): bool
{
    $containsValues = true;
    // checking if the keys array size is the same ad the values array
    if (count($keys) != count($values)) {
        return false;
    }
    // We are iterating the whole array and getting its index on $index and saving the value thats in that index on $key
    foreach ($keys as $index => $key) {
        if (!array_key_exists($key, $arrayToCheck)) {
            return false;
        }
        if ($arrayToCheck[$key] != $values[$index]) {
            $containsValues = false;
        }
    }
    return $containsValues;
}

?>