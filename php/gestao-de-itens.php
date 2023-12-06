<?php
require_once("custom/php/common.php");

// Checking whether the use has the needed permissions or not
if (!doesUserHavePermission("manage_items")) {
    echo "Não tem autorização para aceder a esta página";
} else {
    if (array_key_exists("estado", $_REQUEST) && $_REQUEST["estado"] == "inserir") {
        // Show tittle for the new page state
        echo "<h3>Gestão de itens - inserção</h3>";

        // Server-side verifications can be tested with postman
        $validForm = true;
        $invalidFields = "";

        // Trim the received values, so it has no spaces in the edges and is not a html special char
        $itemName = (isset($_REQUEST["itemName"])) ? trim($_REQUEST["itemName"]) : "";
        $itemName = htmlspecialchars($itemName);

        $typeName = (isset($_REQUEST["typeName"])) ? trim($_REQUEST["typeName"]) : "";
        $typeName = htmlspecialchars($typeName);

        // Data from database to check item type
        $itemStates = get_enum_values($link, "item", "state");

        // Check itemName received is empty or just numbers
        if (empty($itemName) || is_numeric($itemName) || containsOnlySpecialChars($itemName)) {
            $validForm = false;
            $invalidFields .= "<li class='list'>Nome do item é invalido</li>";
        }
        // Checks whether the item type received is valid or not
        if (empty($typeName) || is_numeric($typeName) || !checkFieldExistsOnDatabase($link, $typeName, "item_type", "name")) {
            $validForm = false;
            $invalidFields .= "<li class='list'>Tipo de item é invalido</li>";
        }
        // Checks whether the item state received was valid or not
        if (empty($_REQUEST["state"]) || !in_array($_REQUEST["state"], $itemStates)) {
            $validForm = false;
            $invalidFields .= "<li class='list'>Estado do item é invalido</li>";
        }
        // Checks if there were any errors in the server side verification
        if (!$validForm) {
            echo "<div class='error-div'>$invalidFields</div>";
            voltar_atras();
        } else {
            // Using prepared statements here so to protect against sql injections if values were properly sanitized

            // Fetch the item type id with same name as
            $itemTypeIdQuery = mysqli_prepare($link, "SELECT id FROM item_type WHERE name = ?");
            mysqli_stmt_bind_param($itemTypeIdQuery, "s", $typeName);

            // Checks if the query was successful
            if (!mysqli_stmt_execute($itemTypeIdQuery)) {

                // Gets the error that happened in the prepared statement and outputs the value in htmlspecialchars
                $error = mysqli_stmt_error($itemTypeIdQuery);

                echo "<div class='error-div'>
                        <strong class='list' >Ocorreu um erro na Inserção de dados: " . htmlspecialchars($error) . "</strong>
                      </div>";

            } else {
                // Gets the result of the query if the statement was executed correctly
                $itemTypeIdResult = mysqli_stmt_get_result($itemTypeIdQuery);
                // Fetches the typeId from the resulting query
                $itemTypeId = mysqli_fetch_assoc($itemTypeIdResult)["id"];

                // Start transaction to insert items
                if (!$_SESSION["itemAdded"] && mysqli_begin_transaction($link)) {

                    // Insert new item in the correct table using prepared statements
                    $insertNewItemQuery = mysqli_prepare($link, "INSERT INTO item ( name, item_type_id, state) VALUES ( ?,?,? )");
                    // Replaces the ? in the query for the appropriate values
                    mysqli_stmt_bind_param($insertNewItemQuery, "sss", $itemName, $itemTypeId, $_REQUEST["state"]);

                    // Checks if the query was successful
                    if (!mysqli_stmt_execute($insertNewItemQuery)) {

                        // Gets the error that happened in the prepared statement and outputs the value in htmlspecialchars
                        $error = mysqli_stmt_error($insertNewItemQuery);

                        mysqli_rollback($link);

                        echo "<div class='error-div'>
                                <strong class='list' >Ocorreu um erro na Inserção de dados: " . htmlspecialchars($error) . "</strong>
                              </div>";

                        voltar_atras();

                    } else {
                        echo "<div class='contorno'>
                                <b class='success'>Inseriu os dados de novo item com sucesso.</b>
                              </div>
                              <table class='content-table'>
                                    <thead>
                                        <tr>
                                            <th>id</th>
                                            <th>Nome</th>
                                            <th>Item Type id</th>
                                            <th>Estado</th>
                                        </tr> 
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>" . mysqli_insert_id($link) . "</td>
                                            <td>$itemName</td>
                                            <td>{$_REQUEST["typeName"]}</td>
                                            <td>{$_REQUEST["state"]}</td>
                                        </tr> 
                                    </tbody>
                              </table>
                              <p>Clique em <strong>Continuar</strong> para avançar</p>
                              <a href='$current_page'><button class='button-33'>Continuar</button></a>";

                        // Commit the transaction
                        mysqli_commit($link);

                        $_SESSION["itemAdded"] = true;
                    }
                } // Checks if item was added already so to not cause duplication when refreshing the page
                else if ($_SESSION["itemAdded"]) {

                    echo "<div class='error-div'>
                            <strong class='list'>O item já foi inserido</strong>
                          </div>
                          <a href='$current_page'><button class='button'>Continuar</button></a>";

                } // If it didn't pass all the other checks it means an error occurred on the transaction start
                else {

                    echo "<div class='error-div'>
                            <strong class='list' >Ocorreu um erro no inicio da Inserção de dados: " . mysqli_error($link) . "</strong>
                          </div>";

                    voltar_atras();

                }
            }
        }
    } else {
        // Initial State if there is no REQUEST["estado"]
        echo "<table class='content-table'>";
        echo "<thead>
            <tr>
                <th>tipo de item</th>
                <th>id</th>
                <th>nome do item</th>
                <th>estado</th>
                <th>ação</th>
            </tr>
          </thead>";

        echo "<tbody>";
        // Query the item_types of the database
        $itemTypeQuery = "SELECT type.id, type.name AS typeName FROM item_type AS type ORDER BY type.name";
        $itemTypeData = mysqli_query($link, $itemTypeQuery);
        // Check if query was successful
        if (!$itemTypeData) {

            echo "<div class='error-div'>
                    <strong class='list' >Ocorreu um erro na consulta:" . mysqli_error($link) . "</strong>
                  </div>";

        } else {
            while ($itemType = mysqli_fetch_assoc($itemTypeData)) {
                // Query to fetch all the items that are linked with the current item_type
                $itemQuery = "SELECT item.id, item.name as itemName, item.state FROM item WHERE item.item_type_id = {$itemType["id"]} ORDER BY item.id ";
                $itemData = mysqli_query($link, $itemQuery);
                if (!$itemData) {
                    echo "<div class='error-div'>
                            <strong class='list' >Ocorreu um erro na consulta:" . mysqli_error($link) . "</strong>
                          </div>";

                } else {

                    // Checks if the item_type actually has items linked to him
                    // if not all columns after are unified and outputs that there is no items and jumps current cycle
                    $itemTypeItemsCount = mysqli_num_rows($itemData);
                    if ($itemTypeItemsCount == 0) {
                        $itemTypeRows = "<tr> <td rowspan='1'>{$itemType["typeName"]}";
                        $itemTypeRows .= "<td colspan='5'>Não há itens</td></tr>";
                        echo $itemTypeRows;
                        continue;
                    }

                    // Starting to format the item_type "row" by using the $itemTypeItemsCount value for the rowspan
                    $itemTypeRows = "<tr> <td rowspan='{$itemTypeItemsCount}'>{$itemType["typeName"]}</td>";
                    while ($item = mysqli_fetch_assoc($itemData)) {
                        $itemTypeRows .= "<td>{$item["id"]}</td>";
                        $itemTypeRows .= "<td>{$item["itemName"]}</td>";
                        $itemTypeRows .= "<td>{$item["state"]}</td>";

                        // Checking whether the current item state is active or inactive to have the correct action of changing state
                        $item["state"] == "active" ? $itemAction = "<a class='links' href='$editDataPage?estado=desativar&tipo=item&id={$item["id"]}'>[desativar]</a>" : $itemAction = "<a class='links' href='$editDataPage?estado=ativar&tipo=item&id={$item["id"]}'>[ativar]</a>";

                        // Formatting last column to have the all actions corresponding to the item data
                        $itemTypeRows .= "<td>
                                    <a class='links' href='$editDataPage?estado=editar&tipo=item&id={$item["id"]}'>[editar]</a>
                                    {$itemAction}
                                    <a class='links' href='$editDataPage?estado=apagar&tipo=item&id={$item["id"]}'>[apagar]</a>
                                </td></tr>";

                    }
                    // Finally sending the formatted "row" to the page
                    echo $itemTypeRows;
                }
            }
        }
        // Close the table
        echo "</tbody></table>";

        // Starting the form to be able to create and add new items
        echo "<hr><h3>Gestao de itens - introdução</h3>";
        echo "<form class='container' method='post' action='$current_page'>";

        // The input for the Name of the item and its type
        echo "<h5>Nome do Item</h5>
        <input type='text' name='itemName' id='itemName' placeholder='Ex.: medidas, cabelo, autismo ...'>
        <h5>Tipo de item</h5>";

        // Reusing the Data fetched before to fill the radio buttons
        $itemType = null;
        foreach ($itemTypeData as $itemType) {
            echo "
                  <label class='checkBox'>
                     <input type='radio' name='typeName' value='{$itemType["typeName"]}'>
                     <span class='checkmark'></span>{$itemType["typeName"]}
                  </label>
                  ";
        }

        // Input for the item State using the enums from the Database
        echo "<h5>Estado do Item</h5>";
        $itemStateValues = get_enum_values($link, "item", "state");
        foreach ($itemStateValues as $itemState) {
            echo "
                  <label class='checkBox'>
                     <input type='radio' name='state' id='state' value='{$itemState}'>
                     <span class='checkmark'></span>{$itemState}
                  </label>
                  ";
        }

        // Hidden input to specify which state of the page im in
        echo "<input type='hidden' name='estado' value='inserir'>
          <hr><button class='button-33' type='submit'>Inserir item</button></form>";

        // Initialize the session variable to be able to check if item was already added to DB or not
        $_SESSION["itemAdded"] = false;
    }
}

?>