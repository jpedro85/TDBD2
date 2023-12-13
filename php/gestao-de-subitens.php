
<?php
require_once("custom/php/common.php");
//connect();
if(!is_user_logged_in() && current_user_can('manage_subitems')){
    echo 'O Utilizador não tem permissões para aceder à página';
}else {
    $listUNIT= '';
    $lista = '';
    $estado = isset($_REQUEST["estado"]) ? $_REQUEST["estado"] : '';
    $pattern = '/^[a-zA-Z0-9\s]+$/';
    $camposF = '<h3><b>Gestão de subitens - inserção</b></h3><br>';
    $erro = false;
    if ($estado == "inserir") {//caso o hidden estado esteja a inserir vai aparecer esta parte do código
        //Verifica se as variáveis estão atribuidas ao array $_REQUEST, caso contrário é atribuido o valor de ''
        $ITname = isset($_REQUEST["ITname"]) ? $_REQUEST["ITname"] : '';
        $Eitem = isset($_REQUEST["Eitem"]) ? $_REQUEST["Eitem"] : '';
        $slctunit = isset($_REQUEST["slctunit"]) ? $_REQUEST["slctunit"] : '';
        $formCamp = isset($_REQUEST["formCamp"]) ? $_REQUEST["formCamp"] : '';
        $mandatory = isset($_REQUEST["mandatory"]) ? $_REQUEST["mandatory"] : '';
        // Verificação do subitem name
        if (empty($ITname) || is_numeric($ITname) || !preg_match($pattern, $ITname)) {
            $camposF .= '<li class="list">Falta inserir o nome do subitem, ou este é numérico</li><br>';
            $erro = true;
        }
// Verificação do nome do item
        if (empty($Eitem)) {
            $camposF .= '<li class="list">Falta fazer a escolha do item, é obrigatório </li><br>';
            $erro = true;
        }
// Verificar se a unidade foi selecionada, caso contrário, ficará como "----" na BD
        $slctunit = empty($slctunit) ? "----" : $slctunit;
// Verificação do campo de form
        if ($formCamp == 0) {
            $camposF .= '<li class="list">Tem que inserir um valor maior que 0</li>';
            $erro = true;
        }
// Se houver erros, exibe o aviso
        if ($erro) {
            echo '<div class="contorno">' . $camposF . '</div><br>';
            //voltaatras();

            echo "<script type='text/javascript'>document.write(\"<a href='javascript:history.back()' class='backLink' title='Voltar atr&aacute;s'>Voltar atr&aacute;s</a>\");</script>
            <noscript>
            <a href='" . $_SERVER['HTTP_REFERER'] . "‘ class='backLink' title='Voltar atr&aacute;s'>Voltar atr&aacute;s</a>
            </noscript>";
        }else {
            echo '<form> 
             <input type="hidden" name="ITname" value="' . $ITname . '">
             <input type="hidden" name="Eitem" value="' . $Eitem . '">
             <input type="hidden" name="slctunit" value="' . $slctunit . '">
             <input type="hidden" name="formCamp" value="' . $formCamp . '">
             <input type="hidden" name="mandatory" value="' . $mandatory . '">
             </form>';

//Iserção da dados na Base de Dados
            $itemQuery = mysqli_query($link, "SELECT name as itemName, id FROM item WHERE id = " . $Eitem);
            $itemFetch = mysqli_fetch_assoc($itemQuery);

// Obtendo o ID do subitem
            $subItemQuery = mysqli_query($link, "SELECT id as subItemID FROM subitem WHERE name = '" . $ITname . "'");
            $subItemFetch = mysqli_fetch_assoc($subItemQuery);

// transformaçao do nome de campo de formulário com o id
            $tresLetras = substr("'" . $itemFetch["itemName"] . "'", 1, 3);
            $formFieldName = $tresLetras . "-" . $ITname;

//Query de Inserção
            $insertQuery = "INSERT INTO subitem(id, name, item_id, value_type, form_field_name, form_field_type, unit_type_id, form_field_order, mandatory, state) VALUES 
                (NULL, '" . $_REQUEST["ITname"] . "', '" . $_REQUEST["Eitem"] . "', '" . $_REQUEST["valueT"] . "', '" . $formFieldName . "', '" . $_REQUEST["formTYPE"] . "', '" . $_REQUEST["slctunit"] . "', '" . $_REQUEST["formCamp"] . "', '" . $_REQUEST["mandatory"] . "', 'active')";

// Verificação da query
            if (!mysqli_query($link, $insertQuery)) {
                // Exibindo mensagens de erro
                echo mysqli_error($link);
                echo "<li class='list'>Ocorreu um erro durante a inserção.</b>";
                // voltaatras();
            } else {
                //Query para atualizar o nome de campo de formulário
                /*$queryAtuali= mysqli_query($link,"SELECT id as NewID FROM subitem name = '" . $_REQUEST["ITname"] . "'" );
                $queryAtualiFetch = mysqli_fetch_assoc($queryAtuali);*/
                //Vai me dar o novo ID inserido na Base de Dados
                // Exibindo mensagem de sucesso e botão para continuar
                echo '<li><b class="success">Os dados foram inseridos com sucesso</b><br>Clique em Continuar para AVANÇAR!</li>
        <a href=' . $current_page . ' ><button>Continuar</button></a>';
                $NewID = mysqli_insert_id($link);
                $NewFormFName = $tresLetras . "-" . $NewID . "-" . $_REQUEST["ITname"];
                $updateQuery = "UPDATE subitem SET form_field_name = '" . $NewFormFName . "' WHERE id = " . $NewID;
            }
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
        unit_type_id as SubUnitT,form_field_order as SubFFO, mandatory, state From subitem WHERE item_id=" . $lineItem["id"];
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
                 <td><a  class="links" href="'.$editDataPage.'?estado=editar&tipo=subitem&id=' . $lineSubItem["SubID"] . '">[editar]</a><br>
                     <a  class="links"  href="'.$editDataPage.'?estado=desativar&tipo=subitem&id=' . $lineSubItem["SubID"] . '">[desativar]</a><br>
                     <a  class="links" href="'.$editDataPage.'?estado=apagar&tipo=subitem&id=' . $lineSubItem["SubID"] . '">[apagar]</a>
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
        <form class"container action="' . $current_page . '" method="post">
        <label for="ITname">Nome do Subitem:<b>(Obrigatório!)</b></label>
        <br>
        <input type="text" id="ITname" name="ITname">
        <br>
            <p>Inserção do tipo de dados: </p>';
        $value_types = get_enum_values($link, "subitem", "value_type");
        foreach ($value_types as $v) {
            echo '<label class="checkBox">
                    <input type="radio" name="valueT" value="' . $v . '" checked >
                    <span class="checkmark"></span>' . $v . '
                  </label><br>';
        }
        ?>
            <p>Escolha o Item: (Obrigatório!)</p>
            <select class="box" id="slctItems" name="Eitem">
                <option value=""></option>
                <?= $lista ?>
            </select>
            <p>Inserção do tipo de campo de formulário:</p>
            <?php
            $formf_types = get_enum_values($link, "subitem", "form_field_type");
            foreach ($formf_types as $f) {
                echo '<label class="checkBox">
                        <input type="radio" name="formTYPE" value="' . $f . '"><span class="checkmark"></span>' . $f . '
                      </label><br>';
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
            <label class="checkBox">
                <input type="radio" name="mandatory" value="1" checked>
                <span class="checkmark"></span>Sim
            </label><br>
            <label class= "checkBox">
                <input type="radio" name="mandatory" value="0">
                <span class='checkmark'></span>Não
            </label><br>

            <input type="hidden" name="estado" value="inserir"><br><br>
            <button type="submit" class="button-33">Submeter</button>
        </form>

        <?php
    }
}
?>

