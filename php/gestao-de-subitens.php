<?php
require_once("custom/php/common.php");
connect();
echo 2;
echo '<table>
    <tbody>
    <tr>
    <td>item</td>
    <td>id</td>
    <td>subitem</td>
    <td>tipo de valor</td>
    <td>nome de campo no formulátio</td>
    <td>tipo de campo no formulário</td>
    <td>tipo de unidade</td>
    <td>ordem de campo no formulário</td>
    <td>obrigatório</td>
    <td>estado</td>
    <td>ação</td>
    </tr>';
$queryItem = "SELECT item.name as itname, id From item ORDER BY name";
$resultquery = mysqli_query($link, $queryItem);
while ($lineItem = mysqli_fetch_assoc($resultquery)) {
    echo '<tr> <td>' . $lineItem['itname'] . '</tr></td>';
    $querySubItem="SELECT id as SubID, name as SubItName, value_type as SubValueType, form_field_name as SubFFN, form_field_type as SubFFT,
    unit_type_id as SubUnitT,form_field_order as SubFFO, mandatory, state From subitem";
    $resultquerySub = mysqli_query($link,$querySubItem);
    $number_of_rows = mysqli_num_rows($resultquerySub);
    echo '<tr>';
    if($number_of_rows != 0){

        echo '<td rowspan="'.$number_of_rows.'">' . $lineItem["itname"] . '</td>';
        while ($lineSubItem = mysqli_fetch_assoc($resultquerySub)) {
            $queryUnitType="SELECT id, name FROM  subitem_unit_type  WHERE id=" . $lineSubItem["SubUnitT"];
            $resultqueryUnit=mysqli_query($link,$queryUnitType);
            if(isset($lineSubItem["SubUnitT"])){
                while ($lineSubItemUnit = mysqli_fetch_assoc($resultqueryUnit)) {
                    echo '
                <td>' . $lineSubItem["SubID"] . '
                </td>
                 <td>' . $lineSubItem["SubItName"] . '
                 </td>
                 <td>' . $lineSubItem["SubValueType"] . '
                 </td>
                 <td>' . $lineSubItem["SubFFN"] . '
                 </td>
                 <td>' . $lineSubItem["SubFFT"] . '
                 </td>
                 <td>' . $lineSubItemUnit["name"] . '
                 </td>
                 <td>' . $lineSubItem["SubFFO"] . '
                 </td>
                 <td>' . $lineSubItem["mandatory"] . '
                 </td>
                 <td>' . $lineSubItem["state"] . '
                 </td>
                 <td>[editar] [desativar]
                 </td>
                 </tr>';
                }
            }else{
                echo '
                <td>' . $lineSubItem["SubID"] . '
                </td>
                 <td>' . $lineSubItem["SubItName"] . '
                 </td>
                 <td>' . $lineSubItem["SubValueType"] . '
                 </td>
                 <td>' . $lineSubItem["SubFFN"] . '
                 </td>
                 <td>    --- 
                 </td>
                 <td>' . $lineSubItem["SubFFO"] . '
                 </td>
                 <td>' . $lineSubItem["mandatory"] . '
                 </td>
                 <td>' . $lineSubItem["state"] . '
                 </td>
                 <td>[editar] [desativar]
                 </td>
                 </tr>';
            }
        }
    }else{
        echo '<td>' .$lineItem["itname"] . '</td>
             <td colspan=' . mysqli_num_fields($resultquerySub) + 1 . ' >Este item não tem subitems</td></tr>';
    }
}

echo'
    </tbody></table>'
?>