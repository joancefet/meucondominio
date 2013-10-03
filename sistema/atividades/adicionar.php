<?php 
require_once("../php/conexao.php");
require_once("../php/condominios.php");
require_once("../php/condominiosDAO.php");
require_once("../php/superusuario.php");
require_once("../php/superusuarioDAO.php");
require_once("../php/permissoes.php");
require_once("../php/permissoesDAO.php");
require_once("../php/atividades.php");
require_once("../php/atividadesDAO.php");
require_once("../php/tiposusuarios.php");
require_once("../php/tiposusuariosDAO.php");
require_once("../php/tiposfuncionarios.php");
require_once("../php/tiposfuncionariosDAO.php");
require_once("../php/funcionarios.php");
require_once("../php/funcionariosDAO.php");
require_once("../php/membroscondominio.php");
require_once("../php/membroscondominioDAO.php");
require_once("../php/niveisprioridade.php");
require_once("../php/niveisprioridadeDAO.php");
require_once("../php/publico_alvo.php");
require_once("../php/publico_alvoDAO.php");
require_once("../php/email.php");
require_once("../php/sms.php");

@session_start();


$usuario = $_SESSION['usuario'];
if (!($usuario->logado)){
	header("Location: ../logoff.php");
	exit();
}


$conexao = new Conexao();
$conexao->conecta();
$id_condominio = $_SESSION['id_condominio'];
$mysms = new sms();
$atividades = new atividades();
$tiposusuarios = tiposusuariosDAO::findAll();
$tiposfuncionarios = tiposfuncionariosDAO::findAll();
$condominio = condominiosDAO::findByPk($id_condominio);

//verifica a integridade dos dados digitados.
if (isset($_POST['gravaratv'])) {
$go = true;
$u = 0;
$f = 0;
$atividades->id_membroscondominio = addslashes($_POST['responsavel']);
	$atividades->id_condominio = addslashes($id_condominio);
	$atividades->id_prioridade = addslashes($_POST['prioridade']);
	$atividades->data_realizacao = addslashes($_POST['data']);
	$atividades->descricao = addslashes($_POST['descricao']);
	$atividades->titulo = addslashes($_POST['titulo']);
for ($i = 0 ; $i < sizeof($tiposusuarios) ; $i++) { //quantidade grupos de usuarios
	  $aux = "pau" . "$i";
	   if ($$aux != "") {
	    $u = $u + 1;  } 
	  }
for ($i = 0 ; $i < sizeof($tiposfuncionarios) ; $i++) { //quantidade de grupos de funcionarios
	  $aux = "paf" . "$i";
	   if ($$aux != "") {
	    $f = $f + 1;  } 
	  }
if ($u == 0 && $f == 0 ) { $msg = " Pelo menos um grupo de destinatários deve ser selecionado "; $go = false; }
if ($_POST['prioridade'] == "") { $msg =  " Pelo menos uma prioridade deve ser selecionada "; $go = false; }
if ($_POST['responsavel'] == "") { $msg =  " Pelo menos um responsável deve ser selecionado "; $go = false; }
if ($_POST['descricao'] == "") { $msg = "/ Descrição Inválida /"; $go = false; }
if ($_POST['titulo'] == "") { $msg = "/ Título Inválido /"; $go = false; }
if ($_POST['data'] < date('Y-m-d',time())) { $msg = "/ A data da atividade não pode ser menor do que a data atual. /"; $go = false; }
} 

