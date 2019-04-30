<?php
include_once $_SERVER['DOCUMENT_ROOT'].'/head.php';

if($method === 'GET') {//Read
  $id = $_GET['id'];
  //$name = $_GET['name'];
  $start = $_GET['start'];
  $stop = $_GET['stop'];
  $max = $_GET['max'];

  if((isset($id) && is_numeric($id)) ||
     (isset($start) && is_numeric($start)) ||
     (isset($stop) && is_numeric($stop)) ||
     (isset($max) && is_numeric($max))) {
    $condition = (isset($id) ? 'id='.$id : '');
    $condition .= (isset($start) ? (strlen($condition) > 0 ? ' and ' : '').'start>='.$start : '');
    $condition .= (isset($stop) ? (strlen($condition) > 0 ? ' and ' : '').'stop<='.$stop : '');
    $condition .= (isset($max) ? (strlen($condition) > 0 ? ' and ' : '').'max='.abs($max) : '');

    if(strlen($condition) > 0)
      $items = pg_query($psql, 'select id, name, start, stop, key, max from vote where '.$condition.' order by start;');
    else
      $items = pg_query($psql, 'select id, name, start, stop, key, max from vote order by start;');
  } else {
    $items = pg_query($psql, 'select id, name, start, stop, key, max from vote order by start;');
  }
  $json = '[';
  $currentTime = intval(time());
  while($item = pg_fetch_row($items)) {
    $addresses = pg_fetch_all(pg_query($psql, 'select aid from va where vid='.$item[0].';'));
    $json_addr = '['.implode(',', $addresses).']';
    $privateKey = $currentTime <= intval($item[3]) ? '': $item[4];//<----------------- return PRIVATE KEY
    $json .= '{ "id": '.$item[0].', "name":"'.$item[1].'", "start":'.$item[2].', "stop":'.$item[3].', "key":"'.$privateKey.'", "address":'.$json_addr.' "max":'.$item[5].'},'.PHP_EOL;
  }
  if(strlen($json) > 2)
    $json = substr($json, 0, -2);
  $json .= ']';
  echo $json;
} else if($method === 'POST' && $is_local) {//Create
  $id = $_POST['id'];
  $name = $_POST['name'];
  $start = $_POST['start'];
  $stop = $_POST['stop'];
  $max = $_POST['max'];
  $aids = $_POST['aids'];//<----------------------------------------------------
  /* TODO: 1) При вставке в таблицу VA нужно спускаться по дереву отмеченных адресов до листьев без потомков.
     TODO: 2) Добавить все эти листья в таблицу VA.
  */
  if(isset($start) && is_numeric($start) &&
     isset($stop) && is_numeric($stop) &&
     isset($max) && is_numeric($max) &&
     isset($name)) {

    //TODO: generate PRIVATE KEY
    $privateKey = '123';

    $values = '\''.htmlspecialchars($name).'\', '.$start.', '.$stop.', '.$privateKey.', '.abs($max);
    $id = pg_fetch_row(pg_query($psql, 'insert into vote(name, start, stop, key, max) values ('.$values.') returning id;'))[0];
    //TODO: insert aid[] into VA where vid=$id
    header('Location: ?id='.$id);
    echo '[{ "id": '.$id.', "name":"'.$name.'", "start":'.$start.', "stop":'.$stop.', "key":"", "max":'.abs($max).'}]';
    http_response_code(201);
  } else {
    http_response_code(400); 
  }
} else if($method === 'PUT' && $is_local) {//Update
  parse_str(file_get_contents('php://input'), $_PUT);
  $currentTime = intval(time());

  $id = $_PUT['id'];
  $name = $_PUT['name'];
  $start = $_PUT['start'];
  $stop = $_PUT['stop'];
  $max = $_PUT['max'];
  $aids = $_POST['aids'];//<----------------------------------------------------
  if(isset($id) && is_numeric($id) &&
     ((isset($start) && is_numeric($start)) ||
     (isset($stop) && is_numeric($stop)) ||
     (isset($max) && is_numeric($max)) ||
     isset($name))) {

    $condition = (isset($name) ? 'name=\''.htmlspecialchars($name).'\'' : '');
    $condition .= (isset($start) ? (strlen($condition) > 0 ? ', ' : '').'start='.$start : '');
    $condition .= (isset($stop) ? (strlen($condition) > 0 ? ', ' : '').'stop='.$stop : '');
    $condition .= (isset($max) ? (strlen($condition) > 0 ? ', ' : '').'max='.abs($max) : '');

    $_start = pg_fetch_row(pg_query($psql, 'select start from vote where id='.$id.';'))[0];
    if($currentTime > intval($_start))
      http_response_code(423);//Blocked
    else {
      pg_query($psql, 'delete from va where vid='.$id.';');
      //TODO: insert aid[] into VA where vid=$id
      pg_query($psql, 'update vote set '.$condition.';');
    }
  } else {
    http_response_code(400);
  }
} else {
  http_response_code(405);//Not allowed
}
?>