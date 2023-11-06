<?php
require_once("custom/php/common.php");
connect();
echo 3;
echo '<table>
    <tbody>
    <tr>
    <td>id</td>
    <td>unidade</td>
    <td>subitem</td>
    <td>ação</td>
    </tr>';
$escrita=0;

$query = "SELECT id as UnitID, name as UnitName From subitem_unit_type";
$resultquery=mysqli_query($link,$query);
$number_of_rows = mysqli_num_rows($resultquery);
echo $number_of_rows;
if($number_of_rows != 0) {
    while ($lineUnit = mysqli_fetch_assoc($resultquery)) {
        $querySubIT = "SELECT item.name as ITname, subitem.name as SubName, item_id From item, subitem, subitem_unit_type WHERE item_id=item.id and subitem.unit_type_id=" . $lineUnit["UnitID"];
        $resultquerySUB = mysqli_query($link, $querySubIT);
        echo '<tr>
          <td>' . $lineUnit["UnitID"] . '</td>
          <td>' . $lineUnit["UnitName"] . '</td>';

        $items = array(); // Criar o array fora do loop para armazenar todos os itens
        $inArray = array(); // array para saber se ja foi escrito
        while ($lineSubIt = mysqli_fetch_assoc($resultquerySUB)) {
            $item = $lineSubIt["SubName"] . '(' . $lineSubIt["ITname"] . ')';
            if (!in_array($item, $inArray)) {
                $items[] = $item; // Adicione cada item ao array
                $inArray[] = $item;
            }

        }
        if (!empty($items)) {
            echo '<td>' . implode(', ', $items) . '</td>';
            echo '<td>[editar] [desativar]
                      </td>';
        } else {
            echo '<td> Não há tipos de unidades </td></tr>';
        }
    }
}

?>
