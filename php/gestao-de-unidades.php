<?php
require_once("custom/php/common.php");
connect();
echo 2;
echo '<table>
    <tbody>
    <tr>
    <td>id</td>
    <td>unidade</td>
    <td>subitem</td>
    <td>ação</td>
    </tr>';
    $query = "SELECT id as UnitID, name as UnitName From subitem_unit_type";
    $resultquery=mysqli_query($link,$query);
    $number_of_rows = mysqli_num_rows($resultquery);
    while($lineUnit=mysqli_fetch_assoc($resultquery)){
        $querySubIT="SELECT item.name as ITname, subitem.name as SubName From item, subitem WHERE item_id=item.id";
        $resultquerySUB = mysqli_query($link,$querySubIT);
        echo '<tr>
                     <td rowspan="'.$number_of_rows.'">' . $lineUnit["UnitID"] . '</td>
                     <td rowspan="'.$number_of_rows.'">' . $lineUnit["UnitName"] . '</td>';
            while($lineSubIt=mysqli_fetch_assoc($resultquerySUB)) {
                echo ',
                     <td>' . $lineSubIt["SubName"] . '(' . $lineSubIt["ITname"] . ')</td>';
                echo '<td>[editar] [desativar]
                      </td>
                      </tr>';
            
        }
    }

?>