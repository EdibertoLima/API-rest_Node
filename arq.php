<?php
include('conexao.inc');
include 'mobile_functions.php';
/** 
 * Pрgina para validaусo da matrьcula e senha do usuрrio (Camada Controle)
 * Comunicaусo entre Dataflex e Php (Arquivos txt)
 * @author Ronaldo Barbosa <ronaldo@computex.com.br>
 * Atualizaусo - 05/2010 14:00  ...  07/2013  ... 12/2013
 */

$v_transf = "./transf/";
$v_arqcfg = $v_transf . "econfig.txt";
// Mobile 20/09/2016
$token = "";

if (!file_exists($v_arqcfg)) {
	/* Se nсo existir o arquivo ECONFIG.TXT na pasta "transf" o monitor de internet estр parado.*/
	/*(ALTERAК├O)echo "<script>window.alert('Sistema indispon№┐йvel. Tente mais tarde!')</script>"; */
	echo "<script>window.location.href='blank.html'</script>";
} else {
	// Importa as funушes
	include("functions.php");
	/* Se existir grava o caminho da pasta do logotipo da escola e o caminho de acesso 
	Яs fotos dos alunos.*/
	$arqcfg = fopen($v_arqcfg, "rw");
	$linhacfg = array();
	$seriesCfg = array();

	while (!feof($arqcfg)) {
		$linhacfg[] = fgets($arqcfg);
	}
	fclose($arqcfg);
	$caminhologo  = trim(substr($linhacfg[1], 0, 100));
	$caminhofotos = trim(substr($linhacfg[2], 0, 100));
	$versaoEsistema = trim(substr($linhacfg[5], 0, 100));
	// Bloco de informaушes para (SERIES)
	//$objetoSerie = new Pesquisa();
	//$seriesCfg = $objetoSerie->extrairComando($linhacfg, "SERIES", 10);
	$sqlobj = new ConsultaSql();
	$seriesCfg = $sqlobj->resultadoN("SELECT grau_serie,grau_longo,serie_longa,definter FROM `series` WHERE 1");

	// Matrьcula e senha digitadas no login
	$matric = trim(@$_REQUEST['matric']);
	$esenha = trim(@$_REQUEST['esenha']);
	// Retira aspas para evitar Sql Injection (ataque de hacker)
	$matric = str_replace(' ', '', $matric);
	$esenha  = str_replace(' ', '', $esenha);
	$matric = str_replace('"', '', $matric);
	$esenha  = str_replace('"', '', $esenha);
	$matric = str_replace("'", '', $matric);
	$esenha  = str_replace("'", '', $esenha);
	$matric = str_replace('њ', '', $matric);
	$esenha  = str_replace('њ', '', $esenha);
	$matric = str_replace('Љ', '', $matric);
	$esenha  = str_replace('Љ', '', $esenha);

	$matric = str_replace('.', '', $matric);
	$matric = str_replace(',', '', $matric);
	if (!is_numeric($matric)) {
		$matric = '0';
	}

	// Captura IP do visitante
	$ipLog = $_SERVER['REMOTE_ADDR'];


	// Funусo que cria tabelas no MySql se nсo existirem
	//CriarTabelas(); // 25/02/2021 - criartabelas.php
	// Abre conexсo com o banco
	//f_conecta();
	$servidor = 'localhost';
	$usuario  = 'root';
	$senha    = '';
	$banco    = $nome_banco;

	if (file_exists("db.php")) {
		include("db.php");
		$servidor = descriptografa(@$h);
		$usuario  = descriptografa(@$u);
		$senha    = descriptografa(@$s);
		$banco    = descriptografa(@$b);
	}
	$conexao = new ConexaoBanco();
	$conexao->conectar($servidor, $usuario, $senha, $banco);
	// Limpeza de possьveis registros antigos
	// SELECT * FROM `alunos` WHERE 1253207747 - tempo > 43200  // 12 horas
	// Alunos/Profs com mais de 12 horas no banco MySQL(saьram sem logout) sсo apagados
	$tempoAgora = $_SERVER['REQUEST_TIME'];
	$sqlLimpeza = new ConsultaSql();
	$sqlLimpeza->consulta("DELETE FROM alunostmp   WHERE " . $tempoAgora . " - tempo > 43200");
	$sqlLimpeza->consulta("DELETE FROM enquetestmp WHERE " . $tempoAgora . " - tempo > 43200");
	$sqlLimpeza->consulta("DELETE FROM comentstmp  WHERE " . $tempoAgora . " - tempo > 43200");
	$sqlLimpeza->consulta("DELETE FROM escolastmp  WHERE " . $tempoAgora . " - tempo > 43200");
	$sqlLimpeza->consulta("DELETE FROM notastmp    WHERE " . $tempoAgora . " - tempo > 43200");

	$sqlLimpeza->consulta("DELETE FROM infantiltmp WHERE matricula_prof=" . $matric);
	$sqlLimpeza->consulta("DELETE FROM infantil_alunotmp WHERE matricula_prof=" . $matric);
	$sqlLimpeza->consulta("DELETE FROM infantil_ravalaretmp WHERE matricula_prof=" . $matric);
	$sqlLimpeza->consulta("DELETE FROM infantil_ravalsertmp WHERE matricula_prof=" . $matric);
	$sqlLimpeza->consulta("DELETE FROM infantil_ravaltoptmp WHERE matricula_prof=" . $matric);
	$sqlLimpeza->consulta("DELETE FROM infantil_areatmp WHERE matricula_prof=" . $matric);
	$sqlLimpeza->consulta("DELETE FROM infantil_topicotmp WHERE matricula_prof=" . $matric);
	$sqlLimpeza->consulta("DELETE FROM a_infantiltmp WHERE matricula_aluno=" . $matric);
	$sqlLimpeza->consulta("DELETE FROM a_infantil_alunotmp WHERE matricula_aluno=" . $matric);
	$sqlLimpeza->consulta("DELETE FROM a_infantil_ravalaretmp WHERE matricula_aluno=" . $matric);
	$sqlLimpeza->consulta("DELETE FROM a_infantil_ravalsertmp WHERE matricula_aluno=" . $matric);
	$sqlLimpeza->consulta("DELETE FROM a_infantil_ravaltoptmp WHERE matricula_aluno=" . $matric);
	$sqlLimpeza->consulta("DELETE FROM a_infantil_areatmp WHERE matricula_aluno=" . $matric);
	$sqlLimpeza->consulta("DELETE FROM a_infantil_topicotmp WHERE matricula_aluno=" . $matric);

	// Se matrьcula ou senha estiverem em branco nсo faz pesquisa
	if (empty($matric) || empty($esenha)) {
		$linha5 = 0;
	} else {
		$sqlAcesso = new ConsultaSql();
		$resultfinal = $sqlAcesso->resultado("SELECT SQL_NO_CACHE * FROM alunostmp where matricula = '" . $matric . "' and senha = '" . $esenha . "'");
		$linha5 = $sqlAcesso->quantidadeLinhas("SELECT SQL_NO_CACHE * FROM alunostmp where matricula = '" . $matric . "' and senha = '" . $esenha . "'");
	}
	// Fechou regularmente o programa
	$_SESSION["gSaidaLegal"] = "S";

	if ($linha5 > 0) {
		//$resultfinal = mysql_fetch_array($resultado5);
		$vtempo = $resultfinal['tempo'];
		$vip = $resultfinal['ip'];
		$vip2 = $_SERVER['REMOTE_ADDR'];
		@session_start();
		session_name();
		$_SESSION["gmatric"] = $matric;   //  $resultfinal['matricula'];  ALTERADO (LOGOUT)
		$_SESSION["gsenha"] = $esenha;    //  $resultfinal['senha'];

		// Se acessar com outra mрquina com o mesmo IP (Condiусo executando a mesma rotina para futura decisсo sobre alerta quando usuрrio acessa por mрquinas diferentes

	}
	// Pega um nЩmero de controle "vago" da tabela Acessos
	$controleAcesso = new Controle();
	$controle = $controleAcesso->novoNumero();

	// Se existir nЩmero "vago"
	if ($controle > 0) {

		$v_nomarq = "esistema.$controle";
		$v_arquivo = $v_transf . $v_nomarq;

		// Apaga, se existir, Щltimo arquivo gerado pelo Dataflex com o nЩmero "vago" de controle
		@unlink($v_arquivo);

		// Cria arquivo Txt com as informaушes do login Php->Dataflex
		$arquivo = fopen($v_transf . "einternet.$controle", "w");
		$quebra = chr(13) . chr(10);  // quebra de linha
		fwrite($arquivo, $matric . $quebra);
		fwrite($arquivo, $esenha . $quebra);
		fwrite($arquivo, "LOGIN" . chr(13) . chr(10));
		if ((isset($_REQUEST['token'])) && (!empty($_REQUEST['token']))) {
			$token = $_REQUEST["token"];
			$sistema = $_REQUEST["so"];
			fwrite($arquivo, $token . chr(13) . chr(10));
			fwrite($arquivo, $sistema . chr(13) . chr(10));
		}
		// Fecha arquivo e aguarda resposta Dataflex->Php
		fclose($arquivo);


		$verro = false;


		$v_nomarq = "esistema.$controle";


		$v_arquivo = $v_transf . $v_nomarq;
		$vexiste = false;
		$cronometro = 0;
		// Aguarda enquanto arquivo de resposta ж gerado (esistema)
		while (!file_exists($v_arquivo)) {
			$vexiste = true;
			// espera um quarto de segundo
			usleep(250000);
			//sleep(1);    // Esperava 1 segundo (TESTANDO VELOCIDADE)
			$verro = false;
			$cronometro++;
		}
		// espera meio segundo
		usleep(500000);
		//sleep(1); // Esperava 1 segundo (TESTANDO VELOCIDADE)
	}
	if ($vexiste) {

		if (file_exists($v_transf . "resposta.txt")) {
			$newfile = $v_transf . 'resposta.txt';
			copy($v_arquivo, $newfile);
		}
		// Se gerado arquivo, abre guarda as informaушes de cada bloco
		$arq = fopen($v_arquivo, "rw");
		$linha1 = array();
		while (!feof($arq)) {
			$linha1[] = fgets($arq);
		}
		fclose($arq);
		$qtd = sizeof($linha1);
		$qtd1 = sizeof($linha1);
		$qtdB = sizeof($linha1);

		/*// Bloco de informaушes MENU
		$vqtdados = 0;
		for ($indice = 0; $indice < $qtd; $indice++) {

			if (substr($linha1[$indice], 0, 4) == "MENU") {
				$vqtdados = (int)substr($linha1[$indice], 5);

				$indice++;
			}
			if ($vqtdados > 0) {
				$linha_dados[] = $linha1[$indice];  // cria um array MENU com o conteudo do arquivo
				$vqtdados -= 1;
			}
		}*/

		// Bloco de informaушes ESCOLAS
		/*$temescolas = false;
		for ($indice = 0; $indice < $qtd; $indice++) {

			if (substr($linha1[$indice], 0, 7) == "ESCOLAS") {
				$vqtdadosE = (int)substr($linha1[$indice], 8);
				$indice++;
			}
			if (@$vqtdadosE > 0) {
				$linha_dadosE[] = $linha1[$indice];
				$temescolas = true;
				$vqtdadosE -= 1;
			}
		}*/

		$temescolas = false;
        $objetoescola2 = new ConsultaSql();
        $dadosEscolas = $objetoescola2->resultado('SELECT * FROM `escolasws` ');
        $qtdEsc = sizeof($dadosEscolas);
        if ($qtdEsc > 0) {
            # code...
            $temescolas = true;
        }

		// Bloco de informaушes DADOS do aluno ou professor
		$temdados = false;
		$vqtdados1 = 0;
		$vmensagem = '';
		$linha_dados1 = array();
		for ($indice1 = 0; $indice1 < $qtd1; $indice1++) {
			if (substr($linha1[$indice1], 0, 4) == "ERRO") {
				$verro = true;
				//$vmensagem = substr($linha1[3],0);
				$tamensagem = (int)substr($linha1[3], 0, 3);
				$vmensagem = substr($linha1[3], 4, $tamensagem);
				// Cria, se ainda nсo existirem, as tabelas no Mysql
				//CriarTabelas(); // 25/02/2021 - criartabelas.php
				// Insere registro na tabela LogFile(rastreamento)
				$ipLog = $_SERVER['REMOTE_ADDR'];
				$sqlLog = new ConsultaSql();
				$sqlLog->consulta("INSERT  INTO arquivolog (matricula, ip, hora, tipo, descricao) VALUES ('" . $matric . "', '" . $ipLog . "', NOW(), 'S','" . $vmensagem . "')");

				//$sql = "INSERT  INTO arquivolog (matricula, ip, hora, tipo, descricao) VALUES ('$matric', '$ipLog', NOW(), 'L','$vmensagem')";
				//$result = mysql_query($sql) or die(mysql_error());
			}

			/*if (substr($linha1[$indice1], 0, 5) == "DADOS") {
				$vqtdados1 = (int)substr($linha1[$indice1], 6);
				$temdados = true;
				$indice1++;
			}
			if ($vqtdados1 > 0) {
				$linha_dados1[] = $linha1[$indice1];  	// cria um array com o conteudo do arquivo
				$vqtdados1 -= 1;
			}*/
		}

		// Bloco de informaушes do BOLETIM
		/*$objetoBoletim = new Pesquisa();
		$linha_dadosB = $objetoBoletim->extrairComando($linha1,"BOLETIM",11);
		
        $v_msgboletim = '';
		if (sizeof($linha_dadosB) <= 1) {
			$v_msgboletim = trim(@$linha_dadosB[0]);
		}*/
		$objetoBoletim = new ConsultaSql();
		$boletim = $objetoBoletim->resultado("SELECT boletim_pdf FROM monitmen WHERE 1 ");
		$v_msgboletim = $boletim['boletim_pdf'];



		// Bloco de informaушes do BOLETO
		$v_msgboleto = '';
		$inicioboleto = false;
		$numeracao = "00";
		$contagemLinha = 0;
		for ($indiceBol = 0; $indiceBol < $qtdB; $indiceBol++) {
			if (substr($linha1[$indiceBol], 0, 11) == "BOLETOS SIM") {
				$inicioboleto = true;
				$indiceBol++;
			}
			if (substr($linha1[$indiceBol], 0, 11) == "BOLETOS FIM") {
				$inicioboleto = false;
			}
			if (substr($linha1[$indiceBol], 0, 11) == "BOLETOS NAO") {
				$indiceBol++;
			}
			if ($inicioboleto) {
				$contagemLinha++;
				$linha_dadosBol[] = $linha1[$indiceBol];  	// cria um array BOLETO
				$ordem = explode(";", $linha1[$indiceBol]);
				if ($ordem[0] != $numeracao) {
					$numeracao = $ordem[0];
					$linha_cabBol[] = explode(";", $linha1[$indiceBol]);
				}
				if ($contagemLinha == 20) {
					$footerBoleto[] = explode(";", $linha1[$indiceBol]);
					$contagemLinha = 0;
				}
			}
		}

		// Bloco de informaушes para digitaусo de notas(DISCIPLINA)

		$temnotas = false;
		$v_msgdisciplina = ''; // variavel fazia
		$codigoDaEscola = '01';// faltando pegar do banco

		$MonitMen = new MonitMen();
		$aMonitMen = $MonitMen->getMonitMen('contrato, ano_matricula, inf_texto_livre,ano_corrente,etapa_digitacao');
		$v_etapa = $aMonitMen['etapa'];
		$v_ano = $aMonitMen['ano_corrente'];
		// Bloco de i nforma??es para digita??o de notas(DISCIPLINA)
		$objTurmasSeries = new DaoWS();
		// Bloco de informa??es de Turmas do Coordenador
		$turmasSeries =  $objTurmasSeries->getTurmasSeriesProfessor($v_matricula, $v_ano);
		if (sizeof($turmasSeries)>0) {
			# code...
			$temnotas = true;
		}

		

		// Bloco de informaушes para Material Didрtico           
		/*$objetoMaterial = new Pesquisa();
		$linha_dadosM = $objetoMaterial->extrairComando($linha1, "MATERIAL", 12);
		$temMaterial = false;
		$v_msgMaterial = '';
		if (sizeof($linha_dadosM) <= 1) {
			$v_msgMaterial = trim(@$linha_dadosM[0]);
		} else {
			$temMaterial = true;
			$codigoDaEscola = trim(substr(@$linha_dadosM[1], 0, 2));
		}*/


		// Bloco de informaушes da ENQUETE
		$iniEnq = false;
		$temEnq = false;
		$textoEnq = '';
		$initextoEnq = true;
		$linEnq = 0;
		$qtdQuestao = 0;
		for ($indEnq = 0; $indEnq < $qtd; $indEnq++) {
			if (substr($linha1[$indEnq], 0, 11) == "ENQUETE SIM") {
				$iniEnq = true;
				$temEnq = true;
				$indEnq++;
			}
			if (substr($linha1[$indEnq], 0, 11) == "ENQUETE NAO") {
				$indEnq++;
				$v_tamens = (int)substr($linha1[$indEnq], 0, 3);
				$v_msgEnq = substr($linha1[$indEnq], 4, $v_tamens); //Guarda a mensagem sobre Enquete.
			}
			if (substr($linha1[$indEnq], 0, 7) == "QUESTAO") {
				$qtdQuestao++;
			}
			if (substr($linha1[$indEnq], 0, 11) == "ENQUETE FIM") {
				$iniEnq = false;
			}
			if ($iniEnq) {
				$linEnq++;
				$linha_Enq[] = $linha1[$indEnq];  	// cria um array ENQUETES
				if ($initextoEnq and $linEnq > 2) {
					if (substr($linha1[$indEnq], 0, 9) == "TEXTO FIM") {
						$initextoEnq = false;
					} else {
						$textoEnq .= $linha1[$indEnq];
					}
				}
			}
		}

		// Bloco de informaушes do CORREIO INTERNO (RECADOS)
		/*$inirec = false;
		$qtdrec = 0;
		for ($indiceR = 0; $indiceR < $qtd; $indiceR++) {
			if (substr($linha1[$indiceR], 0, 10) == "RECADO SIM") {
				$inirec = true;
				$indiceR++;
			}
			if (substr($linha1[$indiceR], 0, 10) == "RECADO FIM") {
				$inirec = false;
			}
			if (substr($linha1[$indiceR], 0, 10) == "RECADO NAO") {
				$indiceR++;
				$linha_dadosR[] = "Nenhuma mensagem registrada";
			}
			if ($inirec) {
				$linha_dadosR[] = $linha1[$indiceR];  // cria um array com os Recados
				if (substr($linha1[$indiceR], 25, 1) == "N") {
					$qtdrec++;
				}
			}
		}*/
		// Mensagem anterior para o profeessor: Ao digitar  notas observe a nova maneira de digita&ccedil;&atilde;o no quadro NOTAS.
		// Bloco de informaушes dos LINKS para o aluno
		
		/*if ($v_tipo == 'P') {
			$linha_dadosL[] = "Na digitaусo das notas observe o quadro explicativo no canto superior direito.|";
		} else {
			$linha_dadosL[] = "";
		}
		// Se tem novas mensagens
		if ($qtdrec > 0) {
			$linha_dadosL[0] .= "VocЖ tem " . $qtdrec . " nova(s) mensagem(ns) no correio interno.|";
		}
		$objetoLinks = new Pesquisa();
		$vetorLinks = $objetoLinks->extrairComando($linha1, "LINKS", 9);

		if (substr(@$vetorLinks[0], 0, 3) != 'Sem') {
			$linha_dadosL[0] .= @$vetorLinks[0];
		} */

		// Bloco de informaушes de menu de ocorrЖncias
		/*$inirecOco = false;
		$qtdrecOco = 0;
		for ($indiceROco = 0; $indiceROco < $qtd; $indiceROco++) {
			if (substr($linha1[$indiceROco], 0, 12) == "DEFOCORR SIM") {
				$inirecOco = true;
				$indiceROco++;
			}
			if (substr($linha1[$indiceROco], 0, 12) == "DEFOCORR FIM") {
				$inirecOco = false;
			}
			if (substr($linha1[$indiceROco], 0, 12) == "DEFOCORR NAO") {
				$indiceROco++;
				$linha_dadosROco[] = "Nenhuma registro";
			}
			if ($inirecOco) {
				$linha_dadosROco[] = $linha1[$indiceROco];  // cria um array com os Recados
			}
		}*/

		// Bloco de informaушes de Etapas
		/*$inirecEtapa = false;
		$qtdrecEtapa = 0;
		for ($indiceREtapa = 0; $indiceREtapa < $qtd; $indiceREtapa++) {
			if (substr($linha1[$indiceREtapa], 0, 12) == "ANOLETIV SIM") {
				$inirecEtapa = true;
				$indiceREtapa++;
			}
			if (substr($linha1[$indiceREtapa], 0, 12) == "ANOLETIV FIM") {
				$inirecEtapa = false;
			}
			if (substr($linha1[$indiceREtapa], 0, 12) == "ANOLETIV NAO") {
				$indiceREtapa++;
				$linha_dadosREtapa[] = "Nenhuma registro";
			}
			if ($inirecEtapa) {
				$linha_dadosREtapa[] = $linha1[$indiceREtapa];  // cria um array 
			}
		}*/

		// Bloco de informaушes para Infantil         
		$objetoInfantil = new Pesquisa();
		$dadosInfantil = $objetoInfantil->extrairComando($linha1, "INFANTIL", 12);
		$temInfantil = false;
		$v_msgInfantil = '';
		if (sizeof($dadosInfantil) <= 1) {
			$v_msgInfantil = trim(@$dadosInfantil[0]);
		} else {
			$temInfantil = true;
		}

		// Bloco de informaушes para Infantil (ALUNO)         
		$objetoInfantila = new Pesquisa();
		$dadosInfantila = $objetoInfantila->extrairComando($linha1, "INFANTILA", 13);
		$temInfantila = false;
		$v_msgInfantila = '';
		if (sizeof($dadosInfantila) <= 1) {
			$v_msgInfantila = trim(@$dadosInfantila[0]);
		} else {
			$temInfantila = true;
		}

		// Bloco de informaушes para AGENDA       
		$objetoAgenda = new Pesquisa();
		$dadosAgenda = $objetoAgenda->extrairComando($linha1, "AGENDA", 10);
		$temAgenda = false;
		$v_msgAgenda = '';
		if (@$dadosAgenda[0] == "Sem") {
			$v_msgAgenda = "Nenhuma informaусo para Agenda";
		} else {
			$temAgenda = true;
		}

		// Bloco de informaушes para ANOLETIVO      
		/*$objetoAnoLetivo = new Pesquisa();
		$dadosAnoLetivo = $objetoAnoLetivo->extrairComando($linha1, "ANOLETIV", 12);// caso seja ano letivo pegar do monitmen*/


		// Se arquivo esistema nсo vier com mensagem de erro
		if (!$verro) {
			// Dados do Aluno/Professor
			$v_tipo = substr(@$linha_dados1[0], 0, 1);
			$v_tipo_ct = 'N';
			$arrayTipo = explode(";", @$linha_dados1[0]);
			$tipoUsuario = "X";
			if (isset($arrayTipo[2]))
				$tipoUsuario = $arrayTipo[2];
			$v_matricula = $arrayTipo[1];
			$v_matricula_ct = 'N';
			// Grava registro no LogFile(rastreamento)
			$ipLog = $_SERVER['REMOTE_ADDR'];
			$sqlLog2 = new ConsultaSql();
			$sqlLog2->consulta("INSERT  INTO arquivolog (matricula, ip, hora, tipo, descricao, usuario) VALUES ('" . $matric . "', '" . $ipLog . "', NOW(), 'L', 'Login','" . $tipoUsuario . "')");

			$UserDao = new ConsultaSql();
            if ($v_tipo == 'A') { //falta pai,mae e resp
                $UserBd = $UserDao->resultado("SELECT * FROM `alunosws` WHERE ano=$v_ano AND matricula =" . $v_matricula);
            } else {
                $UserBd = $UserDao->resultado("SELECT *,Nascimento as nascimento,rsp_endereco as endereco,rsp_bairro as bairro, rsp_cep as cep,rsp_cidade as cidade,rsp_uf as uf,rsp_telefone as telefone,rsp_celular as celular,rsp_cpf as cpf, rsp_e_mail as aluno_e_mail FROM `funcionarios` WHERE matricula =" . $v_matricula);
            }
            if (sizeof($UserBd) > 0) {
                # code...
                $temdados = true;
            }

			// Cria variрveis com as informaушes do arquivo TXT recebido
			$v_nome = $UserBd['nome']; // novo 30/06/20
            $v_nome = $versaoPhp > 653 ? $sqlLog2->escapa($v_nome) : mysql_real_escape_string($v_nome);
            $v_endereco  = $UserBd['endereco']; //*funcionarios esta sem essa coluna
            $v_endereco = $versaoPhp > 653 ? $sqlLog2->escapa($v_endereco) : mysql_real_escape_string($v_endereco);
            $v_bairro = $UserBd['bairro'];
            $v_bairro = $versaoPhp > 653 ? $sqlLog2->escapa($v_bairro) : mysql_real_escape_string($v_bairro);
            $v_cep = $UserBd['cep'];
            $v_cep = $versaoPhp > 653 ? $sqlLog2->escapa($v_cep) : mysql_real_escape_string($v_cep);
            $v_cidade = $UserBd['cidade'];
            $v_cidade = $versaoPhp > 653 ? $sqlLog2->escapa($v_cidade) : mysql_real_escape_string($v_cidade);
            $v_uf = $UserBd['uf'];
            $v_telefone = $UserBd['telefone'];
            $v_telefone = $versaoPhp > 653 ? $sqlLog2->escapa($v_telefone) : mysql_real_escape_string($v_telefone);
            $v_celular = $UserBd['celular'];
            $v_celular = $versaoPhp > 653 ? $sqlLog2->escapa($v_celular) : mysql_real_escape_string($v_celular);
            $v_e_mail = $UserBd['aluno_e_mail'];
            $v_e_mail = $versaoPhp > 653 ? $sqlLog2->escapa($v_e_mail) : mysql_real_escape_string($v_e_mail); // Evitar SQL Injection
            $v_cpf = $UserBd['cpf'];
            $v_cpf = $versaoPhp > 653 ? $sqlLog2->escapa($v_cpf) : mysql_real_escape_string($v_cpf); // Evitar SQL Injection
            $v_sexo = $UserBd['sexo'];
            $tipoUsuario = trim(str_replace("\r\n", '', $tipoUsuario));
            $v_nascimento = $UserBd['nascimento'];
            $v_nascimento = $versaoPhp > 653 ? $sqlLog2->escapa($v_nascimento) : mysql_real_escape_string($v_nascimento);
            $escolaObjeto = new ConsultaSql();

			//$v_nome = substr(@$linha_dados1[1], 2, 70);
			//$v_nome_ct = substr(@$linha_dados1[1], 0, 1);
			$v_nome_ct = "";
			//$v_nome = mysql_real_escape_string($v_nome);
			//$v_endereco = substr(@$linha_dados1[2], 2, 50);
			//$v_endereco_ct = substr(@$linha_dados1[2], 0, 1);
			$v_endereco_ct = "";
			//$v_endereco = mysql_real_escape_string($v_endereco);
			//$v_bairro = substr(@$linha_dados1[3], 2, 30);
			//$v_bairro_ct = substr(@$linha_dados1[3], 0, 1);
			$v_bairro_ct = "";
			//$v_bairro = mysql_real_escape_string($v_bairro);
			//$v_cep = substr(@$linha_dados1[4], 2, 10);
			//$v_cep_ct = substr(@$linha_dados1[4], 0, 1);
			$v_cep_ct = "";
			//$v_cep = mysql_real_escape_string($v_cep);
			//$v_cidade = substr(@$linha_dados1[5], 2, 30);
			//$v_cidade_ct = substr(@$linha_dados1[5], 0, 1);
			$v_cidade_ct = "";
			//$v_cidade = mysql_real_escape_string($v_cidade);
			//$v_uf = substr(@$linha_dados1[6], 2, 2);
			//$v_uf_ct = substr(@$linha_dados1[6], 0, 1);
			$v_uf_ct = "";
			//$v_telefone = substr(@$linha_dados1[7], 2, 20);
			//$v_telefone_ct = substr(@$linha_dados1[7], 0, 1);
			$v_telefone_ct = "";
			//$v_telefone = mysql_real_escape_string($v_telefone);
			//$v_celular = substr(@$linha_dados1[8], 2, 20);
			//$v_celular_ct = substr(@$linha_dados1[8], 0, 1);
			$v_celular_ct = "";
			//$v_celular = mysql_real_escape_string($v_celular);
			//$v_e_mail = trim(substr(@$linha_dados1[9], 2, 50));
			//$v_e_mail_ct = substr(@$linha_dados1[9], 0, 1);
			$v_e_mail_ct = "";
			//$v_e_mail = mysql_real_escape_string($v_e_mail); // Evitar SQL Injection
//			$v_cpf = trim(substr(@$linha_dados1[10], 2, 12));
			//$v_cpf_ct = substr(@$linha_dados1[10], 0, 1);
			$v_cpf_ct = "";
			//$v_cpf = mysql_real_escape_string($v_cpf); // Evitar SQL Injection
			//$v_sexo = substr(@$linha_dados1[11], 2, 9);
			//$v_sexo_ct = substr(@$linha_dados1[11], 0, 1);
			$v_sexo_ct = "";
			//$v_nascimento = substr(@$linha_dados1[12], 2, 10);
			//$v_nascimento_ct = substr(@$linha_dados1[12], 0, 1);
			$v_nascimento_ct = "";
			//$v_nascimento = mysql_real_escape_string($v_nascimento);
			// Dados para cabeуalho do Boletim
			//$v_nome_da_escola = substr(@$linha_dados1[13], 2, 50);
			//$v_nome_da_escola_ct = substr(@$linha_dados1[13], 0, 1);
			$v_nome_da_escola_ct = "";
			//$v_codigo_escola = substr(@$linha_dados1[14], 2, 6);
			//$v_codigo_escola_ct = substr(@$linha_dados1[14], 0, 1);
			$escolaObjeto = new ConsultaSql();
            $dadosEscola = $escolaObjeto->resultado("SELECT * FROM `escolasws` ");
            // Dados para cabe?alho do Boletim
            $v_nome_da_escola = utf8_encode($dadosEscola['nome_fantasia']);
            $v_codigo_escola = $dadosEscola['codigo_escola'];
			if ($v_tipo == 'A') {
                $v_turno = $UserBd['turno'];
                $v_grau_serie = $UserBd['grau_serie'];
                $codigoGrauSerie = $UserBd['grau_serie'];
                $v_turma = $UserBd['turma'];
                $v_seq = $UserBd['sequencia'];
            } else {
                $v_turno = "";
                $v_grau_serie = "";
                $codigoGrauSerie = "";
                $v_turma = "";
                $v_seq = "";
            }

			//$v_turno = substr(@$linha_dados1[15], 2, 5);
			$v_turno_ct = "";
			//$v_grau_serie = substr(@$linha_dados1[16], 2, 36);
			$v_grau_serie_ct = "";
			//$codigoGrauSerie = substr($v_grau_serie, 0, 2);

			$tipoBoletim = "D";
			for ($i = 0; $i < count($seriesCfg); $i++) {
				$explodeCfg = $seriesCfg[$i];
				if ($explodeCfg['grau_serie'] == $codigoGrauSerie) {
					$tipoBoletim = $explodeCfg['definter'];
				}
			}

			//$v_turma = substr(@$linha_dados1[17], 2, 8);
			$v_turma_ct = "";
			//$v_seq = substr(@$linha_dados1[18], 2, 7);
			$v_seq_ct = "";
			//$v_situacao = substr(@$linha_dados1[19], 2, 11);
			$v_situacao_ct = '';
			$v_frequencia = "";
			$v_frequencia_ct = "";

			$v_etapa = $aMonitMen['etapa_digitacao'] . "-" . $aMonitMen['etapa_digitacao'] . "? Etapa";

            if ($v_tipo == 'A') {
                $escola = $v_nome_da_escola;
                //$v_ano = substr(@$linha_dados1[21], 2, 6);
                // $v_etapa = substr(@$linha_dados1[22], 2, 22);
            }
            if ($v_tipo == 'P') {
                //$dadosEtapas = explode(';', $linha_dadosREtapa[0]);  //ANOLETIV - primeira escola (sem uso 06/07/2020)
                //$v_ano = $dadosEtapas[15];              //$v_ano = substr($linha_dadosM[0],0,6); (antes erro se n?o MATERIAL)
                // $v_etapa = substr($linha_dadosM[0], 7, 22);
            }

			$v_ano_ct = "";
			$v_etapa_ct = "";
			// Dados do Responsрvel
			$v_rsp_nome = $UserBd['rsp_nome'];
            $v_rsp_nome = $versaoPhp > 653 ? $sqlLog2->escapa($v_rsp_nome) : mysql_real_escape_string($v_rsp_nome);
            $v_rsp_endereco = $UserBd['rsp_endereco'];
            $v_rsp_endereco = $versaoPhp > 653 ? $sqlLog2->escapa($v_rsp_endereco) : mysql_real_escape_string($v_rsp_endereco);
            $v_rsp_bairro = $UserBd['rsp_bairro'];
            $v_rsp_bairro = $versaoPhp > 653 ? $sqlLog2->escapa($v_rsp_bairro) : mysql_real_escape_string($v_rsp_bairro);
            $v_rsp_cep = $UserBd['rsp_cep'];
            $v_rsp_cep = $versaoPhp > 653 ? $sqlLog2->escapa($v_rsp_cep) : mysql_real_escape_string($v_rsp_cep);
            $v_rsp_cidade = $UserBd['rsp_cidade'];
            $v_rsp_cidade = $versaoPhp > 653 ? $sqlLog2->escapa($v_rsp_cidade) : mysql_real_escape_string($v_rsp_cidade);
            $v_rsp_uf = $UserBd['rsp_uf'];
            $v_rsp_telefone = $UserBd['rsp_telefone'];
            $v_rsp_telefone = $versaoPhp > 653 ? $sqlLog2->escapa($v_rsp_telefone) : mysql_real_escape_string($v_rsp_telefone);
            $v_rsp_celular = $UserBd['rsp_celular'];
            $v_rsp_celular = $versaoPhp > 653 ? $sqlLog2->escapa($v_rsp_celular) : mysql_real_escape_string($v_rsp_celular);
            $v_rsp_cpf = $UserBd['rsp_cpf'];
            $v_rsp_cpf = $versaoPhp > 653 ? $sqlLog2->escapa($v_rsp_cpf) : mysql_real_escape_string($v_rsp_cpf);
            $v_rsp_e_mail = $UserBd['rsp_e_mail'];
            $v_rsp_e_mail = $versaoPhp > 653 ? $sqlLog2->escapa($v_rsp_e_mail) : mysql_real_escape_string($v_rsp_e_mail);

			//$v_rsp_nome = substr(@$linha_dados1[23], 2, 70);
			$v_rsp_nome_ct = "";
			//$v_rsp_nome = mysql_real_escape_string($v_rsp_nome);
			//$v_rsp_endereco = substr(@$linha_dados1[24], 2, 50);
			$v_rsp_endereco_ct = "";
			//$v_rsp_endereco = mysql_real_escape_string($v_rsp_endereco);
			//$v_rsp_bairro = substr(@$linha_dados1[25], 2, 30);
			$v_rsp_bairro_ct = "";
			//$v_rsp_bairro = mysql_real_escape_string($v_rsp_bairro);
			//$v_rsp_cep = substr(@$linha_dados1[26], 2, 10);
			$v_rsp_cep_ct = "";
			//$v_rsp_cep = mysql_real_escape_string($v_rsp_cep);
			//$v_rsp_cidade = substr(@$linha_dados1[27], 2, 30);
			$v_rsp_cidade_ct = "";
			//$v_rsp_cidade = mysql_real_escape_string($v_rsp_cidade);
			//$v_rsp_uf = substr(@$linha_dados1[28], 2, 2);
			$v_rsp_uf_ct = "";
			//$v_rsp_telefone = substr(@$linha_dados1[29], 2, 20);
			$v_rsp_telefone_ct = "";
			//$v_rsp_telefone = mysql_real_escape_string($v_rsp_telefone);
			//$v_rsp_celular = substr(@$linha_dados1[30], 2, 20);
			$v_rsp_celular_ct = "";
			//$v_rsp_celular = mysql_real_escape_string($v_rsp_celular);
			//$v_rsp_cpf = substr(@$linha_dados1[31], 2, 14);
			$v_rsp_cpf_ct = "";
			//$v_rsp_cpf = mysql_real_escape_string($v_rsp_cpf);
			//$v_rsp_e_mail = trim(substr(@$linha_dados1[32], 2, 50));
			$v_rsp_e_mail_ct = "";
			//$v_rsp_e_mail = mysql_real_escape_string($v_rsp_e_mail);
			// Dados do Pai
			$v_pai_nome = $UserBd['pai_nome'];
            $v_pai_nome = $versaoPhp > 653 ? $sqlLog2->escapa($v_pai_nome) : mysql_real_escape_string($v_pai_nome);
            $v_pai_endereco = $UserBd['pai_endereco'];
            $v_pai_endereco = $versaoPhp > 653 ? $sqlLog2->escapa($v_pai_endereco) : mysql_real_escape_string($v_pai_endereco);
            $v_pai_bairro = $UserBd['pai_bairro'];
            $v_pai_bairro = $versaoPhp > 653 ? $sqlLog2->escapa($v_pai_bairro) : mysql_real_escape_string($v_pai_bairro);
            $v_pai_cep = $UserBd['pai_cep'];
            $v_pai_cep = $versaoPhp > 653 ? $sqlLog2->escapa($v_pai_cep) : mysql_real_escape_string($v_pai_cep);
            $v_pai_cidade = $UserBd['pai_cidade'];
            $v_pai_cidade = $versaoPhp > 653 ? $sqlLog2->escapa($v_pai_cidade) : mysql_real_escape_string($v_pai_cidade);
            $v_pai_uf = $UserBd['pai_uf'];
            $v_pai_telefone = $UserBd['pai_telefone'];
            $v_pai_telefone = $versaoPhp > 653 ? $sqlLog2->escapa($v_pai_telefone) : mysql_real_escape_string($v_pai_telefone);
            $v_pai_celular = substr(@$linha_dados1[40], 2, 20);
            $v_pai_celular = $versaoPhp > 653 ? $sqlLog2->escapa($v_pai_celular) : mysql_real_escape_string($v_pai_celular);
            $v_pai_cpf = $UserBd['pai_cpf'];
            $v_pai_cpf = $versaoPhp > 653 ? $sqlLog2->escapa($v_pai_cpf) : mysql_real_escape_string($v_pai_cpf);
            $v_pai_e_mail = $UserBd['pai_e_mail'];
            $v_pai_e_mail = $versaoPhp > 653 ? $sqlLog2->escapa($v_pai_e_mail) : mysql_real_escape_string($v_pai_e_mail);
            $v_mae_nome = $UserBd['mae_nome'];
            $v_mae_nome = $versaoPhp > 653 ? $sqlLog2->escapa($v_mae_nome) : mysql_real_escape_string($v_mae_nome);
            $v_mae_endereco = $UserBd['mae_endereco'];
            $v_mae_endereco = $versaoPhp > 653 ? $sqlLog2->escapa($v_mae_endereco) : mysql_real_escape_string($v_mae_endereco);
            $v_mae_bairro = $UserBd['mae_bairro'];
            $v_mae_bairro = $versaoPhp > 653 ? $sqlLog2->escapa($v_mae_bairro) : mysql_real_escape_string($v_mae_bairro);
            $v_mae_cep = $UserBd['mae_cep'];
            $v_mae_cep = $versaoPhp > 653 ? $sqlLog2->escapa($v_mae_cep) : mysql_real_escape_string($v_mae_cep);
            $v_mae_cidade = $UserBd['mae_cidade'];
            $v_mae_cidade = $versaoPhp > 653 ? $sqlLog2->escapa($v_mae_cidade) : mysql_real_escape_string($v_mae_cidade);
            $v_mae_uf = $UserBd['mae_uf'];
            $v_mae_telefone = $UserBd['mae_telefone'];
            $v_mae_telefone = $versaoPhp > 653 ? $sqlLog2->escapa($v_mae_telefone) : mysql_real_escape_string($v_mae_telefone);
            $v_mae_celular = $UserBd['mae_celular'];
            $v_mae_celular = $versaoPhp > 653 ? $sqlLog2->escapa($v_mae_celular) : mysql_real_escape_string($v_mae_celular);
            $v_mae_cpf = $UserBd['mae_cpf'];
            $v_mae_cpf = $versaoPhp > 653 ? $sqlLog2->escapa($v_mae_cpf) : mysql_real_escape_string($v_mae_cpf);
            $v_mae_e_mail = $UserBd['mae_e_mail'];
            $v_mae_e_mail = $versaoPhp > 653 ? $sqlLog2->escapa($v_mae_e_mail) : mysql_real_escape_string($v_mae_e_mail);

			if ($temdados) {
				$sqlUsuario = new ConsultaSql();
				$linha = $sqlUsuario->quantidadeLinhas("SELECT SQL_NO_CACHE * FROM alunostmp where matricula = '" . $v_matricula . "'");
				//$consulta = "SELECT SQL_NO_CACHE * FROM alunostmp where matricula = '$v_matricula'";
				//$resultado = mysql_query($consulta) or die('A consulta falhou!: ' . mysql_error());
				//comando de validaусo (verifica se a consulta ($consulta) ж verdadeira)
				//$linha = mysql_num_rows($resultado);
				if ($linha == 0) {
					$vip = $_SERVER['REMOTE_ADDR']; // Captura IP
					$vtempo = $_SERVER['REQUEST_TIME']; // Tempo inicial
					$vlogout = "1";	 // Verificaусo de saьda correta do sistema
					$v_nome = criptografa($v_nome); // Criptografa nome
					$v_telefone = criptografa($v_telefone);
					$v_celular = criptografa($v_celular);
					$v_rsp_telefone = criptografa($v_rsp_telefone);
					$v_rsp_celular = criptografa($v_rsp_celular);
					// Grava dados recebidos na tabela Mysql Alunos
					$sqlGravaAcesso = new ConsultaSql();
					$sqlGravaAcesso->consulta("INSERT  INTO alunostmp (matricula, matricula_ct, tipo, tipo_ct, nome, nome_ct, endereco, endereco_ct, bairro, bairro_ct, cep, cep_ct, cidade, cidade_ct, uf, uf_ct, telefone, telefone_ct, celular, celular_ct, e_mail, e_mail_ct, sexo, sexo_ct, nascimento, nascimento_ct, msgboletim, msgdisciplina, nome_da_escola, nome_da_escola_ct, codigo_escola, codigo_escola_ct, turno, turno_ct, grau_serie, grau_serie_ct, turma, turma_ct, seq, seq_ct, situacao, situacao_ct, frequencia, frequencia_ct, ano, ano_ct, etapa, etapa_ct, rsp_nome, rsp_nome_ct, rsp_endereco, rsp_endereco_ct, rsp_bairro, rsp_bairro_ct, rsp_cep, rsp_cep_ct, rsp_cidade, rsp_cidade_ct, rsp_uf, rsp_uf_ct, rsp_telefone, rsp_telefone_ct, rsp_celular, rsp_celular_ct, rsp_cpf, rsp_cpf_ct, rsp_e_mail, rsp_e_mail_ct, pai_nome, pai_endereco, pai_bairro, pai_cep, pai_cidade, pai_uf, pai_telefone, pai_celular, pai_cpf, pai_e_mail, mae_nome, mae_endereco, mae_bairro, mae_cep, mae_cidade, mae_uf, mae_telefone, mae_celular, mae_cpf, mae_e_mail, senha, ip, tempo, logout, login) 
					VALUES ('" . $v_matricula . "', '" . $v_matricula_ct . "', '" . $v_tipo . "', '" . $v_tipo_ct . "' ,'" . $v_nome . "' ,'" . $v_nome_ct . "', '" . $v_endereco . "', '" . $v_endereco_ct . "', '" . $v_bairro . "', '" . $v_bairro_ct . "', '" . $v_cep . "', '" . $v_cep_ct . "', '" . $v_cidade . "', '" . $v_cidade_ct . "', '" . $v_uf . "', '" . $v_uf_ct . "', '" . $v_telefone . "', '" . $v_telefone_ct . "', '" . $v_celular . "', '" . $v_celular_ct . "', '" . $v_e_mail . "', '" . $v_e_mail_ct . "', '" . $v_sexo . "', '" . $v_sexo_ct . "', '" . $v_nascimento . "', '" . $v_nascimento_ct . "', '" . $v_msgboletim . "', '" . $v_msgdisciplina . "', '" . $v_nome_da_escola . "', '" . $v_nome_da_escola_ct . "', '" . $v_codigo_escola . "', '" . $v_codigo_escola_ct . "', '" . $v_turno . "', '" . $v_turno_ct . "', '" . $v_grau_serie . "', '" . $v_grau_serie_ct . "', '" . $v_turma . "', '" . $v_turma_ct . "', '" . $v_seq . "', '" . $v_seq_ct . "', '" . $v_situacao . "', '" . $v_situacao_ct . "', '" . $v_frequencia . "', '" . $v_frequencia_ct . "', '" . @$v_ano . "', '" . $v_ano_ct . "', '" . @$v_etapa . "', '" . $v_etapa_ct . "' ,'" . $v_rsp_nome . "' ,'" . $v_rsp_nome_ct . "', '" . $v_rsp_endereco . "', '" . $v_rsp_endereco_ct . "', '" . $v_rsp_bairro . "', '" . $v_rsp_bairro_ct . "', '" . $v_rsp_cep . "', '" . $v_rsp_cep_ct . "', '" . $v_rsp_cidade . "', '" . $v_rsp_cidade_ct . "', '" . $v_rsp_uf . "', '" . $v_rsp_uf_ct . "', '" . $v_rsp_telefone . "', '" . $v_rsp_telefone_ct . "', '" . $v_rsp_celular . "', '" . $v_rsp_celular_ct . "', '" . $v_rsp_cpf . "', '" . $v_rsp_cpf_ct . "', '" . $v_rsp_e_mail . "', '" . $v_rsp_e_mail_ct . "', '" . $v_pai_nome . "' , '" . $v_pai_endereco . "', '" . $v_pai_bairro . "', '" . $v_pai_cep . "', '" . $v_pai_cidade . "', '" . $v_pai_uf . "', '" . $v_pai_telefone . "', '" . $v_pai_celular . "', '" . $v_pai_cpf . "', '" . $v_pai_e_mail . "', '" . $v_mae_nome . "' , '" . $v_mae_endereco . "', '" . $v_mae_bairro . "', '" . $v_mae_cep . "', '" . $v_mae_cidade . "', '" . $v_mae_uf . "', '" . $v_mae_telefone . "', '" . $v_mae_celular . "', '" . $v_mae_cpf . "', '" . $v_mae_e_mail . "', '********', '" . $vip . "', '" . $vtempo . "', '" . $vlogout . "', NOW())");

					//$sql = "INSERT  INTO alunostmp (matricula, matricula_ct, tipo, tipo_ct, nome, nome_ct, endereco, endereco_ct, bairro, bairro_ct, cep, cep_ct, cidade, cidade_ct, uf, uf_ct, telefone, telefone_ct, celular, celular_ct, e_mail, e_mail_ct, sexo, sexo_ct, nascimento, nascimento_ct, msgboletim, msgdisciplina, nome_da_escola, nome_da_escola_ct, codigo_escola, codigo_escola_ct, turno, turno_ct, grau_serie, grau_serie_ct, turma, turma_ct, seq, seq_ct, situacao, situacao_ct, frequencia, frequencia_ct, ano, ano_ct, etapa, etapa_ct, rsp_nome, rsp_nome_ct, rsp_endereco, rsp_endereco_ct, rsp_bairro, rsp_bairro_ct, rsp_cep, rsp_cep_ct, rsp_cidade, rsp_cidade_ct, rsp_uf, rsp_uf_ct, rsp_telefone, rsp_telefone_ct, rsp_celular, rsp_celular_ct, rsp_cpf, rsp_cpf_ct, rsp_e_mail, rsp_e_mail_ct, senha, ip, tempo, logout, login) VALUES ('$v_matricula', '$v_matricula_ct', '$v_tipo', '$v_tipo_ct' ,'$v_nome' ,'$v_nome_ct', '$v_endereco', '$v_endereco_ct', '$v_bairro', '$v_bairro_ct', '$v_cep', '$v_cep_ct', '$v_cidade', '$v_cidade_ct', '$v_uf', '$v_uf_ct', '$v_telefone', '$v_telefone_ct', '$v_celular', '$v_celular_ct', '$v_e_mail', '$v_e_mail_ct', '$v_sexo', '$v_sexo_ct', '$v_nascimento', '$v_nascimento_ct', '$v_msgboletim', '$v_msgdisciplina', '$v_nome_da_escola', '$v_nome_da_escola_ct', '$v_codigo_escola', '$v_codigo_escola_ct', '$v_turno', '$v_turno_ct', '$v_grau_serie', '$v_grau_serie_ct', '$v_turma', '$v_turma_ct', '$v_seq', '$v_seq_ct', '$v_situacao', '$v_situacao_ct', '$v_frequencia', '$v_frequencia_ct', '$v_ano', '$v_ano_ct', '$v_etapa', '$v_etapa_ct' ,'$v_rsp_nome' ,'$v_rsp_nome_ct', '$v_rsp_endereco', '$v_rsp_endereco_ct', '$v_rsp_bairro', '$v_rsp_bairro_ct', '$v_rsp_cep', '$v_rsp_cep_ct', '$v_rsp_cidade', '$v_rsp_cidade_ct', '$v_rsp_uf', '$v_rsp_uf_ct', '$v_rsp_telefone', '$v_rsp_telefone_ct', '$v_rsp_celular', '$v_rsp_celular_ct', '$v_rsp_cpf', '$v_rsp_cpf_ct', '$v_rsp_e_mail', '$v_rsp_e_mail_ct', '********', '$vip', '$vtempo', '$vlogout', NOW())";
					//$result = mysql_query($sql) or die(mysql_error());
				}
				$sqlSelectAluno = new ConsultaSql();
				$resultfinal = $sqlSelectAluno->resultado("SELECT SQL_NO_CACHE * FROM alunostmp where matricula = '" . $v_matricula . "'");
				$linha = $sqlSelectAluno->quantidadeLinhas("SELECT SQL_NO_CACHE * FROM alunostmp where matricula = '" . $v_matricula . "'");

				//$consulta = "SELECT SQL_NO_CACHE * FROM alunostmp where matricula = '$v_matricula'";
				//$resultado = mysql_query($consulta) or die('A consulta falhou!: ' . mysql_error());
				//comando de validaусo (verifica se a consulta ($consulta) ж verdadeira)
				//$linha = mysql_num_rows($resultado);
				//$resultfinal = mysql_fetch_array($resultado);
				$acesso = 'T';

				$vuser = substr(@$linha_dados1[1], 2); // Grava nome do aluno/prof na variрvel usuрrio
				@session_start();
				session_name();
				// Inicia gravaусo de dados em sessшes
				$_SESSION["gmsg"] = $linha_dadosR;     // Mensagens de correio interno
				$_SESSION["gocorre"] = @$linha_dadosO;  // OcorrЖncias
				//$_SESSION["gocopro"] = @$linha_dadosROco;  // OcorrЖncias onLine
				//$_SESSION["getapas"] = @$linha_dadosREtapa; // Etapas para ocorrЖncias
				$_SESSION["gqtdmsg"] = $qtdrec;        // Quantidade de mensagens

				// Etapa por Sжries (16/10/2013)
				//$_SESSION["gSeriesConfig"] = @$seriesCfg; // sem uso
				// Mensagem INFANTIL NAO  (17/10/2013)
				$_SESSION["gMensagemInfantil"] = @$v_msgInfantil;
				// Para Menu digitaушes Normal e Infantil 
				$_SESSION["gMaterial"] = @$linha_dadosM;
				//*****************************

				//$_SESSION["glinks"] = $linha_dadosL;   // Links sem uso 2021
				$_SESSION["glinks"] ="";

				$_SESSION["galtdig"] = "F";
				$_SESSION["gviacesso"] = "T";
				$_SESSION["glogout"] = "1";

				$_SESSION["gcaminhologo"] = $caminhologo;     // Caminho do logotipo da escola
				$_SESSION["gcaminhofotos"] = $caminhofotos;   // Caminho das fotos dos alunos/profs
				$_SESSION["gversaoEsistema"] = $versaoEsistema;  // Versсo do Esistema
				$_SESSION["gTipoBoletim"] = $tipoBoletim;

				$_SESSION["gmenu"] = "";            // Opушes do menu
				$_SESSION["gboletim"] = @$linha_dadosB;        // Informaушes do boletim
				$_SESSION["gboleto"] = @$linha_dadosBol;       // Informaушes do boleto
				$_SESSION["gboletocab"] = @$linha_cabBol;      // Cabeуalho do boleto
				$_SESSION["gboletoFooter"] = @$footerBoleto;      // Cabeуalho do boleto
				$_SESSION["gNomeDaEscola"] = @$v_nome_da_escola; // Nome da Escola
				//$_SESSION["gCodigoDaEscola"] = @$v_codigo_escola; // Cзdigo da Escola
				if ($v_tipo == 'P') {
					$_SESSION["gCodigoDaEscola"] = @$codigoDaEscola; // Cзdigo da Escola
				}
				if ($v_tipo == 'A') {
					$_SESSION["gCodigoDaEscola"] = @$v_codigo_escola; // Cзdigo da Escola
				}
				// ENQUETE
				$_SESSION["gEnquete"] = @$linha_Enq;           // Informaушes da enquete
				$_SESSION["gEnqmsg"]  = @$v_msgEnq;            // Mensagem sobre enquete
				$_SESSION["gtextoEnq"] = @$textoEnq;           // Texto customizado para tela de enquete
				$_SESSION["gquestao"] = @$qtdQuestao;

				$_SESSION["gacesso"] = $acesso;               // Se pode acessar
				$_SESSION["gmatric"] = $matric;               // Matrьcula
				$_SESSION["gsenha"] = $esenha;                // Senha
				$_SESSION["getapa"] = @$v_etapa;               // Etapa
				$_SESSION["ganoletivo"] = $v_ano;		// Bloco ANOLETIVO
				$_SESSION["gano"] = @$v_ano;                   // Ano
				$_SESSION["guser"] = $vuser;                  // Usuрrio
				$_SESSION["gtipo"] = $v_tipo;                 // Tipo de usuрrio(Aluno ou Professor)
				$_SESSION["gtipoUsuario"] = $tipoUsuario;     // Tipo de usuрrio(Aluno, Pai, Mсe ou Responsрvel)
				$_SESSION["gtempo"] = $_SERVER['REQUEST_TIME'];  // Tempo inicial
				$_SESSION["gip"] = $_SERVER['REMOTE_ADDR'];      // IP remoto
				//$_SESSION["gmedia"] = @$mediasAnteriores;
				$_SESSION["gcontrole"] = $controle;              // NЩmero de acesso "vago"

				// Se for Aluno ou Professor (F para opусo Admin)
				if ($v_tipo == "A" || $v_tipo == "P" || $v_tipo == "F") {
					$_SESSION["lmatric"] = $matric;
				}

				$_SESSION["importa_livros"] = false;

				$codigoEscolaBol = str_replace("Esc:", "", $v_codigo_escola);
				$codigoEscolaBol = trim($codigoEscolaBol);
				$caminhologoBol = str_replace("Logo_da_Escola.bmp", "Logo_da_Escola" . $codigoEscolaBol . ".bmp", $caminhologo);
				// Copia logo para uma pasta acessьvel via HTML 
				//Origem
				$delogo = $caminhologoBol;

				//Se a foto existir na origem 
				if (file_exists($delogo)) {
					//Destino
					$res = ImageCreateFromBMP($delogo);
					$paralogo = 'imagens/logo_da_escola' . $codigoEscolaBol . '.jpg';
					imagejpeg(@$res, @$paralogo);
					//Copia se ainda nсo existir no destino
					if (!file_exists(@$paralogo)) {
						copy(@$delogo, @$paralogo);
					}
					//uso: Image(caminho, posicao X,posicao Y,altura, largura)		
				}
				// copia a foto do aluno ou professor para uma pasta acessьvel via HTML 
				// Origem
				$defoto = $caminhofotos . $matric . '.bmp';

				//Destino
				//Se a foto existir na origem 
				if (file_exists($defoto) && !empty($matric)) {
					$res2 = ImageCreateFromBMP($defoto);
					$parafoto = 'imagens/fotos/' . $matric . '.jpg';
					imagejpeg($res2, $parafoto);
					//Copia se ainda nсo existir no destino

					if (file_exists($defoto) && !file_exists($parafoto)) {
						copy($defoto, $parafoto);
					}
				}
			} else {
				$acesso = 'F';  // Acesso nсo permitido. Volta para a tela de Login
				@session_start();
				session_name();
				$_SESSION["gviacesso"] = "F";
				$_SESSION["gacesso"] = "F";
			}

			if ($temnotas) {
				// Criaусo de variрveis com informaушes do arquivo TXT recebido
				// Notas para digitaусo
				$sqlSelectNotas = new ConsultaSql();
				$resultfinal = $sqlSelectNotas->resultado("SELECT SQL_NO_CACHE * FROM notastmp where matricula = '" . $v_matricula . "'");
				$linha = $sqlSelectNotas->quantidadeLinhas("SELECT SQL_NO_CACHE * FROM notastmp where matricula = '" . $v_matricula . "'");

				if ($linha == 0) {
					$qtdNotas = sizeof($linha_dadosN);
					for ($iNotas = 1; $iNotas < $qtdNotas; $iNotas++) {
						$explodeNotas = explode(';', $linha_dadosN[$iNotas]);
						$v_codigo_escola = $explodeNotas[0];
						$v_codigo_serie = $explodeNotas[1];
						$v_codigo_turno = $explodeNotas[2];
						$v_codigo_turma = $explodeNotas[3];
						$v_codigo_disciplina = $explodeNotas[4];
						$v_tipo_linha = $explodeNotas[5];
						$v_sequencia  = $explodeNotas[6];
						$v_matricalu  = $explodeNotas[7];
						$v_serie      = $explodeNotas[8];
						$v_disciplina = $explodeNotas[9];
						$v_nome       = $explodeNotas[10];
						$v_status     = $explodeNotas[11];
						$v_nota1_ct   = $explodeNotas[12];
						$v_nota1      = $explodeNotas[13];
						$v_nota2_ct   = $explodeNotas[14];
						$v_nota2      = $explodeNotas[15];
						$v_nota3_ct   = $explodeNotas[16];
						$v_nota3      = $explodeNotas[17];
						$v_nota4_ct   = $explodeNotas[18];
						$v_nota4      = $explodeNotas[19];
						$v_nota5_ct   = $explodeNotas[20];
						$v_nota5      = $explodeNotas[21];
						$v_nota6_ct   = $explodeNotas[22];
						$v_nota6      = $explodeNotas[23];
						$v_nota7_ct   = $explodeNotas[24];
						$v_nota7      = $explodeNotas[25];
						$v_nota8_ct   = $explodeNotas[26];
						$v_nota8      = $explodeNotas[27];
						$v_nota9_ct   = $explodeNotas[28];
						$v_nota9      = $explodeNotas[29];
						$v_notarp_ct  = $explodeNotas[30];
						$v_notarp     = $explodeNotas[31];
						$v_notape_ct  = $explodeNotas[32];
						$v_notape     = $explodeNotas[33];
						$v_faltas_ct  = $explodeNotas[34];
						$v_faltas     = $explodeNotas[35];

						// Criptografa matrьcula e nome do aluno
						$v_matricalu = criptografa($v_matricalu);
						$v_nome = criptografa($v_nome);
						$vtempo = $_SERVER['REQUEST_TIME']; // Tempo inicial
						// Grava informaушes na tabela Notas
						$sqlInserirNotas = new ConsultaSql();
						$sqlInserirNotas->consulta("INSERT INTO notastmp (matricula, codigo_escola, codigo_serie, codigo_turno, codigo_turma, codigo_disciplina, tipo_linha, sequencia, matricalu, serie, nome , disciplina, status ,nota1_ct, nota1, nota2_ct, nota2, nota3_ct, nota3, nota4_ct, nota4, nota5_ct, nota5, nota6_ct, nota6, nota7_ct, nota7, nota8_ct, nota8, nota9_ct, nota9, notarp_ct, notarp, notape_ct, notape, faltas_ct, faltas, tempo) VALUES ('" . $v_matricula . "', '" . $v_codigo_escola . "', '" . $v_codigo_serie . "', '" . $v_codigo_turno . "', '" . $v_codigo_turma . "', '" . $v_codigo_disciplina . "', '" . $v_tipo_linha . "', '" . $v_sequencia . "', '" . $v_matricalu . "', '" . $v_serie . "', '" . $v_nome . "', '" . $v_disciplina . "', '" . $v_status . "', '" . $v_nota1_ct . "', '" . $v_nota1 . "', '" . $v_nota2_ct . "', '" . $v_nota2 . "', '" . $v_nota3_ct . "', '" . $v_nota3 . "', '" . $v_nota4_ct . "', '" . $v_nota4 . "', '" . $v_nota5_ct . "', '" . $v_nota5 . "', '" . $v_nota6_ct . "', '" . $v_nota6 . "', '" . $v_nota7_ct . "', '" . $v_nota7 . "', '" . $v_nota8_ct . "', '" . $v_nota8 . "', '" . $v_nota9_ct . "', '" . $v_nota9 . "', '" . $v_notarp_ct . "', '" . $v_notarp . "', '" . $v_notape_ct . "', '" . $v_notape . "', '" . $v_faltas_ct . "', '" . $v_faltas . "', '" . $vtempo . "')");

						$acesso = 'T';
					}
				}
			}

			if ($temMedias) {
				// Criaусo de variрveis com informaушes do arquivo TXT recebido

				$sqlSelectMedias = new ConsultaSql();
				$resultfinal = $sqlSelectMedias->resultado("SELECT SQL_NO_CACHE * FROM mediastmp where matricula = '" . $v_matricula . "'");
				$linha = $sqlSelectMedias->quantidadeLinhas("SELECT SQL_NO_CACHE * FROM mediastmp where matricula = '" . $v_matricula . "'");

				if ($linha == 0) {
					$qtdMedias = sizeof($dadosMedias);
					$ano   = "";
					$etapa = "";
					for ($iMedias = 1; $iMedias < $qtdMedias; $iMedias++) {

						$explodeMedias = explode(';', $dadosMedias[$iMedias]);
						if ($explodeMedias[5] == "M") {
							$ano   = $explodeMedias[10];
							$etapa = $explodeMedias[11];
						} else {
							$v_codigo_escola = $explodeMedias[0];
							$v_codigo_serie = $explodeMedias[1];
							$v_codigo_turno = $explodeMedias[2];
							$v_codigo_turma = $explodeMedias[3];
							$v_codigo_disciplina = $explodeMedias[4];
							$v_tipo_linha = $explodeMedias[5];
							$v_sequencia  = $explodeMedias[6];
							$v_matricalu  = $explodeMedias[7];
							$v_serie      = $explodeMedias[8];
							$v_disciplina = $explodeMedias[9];
							$v_nome       = $explodeMedias[10];
							$v_status     = $explodeMedias[11];
							$v_media01    = $explodeMedias[12];
							$v_media02    = $explodeMedias[13];
							$v_media03    = $explodeMedias[14];
							$v_media04    = $explodeMedias[15];
							$v_media05    = $explodeMedias[16];
							$v_media06    = $explodeMedias[17];
							$v_media07    = $explodeMedias[18];
							$v_media08    = $explodeMedias[19];
							$v_media09    = $explodeMedias[20];
							$v_media10    = $explodeMedias[21];
							$v_media11    = $explodeMedias[22];
							$v_media12    = $explodeMedias[23];
							$v_media13    = $explodeMedias[24];
							$v_media14    = $explodeMedias[25];

							// Criptografa matrьcula e nome do aluno
							$v_matricalu = criptografa($v_matricalu);
							$v_nome = criptografa($v_nome);
							$vtempo = $_SERVER['REQUEST_TIME']; // Tempo inicial
							// Grava informaушes na tabela Notas
							$sqlInserirMedias = new ConsultaSql();
							$sqlInserirMedias->consulta("INSERT  INTO mediastmp (ano, etapa, matricula, codigo_escola, codigo_serie, codigo_turno, codigo_turma, codigo_disciplina, tipo_linha, sequencia, matricalu, serie, nome , disciplina, status , media01, media02, media03, media04, media05, media06, media07, media08, media09, media10, media11, media12, media13, media14, tempo) VALUES ('" . $ano . "', '" . $etapa . "', '" . $v_matricula . "', '" . $v_codigo_escola . "', '" . $v_codigo_serie . "', '" . $v_codigo_turno . "', '" . $v_codigo_turma . "', '" . $v_codigo_disciplina . "', '" . $v_tipo_linha . "', '" . $v_sequencia . "', '" . $v_matricalu . "', '" . $v_serie . "', '" . $v_nome . "', '" . $v_disciplina . "', '" . $v_status . "', '" . $v_media01 . "', '" . $v_media02 . "', '" . $v_media03 . "', '" . $v_media04 . "', '" . $v_media05 . "', '" . $v_media06 . "', '" . $v_media07 . "', '" . $v_media08 . "', '" . $v_media09 . "', '" . $v_media10 . "', '" . $v_media11 . "', '" . $v_media12 . "', '" . $v_media13 . "', '" . $v_media14 . "', '" . $vtempo . "')");
							$acesso = 'T';
						}
					}
				}
			}
			if ($temnotas) {   // Observaушes
				// Criaусo de variрveis com informaушes do arquivo TXT recebido
				$sqlLimparObs = new ConsultaSql();
				$sqlLimparObs->consulta("DELETE FROM obstmp WHERE observacao='' AND matricula = '" . $v_matricula . "'");
				$sqlSelectObs = new ConsultaSql();

				$qtdNotas = sizeof($linha_dadosN);
				for ($iNotas = 1; $iNotas < $qtdNotas; $iNotas++) {
					$explodeNotas = explode(';', $linha_dadosN[$iNotas]);
					$v_codigo_escola = $explodeNotas[0];
					$v_codigo_serie = $explodeNotas[1];
					$v_codigo_turno = $explodeNotas[2];
					$v_codigo_turma = $explodeNotas[3];
					$v_codigo_disciplina = $explodeNotas[4];
					$v_tipo_linha = $explodeNotas[5];
					$v_sequencia  = $explodeNotas[6];
					$v_matricalu  = $explodeNotas[7];
					$v_serie      = $explodeNotas[8];
					$v_disciplina = $explodeNotas[9];
					$v_nome       = $explodeNotas[10];
					if ($v_tipo_linha == "D") {
						// Criptografa matrьcula e nome do aluno
						$v_matricalu = criptografa($v_matricalu);
						$v_nome = criptografa($v_nome);
						$linha = $sqlSelectObs->quantidadeLinhas("SELECT SQL_NO_CACHE * FROM obstmp where matricula = '" . $v_matricula . "' AND matricalu = '" . $v_matricalu . "'");
						if ($linha == 0) {
							// Grava informaушes na tabela Obs
							$sqlInserirObs = new ConsultaSql();
							$sqlInserirObs->consulta("INSERT INTO obstmp (matricula, codigo_escola, codigo_serie, codigo_turno, codigo_turma, codigo_disciplina, sequencia, matricalu, serie, nome , disciplina) VALUES ('" . $v_matricula . "', '" . $v_codigo_escola . "', '" . $v_codigo_serie . "', '" . $v_codigo_turno . "', '" . $v_codigo_turma . "', '" . $v_codigo_disciplina . "', '" . $v_sequencia . "', '" . $v_matricalu . "', '" . $v_serie . "', '" . $v_nome . "', '" . $v_disciplina . "')");
						}
						//							else {
						//								$sqlAlterarObs = new ConsultaSql();
						//								$sqlAlterarObs->consulta("UPDATE obstmp SET sequencia = '".$v_sequencia."', nome = '".$v_nome."' WHERE matricula = '".$v_matricula."' AND matricalu = '".$v_matricalu."'");
						//							}
					}
				}
			}
			/*if ($temMaterial) {

				$sqlSelectMaterial = new ConsultaSql();
				$resultfinal = $sqlSelectMaterial->resultado("SELECT SQL_NO_CACHE * FROM notastmp where tipo_linha='M' and matricula = '" . $v_matricula . "'");
				$linha = $sqlSelectMaterial->quantidadeLinhas("SELECT SQL_NO_CACHE * FROM notastmp where tipo_linha='M' and matricula = '" . $v_matricula . "'");

				if ($linha == 0) {
					$qtdMaterial = sizeof($linha_dadosM);
					for ($iMaterial = 1; $iMaterial < $qtdMaterial; $iMaterial++) {
						$explodeMaterial = explode(';', $linha_dadosM[$iMaterial]);
						$v_codigo_escola = $explodeMaterial[0];
						$v_codigo_serie = $explodeMaterial[1];
						$v_codigo_turno = $explodeMaterial[2];
						$v_codigo_turma = $explodeMaterial[3];
						$v_codigo_disciplina = $explodeMaterial[4];
						$v_tipo_linha = $explodeMaterial[5];
						$v_sequencia  = $explodeMaterial[6];
						$v_matricalu  = $explodeMaterial[7];
						$v_serie      = $explodeMaterial[8];
						$v_disciplina = $explodeMaterial[9];

						// Criptografa matrьcula e nome do aluno
						$v_matricalu = criptografa($v_matricalu);

						$vtempo = $_SERVER['REQUEST_TIME']; // Tempo inicial
						// Grava informaушes na tabela Notas
						if (trim($v_codigo_escola) != '') {
							$sqlInserirMaterial = new ConsultaSql();
							$sqlInserirMaterial->consulta("INSERT INTO notastmp (matricula, codigo_escola, codigo_serie, codigo_turno, codigo_turma, codigo_disciplina, tipo_linha, sequencia, matricalu, serie, disciplina, tempo) VALUES ('" . $v_matricula . "', '" . $v_codigo_escola . "', '" . $v_codigo_serie . "', '" . $v_codigo_turno . "', '" . $v_codigo_turma . "', '" . $v_codigo_disciplina . "', '" . $v_tipo_linha . "', '" . $v_sequencia . "', '" . $v_matricalu . "', '" . $v_serie . "', '" . $v_disciplina . "', '" . $vtempo . "')");
						}
						$acesso = 'T';
					}
				}
			}*/
			if ($temEnq) {
				// Cria de variрveis com conteЩdo da tabela Enquete
				$sqlEnquete = new ConsultaSql();
				$linhaEnq = $sqlEnquete->quantidadeLinhas("SELECT SQL_NO_CACHE * FROM enquetestmp where matricula = '" . $v_matricula . "'");

				if ($linhaEnq == 0) {
					$qtdEnq = sizeof($linha_Enq);
					$v_q = false;
					$sqlEnquete    = new ConsultaSql();
					$sqlComentario = new ConsultaSql();
					for ($iEnq = 0; $iEnq < $qtdEnq; $iEnq++) {
						if ($iEnq == 0) {
							$v_numero = substr($linha_Enq[$iEnq], 0, 4);
						}
						if ($iEnq == 1) {
							// Verifica as opушes(carinhas) que estсo habilitadas na enquete
							$op5 = substr($linha_Enq[$iEnq], 0, 20);
							// Exemplo: Muito Satisfeito
							if ($op5 == '                    ') {
								$v_opcao5 = "0";
							} else {
								$v_opcao5 = "1";
							}
							$op4 = substr($linha_Enq[$iEnq], 21, 20);
							// Exemplo: Satisfeito
							if ($op4 == '                    ') {
								$v_opcao4 = "0";
							} else {
								$v_opcao4 = "1";
							}
							$op3 = substr($linha_Enq[$iEnq], 42, 20);
							// 
							if ($op3 == '                    ') {
								$v_opcao3 = "0";
							} else {
								$v_opcao3 = "1";
							}
							$op2 = substr($linha_Enq[$iEnq], 63, 20);
							// Exemplo: Insatisfeito
							if ($op2 == '                    ') {
								$v_opcao2 = "0";
							} else {
								$v_opcao2 = "1";
							}
							$op1 = substr($linha_Enq[$iEnq], 84, 20);
							// Exemplo: Muito insatisfeito
							if ($op1 == '                    ') {
								$v_opcao1 = "0";
							} else {
								$v_opcao1 = "1";
							}
						}
						//  Grava em variрveis cabeуalhos e enunciados das questшes
						if (substr($linha_Enq[$iEnq], 0, 7) == "QUESTAO") {
							$v_q = true;
							$iEnq++;

							/*$_SESSION["gquestao2"] = (int)substr($linha_Enq[$iEnq],0,2);*/
							$v_cabecalho = substr($linha_Enq[$iEnq], 3, 80);
							$v_texto = '';
							$iEnq++;
							while (substr($linha_Enq[$iEnq], 0, 9) != "TEXTO FIM") {
								$v_texto .= $linha_Enq[$iEnq++];
							}
							$iEnq++;
						}
						// Opушes da questсo
						if (substr($linha_Enq[$iEnq], 0, 8) == "ITEM SIM") {
							$iEnq++;
						}
						if (substr($linha_Enq[$iEnq], 0, 8) == "ITEM FIM") {
							$v_q = false;
						}
						if (substr($linha_Enq[$iEnq], 0, 8) == "ITEM NAO") {
							$v_q = false;
						}
						if ($v_q) {
							$v_questao = substr($linha_Enq[$iEnq], 14, 2);
							$v_sequencia = substr($linha_Enq[$iEnq], 17, 2);
							$v_professor = substr($linha_Enq[$iEnq], 20, 8);
							$v_disciplina = substr($linha_Enq[$iEnq], 29, 7);
							$v_nitem = substr($linha_Enq[$iEnq], 37, 2);
							$v_item = substr($linha_Enq[$iEnq], 40, 80);
							$v_resposta = substr($linha_Enq[$iEnq], 121, 1);
							$vtempo = $_SERVER['REQUEST_TIME']; // Tempo inicial
							// grava na tabela Enquetes
							$sqlEnquete->consulta("INSERT  INTO enquetestmp (numero, questao, sequencia, professor, disciplina, nitem, item, texto, cabecalho, opcao1, opcao2, opcao3, opcao4, opcao5, alt1, alt2, alt3, alt4, alt5,resposta, matricula, tempo) VALUES ('" . $v_numero . "', '" . $v_questao . "', '" . $v_sequencia . "', '" . $v_professor . "', '" . $v_disciplina . "', '" . $v_nitem . "', '" . $v_item . "', '" . $v_texto . "', '" . $v_cabecalho . "', '" . $v_opcao1 . "', '" . $v_opcao2 . "', '" . $v_opcao3 . "', '" . $v_opcao4 . "', '" . $v_opcao5 . "', '" . $op1 . "', '" . $op2 . "', '" . $op3 . "', '" . $op4 . "', '" . $op5 . "', '" . $v_resposta . "', '" . $v_matricula . "', '" . $vtempo . "')");
							//$sqlEnq = "INSERT  INTO enquetestmp (numero, questao, sequencia, professor, disciplina, nitem, item, texto, cabecalho, opcao1, opcao2, opcao3, opcao4, opcao5, alt1, alt2, alt3, alt4, alt5,resposta, matricula, tempo) VALUES ('$v_numero', '$v_questao', '$v_sequencia', '$v_professor', '$v_disciplina', '$v_nitem', '$v_item', '$v_texto', '$v_cabecalho', '$v_opcao1', '$v_opcao2', '$v_opcao3', '$v_opcao4', '$v_opcao5', '$op1', '$op2', '$op3', '$op4', '$op5', '$v_resposta', '$v_matricula', '$vtempo')";
							//$resultEnq = mysql_query($sqlEnq) or die(mysql_error());
						}
						$noItem = false;
						if (substr($linha_Enq[$iEnq], 0, 8) == "ITEM NAO") {
							$iEnq++;
							$noItem = true;
						}
						if (substr($linha_Enq[$iEnq], 0, 14) == "COMENTARIO SIM") {
							$iEnq++;
							if ($noItem) {

								$v_questao = substr($linha_Enq[$iEnq], 14, 2);
								$v_sequencia = "";
								$v_professor = "00000000";
								$v_disciplina = "  ";
								$v_texto = substr($linha_Enq[$iEnq], 17, 80);
								$vtempo = $_SERVER['REQUEST_TIME']; // Tempo inicial

								$sqlEnquete->consulta("INSERT  INTO enquetestmp (numero, questao, sequencia, professor, disciplina, matricula, tempo) VALUES ('" . $v_numero . "', '" . $v_questao . "', '" . $v_sequencia . "', '" . $v_professor . "', '" . $v_disciplina . "', '" . $v_matricula . "','" . $vtempo . "')");
								//$sqlEnq = "INSERT  INTO enquetestmp (numero, questao, sequencia, professor, disciplina, matricula, tempo) VALUES ('$v_numero', '$v_questao', '$v_sequencia', '$v_professor', '$v_disciplina', '$v_matricula','$vtempo')";
								//$resultEnq = mysql_query($sqlEnq) or die(mysql_error());
							}
							$v_numero_enq = substr($linha_Enq[$iEnq], 9, 4);
							$v_questao_enq = substr($linha_Enq[$iEnq], 14, 2);
							$v_texto = substr($linha_Enq[$iEnq], 17, 80);
							$v_comentario = mysql_real_escape_string(substr($linha_Enq[$iEnq], 98));
							//$v_comentario = str_replace('"','┤',$v_comentario);
							//$v_comentario = str_replace("'","┤",$v_comentario);
							$vtempo = $_SERVER['REQUEST_TIME']; // Tempo inicial
							$sqlComentario->consulta("INSERT  INTO comentstmp (matricula, numero_enq, questao_enq, texto, comentario, tempo) VALUES ('" . $v_matricula . "', '" . $v_numero_enq . "', '" . $v_questao_enq . "', '" . $v_texto . "', '" . $v_comentario . "', '" . $vtempo . "')");
							//$sqlCom = "INSERT  INTO comentstmp (matricula, numero_enq, questao_enq, texto, comentario, tempo) VALUES ('$v_matricula', '$v_numero_enq', '$v_questao_enq', '$v_texto', '$v_comentario', '$vtempo')";
							//$resultCom = mysql_query($sqlCom) or die(mysql_error());
						}
					}
					$acesso = 'T'; // Acesso autorizado
				}
			}

			if ($temescolas) {
                // Escolas
                // Verifica se j? existe a escola na tabela Mysql
                $sqlEscola = new ConsultaSql();
                $linha = $sqlEscola->quantidadeLinhas("SELECT SQL_NO_CACHE * FROM escolasws where matricula = '" . $v_matricula . "'");

                if ($linha == 0) {

                    for ($i = 0; $i < sizeof($dadosEscolas); $i++) {
                        # code...
                        $escolasloop = $dadosEscolas[$i];
                        $v_codigo = $escolasloop['codigo_escola'];
                        $v_razao_social = utf8_encode($escolasloop['razao_social']);
                        $v_nome_fantasia = utf8_encode($escolasloop['nome_fantasia']);
                        $v_cgc = $escolasloop['cnpj'];
                        $v_endereco = $escolasloop['endereco'];
                        $v_bairro = $escolasloop['bairro'];
                        $v_cep = $escolasloop['cep'];
                        $v_cidade = $escolasloop['cidade'];
                        $v_uf = $escolasloop['uf'];
                        $v_telefone1 = $escolasloop['telefone'];
                        $v_telefone2 = $escolasloop['telefone'];
                        $v_fax = '';
                        $v_e_mail = $escolasloop['email'];
                        $v_diretor = '';
                        $v_inscr_diretor = '';
                        $v_secretario = '';
                        $v_inscr_secret = '';
                        $vtempo = $_SERVER['REQUEST_TIME']; // Tempo inicial
                        // Grava informa??es na tabela Escolas
                        $sqlGravaEscola = new ConsultaSql();
                        $v_nome_fantasia = str_replace("'", "\'", $v_nome_fantasia);
                        $sqlGravaEscola->consulta("INSERT  INTO escolastmp (codigo, razao_social, nome_fantasia, cgc, endereco, bairro, cep, cidade, uf, telefone1, telefone2, fax, e_mail, diretor, inscr_diretor, secretario, inscr_secret, matricula, tempo) VALUES ('" . $v_codigo . "', '" . $v_razao_social . "', '" . $v_nome_fantasia . "', '" . $v_cgc . "', '" . $v_endereco . "', '" . $v_bairro . "', '" . $v_cep . "', '" . $v_cidade . "', '" . $v_uf . "', '" . $v_telefone1 . "', '" . $v_telefone2 . "', '" . $v_fax . "', '" . $v_e_mail . "', '" . $v_diretor . "', '" . $v_inscr_diretor . "', '" . $v_secretario . "', '" . $v_inscr_secret . "', '" . $v_matricula . "', '" . $vtempo . "')");
                        //$sqlE = "INSERT  INTO escolastmp (codigo, razao_social, nome_fantasia, cgc, endereco, bairro, cep, cidade, uf, telefone1, telefone2, fax, e_mail, diretor, inscr_diretor, secretario, inscr_secret, matricula, tempo) VALUES ('$v_codigo', '$v_razao_social', '$v_nome_fantasia', '$v_cgc', '$v_endereco', '$v_bairro', '$v_cep', '$v_cidade', '$v_uf', '$v_telefone1', '$v_telefone2', '$v_fax', '$v_e_mail', '$v_diretor', '$v_inscr_diretor', '$v_secretario', '$v_inscr_secret', '$v_matricula', '$vtempo')";
                        //$resultE = mysql_query($sqlE) or die(mysql_error());
                        $acesso = 'T'; // Acesso autorizado
                        // }
                    }
                }
            }

			if ($temInfantil) {
				$sqlInfantil = new ConsultaSql();
				$linha = $sqlInfantil->quantidadeLinhas("SELECT SQL_NO_CACHE * FROM infantiltmp where matricula_prof = '" . $v_matricula . "'");

				//if ($linha == 0){
				$qtdInfantil = sizeof($dadosInfantil);
				// Cria de variрveis com informaушes do arquivo TXT recebido
				for ($iInfantil = 0; $iInfantil < $qtdInfantil; $iInfantil++) {
					$campos = explode(";", $dadosInfantil[$iInfantil]);
					if ($campos[0] == "HEADER") {
						$sqlGravaInfantil = new ConsultaSql();
						$sqlGravaInfantil->consulta("INSERT  INTO infantiltmp (matricula_prof, escola, serie, turno, turma, disciplina, disciplina_nome, ano, etapa) VALUES ('" . $v_matricula . "', '" . $campos[1] . "', '" . $campos[2] . "', '" . $campos[3] . "', '" . $campos[4] . "', '" . $campos[5] . "', '" . $campos[6] . "', '" . $campos[7] . "', '" . $campos[8] . "')");
					}
					if ($campos[0] == "RAALUNO") {
						usleep(500);
						$sqlUltimoInfantil = new ConsultaSql();
						$pesquisaUltimo = $sqlUltimoInfantil->resultado("SELECT MAX(id) FROM infantiltmp where matricula_prof = '" . $v_matricula . "'");
						$ultimo = $pesquisaUltimo[0];
						$sqlInfantilAluno = new ConsultaSql();
						$sqlInfantilAluno->consulta("INSERT  INTO infantil_alunotmp (id_infantil, matricula_prof, matricula_aluno, nome_aluno, texto1, texto2, texto3, texto4, texto5, texto6, texto7, texto8, conclusao, recno, sequencial, nascimento, idade) VALUES 
							                                                                (" . $ultimo . ", '" . $v_matricula . "', '" . $campos[1] . "', '" . addslashes($campos[2]) . "', '" . addslashes($campos[7]) . "', '" . addslashes($campos[8]) . "', '" . addslashes($campos[9]) . "', '" . addslashes($campos[10]) . "', '" . addslashes($campos[11]) . "', '" . addslashes($campos[12]) . "', '" . addslashes($campos[13]) . "', '" . addslashes($campos[14]) . "', '" . addslashes($campos[6]) . "', " . $campos[15] . ", " . $campos[16] . ", '" . $campos[17] . "', '" . $campos[18] . "')");
						$ultimoAluno = $campos[1];
					}
					/*
						if ($campos[0] == "RAAREA") {
							$sqlInfantilArea = new ConsultaSql();
							$sqlInfantilArea->consulta("INSERT  INTO infantil_areatmp (matricula_aluno, matricula_prof, area, texto, recno) VALUES ('".$ultimoAluno."', '".$v_matricula."', ".addslashes($campos[5]).", '".addslashes($campos[6])."', ".$campos[7].")");
							$ultimaArea = $campos[5];
						}
						if ($campos[0] == "RATOPICO") {
							$sqlInfantilTopico = new ConsultaSql();
							$sqlInfantilTopico->consulta("INSERT  INTO infantil_topicotmp (matricula_aluno, matricula_prof, id_area, topico, avaliacao_etapa1, avaliacao_etapa2, avaliacao_etapa3, avaliacao_etapa4, avaliacao_etapa5, avaliacao_etapa6, avaliacao_etapa7, avaliacao_etapa8, recno) VALUES ('".$ultimoAluno."', '".$v_matricula."', ".$ultimaArea.", '".$campos[6]."', '".$campos[7]."', '".$campos[8]."', '".$campos[9]."', '".$campos[10]."', '".$campos[11]."', '".$campos[12]."', '".$campos[13]."', '".$campos[14]."', ".$campos[15].")");
						}
						*/
					if ($campos[0] == "RAVALSER") {
						$sqlInfantilRavalser = new ConsultaSql();
						$sqlInfantilRavalser->consulta("INSERT  INTO infantil_ravalsertmp (matricula_prof, grau_serie, titulo, fotografia, texto_legenda1, texto_legenda2, texto_legenda3, texto_legenda4, texto_legenda5, texto_legenda6, texto_legenda7, texto_legenda8, texto_legenda9, texto_legenda10, texto_etapa1, texto_etapa2, texto_etapa3, texto_etapa4, texto_etapa5, texto_etapa6, texto_etapa7, texto_etapa8, legenda) VALUES ('" . $v_matricula . "','" . str_pad($campos[1], 2, "0", STR_PAD_LEFT) . "','" . $campos[2] . "','" . $campos[3] . "','" . $campos[4] . "','" . $campos[5] . "','" . $campos[6] . "', '" . $campos[7] . "', '" . $campos[8] . "', '" . $campos[9] . "', '" . $campos[10] . "', '" . $campos[11] . "', '" . $campos[12] . "', '" . $campos[13] . "', '" . $campos[14] . "', '" . $campos[15] . "', '" . $campos[16] . "', '" . $campos[17] . "', '" . $campos[18] . "', '" . $campos[19] . "', '" . $campos[20] . "', '" . $campos[21] . "', '" . $campos[22] . "')");
					}
					if ($campos[0] == "RAVALARE") {
						$sqlInfantilRavalare = new ConsultaSql();
						$sqlInfantilRavalare->consulta("INSERT  INTO infantil_ravalaretmp (matricula_prof, grau_serie, area, fotografia, texto_area, texto_etapa1, texto_etapa2, texto_etapa3, texto_etapa4, texto_etapa5, texto_etapa6, texto_etapa7, texto_etapa8) VALUES ('" . $v_matricula . "','" . str_pad($campos[1], 2, "0", STR_PAD_LEFT) . "'," . $campos[2] . ",'" . $campos[3] . "','" . $campos[4] . "','" . $campos[5] . "','" . $campos[6] . "', '" . $campos[7] . "', '" . $campos[8] . "', '" . $campos[9] . "', '" . $campos[10] . "', '" . $campos[11] . "', '" . $campos[12] . "')");
					}
					if ($campos[0] == "RAVALTOP") {
						$sqlInfantilRavaltop = new ConsultaSql();
						$sqlInfantilRavaltop->consulta("INSERT  INTO infantil_ravaltoptmp (matricula_prof, codigo_escola, ano, grau_serie, area, topico, texto_topico, avaliacao) VALUES ('" . $v_matricula . "','" . str_pad($campos[1], 2, "0", STR_PAD_LEFT) . "','" . $campos[2] . "','" . str_pad($campos[3], 2, "0", STR_PAD_LEFT) . "'," . $campos[4] . ",'" . $campos[5] . "','" . $campos[6] . "', '" . $campos[7] . "')");
					}
				}
			}
			if ($temInfantila) {
				$sqlInfantila = new ConsultaSql();
				$linha = $sqlInfantila->quantidadeLinhas("SELECT SQL_NO_CACHE * FROM a_infantiltmp where matricula_aluno = '" . $v_matricula . "'");

				//if ($linha == 0){
				$qtdInfantila = sizeof($dadosInfantila);
				// Cria de variрveis com informaушes do arquivo TXT recebido
				for ($iInfantila = 0; $iInfantila < $qtdInfantila; $iInfantila++) {
					$campos = explode(";", $dadosInfantila[$iInfantila]);
					if ($campos[0] == "HEADER") {
						$sqlGravaInfantil = new ConsultaSql();
						$sqlGravaInfantil->consulta("INSERT INTO a_infantiltmp (matricula_aluno, escola, serie, turno, turma, disciplina, disciplina_nome, ano, etapa) VALUES ('" . $v_matricula . "', '" . $campos[1] . "', '" . $campos[2] . "', '" . $campos[3] . "', '" . $campos[4] . "', '" . $campos[5] . "', '" . $campos[6] . "', '" . $campos[7] . "', '" . $campos[8] . "')");
					}
					if ($campos[0] == "RAALUNO") {
						usleep(500);
						$sqlUltimoInfantil = new ConsultaSql();
						$pesquisaUltimo = $sqlUltimoInfantil->resultado("SELECT MAX(id) FROM a_infantiltmp where matricula_aluno = '" . $v_matricula . "'");
						$ultimo = $pesquisaUltimo[0];
						$sqlInfantilAluno = new ConsultaSql();
						$sqlInfantilAluno->consulta("INSERT INTO a_infantil_alunotmp (id_infantil, matricula_aluno, nome_aluno, texto1, texto2, texto3, texto4, texto5, texto6, texto7, texto8, conclusao, recno, sequencial, nascimento, idade) VALUES (" . $ultimo . ", '" . $v_matricula . "', '" . $campos[2] . "', '" . $campos[7] . "', '" . $campos[8] . "', '" . $campos[9] . "', '" . $campos[10] . "', '" . $campos[11] . "', '" . $campos[12] . "', '" . $campos[13] . "', '" . $campos[14] . "', '" . $campos[6] . "', " . $campos[15] . ", " . $campos[16] . ", '" . $campos[17] . "', '" . $campos[18] . "')");
						$ultimoAluno = $campos[1];
					}
					if ($campos[0] == "RAAREA") {

						$sqlInfantilArea = new ConsultaSql();
						$sqlInfantilArea->consulta("INSERT INTO a_infantil_areatmp (matricula_aluno, area, texto, recno) VALUES ('" . $ultimoAluno . "', " . addslashes($campos[5]) . ", '" . addslashes($campos[6]) . "', " . $campos[7] . ")");
						$ultimaArea = $campos[5];
					}
					if ($campos[0] == "RATOPICO") {

						$sqlInfantilTopico = new ConsultaSql();
						$sqlInfantilTopico->consulta("INSERT INTO a_infantil_topicotmp (matricula_aluno, id_area, topico, avaliacao_etapa1, avaliacao_etapa2, avaliacao_etapa3, avaliacao_etapa4, avaliacao_etapa5, avaliacao_etapa6, avaliacao_etapa7, avaliacao_etapa8, recno) VALUES ('" . $ultimoAluno . "', " . $ultimaArea . ", '" . $campos[6] . "', '" . $campos[7] . "', '" . $campos[8] . "', '" . $campos[9] . "', '" . $campos[10] . "', '" . $campos[11] . "', '" . $campos[12] . "', '" . $campos[13] . "', '" . $campos[14] . "', " . $campos[15] . ")");
					}
					if ($campos[0] == "RAVALSER") {
						$sqlInfantilRavalser = new ConsultaSql();
						$sqlInfantilRavalser->consulta("INSERT INTO a_infantil_ravalsertmp (matricula_aluno, grau_serie, titulo, fotografia, texto_legenda1, texto_legenda2, texto_legenda3, texto_legenda4, texto_legenda5, texto_legenda6, texto_legenda7, texto_legenda8, texto_legenda9, texto_legenda10, texto_etapa1, texto_etapa2, texto_etapa3, texto_etapa4, texto_etapa5, texto_etapa6, texto_etapa7, texto_etapa8, legenda) VALUES ('" . $v_matricula . "','" . str_pad($campos[1], 2, "0", STR_PAD_LEFT) . "','" . $campos[2] . "','" . $campos[3] . "','" . $campos[4] . "','" . $campos[5] . "','" . $campos[6] . "', '" . $campos[7] . "', '" . $campos[8] . "', '" . $campos[9] . "', '" . $campos[10] . "', '" . $campos[11] . "', '" . $campos[12] . "', '" . $campos[13] . "', '" . $campos[14] . "', '" . $campos[15] . "', '" . $campos[16] . "', '" . $campos[17] . "', '" . $campos[18] . "', '" . $campos[19] . "', '" . $campos[20] . "', '" . $campos[21] . "', '" . $campos[22] . "')");
					}
					if ($campos[0] == "RAVALARE") {
						$sqlInfantilRavalare = new ConsultaSql();
						$sqlInfantilRavalare->consulta("INSERT INTO a_infantil_ravalaretmp (matricula_aluno, grau_serie, area, fotografia, texto_area, texto_etapa1, texto_etapa2, texto_etapa3, texto_etapa4, texto_etapa5, texto_etapa6, texto_etapa7, texto_etapa8) VALUES ('" . $v_matricula . "','" . str_pad($campos[1], 2, "0", STR_PAD_LEFT) . "'," . $campos[2] . ",'" . $campos[3] . "','" . $campos[4] . "','" . $campos[5] . "','" . $campos[6] . "', '" . $campos[7] . "', '" . $campos[8] . "', '" . $campos[9] . "', '" . $campos[10] . "', '" . $campos[11] . "', '" . $campos[12] . "')");
					}
					if ($campos[0] == "RAVALTOP") {
						$sqlInfantilRavaltop = new ConsultaSql();
						$sqlInfantilRavaltop->consulta("INSERT INTO a_infantil_ravaltoptmp (matricula_aluno, codigo_escola, ano, grau_serie, area, topico, texto_topico, avaliacao) VALUES ('" . $v_matricula . "','" . str_pad($campos[1], 2, "0", STR_PAD_LEFT) . "','" . $campos[2] . "','" . str_pad($campos[3], 2, "0", STR_PAD_LEFT) . "'," . $campos[4] . ",'" . $campos[5] . "','" . $campos[6] . "', '" . $campos[7] . "')");
					}
				}
				//}	
			}
			if ($temAgenda) {
				$sqlDelDiario = new ConsultaSql();
				$sqlDelDiario->consulta("DELETE FROM agendaweb WHERE matricula='" . $v_matricula . "'");

				$qtdAgenda = sizeof($dadosAgenda);
				for ($iAgenda = 0; $iAgenda < $qtdAgenda; $iAgenda++) {
					$explodeAgenda = explode(';', $dadosAgenda[$iAgenda]);
					$escolagenda = $explodeAgenda[0];
					$codDisciplina  = $explodeAgenda[2];  // 99/99/9999 substr ( string $string , int $start [, int $length ] )
					$dateData = substr($explodeAgenda[3], 6, 4) . substr($explodeAgenda[3], 3, 2) . substr($explodeAgenda[3], 0, 2);
					$txtTexto = str_replace("|", "\r\n", $explodeAgenda[4]);
					$txtTexto = str_replace('"', '', $txtTexto);
					$txtTexto = str_replace("'", '', $txtTexto);
					$sqlAgenda = new ConsultaSql();
					$sqlAgenda->consulta("INSERT INTO agendaweb (codigo_escola, matricula, codigo_disciplina, data, texto ) 
							 VALUES ('" . $escolagenda . "', '" . $v_matricula . "', '" . $codDisciplina . "','" . $dateData . "', '" . $txtTexto . "')");
				}
			}
		} // !$verro

	} // !$vexite
	// Se nсo obteve arquivo de resposta esistema(Dataflex->Php)
	else {
		$acesso = 'F'; // Acesso negado
		$vuser = '';
	}
} //Fim (SEM ECONFIG.TXT)
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	<title>Gestor Escolar</title>
	<META NAME='language' CONTENT='Portuguese'>
	<META NAME='ROBOTS' CONTENT='ALL'>

</head>

<body>
	<div align="center">
		<span class="style13">
			<?php
			@unlink($v_arquivo);  // Apaga arquivo de resposta esistema

			// Libera nЩmero de controle da tabela Acessos
			$sqlLiberaAcesso = new Controle();
			$sqlLiberaAcesso->libera();

			@session_start();
			session_name();
			$_SESSION["gacesso"] = @$acesso;

			if (@$acesso == 'T') {
				// Direciona para a pрgina inicial do mзdulo web
				if (isset($_REQUEST["cad"])) {
					echo "<script>window.location.href='alunos_view.php'</script>";
				} else {
					echo "<script>window.location.href='index.php'</script>";
				}
			} else {
				if ($verro) {
					// Exibe a mensagem de erro e retorna para a tela de login
					echo "<script>window.alert('" . $vmensagem . "')</script>";
					// Libera nЩmero de controle da tabela Acessos

					$sqlLiberaAcesso->libera();
				}
				// Tela de login

				if (isset($_REQUEST["cad"])) {
					echo "<script>window.close(); </script>";
				} else {
					echo "<script>window.location.href='index2.php'</script>";
				}
			}
			?>
		</span></div>
</body>

</html>
