<?php
include_once $_SERVER['DOCUMENT_ROOT'].'/head.php';

if($method === 'GET') {//Read //TODO: refactoring
  $name = $_GET['name'];//string
  $parent = $_GET['parent'];//long
  $id = $_GET['id'];//long
  $full = $_GET['full'];//boolean (0,1)
  if(isset($id) && is_numeric($id)) {
    if(isset($full)) {
      $parentId = $id;
      $json = '[';
      while($parentId !== 0) {
        $item = pg_fetch_row(pg_query($psql, 'select id, name, parent, lon, lat from address where id='.$parentId.' order by name;'));
        $json .= '{"id":'.$item[0].', "name":"'.$item[1].'", "parent":'.$item[2].', "lon":'.($item[3]??0.0).', "lat":'.($item[4]??0.0).'},'.PHP_EOL;
        $parentId = intval($item[2]);
      }
      if(strlen($json) > 2)
        $json = substr($json, 0, -2);
      $json .= ']';
      echo $json;
    } else {
      $items = pg_query($psql, 'select id, name, parent, lon, lat from address where id='.$id.' order by name;');
      $json = '[';
      while($item = pg_fetch_row($items)) {
        $json .= '{"id":'.$item[0].', "name":"'.$item[1].'", "parent":'.$item[2].', "lon":'.($item[3]??0.0).', "lat":'.($item[4]??0.0).'},'.PHP_EOL;
      }
      if(strlen($json) > 2)
        $json = substr($json, 0, -2);
      $json .= ']';
      echo $json;
    }
  } else if(isset($parent) && is_numeric($parent)) {
    if(isset($name)) {
      $items = pg_query($psql, 'select id, name, parent, lon, lat from address where parent='.$parent.' and name=\''.htmlspecialchars($name).'\' order by name;');
    } else {
      $items = pg_query($psql, 'select id, name, parent, lon, lat from address where parent='.$parent.' order by name;');
    }
    $json = '[';
    while($item = pg_fetch_row($items)) {
      $json .= '{"id":'.$item[0].', "name":"'.$item[1].'", "parent":'.$item[2].', "lon":'.($item[3]??0.0).', "lat":'.($item[4]??0.0).'},'.PHP_EOL;
    }
    if(strlen($json) > 2)
      $json = substr($json, 0, -2);
    $json .= ']';
    echo $json;
  } else if(isset($name)) {
    $items = pg_query($psql, 'select id, name, parent, lon, lat from address where name=\''.htmlspecialchars($name).'\' order by name;');
    $json = '[';
    while($item = pg_fetch_row($items)) {
      $json .= '{"id":'.$item[0].', "name":"'.$item[1].'", "parent":'.$item[2].', "lon":'.($item[3]??0.0).', "lat":'.($item[4]??0.0).'},'.PHP_EOL;
    }
    if(strlen($json) > 2)
      $json = substr($json, 0, -2);
    $json .= ']';
    echo $json;
  } else if(!isset($name) && !isset($parent) && !isset($id)) {
    $items = pg_query($psql, 'select id, name, parent, lon, lat from address order by name;');
    $json = '[';
    while($item = pg_fetch_row($items)) {
      $json .= '{"id":'.$item[0].', "name":"'.$item[1].'", "parent":'.$item[2].', "lon":'.($item[3]??0.0).', "lat":'.($item[4]??0.0).'},'.PHP_EOL;
    }
    if(strlen($json) > 2)
      $json = substr($json, 0, -2);
    $json .= ']';
    echo $json;
  } else {
    http_response_code(400);
  }
} else if($method === 'POST' && $is_local) {//Create
  $name = $_POST['name'];//string
  $parent = $_POST['parent'];//long
  $lon = $_POST['lon'];//double
  $lat = $_POST['lat'];//double

  if(isset($name)) {
    $id = pg_fetch_row(pg_query($psql, 'insert into address(name'.(isset($parent) ? ', parent' : '').(isset($lon) && isset($lat) ? ', lon, lat' : '').') values'.
    '(\''.htmlspecialchars($name).'\''.(isset($parent) && is_numeric($parent) ? ', '.$parent : '').
    (isset($lon) && isset($lat) ? ', '.doubleval($lon).', '.doubleval($lat) : '').') returning id;'))[0];
    header('Location: ?id='.$id);
    echo '[{"id":'.$id.', "name":"'.htmlspecialchars($name).'", "parent":'.(isset($parent) && is_numeric($parent) ? $parent : '').
         ', "lon":'.(isset($lon) && is_double($lon) ? $lon : '0.0').', "lat":'.(isset($lat) && is_double($lat) ? $lat : '0.0').'}]';
    http_response_code(201);
  } else {
    http_response_code(400);
  }
} else if($method === 'PUT' && $is_local) {//Update (replace)
  parse_str(file_get_contents('php://input'), $_PUT);

  $id = intval($_PUT['id'])??null;
  $name = $_PUT['name']??null;
  //$parent = $_PUT['parent'];
  $lon = doubleval($_PUT['lon'])??null;
  $lat = doubleval($_PUT['lat'])??null;
  if(isset($id) && is_numeric($id) &&
     ((isset($name) && !empty($name)) ||
     (isset($lon) && isset($lat) && is_double($lon) && is_double($lat)))) {
    $condition = (isset($name) && !empty($name) ? 'name=\''.htmlspecialchars($name).'\'' : '');
    $condition .= (isset($lon) && isset($lat) && is_double($lon) && is_double($lat) ? (strlen($condition) > 0 ? ', ' : '').'lon='.$lon.', lat='.$lat : '');
    pg_query($psql, 'update address set '.$condition.' where id='.$id.';');
  } else {
    http_response_code(400);
  }
} else if($method === 'DELETE' && $is_local) {//Delete
  parse_str(file_get_contents('php://input'), $_DELETE);
  $id = intval($_DELETE['id'])??null;
  if(isset($id) && is_numeric($id)) {
    //Recursive delete ID and all childs
    pg_query($psql, 'with recursive addr(parent, id) as (select parent, id from address where id='.$id.' union all select p.parent, p.id from addr pr, address p where pr.id = p.parent) delete from address where id in (select id from addr;');
  }
  http_response_code(404);
} else {
  http_response_code(405);
}
?>