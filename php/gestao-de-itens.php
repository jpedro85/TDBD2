<?php
require_once("custom/php/common.php");

if (!doesUserHavePermission("manage_items")) {
    echo "Não tem autorização para aceder a esta página";
} else {
    // Intitial State if there is no POST["estado"]
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
    // Query the item_types of the databse
    $itemTypeQuery = "SELECT type.id, type.name AS typeName FROM item_type AS type ORDER BY type.name";
    $itemTypeData = mysqli_query($link, $itemTypeQuery);
    while ($itemType = mysqli_fetch_assoc($itemTypeData)) {
        // Query to fetch all the items that are linked with the current item_type
        $itemQuery = "SELECT item.id, item.name as itemName, item.state FROM item WHERE item.item_type_id = {$itemType["id"]} ORDER BY item.id ";
        $itemData = mysqli_query($link, $itemQuery);

        // Checks if the item_type actually has items linked to him
        // if not all columns after are unified and says there is no items and jumps current cycle
        $itemTypeItemsCount = mysqli_num_rows($itemData);
        if ($itemTypeItemsCount == 0) {
            echo "<td colspan='5'> Não há itens </td>";
            continue;
        }

        // Starting to format the item_type "row" by using the $itemTypeItemsCount value for the rowspan
        $itemTypeRows = "<tr> <td rowspan='{$itemTypeItemsCount}'>{$itemType["typeName"]}";
        while ($item = mysqli_fetch_assoc($itemData)) {
            $itemTypeRows .= "<td>{$item["id"]}</td>";
            $itemTypeRows .= "<td>{$item["itemName"]}</td>";
            $itemTypeRows .= "<td>{$item["state"]}</td>";
            $itemAction = "";
            // Checking whether the current item state is active or inactive to have the correct action of changing state
            $item["state"] == "active" ? $itemAction = "<a href=''>[desativar]</a>" : $itemAction = "<a href=''>[ativar]</a>";
            // Formatting last column to have the all actions corresponding to the item data
            $itemTypeRows .= "<td>
                                    <a href=''>[editar]</a>
                                    {$itemAction}
                                    <a href=''>[editar]</a>
                                </td></tr>";

        }
        // Finally sending the formatted "row" to the page
        echo $itemTypeRows;
    }
    // Close the table
    echo "</table>";
    echo "<h3>Gestao de itens - introdução</h3>";
    // Start the form to be able to create and add new items
    echo "<form method='post' action='{$current_page}'>";
    // The input for the Name of the item
    echo "<p>Nome do Item</p>
          <input type='text' name='itemName' id='itemName' placeholder='Ex.: medidas, cabelo, autismo ...'>
          <p>Tipo de item</p>";
    $itemType = null;
    foreach ($itemTypeData as $itemType){
        echo "<li><input type='radio' name='{$itemType["typeName"]}' id='typeName'>{$itemType["typeName"]}</li>";
    }
    echo "<p>Estado do Item</p>";
    $itemStateValues = get_enum_values($link,"item","state");

}


?>