if ($go == true && $mgs == "") {	
	//verifica se o su possui permissão para alterar(1) em areas de custo(30)
	if(!permissoesDAO::temPermissao(7,1,$usuario->id_tipo_usuario)){	
		header("Location: ../index.php");
		exit();
	}		
	
//atribui o id ao objeto modulo
	$atividades->id = addslashes($_POST['id']);
	//verifica se o id recebido aponta para um novo usuário(0) ou um antigo(!=0)
	if ($atividades->id != 0){
    $atividades = atasreuniaoDAO::findByPk($_POST['id']);}	
	$atividades->id_membroscondominio = addslashes($_POST['responsavel']);
	$atividades->id_condominio = addslashes($id_condominio);
	$atividades->id_prioridade = addslashes($_POST['prioridade']);
	$atividades->data_realizacao = addslashes($_POST['data']);
	$atividades->descricao = addslashes($_POST['descricao']);
	$atividades->titulo = addslashes($_POST['titulo']);
	$id = atividadesDAO::save($atividades); 
	for ($i = 0 ; $i < sizeof($tiposusuarios) ; $i++) { //salva os membros do condominio
	  $aux = "pau" . "$i";
	   if ($$aux != "") {
	    $lid = atividadesDAO::lastId();
	    $id2 = publico_alvoDAO::saveUsr($lid, $$aux);
	   } 
	  }
	for ($i = 0 ; $i < sizeof($tiposfuncionarios) ; $i++) { //salva os funcionarios do condominio
	  $aux = "paf" . "$i";
	   if ($$aux != "") {
	    $lid = atividadesDAO::lastId();
		$id3 = publico_alvoDAO::saveFnc($lid, $$aux);
	   } 
	  }
     $data = date('d-m-Y', strtotime($atividades->data_realizacao));
	 $pau = publico_alvoDAO::findTopByUsr($lid); //envio de email e sms para os USUARIOS cadastrados na atividade
	if ($pau != "") $membrocondominio = membroscondominioDAO::findTopByTipo($id_condominio, $pau);
	 $sender = atividadesDAO::findAnunciante($lid, $responsavel);
	 $np = niveisprioridadeDAO::findByPk($prioridade); 
    for ($i = 0 ; $i < sizeof($membrocondominio) ; $i++) {
		    	   	  $id4 = email::sendMail(' ATIVIDADE - MeuCondominio.net - '.$titulo.' - Prioridade: '.$np->nome, $descricao.'. A ser realizada no dia: '.$data, $membrocondominio[$i]->email); //enviando email
					  $fone = ereg_replace("[^0-9]", "", $membrocondominio[$i]->celular); //retornando apenas numeros do celular
				      $results = $mysms->send('55'.$fone,' ATIVIDADE - PRIORIDADE '.$np->nome,' MeuCondominio.net '.$sender.', lhe convida para a seguinte atividade: '.$titulo.'. a ser realizada no dia '.$data.' Para maiores detalhes, acesse o email '.$membrocondominio[$i]->email.'.');
	  }
	   
	  $paf = publico_alvoDAO::findTopByFnc($lid); //envio de email e sms para os FUNCIONARIOS cadastrados na atividade
	if ($paf != "") $fnccondominio = funcionariosDAO::findTopByTipo($id_condominio, $paf);
	 $sender = atividadesDAO::findAnunciante($lid, $responsavel);
	 $np = niveisprioridadeDAO::findByPk($prioridade); 
    for ($i = 0 ; $i < sizeof($fnccondominio) ; $i++) {
					$id4 = email::sendMail(' ATIVIDADE - MeuCondominio.net - '.$titulo.' - Prioridade: '.$np->nome, $descricao.'. A ser realizada no dia: '.$data, $fnccondominio[$i]->email); //enviando email
				  $fone = ereg_replace("[^0-9]", "", $fnccondominio[$i]->celular); //retornando apenas numeros do celular
				  $results = $mysms->send('55'.$fone,' ATIVIDADE - PRIORIDADE '.$np->nome,' MeuCondominio.net '.$sender.', lhe convida para a seguinte atividade: '.$titulo.'. a ser realizada no dia '.$data.' Para maiores detalhes, acesse o email '.$fnccondominio[$i]->email.'.');
	}

		header("Location: home.php");
		exit();
	}     


