<?php
require_once("custom/php/common.php");

if (arrayKeysExists(["estado", "tipo", "id"], $_REQUEST) && checkKeysValues(["estado", "tipo"], $_REQUEST, ["editar", "item"])) {
    // checking if the item is going to be updated or not
    if (array_key_exists("updateState", $_REQUEST) && $_REQUEST["updateState"] == "updating") {

        // Server-side verifications, can be tested with postman
        $validForm = true;
        $invalidFields = "";

        // Trim the received values, so it has no spaces in the edges and is not a html special char
        $itemName = (isset($_REQUEST["itemName"])) ? trim($_REQUEST["itemName"]) : "";
        $itemName = htmlspecialchars($itemName);

        $typeId = (isset($_REQUEST["typeId"])) ? trim($_REQUEST["typeId"]) : "";
        $typeId = htmlspecialchars($typeId);

        // Check itemName received is empty or just numbers
        if (empty($itemName) || is_numeric($itemName) || !containsOnlyLatinLetters1($itemName)) {
            $validForm = false;
            $invalidFields .= "<li class='list'>Nome do item é invalido</li>";
        }
        // Checks whether the item type received is valid or not
        if (empty($typeId) || !is_numeric($typeId) || !checkFieldExistsOnDatabase($link, $_REQUEST["typeId"], "item_type", "id")) {
            $validForm = false;
            $invalidFields .= "<li class='list'>Id do tipo de item é invalido</li>";
        }
        // Checks whether the item id received is valid or not
        if (empty($_REQUEST["id"]) || !is_numeric($_REQUEST["id"]) || $_REQUEST["id"] != $_REQUEST["itemId"]) {
            $validForm = false;
            $invalidFields .= "<li class='list'>O id do Item é invalido</li>";
        }

        // Checks if there were any errors in the server side verification
        if (!$validForm) {
            echo "<div class='error-div'>$invalidFields</div><hr>";
            voltar_atras();
        }// if there were no problems update the database
        else {
            if (!$_SESSION["itemUpdated"] && mysqli_begin_transaction($link)) {
                // Using prepared statements here so to protect against sql injections if the values were properly sanitized

                // Updating the correct value on the tables using prepared statements
                $updateItemQuery = mysqli_prepare($link, "UPDATE item SET name = ?, item_type_id = ? WHERE item.id = ?");
                mysqli_stmt_bind_param($updateItemQuery, "sss", $itemName, $typeId, $_REQUEST["id"]);

                // Gets the result of the query execution
                $updateItemResult = mysqli_stmt_execute($updateItemQuery);

                // checking whether the query was successful or not
                if (!$updateItemResult) {

                    // Gets the error that happened in the prepared statement and outputs the value in htmlspecialchars
                    $error = mysqli_stmt_error($updateItemQuery);

                    mysqli_rollback($link);

                    echo "<div class='error-div'>
                            <strong class='list' >Ocorreu um erro na Atualização de dados: " . htmlspecialchars($error) . "</strong>
                          </div>";

                    voltar_atras();
                } else {

                    echo "<div class='contorno'>
                            <p class='success'>Atualizações realizadas com sucesso</p>
                          </div>
                          <p>Clique em continuar para voltar a pagina de gestao de itens</p>
                          <hr><a href='" . get_site_url() . "/gestao-de-itens'><button class='button-33'>Continuar</button></a>";

                    // Commits the transaction
                    mysqli_commit($link);
                    $_SESSION["itemUpdated"] = true;
                }
            }// Checking if the item was already updated
            elseif ($_SESSION["itemUpdated"]) {
                echo "<div class='error-div'>
                         <b class='list'>Os dados ja foram atualizados</b>
                      </div>
                      <hr>
                      <a href='" . get_site_url() . "/gestao-de-itens'><button class='button-33'>Continuar</button></a>";
            }// If it doesn't pass all checks it means an error occurred starting the transaction
            else {

                echo "<div class='error-div'>
                        <strong class='list' >Ocorreu um erro no começo da Atualização de dados: " . mysqli_error($link) . "</strong>
                      </div>";

                voltar_atras();
            }
        }
    } else {
        echo "<form method='post' action='" . get_permalink() . basename($_SERVER["REQUEST_URI"]) . "'>
              <table class='content-table'>";
        echo "
              <thead>
                <tr>
                    <th>id</th>
                    <th>name</th>
                    <th>item_type_id</th>
                    <th>state</th>
                </tr>
              </thead>";

        // Fetching the id, name, item_type, state for the item requested using prepared statements to prevent sql injection
        $itemQuery = mysqli_prepare($link, "SELECT item.name AS itemName,item.item_type_id AS typeId , item.state FROM item WHERE item.id = ?");
        mysqli_stmt_bind_param($itemQuery, "s", $_REQUEST["id"]);

        // Gets the result of the query execution
        $itemQueryResult = mysqli_stmt_execute($itemQuery);

        // Checking if the query was successful
        if (!$itemQueryResult) {

            // Gets the error that happened in the prepared statement and outputs the value in htmlspecialchars
            $error = mysqli_stmt_error($itemQuery);

            echo "<div class='error-div'>
                    <strong class='list' >Ocorreu um erro na consulta:" . htmlspecialchars($error) . "</strong>
                  </div>";

            voltar_atras();
        } else {
            $itemQueryResult = mysqli_stmt_get_result($itemQuery);
            $itemData = mysqli_fetch_assoc($itemQueryResult);

            // Fetching all item_type id for a dropdown box so that we can choose the id more easily without needing mental mapping of the database
            $itemTypeQuery = "SELECT id as typeId FROM item_type";
            $itemTypeQueryResult = mysqli_query($link, $itemTypeQuery);

            if (!$itemTypeQueryResult) {

                echo "<div class='error-div'>
                        <strong class='list' >Ocorreu um erro na consulta:" . mysqli_error($link) . "</strong>
                      </div>";

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
                <input type='hidden' name='itemId' value='{$_REQUEST["id"]}'>
                <p>Clique em <strong>Submeter</strong> para atualizar os dados</p>
                <hr>
                <div class='button-container'>
                    <button class='button-33' type='submit'>Submeter</button></form>
                    " . goBackToOriginalPage("gestao-de-itens") . "
                </div>";

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
            $invalidFields .= "<li class='list'>Estado do item é invalido</li>";
        }

        // Checks whether the item id received is valid or not
        if (empty($_REQUEST["id"]) || !is_numeric($_REQUEST["id"]) || $_REQUEST["id"] != $_REQUEST["itemId"]) {
            $validForm = false;
            $invalidFields .= "<li class='list'>O id do Item é invalido</li>";
        }

        // Checks if there were any errors in the server side verification
        if (!$validForm) {
            echo "<div class='error-div'>$invalidFields</div><hr>";
            voltar_atras();
        } // if there were no problems update the database
        else {
            if (!$_SESSION["itemUpdated"] && mysqli_begin_transaction($link)) {
                // Using prepared statements here so to protect against sql injections if the values were properly sanitized

                // Updating the correct value on the tables using prepared statements
                $updateItemQuery = mysqli_prepare($link, "UPDATE item SET state='active' WHERE item.id = ? ");
                mysqli_stmt_bind_param($updateItemQuery, "s", $_REQUEST["id"]);

                // Gets the result of the query execution
                $updateItemResult = mysqli_stmt_execute($updateItemQuery);

                // checking whether the query was successful or not
                if (!$updateItemResult) {
                    // Gets the error that happened in the prepared statement and outputs the value in htmlspecialchars
                    $error = mysqli_stmt_error($updateItemQuery);

                    mysqli_rollback($link);

                    echo "<div class='error-div'>
                           <strong class='list' >Ocorreu um erro na Atualização de dados: " . htmlspecialchars($error) . "</strong>
                          </div>";

                    voltar_atras();
                } else {

                    echo "<div class='contorno'>
                            <p class='success'>Atualização realizada com sucesso</p>
                          </div>
                          <p>Clique em continuar para voltar a pagina de gestao de itens</p>
                          <hr><a href='" . get_site_url() . "/gestao-de-itens'><button class='button-33'>Continuar</button></a>";

                    // Commit the transaction
                    mysqli_commit($link);
                    $_SESSION["itemUpdated"] = true;
                }
            }// Checking if the item was already updated
            else if ($_SESSION["itemUpdated"]) {
                echo "<div class='error-div'>
                         <b class='list'>Os dados ja foram atualizados</b>
                      </div>
                    <a href='" . get_site_url() . "/gestao-de-itens'><button class='button-33'>Continuar</button></a>";
            } else {

                echo "<div class='error-div'>
                        <strong class='list' >Ocorreu um erro no começo da Atualização de dados: " . mysqli_error($link) . "</strong>
                      </div>";

                voltar_atras();
            }
        }
    } else {
        echo "<strong>Pretende ativar o item?</strong>
          <table class='content-table'>
          <thead>
            <tr>
                <th>id</th>
                <th>name</th>
                <th>item_type_id</th>
                <th>state</th>
            </tr>
          </thead>";

        // Fetching the id, name, item_type, state for the item requested using prepared statements to prevent sql injection
        $itemQuery = mysqli_prepare($link, "SELECT item.name AS itemName,item.item_type_id AS typeId , item.state FROM item WHERE item.id = ? ");
        mysqli_stmt_bind_param($itemQuery, "s", $_REQUEST["id"]);

        // Gets the result of the query execution
        $itemQueryResult = mysqli_stmt_execute($itemQuery);

        if (!$itemQueryResult) {

            // Gets the error that happened in the prepared statement and outputs the value in htmlspecialchars
            $error = mysqli_stmt_error($itemQuery);

            echo "<div class='error-div'>
                    <strong class='list' >Ocorreu um erro na consulta:" . htmlspecialchars($error) . "</strong>
                  </div>";

            voltar_atras();
        } else {

            $itemQueryResult = mysqli_stmt_get_result($itemQuery);
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
            <input type='hidden' name='itemId' value='{$_REQUEST["id"]}'>
            <p>Clique em <strong>Submeter</strong> para atualizar os dados</p>
            <hr>
            <div class='button-container'>
                <button class='button-33' type='submit'>Submeter</button>
                " . goBackToOriginalPage("gestao-de-itens") . "
            </div></form>";

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
            $invalidFields .= "<li class='list'>Estado do item é invalido</li>";
        }

        // Checks whether the item id received is valid or not
        if (empty($_REQUEST["id"]) || !is_numeric($_REQUEST["id"]) || $_REQUEST["id"] != $_REQUEST["itemId"]) {
            $validForm = false;
            $invalidFields .= "<li class='list'>O id do Item é invalido</li>";
        }

        // Checks if there were any errors in the server side verification
        if (!$validForm) {
            echo "<div class='error-div'>$invalidFields</div><hr>";
            voltar_atras();
        } // if there were no problems update the database
        else {
            if (!$_SESSION["itemUpdated"] && mysqli_begin_transaction($link)) {
                // Using prepared statements here so to protect against sql injections if the values were properly sanitized

                // Updating the correct value on the tables using prepared statements
                $updateItemQuery = mysqli_prepare($link, "UPDATE item SET state='inactive' WHERE item.id = ? ");
                mysqli_stmt_bind_param($updateItemQuery, "s", $_REQUEST["id"]);

                // Gets the result of the query execution
                $updateItemResult = mysqli_stmt_execute($updateItemQuery);

                // checking whether the query was successful or not
                if (!$updateItemResult) {
                    // Gets the error that happened in the prepared statement and outputs the value in htmlspecialchars
                    $error = mysqli_stmt_error($updateItemQuery);

                    mysqli_rollback($link);

                    echo "<div class='error-div'>
                           <strong class='list' >Ocorreu um erro na Atualização de dados: " . htmlspecialchars($error) . "</strong>
                          </div>";

                    voltar_atras();
                } else {

                    echo "<div class='contorno'>
                            <p class='success'>Atualização realizada com sucesso</p>
                          </div>
                          <p>Clique em continuar para voltar a pagina de gestao de itens</p>
                          <hr><a href='" . get_site_url() . "/gestao-de-itens'><button class='button-33'>Continuar</button></a>";

                    // Commit the transaction
                    mysqli_commit($link);
                    $_SESSION["itemUpdated"] = true;
                }
            }// Checking if the item was already updated
            else if ($_SESSION["itemUpdated"]) {
                echo "<div class='error-div'>
                         <b class='list'>Os dados ja foram atualizados</b>
                      </div>
                    <a href='" . get_site_url() . "/gestao-de-itens'><button class='button-33'>Continuar</button></a>";
            } else {

                echo "<div class='error-div'>
                        <strong class='list' >Ocorreu um erro no começo da Atualização de dados: " . mysqli_error($link) . "</strong>
                      </div>";

                voltar_atras();
            }
        }
    } else {

        echo "<strong>Pretende desativar o item?</strong>
          <table class='content-table'>
          <thead>
            <tr>
                <th>id</th>
                <th>name</th>
                <th>item_type_id</th>
                <th>state</th>
            </tr>
          </thead>";

        // Fetching the id, name, item_type, state for the item requested using prepared statements to prevent sql injection
        $itemQuery = mysqli_prepare($link, "SELECT item.name AS itemName,item.item_type_id AS typeId , item.state FROM item WHERE item.id = ? ");
        mysqli_stmt_bind_param($itemQuery, "s", $_REQUEST["id"]);

        // Gets the result of the query execution
        $itemQueryResult = mysqli_stmt_execute($itemQuery);

        // Checks whether the query was successful or not
        if (!$itemQueryResult) {
            // Gets the error that happened in the prepared statement and outputs the value in htmlspecialchars
            $error = mysqli_stmt_error($itemQuery);

            mysqli_rollback($link);

            echo "<div class='error-div'>
                    <strong class='list' >Ocorreu um erro na consulta:" . htmlspecialchars($error) . "</strong>
                  </div>";

            voltar_atras();
        } else {
            $itemQueryResult = mysqli_stmt_get_result($itemQuery);
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
            <input type='hidden' name='itemId' value='{$_REQUEST["id"]}'>
            <p>Clique em <strong>Submeter</strong> para atualizar os dados</p>
            <hr>
            <div class='button-container'>
                <button class='button-33' type='submit'>Submeter</button></form>
                " . goBackToOriginalPage("gestao-de-itens") . "
            </div>";

            $_SESSION["itemUpdated"] = false;

        }
    }
} else if (arrayKeysExists(["estado", "tipo", "id"], $_REQUEST) && checkKeysValues(["estado", "tipo"], $_REQUEST, ["apagar", "item"])) {
    if (array_key_exists("updateState", $_REQUEST) && $_REQUEST["updateState"] == "deleting") {
        // Server-side verifications, can be tested with postman
        $validForm = true;
        $invalidFields = "";

        // Checks whether the item id received is valid or not
        if (empty($_REQUEST["id"]) || !is_numeric($_REQUEST["id"]) || $_REQUEST["id"] != $_REQUEST["itemId"]) {
            $validForm = false;
            $invalidFields .= "<li class='list'>O id do Item é invalido</li>";
        }

        // Checks if there were any errors in the server side verification
        if (!$validForm) {
            echo "<div class='error-div'>$invalidFields</div><hr>";
            voltar_atras();
        } // if there were no problems update the database
        else {
            if (!$_SESSION["itemUpdated"] && mysqli_begin_transaction($link)) {
                // Using prepared statements here so to protect against sql injections if the values were properly sanitized


				// Deleting all subitem_allowed_values attached to the subitems attached to the deleting item
	            $deleteSubItemAllowedValuesQuery = mysqli_prepare($link,"DELETE FROM subitem_allowed_value WHERE subitem_id IN (SELECT id FROM subitem WHERE item_id = ?)");
				mysqli_stmt_bind_param($deleteSubItemAllowedValuesQuery,"s", $_REQUEST["id"]);

				// Deleting all the values attached to the subitem that's attached to the deleting item
	            $deleteValuesQuery = mysqli_prepare($link,"DELETE FROM value WHERE subitem_id IN (SELECT id FROM subitem WHERE item_id = ?)");
				mysqli_stmt_bind_param($deleteValuesQuery,"s", $_REQUEST["id"]);

				// Deleting all the subitems attached to the deleting item
	            $deleteSubItemQuery = mysqli_prepare($link,"DELETE FROM subitem WHERE subitem.item_id = ?");
				mysqli_stmt_bind_param($deleteSubItemQuery,"s", $_REQUEST["id"]);

	            // Deleting the correct value on the tables using prepared statements
                $deleteItemQuery = mysqli_prepare($link, "DELETE FROM item WHERE item.id = ? ");
                mysqli_stmt_bind_param($deleteItemQuery, "s", $_REQUEST["id"]);

                // Gets the result of the query execution
                $deleteSubItemAllowedValueResult = mysqli_stmt_execute($deleteSubItemAllowedValuesQuery);
                $deleteValuesResult = mysqli_stmt_execute($deleteValuesQuery);
                $deleteSubItemResult = mysqli_stmt_execute($deleteSubItemQuery);
                $deleteItemResult = mysqli_stmt_execute($deleteItemQuery);

                // checking whether the query was successful or not
                if (!$deleteSubItemAllowedValueResult || !$deleteValuesResult || !$deleteSubItemResult || !$deleteItemResult) {
                    // Gets the error that happened in the prepared statement and outputs the value in htmlspecialchars
                    $error = mysqli_stmt_error($deleteItemQuery);

                    mysqli_rollback($link);

                    echo "<div class='error-div'>
                           <strong class='list' >Ocorreu um erro na Atualização de dados: " . htmlspecialchars($error) . "</strong>
                          </div>";

                    voltar_atras();
                } else {

                    echo "<div class='contorno'>
                            <p class='success'>Eliminações realizadas com sucesso</p>
                          </div>
                          <p>Clique em continuar para voltar a pagina de gestao de itens</p>
                          <hr><a href='" . get_site_url() . "/gestao-de-itens'><button class='button-33'>Continuar</button></a>";

                    // Commit the transaction
                    mysqli_commit($link);
                    $_SESSION["itemUpdated"] = true;
                }
            }// Checking if the item was already updated
            else if ($_SESSION["itemUpdated"]) {
                echo "<div class='error-div'>
                    <b class='list'>Os dados ja foram atualizados</b>
                  </div>
                    <a href='" . get_site_url() . "/gestao-de-itens'><button class='button-33'>Continuar</button></a>";
            } else {

                echo "<div class='error-div'>
                    <strong class='list' >Ocorreu um erro no começo de Atualização de dados: " . mysqli_error($link) . "</strong>
                  </div>";

                voltar_atras();
            }
        }
    } else {
        echo "<strong>Estamos prestes a apagar os dados abaixo da base de dados. Confirma que pertende apagar os mesmos?</strong>
          <table class='content-table'>
          <thead>
            <tr>
                <th>id</th>
                <th>name</th>
                <th>item_type_id</th>
                <th>state</th>
            </tr>
          </thead>";

        // Fetching the id, name, item_type, state for the item requested using prepared statements to prevent sql injection
        $itemQuery = mysqli_prepare($link, "SELECT item.name AS itemName,item.item_type_id AS typeId , item.state FROM item WHERE item.id = ? ");
        mysqli_stmt_bind_param($itemQuery, "s", $_REQUEST["id"]);

        // Gets the result of the query execution
        $itemQueryResult = mysqli_stmt_execute($itemQuery);

        if (!$itemQueryResult) {
            // Gets the error that happened in the prepared statement and outputs the value in htmlspecialchars
            $error = mysqli_stmt_error($itemQuery);

            mysqli_rollback($link);

            echo "<div class='error-div'>
                    <strong class='list' >Ocorreu um erro na consulta:" . htmlspecialchars($error) . "</strong>
                  </div>";

            voltar_atras();
        } else {
            $itemQueryResult = mysqli_stmt_get_result($itemQuery);
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
            <input type='hidden' name='itemId' value='{$_REQUEST["id"]}'>
            <p>Clique em <strong>Submeter</strong> para apagar os dados</p>
            <hr>
            <div class='button-container'>
                <button class='button-33' type='submit'>Submeter</button></form>
                " . goBackToOriginalPage("gestao-de-itens") . "
            </div>";

            $_SESSION["itemUpdated"] = false;

        }
    }
} else if (arrayKeysExists(["estado", "tipo", "id"], $_REQUEST) && checkKeysValues(["estado", "tipo"], $_REQUEST, ["editar", "valor_permitido"])) {
    // checking if the item is going to be updated or not
    if (array_key_exists("updateState", $_REQUEST) && $_REQUEST["updateState"] == "updating") {

        // Server-side verifications, can be tested with postman
        $validForm = true;
        $invalidFields = "";

        // Trim the received values, so it has no spaces in the edges
        $allowedValue = (isset($_REQUEST["allowedValue"])) ? trim($_REQUEST["allowedValue"]) : "";
        $allowedValue = htmlspecialchars($allowedValue);

        $subId = (isset($_REQUEST["subId"])) ? trim($_REQUEST["subId"]) : "";
        $subId = htmlspecialchars($subId);

        // Check itemName received is empty or just numbers
        if (empty($allowedValue) || is_numeric($allowedValue) || !containsOnlyLatinLetters($allowedValue)) {
            $validForm = false;
            $invalidFields .= "<li class='list'>Nome do valor permitido é inválid</li>";
        }
        // Checks whether the subitem id received is valid or not
        if (empty($subId) || !is_numeric($subId) || !checkFieldExistsOnDatabase($link, $_REQUEST["subId"], "subitem", "id")) {
            $validForm = false;
            $invalidFields .= "<li class='list'>Id do subitem é invalido</li>";
        }
        // Checks whether the item id received is valid or not
        if (empty($_REQUEST["allowedId"]) || !is_numeric($_REQUEST["allowedId"]) || $_REQUEST["id"] != $_REQUEST["allowedId"]) {
            $validForm = false;
            $invalidFields .= "<li class='list'>O id do subitem é invalido</li>";
        }
        // Checks if there were any errors in the server side verification
        if (!$validForm) {
            echo "<div class='error-div'>$invalidFields</div><hr>";
            voltar_atras();
        }// if there were no problems update the database
        else {
            if (!$_SESSION["allowedValueUpdated"] && mysqli_begin_transaction($link)) {
                // Using prepared statements here so to protect against sql injections if the values were properly sanitized

                // Updating the correct value on the tables using prepared statements
                $updateAllowedValueQuery = mysqli_prepare($link, "UPDATE subitem_allowed_value SET value = ?, subitem_id = ? WHERE subitem_allowed_value.id = ? ");
                mysqli_stmt_bind_param($updateAllowedValueQuery, "sss", $allowedValue, $subId, $_REQUEST["id"]);

                // Gets the result of the query execution
                $updateAllowedValueResult = mysqli_stmt_execute($updateAllowedValueQuery);

                // checking whether the query was successful or not
                if (!$updateAllowedValueResult) {
                    $error = mysqli_stmt_error($updateAllowedValueQuery);

                    mysqli_rollback($link);

                    echo "<div class='error-div'>
                           <strong class='list' >Ocorreu um erro na Atualização de dados: " . htmlspecialchars($error) . "</strong>
                          </div>";

                    voltar_atras();
                } else {

                    echo "<div class='contorno'>
                            <p class='success'>Atualizações realizadas com sucesso</p>
                          </div>
                          <p>Clique em continuar para voltar a pagina de gestao de valores permitidos</p>
                          <hr><a href='" . get_site_url() . "/gestao-de-valores-permitidos'><button class='button-33'>Continuar</button></a>";
                    // Commit the transaction
                    mysqli_commit($link);
                    $_SESSION["allowedValueUpdated"] = true;
                }
            }// Checking if the item was already updated
            elseif ($_SESSION["allowedValueUpdated"]) {
                echo "<div class='error-div'>
                         <b class='list'>Os dados ja foram atualizados</b>
                      </div>
                    <a href='" . get_site_url() . "/gestao-de-valores-permitidos'><button class='button-33'>Continuar</button></a>";
            } else {

                echo "<div class='error-div'>
                        <strong class='list' >Ocorreu um erro no começo da Atualização de dados: " . mysqli_error($link) . "</strong>
                      </div>";

                voltar_atras();
            }
        }
    } else {
        echo "<form method='post' action='" . get_permalink() . basename($_SERVER["REQUEST_URI"]) . "'>
              <table class='content-table'>";
        echo "
          <thead>
            <tr>
                <th>id</th>
                <th>subitem_id</th>
                <th>value</th>
                <th>state</th>
            </tr>
          </thead>";

        // Fetching the subitem_id, value, state for the allowed_value requested using prepared statements to prevent sql injections
        $allowedValueQuery = mysqli_prepare($link, "SELECT subitem_allowed_value.subitem_id AS subId,subitem_allowed_value.value , subitem_allowed_value.state FROM subitem_allowed_value WHERE subitem_allowed_value.id = ? ");
        mysqli_stmt_bind_param($allowedValueQuery, "s", $_REQUEST["id"]);

        $allowedValueQueryResult = mysqli_stmt_execute($allowedValueQuery);

        // Checking if the query was successful
        if (!$allowedValueQueryResult) {
            $error = mysqli_stmt_error($allowedValueQuery);

            echo "<div class='error-div'>
                    <strong class='list' >Ocorreu um erro na consulta:" . htmlspecialchars($error) . "</strong>
                  </div>";

            voltar_atras();
        } else {
            $allowedValueQueryResult = mysqli_stmt_get_result($allowedValueQuery);
            $allowedValueData = mysqli_fetch_assoc($allowedValueQueryResult);

            // Fetching all subitem ids for a dropdown box so that we can choose the id more easily without needing mental mapping of the database
            $subItemQuery = "SELECT id as subId FROM subitem ORDER BY id";
            $subItemQueryResult = mysqli_query($link, $subItemQuery);

            // Checking if the query was successful
            if (!$subItemQueryResult) {

                echo "<div class='error-div'>
                    <strong class='list' >Ocorreu um erro na consulta:" . mysqli_error($link) . "</strong>
                  </div>";

                voltar_atras();
            } else {

                // Starting to format the dropdown
                $dropdownSubItemId = "<select name='subId' id='subId'>";
                while ($row = mysqli_fetch_assoc($subItemQueryResult)) {
                    if ($row["subId"] == $allowedValueData["subId"]) {
                        $dropdownSubItemId .= "<option value='{$row["subId"]}' selected>{$row["subId"]}</option>";
                    } else {
                        $dropdownSubItemId .= "<option value='{$row["subId"]}'>{$row["subId"]}</option>";
                    }
                }
                $dropdownSubItemId .= "</select>";

                echo "
                <tbody>
                    <tr>
                        <td><strong>{$_REQUEST["id"]}</strong></td>
                        <td>$dropdownSubItemId</td>
                        <td><input type='text' name='allowedValue' id='allowedValue' value='{$allowedValueData["value"]}'></td>
                        <td><strong>{$allowedValueData["state"]}</strong></td>
                    </tr>
                </tbody>
                </table>
                <input type='hidden' name='updateState' value='updating'>
                <input type='hidden' name='allowedId' value='{$_REQUEST["id"]}'>
                <p>Clique em <strong>Submeter</strong> para atualizar os dados</p>
                <hr>
                <div class='button-container'>
                    <button class='button-33' type='submit'>Submeter</button></form>
                    " . goBackToOriginalPage("gestao-de-valores-permitidos") . "
                </div>";

                $_SESSION["allowedValueUpdated"] = false;
            }
        }
    }
} else if (arrayKeysExists(["estado", "tipo", "id"], $_REQUEST) && checkKeysValues(["estado", "tipo"], $_REQUEST, ["ativar", "valor_permitido"])) {
    if (array_key_exists("updateState", $_REQUEST) && $_REQUEST["updateState"] == "activating") {
        // Server-side verifications, can be tested with postman
        $validForm = true;
        $invalidFields = "";

        // Checks whether the item type received is valid or not
        if (empty($_REQUEST["state"]) || $_REQUEST["state"] != "active") {
            $validForm = false;
            $invalidFields .= "<li class='list'>Estado do valor permitido é invalido</li>";
        }
        // Checks whether the item id received is valid or not
        if (empty($_REQUEST["allowedId"]) || !is_numeric($_REQUEST["allowedId"]) || $_REQUEST["id"] != $_REQUEST["allowedId"]) {
            $validForm = false;
            $invalidFields .= "<li class='list'>O id do subitem é invalido</li>";
        }
        // Checks if there were any errors in the server side verification
        if (!$validForm) {
            echo "<div class='error-div'>$invalidFields</div><hr>";
            voltar_atras();
        } // if there were no problems update the database
        else {
            if (!$_SESSION["allowedValueUpdated"] && mysqli_begin_transaction($link)) {
                // Using prepared statements here so to protect against sql injections if the values were properly sanitized

                // Updating the correct value on the tables using prepared statements
                $updateAllowedValueQuery = mysqli_prepare($link, "UPDATE subitem_allowed_value SET state = 'active' WHERE subitem_allowed_value.id = ? ");
                mysqli_stmt_bind_param($updateAllowedValueQuery, "s", $_REQUEST["id"]);

                // Gets the result of the query execution
                $updateAllowedValueQueryResult = mysqli_stmt_execute($updateAllowedValueQuery);

                // checking whether the query was successful or not
                if (!$updateAllowedValueQueryResult) {
                    $error = mysqli_stmt_error($updateAllowedValueQuery);

                    mysqli_rollback($link);

                    echo "<div class='error-div'>
                           <strong class='list' >Ocorreu um erro na Atualização de dados: " . htmlspecialchars($error) . "</strong>
                          </div>";

                    voltar_atras();
                } else {

                    echo "<div class='contorno'>
                            <p class='success'>Atualização realizada com sucesso</p>
                          </div>
                          <p>Clique em continuar para voltar a pagina de gestao de valores permitidos</p>
                          <hr><a href='" . get_site_url() . "/gestao-de-valores-permitidos'><button class='button-33'>Continuar</button></a>";

                    // Commit the transaction
                    mysqli_commit($link);
                    $_SESSION["allowedValueUpdated"] = true;
                }
            }// Checking if the item was already updated
            else if ($_SESSION["allowedValueUpdated"]) {
                echo "<div class='error-div'>
                         <b class='list'>Os dados ja foram atualizados</b>
                      </div>
                    <a href='" . get_site_url() . "/gestao-de-valores-permitidos'><button class='button-33'>Continuar</button></a>";
            } else {

                echo "<div class='error-div'>
                        <strong class='list' >Ocorreu um erro no começo da Atualização de dados: " . mysqli_error($link) . "</strong>
                      </div>";

                voltar_atras();
            }
        }
    } else {
        echo "<strong>Pretende ativar o valor permitido?</strong>
          <table class='content-table'>
          <thead>
            <tr>
                <th>id</th>
                <th>subitem_id</th>
                <th>value</th>
                <th>state</th>
            </tr>
          </thead>";

        // Fetching the subitem_id, value, state for the allowed_value requested using prepared statements to prevent sql injections
        $allowedValueQuery = mysqli_prepare($link, "SELECT subitem_allowed_value.subitem_id AS subId,subitem_allowed_value.value , subitem_allowed_value.state FROM subitem_allowed_value WHERE subitem_allowed_value.id = ? ");
        mysqli_stmt_bind_param($allowedValueQuery, "s", $_REQUEST["id"]);

        $allowedValueQueryResult = mysqli_stmt_execute($allowedValueQuery);

        if (!$allowedValueQueryResult) {
            $error = mysqli_stmt_error($allowedValueQuery);

            echo "<div class='error-div'>
                    <strong class='list' >Ocorreu um erro na consulta:" . htmlspecialchars($error) . "</strong>
                  </div>";

            voltar_atras();
        } else {
            $allowedValueQueryResult = mysqli_stmt_get_result($allowedValueQuery);
            $allowedValueData = mysqli_fetch_assoc($allowedValueQueryResult);

            echo "
            <tbody>
                <tr>
                    <td><strong>{$_REQUEST["id"]}</strong></td>
                    <td><strong>{$allowedValueData["subId"]}</strong></td>
                    <td><strong>{$allowedValueData["value"]}</strong></td>
                    <td><strong>{$allowedValueData["state"]}</strong></td>
                </tr>
            </tbody>
            </table>
            <form method='post' action='" . get_permalink() . basename($_SERVER["REQUEST_URI"]) . "'>
            <input type='hidden' name='state' value='active'>
            <input type='hidden' name='updateState' value='activating'>
            <input type='hidden' name='allowedId' value='{$_REQUEST["id"]}'>
            <p>Clique em <strong>Submeter</strong> para atualizar os dados</p>
            <hr>
            <div class='button-container'>
                <button class='button-33' type='submit'>Submeter</button></form>
                " . goBackToOriginalPage("gestao-de-valores-permitidos") . "
            </div>";

            voltar_atras();

            $_SESSION["allowedValueUpdated"] = false;

        }
    }
} // Checking if the request is to deactivate the item
else if (arrayKeysExists(["estado", "tipo", "id"], $_REQUEST) && checkKeysValues(["estado", "tipo"], $_REQUEST, ["desativar", "valor_permitido"])) {
    if (array_key_exists("updateState", $_REQUEST) && $_REQUEST["updateState"] == "deactivating") {
        // Server-side verifications, can be tested with postman
        $validForm = true;
        $invalidFields = "";

        // Checks whether the item type received is valid or not
        if (empty($_REQUEST["state"]) || $_REQUEST["state"] != "inactive") {
            $validForm = false;
            $invalidFields .= "<li class='list'>Estado do valor permitido é invalido</li>";
        }

        // Checks whether the item id received is valid or not
        if (empty($_REQUEST["allowedId"]) || !is_numeric($_REQUEST["allowedId"]) || $_REQUEST["id"] != $_REQUEST["allowedId"]) {
            $validForm = false;
            $invalidFields .= "<li class='list'>O id do subitem é invalido</li>";
        }

        // Checks if there were any errors in the server side verification
        if (!$validForm) {
            echo "<div class='error-div'>$invalidFields</div><hr>";
            voltar_atras();
        } // if there were no problems update the database
        else {
            if (!$_SESSION["allowedValueUpdated"] && mysqli_begin_transaction($link)) {
                // Using prepared statements here so to protect against sql injections if the values were properly sanitized

                // Updating the correct value on the tables using prepared statements
                $updateAllowedValueQuery = mysqli_prepare($link, "UPDATE subitem_allowed_value SET state='inactive' WHERE subitem_allowed_value.id = ? ");
                mysqli_stmt_bind_param($updateAllowedValueQuery, "s", $_REQUEST["id"]);

                // Gets the result of the query execution
                $updateAllowedValueQueryResult = mysqli_stmt_execute($updateAllowedValueQuery);

                // checking whether the query was successful or not
                if (!$updateAllowedValueQuery) {
                    $error = mysqli_stmt_error($updateAllowedValueQuery);

                    mysqli_rollback($link);

                    echo "<div class='error-div'>
                           <strong class='list' >Ocorreu um erro na Atualização de dados: " . htmlspecialchars($error) . "</strong>
                          </div>";

                    voltar_atras();
                } else {

                    echo "<div class='contorno'>
                            <p class='success'>Atualização realizada com sucesso</p>
                          </div>
                          <p>Clique em continuar para voltar a pagina de gestao de itens</p>
                          <hr><a href='" . get_site_url() . "/gestao-de-valores-permitidos'><button class='button-33'>Continuar</button></a>";

                    // Commit the transaction
                    mysqli_commit($link);
                    $_SESSION["allowedValueUpdated"] = true;
                }
            }// Checking if the item was already updated
            else if ($_SESSION["allowedValueUpdated"]) {
                echo "<div class='error-div'>
                         <b class='list'>Os dados ja foram atualizados</b>
                      </div>
                    <a href='" . get_site_url() . "/gestao-de-valores-permitidos'><button class='button-33'>Continuar</button></a>";
            } else {

                echo "<div class='error-div'>
                        <strong class='list' >Ocorreu um erro no começo da Atualização de dados: " . mysqli_error($link) . "</strong>
                      </div>";

                voltar_atras();
            }
        }
    } else {

        echo "<strong>Pretende desativar o valor permitido?</strong>
              <table class='content-table'>
              <thead>
                <tr>
                    <th>id</th>
                    <th>subitem_id</th>
                    <th>value</th>
                    <th>state</th>
                </tr>
              </thead>";

        // Fetching the subitem_id, value, state for the allowed_value requested using prepared statements to prevent sql injections
        $allowedValueQuery = mysqli_prepare($link, "SELECT subitem_allowed_value.subitem_id AS subId,subitem_allowed_value.value , subitem_allowed_value.state FROM subitem_allowed_value WHERE subitem_allowed_value.id = ? ");
        mysqli_stmt_bind_param($allowedValueQuery, "s", $_REQUEST["id"]);

        $allowedValueQueryResult = mysqli_stmt_execute($allowedValueQuery);

        if (!$allowedValueQueryResult) {
            $error = mysqli_stmt_error($allowedValueQuery);

            echo "<div class='error-div'>
                    <strong class='list' >Ocorreu um erro na consulta:" . htmlspecialchars($allowedValueQuery) . "</strong>
                  </div>";

            voltar_atras();
        } else {
            $allowedValueQueryResult = mysqli_stmt_get_result($allowedValueQuery);
            $allowedValueData = mysqli_fetch_assoc($allowedValueQueryResult);

            echo "
            <tbody>
                <tr>
                    <td><strong>{$_REQUEST["id"]}</strong></td>
                    <td><strong>{$allowedValueData["subId"]}</strong></td>
                    <td><strong>{$allowedValueData["value"]}</strong></td>
                    <td><strong>{$allowedValueData["state"]}</strong></td>
                </tr>
            </tbody>
            </table>
            <form method='post' action='" . get_permalink() . basename($_SERVER["REQUEST_URI"]) . "'>
            <input type='hidden' name='state' value='inactive'>
            <input type='hidden' name='updateState' value='deactivating'>
            <input type='hidden' name='allowedId' value='{$_REQUEST["id"]}'>
            <p>Clique em <strong>Submeter</strong> para atualizar os dados</p>
            <hr>
            <div class='button-container'>
                <button class='button-33' type='submit'>Submeter</button></form>
                " . goBackToOriginalPage("gestao-de-valores-permitidos") . "
            </div>";

            $_SESSION["allowedValueUpdated"] = false;

        }
    }
} else if (arrayKeysExists(["estado", "tipo", "id"], $_REQUEST) && checkKeysValues(["estado", "tipo"], $_REQUEST, ["apagar", "valor_permitido"])) {
    if (array_key_exists("updateState", $_REQUEST) && $_REQUEST["updateState"] == "deleting") {
        // Server-side verifications, can be tested with postman
        $validForm = true;
        $invalidFields = "";

        // Checks whether the item id received is valid or not
        if (empty($_REQUEST["allowedId"]) || !is_numeric($_REQUEST["allowedId"]) || $_REQUEST["id"] != $_REQUEST["allowedId"]) {
            $validForm = false;
            $invalidFields .= "<li class='list'>O id do subitem é invalido</li>";
        }

        // Checks if there were any errors in the server side verification
        if (!$validForm) {
            echo "<div class='error-div'>$invalidFields</div><hr>";
            voltar_atras();
        } // if there were no problems update the database
        else {
            if (!$_SESSION["allowedValueUpdated"] && mysqli_begin_transaction($link)) {
                // Using prepared statements here so to protect against sql injections if the values were properly sanitized

                // Updating the correct value on the tables using prepared statements
                $deleteAllowedValueQuery = mysqli_prepare($link, "DELETE FROM subitem_allowed_value WHERE subitem_allowed_value.id = ? ");
                mysqli_stmt_bind_param($deleteAllowedValueQuery, "s", $_REQUEST["id"]);

                // Gets the result of the query execution
                $deleteAllowedValueQueryResult = mysqli_stmt_execute($deleteAllowedValueQuery);

                // checking whether the query was successful or not
                if (!$deleteAllowedValueQuery) {
                    $error = mysqli_stmt_error($deleteAllowedValueQuery);

                    mysqli_rollback($link);

                    echo "<div class='error-div'>
                           <strong class='list' >Ocorreu um erro na Atualização de dados: " . htmlspecialchars($error) . "</strong>
                          </div>";

                    voltar_atras();
                } else {

                    echo "<div class='contorno'>
                            <p class='success'>Eliminações realizadas com sucesso</p>
                          </div>
                          <p>Clique em continuar para voltar a pagina de gestao de itens</p>
                          <hr><a href='" . get_site_url() . "/gestao-de-valores-permitidos'><button class='button-33'>Continuar</button></a>";

                    // Commit the transaction
                    mysqli_commit($link);
                    $_SESSION["allowedValueUpdated"] = true;
                }
            }// Checking if the item was already updated
            else if ($_SESSION["allowedValueUpdated"]) {
                echo "<div class='error-div'>
                        <b class='list'>Os dados ja foram atualizados</b>
                      </div>
                    <a href='" . get_site_url() . "/gestao-de-valores-permitidos'><button class='button-33'>Continuar</button></a>";
            } else {

                echo "<div class='error-div'>
                        <strong class='list' >Ocorreu um erro no começo da Atualização de dados: " . mysqli_error($link) . "</strong>
                      </div>";

                voltar_atras();
            }
        }
    } else {
        echo "<strong>Estamos prestes a apagar os dados abaixo da base de dados . Confirma que pertende apagar os mesmos ?</strong>
              <table class='content-table'>
              <thead>
                <tr>
                    <th>id</th>
                    <th>subitem_id</th>
                    <th>value</th>
                    <th>state</th>
                </tr>
              </thead>";

        // Fetching the subitem_id, value, state for the allowed_value requested using prepared statements to prevent sql injections
        $allowedValueQuery = mysqli_prepare($link, "SELECT subitem_allowed_value . subitem_id as subId,subitem_allowed_value . value , subitem_allowed_value . state FROM subitem_allowed_value WHERE subitem_allowed_value.id = ? ");
        mysqli_stmt_bind_param($allowedValueQuery, "s", $_REQUEST["id"]);

        $allowedValueQueryResult = mysqli_stmt_execute($allowedValueQuery);

        if (!$allowedValueQueryResult) {
            $error = mysqli_stmt_error($allowedValueQuery);

            echo "<div class='error-div'>
                    <strong class='list' >Ocorreu um erro na consulta:" . htmlspecialchars($error) . "</strong>
                  </div>";

            voltar_atras();
        } else {
            $allowedValueQueryResult = mysqli_stmt_get_result($allowedValueQuery);
            $allowedValueData = mysqli_fetch_assoc($allowedValueQueryResult);

            echo "
            <tbody>
                <tr>
                    <td><strong >{$_REQUEST["id"]}</strong></td >
                    <td><strong >{$allowedValueData["subId"]}</strong></td>
                    <td><strong >{$allowedValueData["value"]}</strong></td>
                    <td><strong >{$allowedValueData["state"]}</strong></td>
                </tr>
            </tbody>
            </table>
            <form method = 'post' action = '" . get_permalink() . basename($_SERVER["REQUEST_URI"]) . "' >
            <input type = 'hidden' name = 'updateState' value = 'deleting' >
            <input type='hidden' name='allowedId' value='{$_REQUEST["id"]}'>
            <p> Clique em <strong>Submeter </strong> para apagar os dados </p>
            <hr>
            <div class='button-container'>
                <button class='button-33' type='submit'>Submeter</button></form>
                " . goBackToOriginalPage("gestao-de-valores-permitidos") . "
            </div>";

            $_SESSION["allowedValueUpdated"] = false;

        }
    }
}if ( arrayKeysExists( [ "estado", "tipo", "id" ], $_REQUEST ) && checkKeysValues(["estado","tipo"], $_REQUEST, ["editar","subitem"]) ) {
	// checking if the item is going to be updated or not
	if ( array_key_exists( "updateState", $_REQUEST ) && $_REQUEST["updateState"] == "updating" ) {

		// Server-side verifications, can be tested with postman
		$validForm     = true;
		$invalidFields = "";

		// Trim the received values, so it has no spaces in the edges and is not a html special char
		$subitemName = ( isset( $_REQUEST["subitemName"] ) ) ? trim( $_REQUEST["subitemName"] ) : "";
		$subitemName = htmlspecialchars( $subitemName );

		$formOrder = ( isset( $_REQUEST["formOrder"] ) ) ? trim( $_REQUEST["formOrder"] ) : "";
		$formOrder = htmlspecialchars( $formOrder );

		// Check itemName received is empty or just numbers
		if (empty($subitemName) || is_numeric($subitemName) || !containsOnlyLatinLetters($subitemName)) {
			$validForm     = false;
			$invalidFields .= "<li class='list'>O nome do subitem é invalido</li>";
		}
		// Checking Item_id is valid
		if ( empty( $_REQUEST["itemId"] ) || ! is_numeric( $_REQUEST["itemId"] ) || ! checkFieldExistsOnDatabase( $link, $_REQUEST["itemId"], "item", "id" ) ) {
			$validForm     = false;
			$invalidFields .= "<li class='list'>O id do Item é invalido</li>";
		}
		// Checking unit id is valid
		if ( empty( $_REQUEST["unitId"] ) || ! is_numeric( $_REQUEST["unitId"] ) || ! checkFieldExistsOnDatabase( $link, $_REQUEST["unitId"], "subitem_unit_type", "id" ) ) {
			$validForm     = false;
			$invalidFields .= "<li class='list'>O id da unidade é invalido</li>";
		}
		// Checks whether the item type received is valid or not
		if ( empty( $formOrder ) || ! is_numeric( $formOrder ) ) {
			$validForm     = false;
			$invalidFields .= "<li class='list'>O form_field_order é invalido</li>";
		}
		// Checking whether the mandatory value is valid
		if ( !is_numeric( $_REQUEST["mandatoryValue"]) || ($_REQUEST["mandatoryValue"] != 0 && $_REQUEST["mandatoryValue"] != 1 )) {
			$validForm     = false;
			$invalidFields .= "<li class='list'>O mandatory é invalido</li>";
		}
		// Checks whether the item id received is valid or not
		if ( empty( $_REQUEST["id"] ) || ! is_numeric( $_REQUEST["id"] ) || $_REQUEST["id"] != $_REQUEST["subitemId"] ) {
			$validForm     = false;
			$invalidFields .= "<li class='list'>O id do subItem é invalido</li>";
		}
		// Checks if there were any errors in the server side verification
		if ( ! $validForm ) {
			echo "<div class='error-div'>$invalidFields</div><hr>";
			voltar_atras();
		}// if there were no problems update the database
		else {
			if ( ! $_SESSION["subItemUpdated"] && mysqli_begin_transaction( $link ) ) {
				// Using prepared statements here so to protect against sql injections if the values were properly sanitized

				// Updating the correct value on the tables using prepared statements
				$updateSubItemQuery = mysqli_prepare( $link, "UPDATE subitem SET name = ?, item_id = ?, unit_type_id = ?, form_field_order = ?, mandatory = ? WHERE subitem.id = ?" );
				mysqli_stmt_bind_param( $updateSubItemQuery, "ssssss", $subitemName, $_REQUEST["itemId"], $_REQUEST["unitId"], $formOrder, $_REQUEST["mandatoryValue"], $_REQUEST["id"] );

				// Gets the result of the query execution
				$updateSubItemResult = mysqli_stmt_execute( $updateSubItemQuery );

				// checking whether the query was successful or not
				if ( ! $updateSubItemResult ) {

					// Gets the error that happened in the prepared statement and outputs the value in htmlspecialchars
					$error = mysqli_stmt_error( $updateSubItemQuery );

					mysqli_rollback( $link );

					echo "<div class='error-div'>
                            <strong class='list' >Ocorreu um erro na Atualização de dados: " . htmlspecialchars( $error ) . "</strong>
                          </div>";

					voltar_atras();
				} else {

					echo "<div class='contorno'>
                            <p class='success'>Atualizações realizadas com sucesso</p>
                          </div>
                          <p>Clique em continuar para voltar a pagina de gestao de itens</p>
                          <hr><a href='" . get_site_url() . "/gestao-de-subitens'><button class='button-33'>Continuar</button></a>";

					// Commits the transaction
					mysqli_commit( $link );
					$_SESSION["subItemUpdated"] = true;
				}
			}// Checking if the item was already updated
			elseif ( $_SESSION["subItemUpdated"] ) {
				echo "<div class='error-div'>
                         <b class='list'>Os dados ja foram atualizados</b>
                      </div>
                      <hr>
                      <a href='" . get_site_url() . "/gestao-de-subitens'><button class='button-33'>Continuar</button></a>";
			}// If it doesn't pass all checks it means an error occurred starting the transaction
			else {

				echo "<div class='error-div'>
                        <strong class='list' >Ocorreu um erro no começo da Atualização de dados: " . mysqli_error( $link ) . "</strong>
                      </div>";

				voltar_atras();
			}
		}
	} else {
		echo "<form method='post' action='" . get_permalink() . basename( $_SERVER["REQUEST_URI"] ) . "'>
              <table class='content-table'>";
		echo "
              <thead>
                <tr>
                    <th>id</th>
                    <th>name</th>
                    <th>item_id</th>
                    <th>value_type</th>
                    <th>form_field_name</th>
                    <th>form_field_type</th>
                    <th>unit_type_id</th>
                    <th>form_field_order</th>
                    <th>mandatory</th>
                    <th>state</th>
                </tr>
              </thead>";

		// Fetching the name, item_id, value_type, form_field_name, form_field_type, unit_type_id, form_field_order, mandatory, and state for the requested subitem using prepared statements to prevent SQL injection.
		$subItemQuery = mysqli_prepare( $link, "SELECT subitem.name, subitem.item_id, subitem.value_type, subitem.form_field_name, subitem.form_field_type, subitem.unit_type_id, subitem.form_field_order, subitem.mandatory,subitem.state FROM subitem WHERE subitem.id = ?" );
		mysqli_stmt_bind_param( $subItemQuery, "s", $_REQUEST["id"] );

		// Gets the result of the query execution
		$subItemQueryResult = mysqli_stmt_execute( $subItemQuery );

		// Checking if the query was successful
		if ( ! $subItemQueryResult ) {

			// Gets the error that happened in the prepared statement and outputs the value in htmlspecialchars
			$error = mysqli_stmt_error( $subItemQuery );

			echo "<div class='error-div'>
                    <strong class='list' >Ocorreu um erro na consulta:" . htmlspecialchars( $error ) . "</strong>
                  </div>";

			voltar_atras();
		} else {
			$subItemQueryResult = mysqli_stmt_get_result( $subItemQuery );
			$subItemData = mysqli_fetch_assoc( $subItemQueryResult );

			// Fetching all item_type id for a dropdown box so that we can choose the id more easily without needing mental mapping of the database
			$itemIdQuery = "SELECT id FROM item ORDER BY id ASC ";
			$itemIdQueryResult = mysqli_query( $link, $itemIdQuery );

			$unitIdQuery = "SELECT id, name FROM subitem_unit_type ORDER BY id ASC";
			$unitIdQueryResult = mysqli_query( $link, $unitIdQuery );


			if ( ! $itemIdQueryResult || !$unitIdQueryResult ) {

				echo "<div class='error-div'>
                        <strong class='list' >Ocorreu um erro na consulta:" . mysqli_error( $link ) . "</strong>
                      </div>";

				voltar_atras();

			} else {

				// Starting to format the dropdown
				$dropdownItemId = "<select name='itemId' >";
				while ( $row = mysqli_fetch_assoc( $itemIdQueryResult ) ) {
					if ( $row["id"] == $subItemData["item_id"] ) {
						$dropdownItemId .= "<option value='{$row["id"]}' selected>{$row["id"]}</option>";
					} else {
						$dropdownItemId .= "<option value='{$row["id"]}'>{$row["id"]}</option>";
					}
				}
				$dropdownItemId .= "</select>";

				// Starting to format the dropdown
				$dropdownUnitTypeId = "<select name='unitId'>";
				while ( $row = mysqli_fetch_assoc( $unitIdQueryResult ) ) {
					if ( $row["id"] == $subItemData["unit_type_id"] ) {
						$dropdownUnitTypeId .= "<option value='{$row["id"]}' selected>{$row["id"]} - {$row["name"]}</option>";
					} else {
						$dropdownUnitTypeId .= "<option value='{$row["id"]}'>{$row["id"]} - {$row["name"]}</option>";
					}
				}
				$dropdownUnitTypeId .= "</select>";

				// Starting the Mandatory dropdown
				$dropdownMandatory = "<select name='mandatoryValue' >";
				// Selecting the option depending on the current mandatory value of the subitem
				( $subItemData["mandatory"] == 0 ) ? $dropdownMandatory .= "<option value='0' selected>Não</option>" : $dropdownMandatory .= "<option value='0'>Não</option>";
				( $subItemData["mandatory"] == 1 ) ? $dropdownMandatory .= "<option value='1' selected>Sim</option>" : $dropdownMandatory .= "<option value='1'>Sim</option>";

				echo "
                <tbody>
                    <tr>
                        <td><strong>{$_REQUEST["id"]}</strong></td>
                        <td><input type='text' name='subitemName' value='{$subItemData["name"]}'></td>
                        <td>$dropdownItemId</td>
                        <td><strong>{$subItemData["value_type"]}</strong></td>
                        <td><strong>{$subItemData["form_field_name"]}</strong></td>
                        <td><strong>{$subItemData["form_field_type"]}</strong></td>
                        <td>$dropdownUnitTypeId</td>
                        <td><input type='text' name='formOrder' value='{$subItemData["form_field_order"]}'></td>
                    	<td>$dropdownMandatory</td>
                    	<td>{$subItemData["state"]}</td>
                    </tr>
                </tbody>
                </table>
                <input type='hidden' name='updateState' value='updating'>
                <input type='hidden' name='subitemId' value='{$_REQUEST["id"]}'>
                <p>Clique em <strong>Submeter</strong> para atualizar os dados</p>
                <hr>
                <div class='button-container'>
                    <button class='button-33' type='submit'>Submeter</button></form>
                    " . goBackToOriginalPage( "gestao-de-subitens" ) . "
                </div>";

				$_SESSION["subItemUpdated"] = false;
			}
		}
	}
}else if (arrayKeysExists(["estado", "tipo", "id"], $_REQUEST) && checkKeysValues(["estado", "tipo"], $_REQUEST, ["ativar", "subitem"])) {
	if (array_key_exists("updateState", $_REQUEST) && $_REQUEST["updateState"] == "activating") {
		// Server-side verifications, can be tested with postman
		$validForm = true;
		$invalidFields = "";

		// Checks whether the subitem type received is valid or not
		if (empty($_REQUEST["state"]) || $_REQUEST["state"] != "active") {
			$validForm = false;
			$invalidFields .= "<li class='list'>Estado do subitem é invalido</li>";
		}

		// Checks whether the subitem id received is valid or not
		if (empty($_REQUEST["id"]) || !is_numeric($_REQUEST["id"]) || $_REQUEST["id"] != $_REQUEST["subitemId"]) {
			$validForm = false;
			$invalidFields .= "<li class='list'>O id do subitem é invalido</li>";
		}

		// Checks if there were any errors in the server side verification
		if (!$validForm) {
			echo "<div class='error-div'>$invalidFields</div><hr>";
			voltar_atras();
		} // if there were no problems update the database
		else {
			if (!$_SESSION["subItemUpdated"] && mysqli_begin_transaction($link)) {
				// Using prepared statements here so to protect against sql injections if the values were properly sanitized

				// Updating the correct value on the tables using prepared statements
				$updateSubItemQuery = mysqli_prepare($link, "UPDATE subitem SET state='active' WHERE subitem.id = ? ");
				mysqli_stmt_bind_param($updateSubItemQuery, "s", $_REQUEST["id"]);

				// Gets the result of the query execution
				$updateSubItemResult = mysqli_stmt_execute($updateSubItemQuery);

				// checking whether the query was successful or not
				if (!$updateSubItemResult) {
					// Gets the error that happened in the prepared statement and outputs the value in htmlspecialchars
					$error = mysqli_stmt_error($updateSubItemQuery);

					mysqli_rollback($link);

					echo "<div class='error-div'>
                           <strong class='list' >Ocorreu um erro na Atualização de dados: " . htmlspecialchars($error) . "</strong>
                          </div>";

					voltar_atras();
				} else {

					echo "<div class='contorno'>
                            <p class='success'>Atualização realizada com sucesso</p>
                          </div>
                          <p>Clique em continuar para voltar a pagina de gestao de itens</p>
                          <hr><a href='" . get_site_url() . "/gestao-de-subitens'><button class='button-33'>Continuar</button></a>";

					// Commit the transaction
					mysqli_commit($link);
					$_SESSION["subItemUpdated"] = true;
				}
			}// Checking if the subitem was already updated
			else if ($_SESSION["subItemUpdated"]) {
				echo "<div class='error-div'>
                         <b class='list'>Os dados ja foram atualizados</b>
                      </div>
                    <a href='" . get_site_url() . "/gestao-de-subitens'><button class='button-33'>Continuar</button></a>";
			} else {

				echo "<div class='error-div'>
                        <strong class='list' >Ocorreu um erro no começo da Atualização de dados: " . mysqli_error($link) . "</strong>
                      </div>";

				voltar_atras();
			}
		}
	} else {
		echo "<strong>Pretende ativar o subitem?</strong>
          <table class='content-table'>
          <thead>
            <tr>
                <th>id</th>
                <th>name</th>
                <th>item_id</th>
                <th>value_type</th>
                <th>form_field_name</th>
                <th>form_field_type</th>
                <th>unit_type_id</th>
                <th>form_field_order</th>
                <th>mandatory</th>
                <th>state</th>
            </tr>
          </thead>";

		// Fetching the name, item_id, value_type, form_field_name, form_field_type, unit_type_id, form_field_order, mandatory, and state for the requested subitem using prepared statements to prevent SQL injection.
		$subItemQuery = mysqli_prepare($link, "SELECT subitem.name, subitem.item_id, subitem.value_type, subitem.form_field_name, subitem.form_field_type, subitem.unit_type_id, subitem.form_field_order, subitem.mandatory,subitem.state FROM subitem WHERE subitem.id = ?");
		mysqli_stmt_bind_param($subItemQuery, "s", $_REQUEST["id"]);

		// Gets the result of the query execution
		$subItemQueryResult = mysqli_stmt_execute($subItemQuery);

		if (!$subItemQueryResult) {

			// Gets the error that happened in the prepared statement and outputs the value in htmlspecialchars
			$error = mysqli_stmt_error($subItemQuery);

			echo "<div class='error-div'>
                    <strong class='list' >Ocorreu um erro na consulta:" . htmlspecialchars($error) . "</strong>
                  </div>";

			voltar_atras();
		} else {

			$subItemQueryResult = mysqli_stmt_get_result($subItemQuery);
			$subItemData = mysqli_fetch_assoc($subItemQueryResult);

			echo "
            <tbody>
                <tr>
                    <td><strong>{$_REQUEST["id"]}</strong></td>
                    <td><strong>{$subItemData["name"]}</strong></td>
                    <td><strong>{$subItemData["item_id"]}</strong></td>
                    <td><strong>{$subItemData["value_type"]}</strong></td>
                    <td><strong>{$subItemData["form_field_name"]}</strong></td>
                    <td><strong>{$subItemData["form_field_type"]}</strong></td>
                    <td><strong>{$subItemData["unit_type_id"]}</strong></td>
                    <td><strong>{$subItemData["form_field_order"]}</strong></td>
                    <td><strong>{$subItemData["mandatory"]}</strong></td>
                    <td><strong>{$subItemData["state"]}</strong></td>
                </tr>
            </tbody>
            </table>
            <form method='post' action='" . get_permalink() . basename($_SERVER["REQUEST_URI"]) . "'>
            <input type='hidden' name='state' value='active'>
            <input type='hidden' name='updateState' value='activating'>
            <input type='hidden' name='subitemId' value='{$_REQUEST["id"]}'>
            <p>Clique em <strong>Submeter</strong> para atualizar os dados</p>
            <hr>
            <div class='button-container'>
                <button class='button-33' type='submit'>Submeter</button>
                " . goBackToOriginalPage("gestao-de-subitens") . "
            </div></form>";

			$_SESSION["subItemUpdated"] = false;

		}
	}
} // Checking if the request is to deactivate the item
else if (arrayKeysExists(["estado", "tipo", "id"], $_REQUEST) && checkKeysValues(["estado", "tipo"], $_REQUEST, ["desativar", "subitem"])) {
	if (array_key_exists("updateState", $_REQUEST) && $_REQUEST["updateState"] == "deactivating") {
		// Server-side verifications, can be tested with postman
		$validForm = true;
		$invalidFields = "";

		// Checks whether the subitem state received is valid or not
		if (empty($_REQUEST["state"]) || $_REQUEST["state"] != "inactive") {
			$validForm = false;
			$invalidFields .= "<li class='list'>Estado do item é invalido</li>";
		}

		// Checks whether the subitem id received is valid or not
		if (empty($_REQUEST["id"]) || !is_numeric($_REQUEST["id"]) || $_REQUEST["id"] != $_REQUEST["subitemId"]) {
			$validForm = false;
			$invalidFields .= "<li class='list'>O id do Item é invalido</li>";
		}

		// Checks if there were any errors in the server side verification
		if (!$validForm) {
			echo "<div class='error-div'>$invalidFields</div><hr>";
			voltar_atras();
		} // if there were no problems update the database
		else {
			if (!$_SESSION["subItemUpdated"] && mysqli_begin_transaction($link)) {
				// Using prepared statements here so to protect against sql injections if the values were properly sanitized

				// Updating the correct value on the tables using prepared statements
				$updateSubItemQuery = mysqli_prepare($link, "UPDATE subitem SET state='inactive' WHERE subitem.id = ? ");
				mysqli_stmt_bind_param($updateSubItemQuery, "s", $_REQUEST["id"]);

				// Gets the result of the query execution
				$updateSubItemResult = mysqli_stmt_execute($updateSubItemQuery);

				// checking whether the query was successful or not
				if (!$updateSubItemResult) {
					// Gets the error that happened in the prepared statement and outputs the value in htmlspecialchars
					$error = mysqli_stmt_error($updateSubItemQuery);

					mysqli_rollback($link);

					echo "<div class='error-div'>
                           <strong class='list' >Ocorreu um erro na Atualização de dados: " . htmlspecialchars($error) . "</strong>
                          </div>";

					voltar_atras();
				} else {

					echo "<div class='contorno'>
                            <p class='success'>Atualização realizada com sucesso</p>
                          </div>
                          <p>Clique em continuar para voltar a pagina de gestao de itens</p>
                          <hr><a href='" . get_site_url() . "/gestao-de-subitens'><button class='button-33'>Continuar</button></a>";

					// Commit the transaction
					mysqli_commit($link);
					$_SESSION["subItemUpdated"] = true;
				}
			}// Checking if the subitem was already updated
			else if ($_SESSION["subItemUpdated"]) {
				echo "<div class='error-div'>
                         <b class='list'>Os dados ja foram atualizados</b>
                      </div>
                    <a href='" . get_site_url() . "/gestao-de-subitens'><button class='button-33'>Continuar</button></a>";
			} else {

				echo "<div class='error-div'>
                        <strong class='list' >Ocorreu um erro no começo da Atualização de dados: " . mysqli_error($link) . "</strong>
                      </div>";

				voltar_atras();
			}
		}
	} else {

		echo "<strong>Pretende desativar o subitem?</strong>
          <table class='content-table'>
          <thead>
            <tr>
                <th>id</th>
                <th>name</th>
                <th>item_id</th>
                <th>value_type</th>
                <th>form_field_name</th>
                <th>form_field_type</th>
                <th>unit_type_id</th>
                <th>form_field_order</th>
                <th>mandatory</th>
                <th>state</th>
            </tr>
          </thead>";

		// Fetching the name, item_id, value_type, form_field_name, form_field_type, unit_type_id, form_field_order, mandatory, and state for the requested subitem using prepared statements to prevent SQL injection.
		$subItemQuery = mysqli_prepare($link, "SELECT subitem.name, subitem.item_id, subitem.value_type, subitem.form_field_name, subitem.form_field_type, subitem.unit_type_id, subitem.form_field_order, subitem.mandatory,subitem.state FROM subitem WHERE subitem.id = ?");
		mysqli_stmt_bind_param($subItemQuery, "s", $_REQUEST["id"]);

		// Gets the result of the query execution
		$subItemQueryResult = mysqli_stmt_execute($subItemQuery);

		// Checks whether the query was successful or not
		if (!$subItemQueryResult) {
			// Gets the error that happened in the prepared statement and outputs the value in htmlspecialchars
			$error = mysqli_stmt_error($subItemQuery);

			mysqli_rollback($link);

			echo "<div class='error-div'>
                    <strong class='list' >Ocorreu um erro na consulta:" . htmlspecialchars($error) . "</strong>
                  </div>";

			voltar_atras();
		} else {
			$subItemQueryResult = mysqli_stmt_get_result($subItemQuery);
			$subItemData = mysqli_fetch_assoc($subItemQueryResult);

			echo "
            <tbody>
                <tr>
                    <td><strong>{$_REQUEST["id"]}</strong></td>
                    <td><strong>{$subItemData["name"]}</strong></td>
                    <td><strong>{$subItemData["item_id"]}</strong></td>
                    <td><strong>{$subItemData["value_type"]}</strong></td>
                    <td><strong>{$subItemData["form_field_name"]}</strong></td>
                    <td><strong>{$subItemData["form_field_type"]}</strong></td>
                    <td><strong>{$subItemData["unit_type_id"]}</strong></td>
                    <td><strong>{$subItemData["form_field_order"]}</strong></td>
                    <td><strong>{$subItemData["mandatory"]}</strong></td>
                    <td><strong>{$subItemData["state"]}</strong></td>
                </tr>
            </tbody>
            </table>
            <form method='post' action='" . get_permalink() . basename($_SERVER["REQUEST_URI"]) . "'>
            <input type='hidden' name='state' value='inactive'>
            <input type='hidden' name='updateState' value='deactivating'>
            <input type='hidden' name='subitemId' value='{$_REQUEST["id"]}'>
            <p>Clique em <strong>Submeter</strong> para atualizar os dados</p>
            <hr>
            <div class='button-container'>
                <button class='button-33' type='submit'>Submeter</button></form>
                " . goBackToOriginalPage("gestao-de-subitens") . "
            </div>";

			$_SESSION["subItemUpdated"] = false;

		}
	}
} else if (arrayKeysExists(["estado", "tipo", "id"], $_REQUEST) && checkKeysValues(["estado", "tipo"], $_REQUEST, ["apagar", "subitem"])) {
	if (array_key_exists("updateState", $_REQUEST) && $_REQUEST["updateState"] == "deleting") {
		// Server-side verifications, can be tested with postman
		$validForm = true;
		$invalidFields = "";

		// Checks whether the subitem id received is valid or not
		if (empty($_REQUEST["id"]) || !is_numeric($_REQUEST["id"]) || $_REQUEST["id"] != $_REQUEST["subitemId"]) {
			$validForm = false;
			$invalidFields .= "<li class='list'>O id do Item é invalido</li>";
		}
		// Checks if there were any errors in the server side verification
		if (!$validForm) {
			echo "<div class='error-div'>$invalidFields</div><hr>";
			voltar_atras();
		} // if there were no problems update the database
		else {
			if (!$_SESSION["subItemUpdated"] && mysqli_begin_transaction($link)) {
				// Using prepared statements here so to protect against sql injections if the values were properly sanitized


				// Deleting all subitem_allowed_values attached to the subitems attached to the deleting item
				$deleteSubItemAllowedValuesQuery = mysqli_prepare($link,"DELETE FROM subitem_allowed_value WHERE subitem_id = ?");
				mysqli_stmt_bind_param($deleteSubItemAllowedValuesQuery,"s", $_REQUEST["id"]);

				// Deleting all the values attached to the subitem that's attached to the deleting item
				$deleteValuesQuery = mysqli_prepare($link,"DELETE FROM value WHERE subitem_id = ?");
				mysqli_stmt_bind_param($deleteValuesQuery,"s", $_REQUEST["id"]);

				// Deleting all the subitems attached to the deleting item
				$deleteSubItemQuery = mysqli_prepare($link,"DELETE FROM subitem WHERE id = ?");
				mysqli_stmt_bind_param($deleteSubItemQuery,"s", $_REQUEST["id"]);

				// Gets the result of the query execution
				$deleteSubItemAllowedValuesResult = mysqli_stmt_execute($deleteSubItemAllowedValuesQuery);
				$deleteValuesResult = mysqli_stmt_execute($deleteValuesQuery);
				$deleteSubItemResult = mysqli_stmt_execute($deleteSubItemQuery);

				// checking whether the query was successful or not
				if (!$deleteSubItemAllowedValuesResult || !$deleteValuesResult || !$deleteSubItemResult) {
					// Gets the error that happened in the prepared statement and outputs the value in htmlspecialchars

					$error =  mysqli_stmt_error($deleteSubItemAllowedValuesQuery);
					$listErrors ="<li class='list'>" . htmlspecialchars($error). "</li>";
					$error = mysqli_stmt_error($deleteValuesQuery);
					$listErrors .="<li class='list'>" . htmlspecialchars($error). "</li>";
					$error = mysqli_stmt_error($deleteSubItemQuery);
					$listErrors .="<li class='list'>" . htmlspecialchars($error). "</li>";

					mysqli_rollback($link);

					echo "<div class='error-div'>
                           <strong class='list' >Ocorreu um erro na Atualização de dados: <br> $listErrors </strong>
                          </div>";

					voltar_atras();
				} else {

					echo "<div class='contorno'>
                            <p class='success'>Eliminações realizadas com sucesso</p>
                          </div>
                          <p>Clique em continuar para voltar a pagina de gestao de itens</p>
                          <hr><a href='" . get_site_url() . "/gestao-de-subitens'><button class='button-33'>Continuar</button></a>";

					// Commit the transaction
					mysqli_commit($link);
					$_SESSION["subItemUpdated"] = true;
				}
			}// Checking if the subitem was already updated
			else if ($_SESSION["subItemUpdated"]) {
				echo "<div class='error-div'>
                    <b class='list'>Os dados ja foram atualizados</b>
                  </div>
                    <a href='" . get_site_url() . "/gestao-de-subitens'><button class='button-33'>Continuar</button></a>";
			} else {

				echo "<div class='error-div'>
                    <strong class='list' >Ocorreu um erro no começo de Atualização de dados: " . mysqli_error($link) . "</strong>
                  </div>";

				voltar_atras();
			}
		}
	} else {
		echo "<strong>Estamos prestes a apagar os dados abaixo da base de dados. Confirma que pertende apagar os mesmos?</strong>
          <table class='content-table'>
          <thead>
            <tr>
                <th>id</th>
                <th>name</th>
                <th>item_id</th>
                <th>value_type</th>
                <th>form_field_name</th>
                <th>form_field_type</th>
                <th>unit_type_id</th>
                <th>form_field_order</th>
                <th>mandatory</th>
                <th>state</th>
            </tr>
          </thead>";

		// Fetching the name, item_id, value_type, form_field_name, form_field_type, unit_type_id, form_field_order, mandatory, and state for the requested subitem using prepared statements to prevent SQL injection.
		$subItemQuery = mysqli_prepare($link, "SELECT subitem.name, subitem.item_id, subitem.value_type, subitem.form_field_name, subitem.form_field_type, subitem.unit_type_id, subitem.form_field_order, subitem.mandatory,subitem.state FROM subitem WHERE subitem.id = ?");
		mysqli_stmt_bind_param($subItemQuery, "s", $_REQUEST["id"]);

		// Gets the result of the query execution
		$subItemQueryResult = mysqli_stmt_execute($subItemQuery);

		if (!$subItemQueryResult) {
			// Gets the error that happened in the prepared statement and outputs the value in htmlspecialchars
			$error = mysqli_stmt_error($subItemQuery);

			mysqli_rollback($link);

			echo "<div class='error-div'>
                    <strong class='list' >Ocorreu um erro na consulta:" . htmlspecialchars($error) . "</strong>
                  </div>";

			voltar_atras();
		} else {
			$subItemQueryResult = mysqli_stmt_get_result($subItemQuery);
			$subItemData        = mysqli_fetch_assoc($subItemQueryResult);

			echo "
            <tbody>
                <tr>
                    <td><strong>{$_REQUEST["id"]}</strong></td>
                    <td><strong>{$subItemData["name"]}</strong></td>
                    <td><strong>{$subItemData["item_id"]}</strong></td>
                    <td><strong>{$subItemData["value_type"]}</strong></td>
                    <td><strong>{$subItemData["form_field_name"]}</strong></td>
                    <td><strong>{$subItemData["form_field_type"]}</strong></td>
                    <td><strong>{$subItemData["unit_type_id"]}</strong></td>
                    <td><strong>{$subItemData["form_field_order"]}</strong></td>
                    <td><strong>{$subItemData["mandatory"]}</strong></td>
                    <td><strong>{$subItemData["state"]}</strong></td>
                </tr>
            </tbody>
            </table>
            <form method='post' action='" . get_permalink() . basename($_SERVER["REQUEST_URI"]) . "'>
            <input type='hidden' name='updateState' value='deleting'>
            <input type='hidden' name='subitemId' value='{$_REQUEST["id"]}'>
            <p>Clique em <strong>Submeter</strong> para apagar os dados</p>
            <hr>
            <div class='button-container'>
                <button class='button-33' type='submit'>Submeter</button></form>
                " . goBackToOriginalPage("gestao-de-subitens") . "
            </div>";

			$_SESSION["subItemUpdated"] = false;

		}
	}
}else if ( arrayKeysExists( [ "estado", "tipo", "id" ], $_REQUEST ) && checkKeysValues( ["estado","tipo"], $_REQUEST, [ "editar", "unidade" ] ) ) {
	// checking if the item is going to be updated or not
	if ( array_key_exists( "updateState", $_REQUEST ) && $_REQUEST["updateState"] == "updating" ) {

		// Server-side verifications, can be tested with postman
		$validForm     = true;
		$invalidFields = "";

		// Pattern for the units
		$pattern = '/^[a-zA-Z0-9\s^\/]+$/';

		// Trim the received values, so it has no spaces in the edges and is not a html special char
		$unitName = ( isset( $_REQUEST["unitName"] ) ) ? trim( $_REQUEST["unitName"] ) : "";
		$unitName = htmlspecialchars( $unitName );

		// Check unitName received is empty or just numbers
		if ( empty( $unitName ) || is_numeric( $unitName ) || ! preg_match( $pattern, $unitName ) ) {
			$validForm     = false;
			$invalidFields .= "<li class='list'>O nome da unidade é invalido</li>";
		}
		// Checks whether the unit id received is valid or not
		if ( empty( $_REQUEST["id"] ) || ! is_numeric( $_REQUEST["id"] ) || $_REQUEST["id"] != $_REQUEST["unitId"] ) {
			$validForm     = false;
			$invalidFields .= "<li class='list'>O id da unidade é invalido</li>";
		}
		// Checks if there were any errors in the server side verification
		if ( ! $validForm ) {
			echo "<div class='error-div'>$invalidFields</div><hr>";
			voltar_atras();
		}// if there were no problems update the database
		else {
			if ( ! $_SESSION["unitUpdated"] && mysqli_begin_transaction( $link ) ) {
				// Using prepared statements here so to protect against sql injections if the values were properly sanitized

				// Updating the correct value on the tables using prepared statements
				$updateUnitQuery = mysqli_prepare( $link, "UPDATE subitem_unit_type SET name = ? WHERE id = ?" );
				mysqli_stmt_bind_param( $updateUnitQuery, "ss", $unitName, $_REQUEST["id"] );

				// Gets the result of the query execution
				$updateUnitResult = mysqli_stmt_execute( $updateUnitQuery );

				// Checking whether the query was successful or not
				if ( ! $updateUnitResult ) {

					// Gets the error that happened in the prepared statement and outputs the value in htmlspecialchars
					$error = mysqli_stmt_error( $updateUnitQuery );

					mysqli_rollback( $link );

					echo "<div class='error-div'>
                            <strong class='list' >Ocorreu um erro na Atualização de dados: " . htmlspecialchars( $error ) . "</strong>
                          </div>";

					voltar_atras();
				} else {

					echo "<div class='contorno'>
                            <p class='success'>Atualizações realizadas com sucesso</p>
                          </div>
                          <p>Clique em continuar para voltar a pagina de gestao de itens</p>
                          <hr><a href='" . get_site_url() . "/gestao-de-unidades'><button class='button-33'>Continuar</button></a>";

					// Commits the transaction
					mysqli_commit( $link );
					$_SESSION["unitUpdated"] = true;
				}
			}// Checking if the unit was already updated
			elseif ( $_SESSION["unitUpdated"] ) {
				echo "<div class='error-div'>
                         <b class='list'>Os dados ja foram atualizados</b>
                      </div>
                      <hr>
                      <a href='" . get_site_url() . "/gestao-de-unidades'><button class='button-33'>Continuar</button></a>";
			}// If it doesn't pass all checks it means an error occurred starting the transaction
			else {

				echo "<div class='error-div'>
                        <strong class='list' >Ocorreu um erro no começo da Atualização de dados: " . mysqli_error( $link ) . "</strong>
                      </div>";

				voltar_atras();
			}
		}
	} else {
		echo "<form method='post' action='" . get_permalink() . basename( $_SERVER["REQUEST_URI"] ) . "'>
              <table class='content-table'>";
		echo "
              <thead>
                <tr>
                    <th>id</th>
                    <th>unidade</th>
                </tr>
              </thead>";

		// Fetching the name and id for the requested unit using prepared statements to prevent SQL injection.
		$unitQuery = mysqli_prepare( $link, "SELECT id,name FROM subitem_unit_type WHERE id = ?" );
		mysqli_stmt_bind_param( $unitQuery, "s", $_REQUEST["id"] );

		// Gets the result of the query execution
		$unitResult = mysqli_stmt_execute( $unitQuery );

		// Checking if the query was successful
		if ( ! $unitResult ) {

			// Gets the error that happened in the prepared statement and outputs the value in htmlspecialchars
			$error = mysqli_stmt_error( $unitQuery );

			echo "<div class='error-div'>
                    <strong class='list' >Ocorreu um erro na consulta:" . htmlspecialchars( $error ) . "</strong>
                  </div>";

			voltar_atras();
		} else {
			$unitResult = mysqli_stmt_get_result( $unitQuery );
			$unitData   = mysqli_fetch_assoc( $unitResult );

			echo "
                <tbody>
                    <tr>
                        <td><strong>{$_REQUEST["id"]}</strong></td>
                        <td><input type='text' name='unitName' value='{$unitData["name"]}'></td>
                    </tr>
                </tbody>
                </table>
                <input type='hidden' name='updateState' value='updating'>
                <input type='hidden' name='unitId' value='{$_REQUEST["id"]}'>
                <p>Clique em <strong>Submeter</strong> para atualizar os dados</p>
                <hr>
                <div class='button-container'>
                    <button class='button-33' type='submit'>Submeter</button></form>
                    " . goBackToOriginalPage( "gestao-de-unidades" ) . "
                </div>";

			$_SESSION["unitUpdated"] = false;
		}
	}
}else if (arrayKeysExists(["estado", "tipo", "id"], $_REQUEST) && checkKeysValues(["estado", "tipo"], $_REQUEST, ["apagar", "unidade"])) {
	if (array_key_exists("updateState", $_REQUEST) && $_REQUEST["updateState"] == "deleting") {
		// Server-side verifications, can be tested with postman
		$validForm = true;
		$invalidFields = "";

		// Checks whether the unit id received is valid or not
		if (empty($_REQUEST["id"]) || !is_numeric($_REQUEST["id"]) || $_REQUEST["id"] != $_REQUEST["unitId"]) {
			$validForm = false;
			$invalidFields .= "<li class='list'>O id do Item é invalido</li>";
		}
		// Checks if there were any errors in the server side verification
		if (!$validForm) {
			echo "<div class='error-div'>$invalidFields</div><hr>";
			voltar_atras();
		} // if there were no problems update the database
		else {
			if (!$_SESSION["unitUpdated"] && mysqli_begin_transaction($link)) {
				// Using prepared statements here so to protect against sql injections if the values were properly sanitized


				// Deleting the subitem_unit_type requested
				$deleteUnitQuery = mysqli_prepare($link,"DELETE FROM subitem_unit_type WHERE id = ?");
				mysqli_stmt_bind_param($deleteUnitQuery,"s", $_REQUEST["id"]);

				$deleteUnitResult = mysqli_stmt_execute($deleteUnitQuery);

				// checking whether the query was successful or not
				if (!$deleteUnitResult) {
					// Gets the error that happened in the prepared statement and outputs the value in htmlspecialchars

					$error = mysqli_stmt_error($deleteUnitQuery);

					mysqli_rollback($link);

					echo "<div class='error-div'>
                           <strong class='list' >Ocorreu um erro na Atualização de dados: <br> $listErrors </strong>
                          </div>";

					voltar_atras();
				} else {

					echo "<div class='contorno'>
                            <p class='success'>Eliminações realizadas com sucesso</p>
                          </div>
                          <p>Clique em continuar para voltar a pagina de gestao de itens</p>
                          <hr><a href='" . get_site_url() . "/gestao-de-unidades'><button class='button-33'>Continuar</button></a>";

					// Commit the transaction
					mysqli_commit($link);
					$_SESSION["unitUpdated"] = true;
				}
			}// Checking if the unit was already updated
			else if ($_SESSION["unitUpdated"]) {
				echo "<div class='error-div'>
                    <b class='list'>Os dados ja foram atualizados</b>
                  </div>
                    <a href='" . get_site_url() . "/gestao-de-unidades'><button class='button-33'>Continuar</button></a>";
			} else {

				echo "<div class='error-div'>
                    <strong class='list' >Ocorreu um erro no começo de Atualização de dados: " . mysqli_error($link) . "</strong>
                  </div>";

				voltar_atras();
			}
		}
	} else {
		echo "<strong>Estamos prestes a apagar os dados abaixo da base de dados. Confirma que pertende apagar os mesmos?</strong>
          <table class='content-table'>
          <thead>
            <tr>
                <th>id</th>
                <th>name</th>
            </tr>
          </thead>";

		// Fetching the name and id for the requested unit using prepared statements to prevent SQL injection.
		$unitQuery = mysqli_prepare( $link, "SELECT id,name FROM subitem_unit_type WHERE id = ?" );
		mysqli_stmt_bind_param( $unitQuery, "s", $_REQUEST["id"] );

		// Gets the result of the query execution
		$unitQueryResult = mysqli_stmt_execute($unitQuery);

		if (!$unitQueryResult) {
			// Gets the error that happened in the prepared statement and outputs the value in htmlspecialchars
			$error = mysqli_stmt_error($unitQuery);

			mysqli_rollback($link);

			echo "<div class='error-div'>
                    <strong class='list' >Ocorreu um erro na consulta:" . htmlspecialchars($error) . "</strong>
                  </div>";

			voltar_atras();
		} else {
			$unitQueryResult = mysqli_stmt_get_result($unitQuery);
			$unitData = mysqli_fetch_assoc($unitQueryResult);

			echo "
            <tbody>
                <tr>
                    <td><strong>{$_REQUEST["id"]}</strong></td>
                    <td><strong>{$unitData["name"]}</strong></td>
                </tr>
            </tbody>
            </table>
            <form method='post' action='" . get_permalink() . basename($_SERVER["REQUEST_URI"]) . "'>
            <input type='hidden' name='updateState' value='deleting'>
            <input type='hidden' name='unitId' value='{$_REQUEST["id"]}'>
            <p>Clique em <strong>Submeter</strong> para apagar os dados</p>
            <hr>
            <div class='button-container'>
                <button class='button-33' type='submit'>Submeter</button></form>
                " . goBackToOriginalPage("gestao-de-unidades") . "
            </div>";

			$_SESSION["unitUpdated"] = false;

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
    // We are iterating the whole array and getting its index on $index and saving the value that's in that index on $key
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

