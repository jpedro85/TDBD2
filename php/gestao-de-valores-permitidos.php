<?php
require_once("custom/php/common.php");

// Checks if the user accessing the page has the permissions to access its contents
if (!doesUserHavePermission("manage_allowed_values")) {
    echo "Não tem autorização para aceder a esta página";
} else {
    if (array_key_exists("estado", $_REQUEST) && $_REQUEST["estado"] == "inserir") {
        echo "<h3>Gestão de valores permitidos - inserção</h3>";

        $validForm = true;
        $invalidField = "";

        $newAllowedValued = (isset($_REQUEST["value"])) ? trim($_REQUEST["value"]) : "";

        // Check allowed value received is empty or just numbers
        if (empty($newAllowedValued) || is_numeric($newAllowedValued)) {
            $validForm = false;
            $invalidField .= "<p>Nome do valor permitido é invalido</p>";
        }
        // Checks if there were any errors in the server side verification
        if (!$validForm) {
            echo $invalidField;
        } else {

        }
    } else {
        // Initial State if there is no REQUEST["estado"]
        echo "<table>";
        echo "<thead>
            <tr>
                <th>item</th>
                <th>id</th>
                <th>subitem</th>
                <th>id</th>
                <th>valores permitidos</th>
                <th>estado</th>
                <th>ação</th>
            </tr>
          </thead>";

        echo "<tbody>";
        // Item subitens subitem _allowed_value
        // Query the item of the database
        $itemQuery = "SELECT item.id, item.name FROM item ORDER BY item.name";
        $itemData = mysqli_query($link, $itemQuery);
        // Checks if the query was successful
        if (!$itemData) {
            echo "Ocorreu um erro na consulta:" . mysqli_error($link);
        } else {
            while ($item = mysqli_fetch_assoc($itemData)) {
                // Amount of subvalues of item for the rowspan its same value as the amount of allowed values
                $itemSubValuesAmount = 0;

                // Query the corresponding subitens of the item
                $subitemQuery = "SELECT subitem.id, subitem.name FROM subitem WHERE item_id='{$item["id"]}' AND subitem.value_type='enum' ORDER BY name";
                $subitemData = mysqli_query($link, $subitemQuery);

                // Checks wheter the query was successful or not
                if (!$subitemData) {
                    echo "Ocorreu um erro na consulta:" . mysqli_error($link);
                } else {
                    $allowedValueRows = "";

                    // Check if item has subitens if not fills the rest of the columns and jumps to the next item
                    $subitemAmount = mysqli_num_rows($subitemData);
                    if ($subitemAmount == 0) {
                        $itemSubValuesAmount += 1;
                        $allowedValueRows .= "<tr><td rowspan='1'>{$item["name"]}</td>";
                        $allowedValueRows .= "<td colspan='6'>Não há subitems especificados cujo tipo de valor seja enum. Especificar primeiro novo(s) item(s) e depois voltar a esta opção</td></tr>";
                        continue;
                    }
                    while ($subitem = mysqli_fetch_assoc($subitemData)) {
                        //
                        $hrefSubitem = $current_page ."?estado=introducao&subitem=". $subitem["id"];

                        // Query the corresponding allowed values linked to this subitem
                        $subitemAllowedValueQuery = "SELECT id, value, state FROM subitem_allowed_value WHERE subitem_id = '{$subitem["id"]}' ORDER BY id";
                        $subitemAllowedValueData = mysqli_query($link, $subitemAllowedValueQuery);
                        if (!$subitemAllowedValueData) {
                            echo "Ocorreu um erro na consulta:" . mysqli_error($link);
                        } else {
                            // Checks whether the subitem has allowed values if it doesn't fills the rest of the columns and jumps to the next subitem
                            $allowedValueAmount = mysqli_num_rows($subitemAllowedValueData);
                            if ($allowedValueAmount == 0) {
                                $itemSubValuesAmount += 1;
                                $allowedValueRows .= "<td rowspan='1'>{$subitem["id"]}</td>";
                                $allowedValueRows .= "<td rowspan='1'><a href='$hrefSubitem'>[{$subitem["name"]}]</a></td>";
                                $allowedValueRows .= "<td rowspan='1' colspan='4'>Não há valores permitidos</td></tr>";
                                continue;
                            }

                            // The amount of subvalues the item is going to have is the amount of allowed values
                            $itemSubValuesAmount += $allowedValueAmount;

                            // Starting to format the subitem "row" by using the $allowedValueAmount for the rowspan
                            $allowedValueRows .= "<td rowspan='$allowedValueAmount'>{$subitem["id"]}</td>";
                            $allowedValueRows .= "<td rowspan='$allowedValueAmount'><a href='$hrefSubitem'>[{$subitem["name"]}]</a></td>";
                            while ($allowedValue = mysqli_fetch_assoc($subitemAllowedValueData)) {
                                // Starting to format the valor permitido "row"
                                $allowedValueRows .= "<td>{$allowedValue["id"]}</td>";
                                $allowedValueRows .= "<td>{$allowedValue["value"]}</td>";
                                $allowedValueRows .= "<td>{$allowedValue["state"]}</td>";

                                // Checking whether the current allowedValue state is active or inactive to have the correct action of changing state
                                $allowedValue["state"] == "active" ? $allowedValueAction = "<a href=''>[desativar]</a>" : $allowedValueAction = "<a href=''>[ativar]</a>";

                                // Formatting last column to have the all actions corresponding to the allowedValue data
                                $allowedValueRows .= "<td>
                                    <a href=''>[editar]</a>
                                    {$allowedValueAction}
                                    <a href=''>[editar]</a>
                                </td></tr>";
                            }
                        }
                        $allowedValueRows .= "</tr>";
                    }
                    // Starting to format the subitem "row" by using the $subitemAmount value for the rowspan
                    $itemRows = "<tr> <td rowspan='{$itemSubValuesAmount}'>{$item["name"]}</td>";
                    $itemRows .= $allowedValueRows;
                    echo $itemRows;
                }
            }
        }
        // Close the table
        echo "</tbody></table>";

    }
}
?>