//verifica se a pagina recebeu um id para alteração
if(isset($_GET['id'])){
	$atasreuniao = atasreuniaoDAO::findByPk($_GET['id']);
	$ep = 0; } else { 
	if(!permissoesDAO::temPermissao(7,2,$usuario->id_tipo_usuario)){ //verifica se o su possue permissão para inserir(2) em area custo(30)
		header("Location: ../index.php");
		exit();
	}

}
$pontinhos = "../";
$j = 1;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>| Sistema |</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<script type="text/JavaScript">
<!--
function MM_preloadImages() { //v3.0
  var d=document; if(d.images){ if(!d.MM_p) d.MM_p=new Array();
    var i,j=d.MM_p.length,a=MM_preloadImages.arguments; for(i=0; i<a.length; i++)
    if (a[i].indexOf("#")!=0){ d.MM_p[j]=new Image; d.MM_p[j++].src=a[i];}}
}

function MM_jumpMenu(targ,selObj,restore){ //v3.0
  eval(targ+".location='"+selObj.options[selObj.selectedIndex].value+"'");
  if (restore) selObj.selectedIndex=0;
}
//-->
</script>
<script language="javascript" type="text/javascript" src="../js/funcoes.js" charset="iso-8859-1" >
</script>
<script language="javascript" type="text/javascript" src="../js/calendario.js">
</script>

<link href="../inc/estilos.css" rel="stylesheet" type="text/css" />
</head>

