<?php
require_once("custom/php/common.php");
// TODO Css for the popup saying the invalid form inputs
// TODO CSS for the error in queries
// TODO Client-Side Verifications

if (!doesUserHavePermission("manage_items")) {
    echo "Não tem autorização para aceder a esta página";
} else {
    if (array_key_exists("estado", $_REQUEST) && $_REQUEST["estado"] == "inserir") {
        // Show tittle for the new page state
        echo "<h3>Gestão de itens - inserção</h3>";

        // Server-side verifications can be tested with postman
        $validForm = true;
        $invalidFields = "";

        // Trim the received values, so it has no spaces in the edges
        $itemName = (isset($_REQUEST["itemName"])) ? trim($_REQUEST["itemName"]) : "";
        $typeName = (isset($_REQUEST["typeName"])) ? trim($_REQUEST["typeName"]) : "";

        // Data from database to check item type
        $itemStates = get_enum_values($link, "item", "state");

        // Check itemName received is empty or just numbers
        if (empty($itemName) || is_numeric($itemName)) {
            $validForm = false;
            $invalidFields .= "<p>Nome do item é invalido</p>";
        }
        // Checks whether the item type received is valid or not
        if (empty($typeName) || is_numeric($typeName) || !checkFieldExistsOnDatabase($link, $typeName, "item_type", "name")) {
            $validForm = false;
            $invalidFields .= "<p>Tipo de item é invalido</p>";
        }
        // Checks whether the item state received was valid or not
        if (empty($_REQUEST["state"]) || !in_array($_REQUEST["state"], $itemStates)) {
            $validForm = false;
            $invalidFields .= "<p>Estado do item é invalido</p>";
        }
        // Checks if there were any errors in the server side verification
        if (!$validForm) {
            echo $invalidFields;
            voltar_atras();
        } else {
            // Fetch the item type id with same name as
            $itemTypeIdQuery = "SELECT id FROM item_type WHERE name = '{$typeName}'";
            $itemTypeIdResult = mysqli_query($link, $itemTypeIdQuery);

            // Checks if the query was successful
            if (!$itemTypeIdResult) {
                echo "Ocorreu um erro na busca do item_typed id: " . mysqli_error($link);
            } else {
                $itemTypeId = mysqli_fetch_assoc($itemTypeIdResult)["id"];
                // Start transaction to insert items
                if (!$_SESSION["itemAdded"] && mysqli_begin_transaction($link)) {

                    // Insert new item in the correct table
                    $insertNewItemQuery = "INSERT INTO item ( name, item_type_id, state) VALUES ( '" . $itemName . "', '" . $itemTypeId . "', '" . $_REQUEST["state"] . "')";
                    $insertNewItemResult = mysqli_query($link, $insertNewItemQuery);

                    // Checks if the query was successful
                    if (!$insertNewItemResult) {
                        mysqli_rollback($link);
                        echo "Ocorreu um erro na Inserção de dados: " . mysqli_error($link);
                        voltar_atras();
                    } else {
                        echo "<p>Inseriu os dados de novo item com sucesso.</p>
                              <table>
                                    <tr>
                                        <th>id</th>
                                        <th>Nome</th>
                                        <th>Item Type id</th>
                                        <th>Estado</th>
                                    </tr> 
                                    <tr>
                                        <td>" . mysqli_insert_id($link) . "</td>
                                        <td>$itemName</td>
                                        <td>{$_REQUEST["typeName"]}</td>
                                        <td>{$_REQUEST["state"]}</td>
                                    </tr> 
                              </table>
                              <p>Clique em <b>Continuar</b> para avançar</p>
                              <a href='$current_page'><button href='$current_page' >Continuar</button></a>";
                        // Commit the transaction
                        mysqli_commit($link);
                        $_SESSION["itemAdded"] = true;
                    }
                } // Checks if item was added already so to not cause duplication when refreshing the page
                else if ($_SESSION["itemAdded"]) {
                    echo "O item já foi inserido";
                    voltar_atras();
                }
            }
        }


    } else {
        // Initial State if there is no REQUEST["estado"]
        echo "<table>";
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
            echo "Ocorreu um erro na consulta:" . mysqli_error($link);
        } else {
            while ($itemType = mysqli_fetch_assoc($itemTypeData)) {
                // Query to fetch all the items that are linked with the current item_type
                $itemQuery = "SELECT item.id, item.name as itemName, item.state FROM item WHERE item.item_type_id = {$itemType["id"]} ORDER BY item.id ";
                $itemData = mysqli_query($link, $itemQuery);
                if (!$itemData) {
                    echo "Ocorreu um erro na consulta:" . mysqli_error($link);
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
                        $item["state"] == "active" ? $itemAction = "<a href='$editDataPage?estado=ativar&tipo=item&id={$item["id"]}'>[desativar]</a>" : $itemAction = "<a href='$editDataPage?estado=ativar&tipo=item&id={$item["id"]}'>[ativar]</a>";

                        // Formatting last column to have the all actions corresponding to the item data
                        $itemTypeRows .= "<td>
                                    <a href='$editDataPage?estado=editar&tipo=item&id={$item["id"]}'>[editar]</a>
                                    {$itemAction}
                                    <a href='$editDataPage?estado=apagar&tipo=item&id={$item["id"]}'>[apagar]</a>
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
        echo "<form method='post' action='{$current_page}'>";

        // The input for the Name of the item and its type
        echo "<h5>Nome do Item</h5>
        <input type='text' name='itemName' id='itemName' placeholder='Ex.: medidas, cabelo, autismo ...'>
        <h5>Tipo de item</h5>";

        // Reusing the Data fetched before to fill the radio buttons
        $itemType = null;
        foreach ($itemTypeData as $itemType) {
            echo "<li><input type='radio' name='typeName' id='typeName' value='{$itemType["typeName"]}'>{$itemType["typeName"]}</li>";
        }

        // Input for the item State using the enums from the Database
        echo "<h5>Estado do Item</h5>";
        $itemStateValues = get_enum_values($link, "item", "state");
        foreach ($itemStateValues as $itemState) {
            echo "<li><input type='radio' name='state' id='state' value='{$itemState}'>{$itemState}</li>";
        }

        // Hidden input to specify which state of the page im in
        echo "<input type='hidden' name='estado' value='inserir'>
          <hr><button type='submit'>Inserir item</button>";

        // Initialize the session variable to be able to check if item was already added to DB or not
        $_SESSION["itemAdded"] = false;
    }

}

?>