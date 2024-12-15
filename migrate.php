<?php
/*
  Descrição do Desafio:
    Você precisa realizar uma migração dos dados fictícios que estão na pasta <dados_sistema_legado> para a base da clínica fictícia MedicalChallenge.
    Para isso, você precisa:
      1. Instalar o MariaDB na sua máquina. Dica: Você pode utilizar Docker para isso;
      2. Restaurar o banco da clínica fictícia Medical Challenge: arquivo <medical_challenge_schema>;
      3. Migrar os dados do sistema legado fictício que estão na pasta <dados_sistema_legado>:
        a) Dica: você pode criar uma função para importar os arquivos do formato CSV para uma tabela em um banco temporário no seu MariaDB.
      4. Gerar um dump dos dados já migrados para o banco da clínica fictícia Medical Challenge.
*/

// Importação de Bibliotecas:
include "./lib.php";

//variaveis login/senha/host bd
$host = "localhost";
$user = "root";
$pass = "root";
$dbname = "MedicalChallenge";
$dbtemp = "0temp";

// Conexão com o banco da clínica fictícia:
$connMedical = mysqli_connect($host, $user, $pass, $dbname)
  or die("Não foi possível conectar os servidor MySQL: MedicalChallenge\n");

// Conexão com o banco temporário:
$connTemp = mysqli_connect($host, $user, $pass, $dbtemp)
  or die("Não foi possível conectar os servidor MySQL: 0temp\n");

// Informações de Inicio da Migração:
echo "Início da Migração: " . dateNow() . ".\n\n";

/*
  Seu código vai aqui!
*/
//--Importar csv para o banco temporario
$files = ["dados_sistema_legado/20210512_agendamentos.csv", "dados_sistema_legado/20210512_pacientes.csv"];
$table_name = ['agendamentos', 'pacientes'];

foreach($files as $index => $f){
    $file = open_csv($f);
    $csv_array = csv_rows($file);
    format_date($csv_array, 2);
    $sql_values = add_values($csv_array);
    $sql = prepare_sql_to_insert($sql_values, $table_name[$index]);
    insert_into_db($connTemp, $sql);
  }
//--------------------------------------

//--migrar tabela convenio--------------
$convenio = select_into_db($connTemp," SELECT distinct convenio 
  FROM 0temp.pacientes AS tmp 
  LEFT JOIN medicalchallenge.convenios AS med 
  ON tmp.convenio = med.nome 
  WHERE med.nome IS NULL;");

if(!empty($convenio)){
  $convenio = set_spefic_value($convenio, 0, "Convenio do Hospital", 2);
  $convenio = set_spefic_value($convenio, 1, "Convenio da Migraçao", 2);
  $sql_values = add_values($convenio, null , 1);
  $sql = prepare_sql_to_insert($sql_values, "convenios");
  insert_into_db($connMedical, $sql);
}

//-------------------------------------

//--migrar tabela paciente-------------
insert_into_db($connTemp ,"UPDATE pacientes SET sex_pac = 
  CASE 
    WHEN sex_pac = 'M' THEN 'Masculino'
    WHEN sex_pac = 'F' THEN 'Feminino'
    ELSE sex_pac
  END
;");

$pacientes = select_into_db($connTemp, "SELECT DISTINCT tmp.nome_paciente, tmp.sex_pac, tmp.nasc_paciente, tmp.cpf_paciente, tmp.rg_paciente, convenio.id, tmp.cod_paciente 
FROM 0temp.pacientes AS tmp
INNER JOIN medicalchallenge.convenios as convenio
ON tmp.convenio = convenio.nome
LEFT JOIN medicalchallenge.pacientes AS med
ON tmp.cpf_paciente = med.cpf
WHERE med.cpf IS NULL
;");
if(!empty($pacientes)){
  $sql_values = add_values($pacientes, null, 1);
  $sql = prepare_sql_to_insert($sql_values, "pacientes");
  insert_into_db($connMedical, $sql);
}

//--migrar tabela procediemento---------
$procedimentos = select_into_db($connTemp, "SELECT DISTINCT procedimento 
  FROM 0temp.agendamentos AS tmp 
  LEFT JOIN medicalchallenge.procedimentos AS med 
  ON tmp.procedimento = med.nome 
  WHERE med.nome IS NULL;");

if(!empty($procedimentos)){
  $sql_values = add_values($procedimentos, null, 1);
  $sql = prepare_sql_to_insert($sql_values, "procedimentos");
  insert_into_db($connMedical, $sql);
}
//-------------------------------------

//--migrar tabela profissionais-------
insert_into_db($connTemp, "UPDATE agendamentos SET medico = 
  CASE
    WHEN medico = 'Pietro' THEN 'Dr. Analista Pietro'
    WHEN medico = 'Dr. Lucas' THEN 'Dr. Lucas KNE'
    ELSE medico 
  END
;");
$profissionais = select_into_db($connTemp, "SELECT DISTINCT medico 
  FROM 0temp.agendamentos AS tmp 
  LEFT JOIN medicalchallenge.profissionais AS med 
  ON tmp.medico = med.nome 
  WHERE med.nome IS NULL;");

if(!empty($profissionais)){
  $sql_values = add_values($profissionais, null, 1);
  $sql = prepare_sql_to_insert($sql_values, "profissionais");
  insert_into_db($connMedical, $sql);
}
//-------------------------------------

//-migrar tabela agendamentos-------
$agendamentos = select_into_db($connTemp, "SELECT DISTINCT 
    agendamentos.cod_agendamento ,pacientes.id, profisionais.id, 
    CONCAT(agendamentos.dia, ' ', agendamentos.hora_inicio) as dh_inicio,
    CONCAT(agendamentos.dia, ' ', agendamentos.hora_fim) as dh_fim, convenios.id, procedimentos.id, agendamentos.descricao 
    from 0temp.agendamentos
    inner join medicalchallenge.pacientes as pacientes on agendamentos.cod_paciente = pacientes.cod_referencia
    inner join medicalchallenge.profissionais as profisionais on agendamentos.medico = profisionais.nome
    inner join medicalchallenge.convenios as convenios on agendamentos.convenio = convenios.nome
    inner join medicalchallenge.procedimentos as procedimentos on agendamentos.procedimento = procedimentos.nome
    left join medicalchallenge.agendamentos as agn on agendamentos.cod_agendamento = agn.id
    where agn.id is null
    order by dh_inicio;");

if(!empty($agendamentos)){
  $sql_values = add_values($agendamentos);
  $sql = prepare_sql_to_insert($sql_values, "agendamentos");
  insert_into_db($connMedical,$sql);
}
//-------------------------------------

$dumpFile = 'dump_' . $dbname . '_' . date('Y-m-d_H-i-s') . '.sql';
$sqlDump = "mysqldump -h $host -u $user -p$pass --databases $dbname --add-drop-database > $dumpFile";
exec($sqlDump);


// Encerrando as conexões:
$connMedical->close();
$connTemp->close();


// Informações de Fim da Migração:
echo "Fim da Migração: " . dateNow() . ".\n";