<body bgcolor="#ffffff">
<table width="714" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td><img src="../images/spacer.gif" width="169" height="1" border="0" alt="" /></td>
    <td><img src="../images/spacer.gif" width="25" height="1" border="0" alt="" /></td>
    <td><img src="../images/spacer.gif" width="178" height="1" border="0" alt="" /></td>
    <td><img src="../images/spacer.gif" width="78" height="1" border="0" alt="" /></td>
    <td><img src="../images/spacer.gif" width="87" height="1" border="0" alt="" /></td>
    <td><img src="../images/spacer.gif" width="90" height="1" border="0" alt="" /></td>
    <td><img src="../images/spacer.gif" width="48" height="1" border="0" alt="" /></td>
    <td><img src="../images/spacer.gif" width="39" height="1" border="0" alt="" /></td>
    <td><img src="../images/spacer.gif" width="1" height="1" border="0" alt="" /></td>
  </tr>
  <tr>
    <td rowspan="9" valign="top" background="../images/complemento_menu_bottom.jpg"><?php  include("../inc/menu.php"); ?></td>
    <td colspan="7" rowspan="9" valign="top" background="../images/bg_geral_box.jpg" bgcolor="#FFFFFF"><table width="545" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td><!-- menu superior -->
              <table width="545" border="0" align="center" cellpadding="0" cellspacing="0">
                <tr>
                  <td width="533" valign="top" background="../images/topo_espaco.jpg"><img src="../images/topo_espaco.jpg" width="203" height="40" /></td>
                  <td valign="top" background="images/topo_espaco.jpg"><a href="home.php"><img src="../images/botao_listar_off.jpg" name="listar1" width="78" height="40" border="0" id="listar1" /></a></td>
                  <td valign="top" background="images/topo_espaco.jpg"><a href="buscar.php"><img src="../images/botao_pesquisar_off.jpg" name="pesquisar" width="90" height="40" border="0" id="listar1" /></a></td>
                  <td valign="top" background="images/topo_espaco.jpg"><a href="adicionar.php"><img src="../images/botao_cadastrar.jpg" name="cadastrar1" width="87" height="40" border="0" id="cadastrar1" /></a></td>
                  <td valign="top" background="images/topo_espaco.jpg"><a href="#"></a></td>
                  <td valign="top" background="images/topo_espaco.jpg"><a href="excluir.php"><img src="../images/botao_excluir_off.jpg" name="excluir1" width="87" height="40" border="0" id="excluir1" /></a></td>
                </tr>
            </table></td>
      </tr>
      <tr>
        <td height="71" background="../images/topo_box.jpg"><!-- titulo da secao -->
              <h1>ATIVIDADES</h1></td>
      </tr>
    </table>
        <table width="545" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td width="25" background="../images/le_box_bg.jpg">&nbsp;</td>
            <td width="481" bgcolor="#FFFFFF"><!-- texto sempre dentro da tag "<p>" -->
                <p align="center"> <br />
                    <br />
                    CONDOMÍNIO -&gt; <strong>
                    <?php=$condominio->nome?>
                    </strong><br />
              </p>
                <form action="adicionar.php" method="post" onsubmit="javascript:return confirmaAtividades(this)">
                  <div align="center"> <font class="warning">
                    <?php  if(isset($msg) )
						echo $msg;?>
                    </font> <br />
                    <br />
                  <table cellpadding="0" cellspacing="0" width="96%" class="tabela1">
                    <tr>
                      <td bgcolor="#6598CB"><table width="100%" cellpadding="0" cellspacing="0">
                          <tr>
                            <td width="89%" class="tabela1_titulo1">Dados Cadastrais </td>
                          </tr>
                      </table></td>
                    </tr>
                    <tr>
                      <td><table cellpadding="1" cellspacing="1" width="100%">
                        <tr>
                          <td class="tabela1_titulo2" colspan="3">obs: </td>
                        </tr>
                        <tr>
                          <td width="24%"  align="right" class="tabela1_linha2">Data da Atividade </td>
                          <td colspan="2" class="tabela1_linha2" ><script>DateInput('data', true, 'YYYY-MM-DD')</script>                          </td>
                        </tr>
                        <tr>
                          <td class="tabela1_linha2"  align="right">Título</td>
                          <td colspan="2" class="tabela1_linha2" ><input type="text" class="FORMULARIO"  name="titulo" size="50" maxlength="250" value="<?php=$atividades->titulo?>" /></td>
                        </tr>
                        <tr>
                          <td class="tabela1_linha2"  align="right">Descrição</td>
                          <td colspan="2" class="tabela1_linha2" ><textarea name="descricao" cols="47" rows="10" class="FORMULARIO"><?php=$atividades->descricao?></textarea></td>
                        </tr>
                        <tr>
                          <td class="tabela1_linha2"  align="right">Responsável</td>
                          <td colspan="2" class="tabela1_linha2" ><select name="responsavel">
                              <?php  if ($usuario->id_tipo_usuarios == 2 || $usuario->id_tipo_usuarios == 3 || $usuario->id_tipo_usuarios == 5 || $usuario->id_tipo_usuarios == 7){ ?>
                              <option selected="selected" value="<?php=$usuario->id?>">
                              <?php=$usuario->nome?>
                              </option>
                              <?php  } else { ?>
                              <option selected="selected" value=""> Selecionar </option>
                              <?php  } ?>
                              <option value="value""">------------</option>
                              <?php  $responsavel = membroscondominioDAO::findAllByCond($id_condominio); for ($i = 0; $i < sizeof(membroscondominioDAO::findAllByCond($id_condominio)); $i++){ ?>
                              <option value="<?php=$responsavel[$i]->id?>">
                              <?php=$responsavel[$i]->nome?>
                              </option>
                              <?php  } ?>
                          </select></td>
                        </tr>
                        <tr>
                          <td rowspan="2"  align="right" class="tabela1_linha2">Público Alvo </td>
                          <td class="tabela1_linha2" ><div align="center">Membros do Condomínio </div></td>
                          <td class="tabela1_linha2" ><div align="center">Funcionários do Condomínio </div></td>
                        </tr>
                        <tr>
                          <td width="38%" valign="top" class="tabela1_linha2" ><div align="left">
                              <?php  for ($i = 0 ; $i < sizeof($tiposusuarios) ; $i ++){ if($tiposusuarios[$i]->nome != 'Super Usuário'){ ?>
                              <input type="checkbox" id="a" name="pau<?php=$i?>" value="<?php=$tiposusuarios[$i]->id?>" />
                              <?php=$tiposusuarios[$i]->nome?>
                              <br />
                              <?php  } } ?>
                              <br />
                          </div></td>
                          <td width="38%" valign="top" class="tabela1_linha2" ><div align="left">
                              <?php  for ($i = 0 ; $i < sizeof($tiposfuncionarios); $i ++){ ?>
                              <input type="checkbox" id="pa" name="paf<?php=$i?>" value="<?php=$tiposfuncionarios[$i]->id?>" />
                              <?php=$tiposfuncionarios[$i]->nome?>
                              <br />
                              <?php  } ?>
                              <br />
                          </div></td>
                        </tr>
                        <tr>
                          <td  align="right" class="tabela1_linha2">Prioridade</td>
                          <td width="38%" valign="top" class="tabela1_linha2" ><select name="prioridade">
                            <option selected="selected" value=""> Selecionar </option>
                           
                            <option value="value""">------------</option>
                            <?php  $niveisprioridades = niveisprioridadeDAO::findAll(); 
							for ($i = 0; $i < sizeof($niveisprioridades); $i++){ ?>
                            <option value="<?php=$niveisprioridades[$i]->id?>">
                            <?php=$niveisprioridades[$i]->nome?>
                            </option>
                            <?php  } ?>
                          </select></td>
                          <td width="38%" valign="top" class="tabela1_linha2" >&nbsp;</td>
                        </tr>
                        
                        <tr>
                          <td colspan="3"  align="right" class="tabela1_linha2"><div align="center"><br />
                                  <input name="gravaratv" type="image" src="../images/enviar.jpg" value="bla" />
                          </div></td>
                        </tr>
                      </table></td>
                    </tr>
                  </table>
				  
                              </form>               </td>
            <td width="39" background="../images/ld_box_bg.jpg">&nbsp;</td>
          </tr>
      </table></td>
    <td><img src="../images/spacer.gif" width="1" height="40" border="0" alt="" /></td>
  </tr>
  <tr>
    <td><img src="../images/spacer.gif" width="1" height="71" border="0" alt="" /></td>
  </tr>
  <tr>
    <td><img src="../images/spacer.gif" width="1" height="6" border="0" alt="" /></td>
  </tr>
  <tr>
    <td><img src="../images/spacer.gif" width="1" height="83" border="0" alt="" /></td>
  </tr>
  <tr>
    <td><img src="../images/spacer.gif" width="1" height="48" border="0" alt="" /></td>
  </tr>
  <tr>
    <td><img src="../images/spacer.gif" width="1" height="73" border="0" alt="" /></td>
  </tr>
  <tr>
    <td><img src="../images/spacer.gif" width="1" height="54" border="0" alt="" /></td>
  </tr>
  <tr>
    <td><img src="../images/spacer.gif" width="1" height="14" border="0" alt="" /></td>
  </tr>
  <tr>
    <td><img src="../images/spacer.gif" width="1" height="26" border="0" alt="" /></td>
  </tr>
  <tr>
    <td colspan="8" valign="top"><img name="fim_box" src="../images/fim_box.jpg" width="714" height="32" border="0" id="fim_box" alt="" /></td>
    <td><img src="../images/spacer.gif" width="1" height="32" border="0" alt="" /></td>
  </tr>
  <tr>
    <td colspan="8" valign="top"><div align="center">
      <table width="655" border="0" align="right" cellpadding="0" cellspacing="0">
        <tr>
          <td><div align="center"><img src="../images/rodape.jpg" width="583" height="23" /></div></td>
        </tr>
      </table>
    </div></td>
    <td><img src="../images/spacer.gif" width="1" height="32" border="0" alt="" /></td>
  </tr>
</table>
 <blockquote>&nbsp;</blockquote>
<blockquote>&nbsp;</blockquote>
</body>
</html>
