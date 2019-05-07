<?php
include_once $_SERVER['DOCUMENT_ROOT'].'/head.php';

if($method === 'GET') {//Read
  $id = $_GET['id'];
  //$name = $_GET['name'];
  //$description = $_GET['description'];
  $position = $_GET['position'];
  $vid = $_GET['vid'];
  $condition = '';
  if((isset($id) && is_numeric($id)) ||
     (isset($position) && is_numeric($position)) ||
     (isset($vid) && is_numeric($vid))) {
    $condition = (isset($id) ? 'id='.$id : '');
    $condition .= (isset($position) ? (strlen($condition) > 0 ? ' and ' : '').'position='.$position : '');
    $condition .= (isset($vid) ? (strlen($condition) > 0 ? ' and ' : '').'vid='.$vid : '');

    if(strlen($condition) > 0)
      $condition = 'where '.$condition;
  }
  $items = pg_query($psql, 'select K.id, K.name, K.description, K.position, K.vid from rival K '.$condition.' order by K.position;');

  $json = '[';
  while($item = pg_fetch_row($items)) {
    $json .= '{ "id": '.$item[0].', "name":"'.$item[1].'", "description":"'.$item[2].'", "position":'.$item[3].', "vid":'.$item[4].'},'.PHP_EOL;
  }
  if(strlen($json) > 2)
    $json = substr($json, 0, -2);
  $json .= ']';
  echo $json;
} else if($method === 'POST' && $is_local) {//Create
  $name = $_POST['name'];
  $description = $_POST['description'];
  $position = $_POST['position'];
  $vid = $_POST['vid'];

  if(isset($position) && is_numeric($position) &&
     isset($vid) && is_numeric($vid) &&
     isset($name) && isset($description)) {
    $currentTime = intval(time());
    $_start = pg_fetch_row(pg_query($psql, 'select start from vote where id='.$vid.';'))[0];
    if($currentTime > intval($_start))
      http_response_code(423);//Blocked
    else {
      $values = '\''.htmlspecialchars($name).'\', \''.htmlspecialchars($description).'\', '.$position.', '.$vid;
      $id = pg_fetch_row(pg_query($psql, 'insert into rival(name, description, position, vid) values ('.$values.') returning id;'))[0];
      header('Location: ?id='.$id);
      echo '[{ "id": '.$id.', "name":"'.htmlspecialchars($name).'", "description":"'.htmlspecialchars($description).'", "position":'.$position.', "vid":'.$vid.'}]';
      http_response_code(201);
    }
  } else {
    http_response_code(400); 
  }
} else if($method === 'PUT' && $is_local) {//Update
  parse_str(file_get_contents('php://input'), $_PUT);
  $id = $_PUT['id']??null;
  $name = $_PUT['name']??null;
  $description = $_PUT['description']??null;
  $position = $_PUT['position']??null;
  $vid = $_PUT['vid']??null;
  if(isset($vid) && is_numeric($vid)) {
    $currentTime = intval(time());
    $_start = pg_fetch_row(pg_query($psql, 'select start from vote where id='.$vid.';'))[0];
    if($currentTime > intval($_start))
      http_response_code(423);//Blocked
    else {
      if(isset($id) && is_numeric($id) &&
         ((isset($position) && is_numeric($position)) ||
         (isset($vid) && is_numeric($vid)) ||
         isset($name) ||
         isset($description))) {
        $condition = (isset($position) ? 'position='.$position : '');
        $condition .= (isset($vid) ? (strlen($condition) > 0 ? ', ' : '').'vid='.$vid : '');
        $condition .= (isset($name) ? (strlen($condition) > 0 ? ', ' : '').'name=\''.htmlspecialchars($name).'\'' : '');
        $condition .= (isset($description) ? (strlen($condition) > 0 ? ', ' : '').'description=\''.htmlspecialchars($description).'\'' : '');
        pg_query($psql, 'update rival set '.$condition.';');
      } else {
        http_response_code(400);
      }
    }
  } else {
    http_response_code(400);
  }
} else if($method === 'DELETE' && $is_local) {//Delete
  parse_str(file_get_contents('php://input'), $_DELETE);
  $id = $_DELETE['id']??null;
  if(isset($id) && is_numeric($id)) {
    $currentTime = intval(time());
    $_start = pg_fetch_row(pg_query($psql, 'select start from vote where id='.$vid.';'))[0];
    if($currentTime > intval($_start))
      http_response_code(423);//Blocked
    else {
      pg_query($psql, 'delete from rival where id='.$id.';');
      http_response_code(404);
    }
  } else {
    http_response_code(400);
  }
} else {
  http_response_code(405);//Not allowed
}
?>