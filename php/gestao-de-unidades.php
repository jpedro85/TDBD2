<?php
require_once("custom/php/common.php");
connect();
echo 3;
if(!is_user_logged_in()){
    echo 'O Utilizador não tem permissões para aceder à página';
}else{
$camposF="";
    if ($_REQUEST["estado"] == "inserir") {//caso o hidden estado esteja a inserir vai aparecer esta parte do código
        $camposF .= '<h3><b>Gestão de subitens - inserção</b></h3>
        <br>';
        if (empty($_REQUEST["UnitType"]) || is_numeric($_REQUEST["UnitType"])) {//verificaçao do subitem name
            $camposF .= '<li class="list">Falta inserir o nome do subitem, ou este é numérico</li>
            <br>';
            $erro = true;
        } else {
            $erro = false;
        }
    }
        echo '<table>
    <tbody>
    <tr>
    <td>id</td>
    <td>unidade</td>
    <td>subitem</td>
    <td>ação</td>
    </tr>';
        $escrita = 0;
        $query = "SELECT id as UnitID, name as UnitName From subitem_unit_type";
        $resultquery = mysqli_query($link, $query);
        $number_of_rows = mysqli_num_rows($resultquery);
        echo $number_of_rows;
        if ($number_of_rows != 0) {
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
                    echo '<td><a class="links" href="http://localhost/sgbd/edicao-de-dados/">[editar]</aclas><br>
                     <a class="links" href="http://localhost/sgbd/edicao-de-dados/">[desativar]</a><br>
                      </td>';
                } else {
                    echo '<td> Não há tipos de unidades </td></tr>';
                }
            }
            echo '</table>';
        }
        echo '
        <h3>
        <b>Gestão de unidades - introdução</b>
        </h3>
        <form action="' . $current_page . '" method="post">
        <label for="UnitType">Nome:(Obrigatório)</label>
        <br>
        <input type="text" id="UnitType" name="UnitType">
        <br>';
        echo '<input type="hidden" name="estado" value="inserir"><br><br>
                        <button type="submit">Inserir tipo de Unidade</form><br>';
}
    ?>

