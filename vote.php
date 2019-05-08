<?php
include_once $_SERVER['DOCUMENT_ROOT'].'/head.php';

if($method === 'GET') {//Read
  $id = $_GET['id'];
  //$name = $_GET['name'];
  $start = $_GET['start'];
  $stop = $_GET['stop'];
  $max = $_GET['max'];

  $lon = $_GET['lon'];
  $lat = $_GET['lat'];

  if((isset($id) && is_numeric($id)) ||
     (isset($start) && is_numeric($start)) ||
     (isset($stop) && is_numeric($stop)) ||
     (isset($max) && is_numeric($max))) {
    $condition = (isset($id) ? 'id='.$id : '');
    $condition .= (isset($start) ? (strlen($condition) > 0 ? ' and ' : '').'start>='.$start : '');
    $condition .= (isset($stop) ? (strlen($condition) > 0 ? ' and ' : '').'stop<='.$stop : '');
    $condition .= (isset($max) ? (strlen($condition) > 0 ? ' and ' : '').'max='.abs($max) : '');

    $items = pg_query($psql, 'select id, name, start, stop, key, max from vote '.(strlen($condition) > 0 ? 'where '.$condition : '').' order by start;');
  } else if((isset($lon) && isset($lat) && is_double($lon) && is_double($lat))) {
    //TODO TEST: Выбрать все голосования по GPS-координатам
    // + Найти ближайший адрес по GPS
    // + Найти голосования для данного адреса
    // + Найти голосования для всех родителей (parent) этого адреса
    $currentAddress = pg_fetch_row(pg_query($psql, 'select A.id, A.parent from address A where sqrt(pow(A.lon-'.$lon.',2)+pow(A.lat-'.$lat.',2))=(select min(sqrt(pow(B.lon-'.$lon.',2)+pow(B.lat-'.$lat.',2))) from address B);'))[0];
    $items = pg_query($psql, 'with recursive addr(aid, parent) as
    (select A.id, A.parent from address A where sqrt(pow(A.lon-'.$lon.',2)+pow(A.lat-'.$lat.',2))=(select min(sqrt(pow(B.lon-'.$lon.',2)+pow(B.lat-'.$lat.',2))) from address B)
    union all select P.id, P.parent from addr A, address P where A.parent = P.id)
    select V.id, V.name, V.start, V.stop, V.key, V.max from addr A, va VA, vote V where A.aid=VA.aid and V.id=VA.vid order by V.start;');

  } else {
    $items = pg_query($psql, 'select id, name, start, stop, key, max from vote order by start;');
  }
  $json = '[';
  $currentTime = intval(time());
  while($item = pg_fetch_row($items)) {
    $addresses = pg_fetch_all_columns(pg_query($psql, 'select aid from va where vid='.$item[0].';'), 0);
    $rivals = pg_fetch_all_columns(pg_query($psql, 'select id from rival where vid='.$item[0].';'), 0);
    $json_addresses = '['.implode(',', $addresses).']';
    $json_rivals = '['.implode(',', $rivals).']';
    $privateKey = $currentTime <= intval($item[3]) ? '': $item[4];//<----------------- return PRIVATE KEY
    $json .= '{ "id":'.$item[0].', "name":"'.$item[1].'", "start":'.$item[2].', "stop":'.$item[3].', "key":"'.$privateKey.'", "rival":'.$json_rivals.', address":'.$json_addresses.', "max":'.$item[5].'},'.PHP_EOL;
  }
  if(strlen($json) > 2)
    $json = substr($json, 0, -2);
  $json .= ']';
  echo $json;
} else if($method === 'POST' && $is_local) {//Create
  $name = $_POST['name'];
  $start = $_POST['start'];
  $stop = $_POST['stop'];
  $max = $_POST['max'];
  $aids = $_POST['aids'];

  if(isset($start) && is_numeric($start) &&
     isset($stop) && is_numeric($stop) &&
     isset($max) && is_numeric($max) &&
     isset($name)) {

    //TODO: generate PRIVATE KEY (Paillier)
    $privateKey = uniqid('', true);//<---------------------------------------

    $values = '\''.htmlspecialchars($name).'\', '.$start.', '.$stop.', \''.$privateKey.'\', '.abs($max);
    $id = pg_fetch_row(pg_query($psql, 'insert into vote(name, start, stop, key, max) values ('.$values.') returning id;'))[0];
    $aids_ = explode(',', $aids);
    $values = $id.','.implode('), ('.$id.',', $aids_);
    pg_query($psql, 'insert into va(vid,aid) values ('.$values.');');
    header('Location: ?id='.$id);
    echo '[{ "id":'.$id.', "name":"'.$name.'", "start":'.$start.', "stop":'.$stop.', "key":"", "rival":[], address":['.$aids.'], "max":'.abs($max).'}]';
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
  $aids = $_PUT['aids'];
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
      $aids = explode(',', $aids);
      $values = $id.','.implode('), ('.$id.',', $aids);
      pg_query($psql, 'insert into va(vid,aid) values ('.$values.');');
      pg_query($psql, 'update vote set '.$condition.';');
    }
  } else {
    http_response_code(400);
  }
} else if($method === 'DELETE' && $is_local) {//Delete
  parse_str(file_get_contents('php://input'), $_DELETE);
  $currentTime = intval(time());

  $id = $_DELETE['id'];
  if(isset($id) && is_numeric($id)) {
    $_start = pg_fetch_row(pg_query($psql, 'select start from vote where id='.$id.';'))[0];
    if($currentTime > intval($_start))
      http_response_code(423);//Blocked
    else {
      pg_query($psql, 'delete from va where vid='.$id.'; delete from rival where vid='.$id.'; delete from result where vid='.$id.'; delete from vote where id='.$id.';');
      http_response_code(404);
    }
  } else {
    http_response_code(400);
  }
} else {
  http_response_code(405);//Not allowed
}
?>