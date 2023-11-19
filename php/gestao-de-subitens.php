<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="\\wsl.localhost\Ubuntu\opt\lampp\htdocs\sgbd\custom\css\ag.css">
</head>
<body>
<?php
require_once("custom/php/common.php");
connect();
if(!is_user_logged_in()){
    echo 'O Utilizador não tem permissões para aceder à página';
}else {
    echo 2;
    $camposF = '<h3><b>Gestão de subitens - inserção</b></h3><br>';
    $erro = false;
    if ($_REQUEST["estado"] == "inserir") {//caso o hidden estado esteja a inserir vai aparecer esta parte do código
// Verificação do subitem name
        if (empty($_REQUEST["ITname"]) || is_numeric($_REQUEST["ITname"])) {
            $camposF .= '<li class="list">Falta inserir o nome do subitem, ou este é numérico</li><br>';
            $erro = true;
        }
// Verificação do nome do item
        if (empty($_REQUEST["Eitem"])) {
            $camposF .= '<li class="list">Falta fazer a escolha do item, é obrigatório </li><br>';
            $erro = true;
        }
// Verificar se a unidade foi selecionada, caso contrário, ficará como "----" na BD
        $_REQUEST["slctunit"] = empty($_REQUEST["slctunit"]) ? "----" : $_REQUEST["slctunit"];
// Verificação do campo de form
        if ($_REQUEST["formCamp"] == 0) {
            $camposF .= '<li class="list">Tem que inserir um valor maior que 0</li>';
            $erro = true;
        }
// Se houver erros, exibe o aviso
        if ($erro) {
            echo '<div class="contorno">' . $camposF . '</div><br>';
            voltaatras();
        }
    }else{
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
                    echo '
                     <td>' . $lineSubItem["SubID"] . '</td>
                     <td>' . $lineSubItem["SubItName"] . '</td>
                     <td>' . $lineSubItem["SubValueType"] . '</td>
                     <td>' . $lineSubItem["SubFFN"] . '</td>
                     <td>' . $lineSubItem["SubFFT"] . '</td>';
                    if (isset($lineSubItem["SubUnitT"])) {
                        $lineSubItemUnit = mysqli_fetch_assoc($resultqueryUnit);
                        $listUNIT .= '<option value="' . $lineSubItemUnit["id"] . '"> ' . $lineSubItemUnit['name'] . '</option>';
                        echo '
                          <td>' . $lineSubItemUnit["name"] . '</td>';
                    } else {
                        echo '
                        <td>---</td>';
                    }
                    echo '
                 <td>' . $lineSubItem["SubFFO"] . '</td>
                 <td>' . $lineSubItem["mandatory"] . '</td>
                 <td>' . $lineSubItem["state"] . '</td>
                 <td><a class="links" href="http://localhost/sgbd/edicao-de-dados/">[editar]</a><br>
                     <a class="links" href="http://localhost/sgbd/edicao-de-dados/">[desativar]</a><br>
                     <a class="links" href="http://localhost/sgbd/edicao-de-dados/">[apagar]</a>
                 </td>
                 </tr>';
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
        ?>
        <br>
        <form action="<?= $current_page ?>" method="post">
            <label for="ITname">Nome do Item:</label><br>
            <input type="text" id="ITname" name="ITname"><br>

            <p>Inserção do tipo de dados:</p>
            <?php
            $value_types = get_enum_values($link, "subitem", "value_type");
            foreach ($value_types as $v) {
                echo '<input type="radio" name="valueT" value="' . $v . '" checked>' . $v . '<br>';
            }
            ?>
            <p>Escolha o Item</p>
            <select id="slctItems" name="Eitem">
                <option value=""></option>
                <?= $lista ?>
            </select>
            <p>Inserção do tipo de campo de formulário:</p>
            <?php
            $formf_types = get_enum_values($link, "subitem", "form_field_type");
            foreach ($formf_types as $f) {
                echo '<input type="radio" name="formTYPE" value="' . $f . '">' . $f . '<br>';
            }
            ?>
            <p>Escolha o tipo de unidade</p>
            <select id="slctunid" name="slctunit">
                <?= $listUNIT ?>
            </select>
            <br>
            <p>Insira o número de campo de formulário</p>
            <input type="text" id="formCamp" name="formCamp"><br>
            <p>Mandatoriedade</p>
            <input type="radio" name="mandatory" value="1" checked><label for="mandatorio">Sim</label><br>
            <input type="radio" name="mandatory" value="0"><label for="mandatorio">Não</label><br>
            <input type="hidden" name="estado" value="inserir"><br><br>
            <button type="submit" class="button-33">Submeter</button>
        </form>
        <?php
    }
}
?>
</body>
</html>
