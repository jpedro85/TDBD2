<?php
require_once("custom/php/common.php");
connect();
if(!is_user_logged_in()){
    echo 'O Utilizador não tem permissões para aceder à página';
}else {

    echo 2;
    echo '<table class="content-table">
    <tbody>
         <thead>
         <th>Item </th>
         <th>id</th>
         <th>subitem</th>
         <th>tipo de valor</th>
         <th>nome do campo no formulário</th>
         <th>tipo do campo no formulário</th>
         <th>tipo de unidade</th>
         <th>ordem do campo no formulário</th>
         <th>obrigatório</th>
         <th>estado</th>
         <th>ação</th>      
         </thead>';
    $queryItem = "SELECT item.name as itname, id From item ORDER BY name";
    $resultquery = mysqli_query($link, $queryItem);
    while ($lineItem = mysqli_fetch_assoc($resultquery)) {

        $querySubItem = "SELECT id as SubID, name as SubItName, value_type as SubValueType, form_field_name as SubFFN, form_field_type as SubFFT,
        unit_type_id as SubUnitT,form_field_order as SubFFO, mandatory, state From subitem";
        $resultquerySub = mysqli_query($link, $querySubItem);
        $number_of_rows = mysqli_num_rows($resultquerySub);
        $lista .= '<option value="' . $lineItem['id'] . '"> ' . $lineItem['itname'] . '</option>';
        if ($number_of_rows != 0) {
            echo '<tr> <td rowspan="' . $number_of_rows . '">' . $lineItem['itname'] . '</td>';
            while ($lineSubItem = mysqli_fetch_assoc($resultquerySub)) {
                $queryUnitType = "SELECT id, name FROM  subitem_unit_type  WHERE id=" . $lineSubItem["SubUnitT"];
                $resultqueryUnit = mysqli_query($link, $queryUnitType);
                if (isset($lineSubItem["SubUnitT"])) {
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
                 <td><a class="links" href="http://localhost/sgbd/edicao-de-dados/">[editar]</aclas><br>
                     <a class="links" href="http://localhost/sgbd/edicao-de-dados/">[desativar]</a><br>
                     <a class="links" href="http://localhost/sgbd/edicao-de-dados/">[apagar]</a>
                 </td>
                 </tr>';
                        $listUNIT .= '<option value="'.$lineSubItemUnit["id"].'"> ' . $lineSubItemUnit['name'] . '</option>';
                    }
                } else {
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
                 <td>    --- 
                 </td>
                 <td>' . $lineSubItem["SubFFO"] . '
                 </td>
                 <td>' . $lineSubItem["mandatory"] . '
                 </td>
                 <td>' . $lineSubItem["state"] . '
                 </td>
                 <td><a class="links" href="http://localhost/sgbd/edicao-de-dados/">[editar]</aclas><br>
                     <a class="links" href="http://localhost/sgbd/edicao-de-dados/">[desativar]</a><br>
                     <a class="links" href="http://localhost/sgbd/edicao-de-dados/">[apagar]</a>
                 </td>
                 </tr>';
                }
            }
        } else {
            echo '<td>' . $lineItem["itname"] . '</td>
             <td colspan=' . mysqli_num_fields($resultquerySub) + 1 . ' >Este item não tem subitems</td></tr>';
        }
    }

    echo '
    </tbody></table>';
    echo '
        <h2>
        <b>Gestão de SubItems -INTRODUÇÃO</b>
        </h2>
        <form action="' . $current_page . '" method="post>
        <label for="ITname">Nome do Item:</label>
        <br>
        <input type="text" id="ITname" name="ITname">
        <br>
            <p>Inserção do tipo de dados: </p>';
    $value_types = get_enum_values($link, "subitem", "value_type");
    foreach ($value_types as $v) {
        echo '<input type="radio" name="valueT" value="' . $v . '" checked >' . $v . '<br>';
    }
    //Introdução de dados por parte do utilizador
    echo '<br>';
    echo '
            <p>Escolha o Item</p>
            <select id="slctItems" name="Eitem" >  <option value="" ></option> ' . $lista . ' </select>';
    echo '<p>Inserção do tipo de campo de formulario: </p>';
    $formf_types = get_enum_values($link, "subitem", "form_field_type");
    foreach ($formf_types as $f) {
        echo '<input type="radio" name="formTYPE" value="' . $f . '">' . $f . '<br>';
    };
    echo '<p>Escolha o tipo de unidade</p>';
    echo '<select id="slctunid" name="slctunit"> ' . $listUNIT . ' </select>
                      <br>
                      <p>Insira o numero de campo de formulario</p>    
                      <input type="text" id="formCamp" name="formCamp" >
                      <br>
                      ';
    echo '<p> Mandatoriedade </p>
                      <input type="radio" name="mandatory" value="1" checked><label for="mandatorio">Sim</label>
                      <br>
                      <input type="radio" name="mandatory" value="0"><label for="mandatorio">Não</label>
                      <br>';
    echo '<input type="hidden" name="estado" value="inserir"><br><br>
                        <button type="submit" class="button-33">Submeter</form><br>';
}
?>
