<?php
require_once("custom/php/common.php");

if(!is_user_logged_in() & current_user_can('manage_unit_types')){
    echo 'O Utilizador não tem permissões para aceder à página';
    'Caso queira aceder à pagina tem que ter as permissões necessárias';
}else {
    echo 
}else{
    $estado = '';
    if (isset($_REQUEST["estado"])) {
        $estado = $_REQUEST["estado"];
        $erro = true;
    }
    $pattern = '/^[a-zA-Z0-9\s^\/]+$/';
    $camposF="";
    $erro = false;
    if ($estado == "inserir") {//caso o hidden estado esteja a inserir vai aparecer esta parte do código

        $camposF .= '<h3><b>Gestão de subitens - inserção</b></h3>
        <br>';
        if (empty($_REQUEST["UnitType"]) || is_numeric($_REQUEST["UnitType"]) || !preg_match($pattern, $_REQUEST["UnitType"])) {//verificaçao do subitem name
            $camposF .= '<li class="list">Falta inserir o nome do subitem, ou este não cumpre os requisitos necessários</li>
            <br>';
            $erro = true;
        } else {
            $erro = false;
        }
        if ($erro) {
            echo '<div class="contorno">' . $camposF . '</div><br>';
            echo'<a class="links" href="http://localhost/sgbd/gestao-de-unidades/">Voltar atrás!</a><br>';
        }else{
        echo
        '<form> 
             <input type="hidden" name="UnitType" value="' . $_REQUEST["UnitType"] . '"></form>';
        $insertQuery = "INSERT INTO subitem_unit_type (id, name) VALUES (NULL,'".$_REQUEST["UnitType"]. "')";
// Verificação da query
        if (!mysqli_query($link, $insertQuery)) {
            // Exibindo mensagens de erro
            echo mysqli_error($link);
            echo "<li class='list'>Ocorreu um erro durante a inserção.</b>";
            // voltaatras();
        } else {
            echo '<li><b class="success">Inseriu os dados de novo tipo de unidade com sucesso.</b><br>Clique em Continuar para AVANÇAR!</li>
        <a href=' . $current_page . ' ><button class="button1">Continuar</button></a>';
        }
        }
    }else{
    echo '<table class="content-table">
    <tbody>
    <thead>
    <th>id</th>
    <th>unidade</th>
    <th>subitem</th>
    <th>ação</th>
    </thead>';
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
        <br>
        <input type="hidden" name="estado" value="inserir"><br><br>
        <button type="submit" class="button-33">Inserir tipo de Unidade</button>
        </form>';
    }
}
?>

