<?php
require_once("custom/php/common.php");

// Checks if the user accessing the page has the permissions to access its contents
if (!doesUserHavePermission("manage_allowed_values")) {

    echo "<div class='error-div'>
            <strong class='list' >Não tem autorização para aceder a esta página</strong>
          </div>";

} else {

    if (array_key_exists("estado", $_REQUEST) && $_REQUEST["estado"] == "introducao") {

        // Starting the form to be able to create or add new allowed values
        echo "<hr><h3>Gestão de valores permitidos - introdução</h3>";
        echo "<form method='post' action='{$current_page}'>";

        // The input for the new allowed value
        echo "<h5>Nome do Valor Permitido</h5>
              <input type='text' name='value' id='valueName' placeholder='Ex.: fino, ligeiro, moderado, fechadas ...'>
              <input type='hidden' name='estado' value='inserir' >
              <button type='submit'>Inserir valor permitido</button></form>
              <a href='$current_page'><button>Voltar Atrás</button></a>";

        $_SESSION["allowedValueAdded"] = false;
        $_SESSION["subitemId"] = $_REQUEST["subitem"];

    } else if (array_key_exists("estado", $_REQUEST) && $_REQUEST["estado"] == "inserir") {

        echo "<h3>Gestão de valores permitidos - inserção</h3>";

        $validForm = true;
        $invalidField = "";

        $newAllowedValued = (isset($_REQUEST["value"])) ? trim($_REQUEST["value"]) : "";
        $newAllowedValued = htmlspecialchars($newAllowedValued);

        // Check allowed value received is empty or just numbers
        if (empty($newAllowedValued) || is_numeric($newAllowedValued) || containsOnlySpecialChars($newAllowedValued)) {
            $validForm = false;
            $invalidField .= "<li class='list'>Nome do valor permitido é inválido</li>";
        }
        // Checks if there were any errors in the server side verification
        if (!$validForm) {

            echo "<div class='error-div'>$invalidField</div>";

            voltar_atras();
        } else {

            // Insert new allowed value in the correct table using prepared statements
            $insertNewAllowedValueQuery = mysqli_prepare($link, "INSERT INTO subitem_allowed_value(subitem_id, value, state) VALUES ( ?, ?, 'active' )");
            mysqli_stmt_bind_param($insertNewAllowedValueQuery, "ss", $_SESSION["subitemId"], $newAllowedValued);
            // $insertNewAllowedValueQuery = "INSERT INTO subitem_allowed_value(subitem_id, value, state) VALUES ('{$_SESSION["subitemId"]}', '$newAllowedValued', 'active' )";


            // Checks whether new allowed value was already added to the database if not start transaction
            if (!$_SESSION["allowedValueAdded"] && mysqli_begin_transaction($link)) {
                // Checks whether the insert query was successful or not if not rollbacks the transaction and shows error that happened in the transaction
                $insertNewAllowedValueResult = mysqli_stmt_execute($insertNewAllowedValueQuery);
                // $insertNewAllowedValueResult = mysqli_query($link, $insertNewAllowedValueQuery);
                if (!$insertNewAllowedValueResult) {

                    // Gets the error that happened in the prepared statement and outputs the value in htmlspecialchars
                    $error = mysqli_stmt_error($insertNewAllowedValueQuery);

                    mysqli_rollback($link);

                    echo "<div class='error-div'>
                                <strong class='list' >Ocorreu um erro na Inserção de dados: " . htmlspecialchars($error) . "</strong>
                              </div>";

                    voltar_atras();
                } else {
                    echo "<b class='success'>Inseriu os dados de novo valor permitido com sucesso.</b>
                              <table class='content-table'>
                                    <thead>
                                        <tr>
                                            <th>id</th>
                                            <th>value</th>
                                            <th>subitem_id</th>
                                            <th>state</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>" . mysqli_insert_id($link) . "</td>
                                            <td> $newAllowedValued</td>
                                            <td>{$_SESSION["subitemId"]}</td>
                                            <td>active</td>
                                        </tr> 
                                    </tbody>
                              </table>
                              <p>Clique em <strong>Continuar</strong> para avançar</p>
                              <a href='$current_page'><button>Continuar</button></a>";

                    // Commit the transaction
                    mysqli_commit($link);

                    // Session varible to check whether value has been added or not so to not have duplicates
                    $_SESSION["allowedValueAdded"] = true;

                }
            } // Checks if item was added already so to not cause duplication when refreshing the page
            else if ($_SESSION["allowedValueAdded"]) {

                echo "<div class='error-div'>
                        <strong class='list'>O valor permitido já foi inserido</strong>
                      </div>
                      <a href='$current_page'><button>Continuar</button></a>";

            } // If it didn't pass all the other checks it means an error occurred on the transaction start
            else {

                echo "<div class='error-div'>
                            <strong class='list' >Ocorreu um erro no inicio da Inserção de dados: " . mysqli_error($link) . "</strong>
                          </div>";

                voltar_atras();

            }
        }
    } else {
        // Initial State if there is no REQUEST["estado"]
        echo "<table class='content-table'>";
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
        // Query the item of the database
        $itemQuery = "SELECT item.id, item.name FROM item ORDER BY item.name";
        $itemData = mysqli_query($link, $itemQuery);
        // Checks if the query was successful
        if (!$itemData) {

            echo "<div class='error-div'>
                    <strong class='list' >Ocorreu um erro na consulta:" . mysqli_error($link) . "</strong>
                  </div>";

        } else {
            while ($item = mysqli_fetch_assoc($itemData)) {
                // Amount of subvalues of item for the rowspan its same value as the amount of allowed values
                $itemSubValuesAmount = 0;

                // Query the corresponding subitens of the item
                $subItemQuery = "SELECT subitem.id, subitem.name FROM subitem WHERE item_id='{$item["id"]}' AND subitem.value_type='enum' ORDER BY name";
                $subItemData = mysqli_query($link, $subItemQuery);

                // Checks wheter the query was successful or not
                if (!$subItemData) {

                    echo "<div class='error-div'>
                            <strong class='list' >Ocorreu um erro na consulta:" . mysqli_error($link) . "</strong>
                          </div>";

                } else {
                    $allowedValueRows = "";

                    // Check if item has subitens if not fills the rest of the columns and jumps to the next item
                    $subItemAmount = mysqli_num_rows($subItemData);
                    if ($subItemAmount == 0) {
                        // Adds +1 to $itemSubValuesAmount because it doesnt have subitens or values to count for the rowspan
                        $itemSubValuesAmount += 1;
                        $allowedValueRows .= "<tr><td rowspan='1'>{$item["name"]}</td>";
                        $allowedValueRows .= "<td colspan='6'>Não há subitems especificados cujo tipo de valor seja enum. Especificar primeiro novo(s) item(s) e depois voltar a esta opção</td></tr>";
                        echo $allowedValueRows;
                        continue;
                    }
                    while ($subItem = mysqli_fetch_assoc($subItemData)) {
                        // Href for to add a new value to the subitem
                        $hrefSubItem = $current_page . "?estado=introducao&subitem=" . $subItem["id"];

                        // Query the corresponding allowed values linked to this subitem
                        $subItemAllowedValueQuery = "SELECT id, value, state FROM subitem_allowed_value WHERE subitem_id = '{$subItem["id"]}' ORDER BY id";
                        $subItemAllowedValueData = mysqli_query($link, $subItemAllowedValueQuery);
                        if (!$subItemAllowedValueData) {

                            echo "<div class='error-div'>
                                    <strong class='list' >Ocorreu um erro na consulta:" . mysqli_error($link) . "</strong>
                                  </div>";

                        } else {
                            // Checks whether the subitem has allowed values if it doesn't fills the rest of the columns and jumps to the next subitem
                            $allowedValueAmount = mysqli_num_rows($subItemAllowedValueData);
                            if ($allowedValueAmount == 0) {
                                // Adds +1 to $itemSubValuesAmount because it doesnt have values to count so it counts only the subitem for the rowspan
                                $itemSubValuesAmount += 1;
                                $allowedValueRows .= "<td rowspan='1'>{$subItem["id"]}</td>";
                                $allowedValueRows .= "<td rowspan='1'><a href='$hrefSubItem'>[{$subItem["name"]}]</a></td>";
                                $allowedValueRows .= "<td rowspan='1' colspan='4'>Não há valores permitidos</td></tr>";
                                continue;
                            }

                            // The amount of subvalues the item is going to have is the amount of allowed values
                            $itemSubValuesAmount += $allowedValueAmount;

                            // Starting to format the subitem "row" by using the $allowedValueAmount for the rowspan
                            $allowedValueRows .= "<td rowspan='$allowedValueAmount'>{$subItem["id"]}</td>";
                            $allowedValueRows .= "<td rowspan='$allowedValueAmount'><a href='$hrefSubItem'>[{$subItem["name"]}]</a></td>";
                            while ($allowedValue = mysqli_fetch_assoc($subItemAllowedValueData)) {
                                // Starting to format the valor permitido "row"
                                $allowedValueRows .= "<td>{$allowedValue["id"]}</td>";
                                $allowedValueRows .= "<td>{$allowedValue["value"]}</td>";
                                $allowedValueRows .= "<td>{$allowedValue["state"]}</td>";

                                // Checking whether the current allowedValue state is active or inactive to have the correct action of changing state
                                $allowedValue["state"] == "active" ? $allowedValueAction = "<a class='links' href='$editDataPage?estado=desativar&tipo=valor_permitido&id={$allowedValue["id"]}'>[desativar]</a>" : $allowedValueAction = "<a class='links' href='$editDataPage?estado=ativar&tipo=valor_permitido&id={$allowedValue["id"]}'>[ativar]</a>";

                                // Formatting last column to have the all actions corresponding to the allowedValue data
                                $allowedValueRows .= "<td>
                                    <a class='links' href='$editDataPage?estado=editar&tipo=valor_permitido&id={$allowedValue["id"]}'>[editar]</a>
                                    {$allowedValueAction}
                                    <a class='links' href='$editDataPage?estado=apagar&tipo=valor_permitido&id={$allowedValue["id"]}'>[apagar]</a>
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

        // Setting the allowedValueAdded to false so that we can use it later to remove the possibility of dupes
        $_SESSION["allowedValueAdded"] = false;
    }
}
?>