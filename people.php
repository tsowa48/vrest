<?php
include_once $_SERVER['DOCUMENT_ROOT'].'/head.php';

if($method === 'GET' && $is_local) {//Read
  $id = $_GET['id'];
  $fio = $_GET['fio'];
  $birth = $_GET['birth'];
  $male = $_GET['male'];
  //$secret = $_GET['secret'];
  $aid = $_GET['aid'];

  if((isset($id) && is_numeric($id)) ||
     isset($fio) ||
     (isset($birth) && is_numeric($birth)) ||
     (isset($male) && is_numeric($male)) ||
     (isset($aid) && is_numeric($aid))) {
  
      $condition = (isset($id) ? 'id='.$id : '');
      $condition .= (isset($fio) ? (strlen($condition) > 0 ? ' and ' : '').'fio=\''.htmlspecialchars($fio).'\'' : '');
      $condition .= (isset($birth) ? (strlen($condition) > 0 ? ' and ' : '').'birth='.$birth : '');
      $condition .= (isset($male) ? (strlen($condition) > 0 ? ' and ' : '').'male='.$male : '');
      $condition .= (isset($aid) ? (strlen($condition) > 0 ? ' and ' : '').'aid='.$aid : '');
  }
  $items = pg_query($psql, 'select id, fio, birth, male, aid from people'.(strlen($condition) > 0 ? ' where '.$condition : ' ').'order by fio;');
  $json = '[';
  while($item = pg_fetch_row($items)) {
    $json .= '{ "id": '.$item[0].', "fio":"'.$item[1].'", "birth":'.$item[2].', "male":'.$item[3].', "aid":'.$item[4].'},'.PHP_EOL;
  }
  if(strlen($json) > 2)
    $json = substr($json, 0, -2);
  $json .= ']';
  echo $json;
} else if($method === 'POST' && $is_local) {//Create
  $fio = $_POST['fio'];
  $birth = $_POST['birth'];
  $male = $_POST['male'];
  //$secret = $_POST['secret'];
  $aid = $_POST['aid'];

  if(isset($fio) &&
     isset($birth) && is_numeric($birth) &&
     isset($male) && is_numeric($male) &&
     isset($aid) && is_numeric($aid)) {

    $values = '\''.htmlspecialchars($fio).'\', '.$birth.', '.$male.', '.$aid;
    $id = pg_fetch_row(pg_query($psql, 'insert into people(fio, birth, male, aid) values ('.$values.') returning id;'))[0];
    header('Location: ?id='.$id);
    echo '[{ "id": '.$id.', "fio":"'.htmlspecialchars($fio).'", "birth":'.$birth.', "male":'.$male.', "aid":'.$aid.'}]';
    http_response_code(201);
  } else {
    http_response_code(400);
  }
} else if($method === 'PUT') {//Update
  parse_str(file_get_contents('php://input'), $_PUT);
  $id = $_PUT['id']??null;
  if($is_local) {
    $fio = $_PUT['fio']??null;
    $birth = $_PUT['birth']??null;//<----------------------- (добавить проверки возможности изменения)
    $male = $_PUT['male']??null;//<----------------------- (добавить проверки возможности изменения)
    $aid = $_PUT['aid']??null;//TODO: если есть голосование по этому адресу в данное время, то не изменять его?

    if((isset($id) && is_numeric($id)) &&
       (isset($fio) ||
       (isset($birth) && is_numeric($birth)) ||
       (isset($male) && is_numeric($male)) ||
       (isset($aid) && is_numeric($aid)))) {
      $condition = (isset($fio) ? 'fio=\''.htmlspecialchars($fio).'\'' : '');
      $condition .= (isset($birth) ? (strlen($condition) > 0 ? ', ' : '').'birth='.$birth : '');
      $condition .= (isset($male) ? (strlen($condition) > 0 ? ', ' : '').'male='.$male : '');
      $condition .= (isset($aid) ? (strlen($condition) > 0 ? ', ' : '').'aid='.$aid : '');

      pg_query($psql, 'update people set '.$condition.' where id='.$id.';');
    } else {
      http_response_code(400);
    }
  } else {
    $secret = $_PUT['secret']??null;//TODO: В какой момент менять?
    if(isset($id) && is_numeric($id) && isset($secret)) {
      pg_query($psql, 'update people set secret=\''.htmlspecialchars($secret).'\' where id='.$id.';');
    } else {
      http_response_code(400);
    }
  }
} else if($method === 'DELETE' && $is_local) {//Delete
  parse_str(file_get_contents('php://input'), $_DELETE);
  $id = $_DELETE['id']??null;
  if(isset($id) && is_numeric($id)) {
    pg_query($psql, 'delete from people where id='.$id.';');
    http_response_code(404);
  } else {
    http_response_code(400);
  }
} else {
  http_response_code(405);//Not allowed
}
?>