<?php
include_once $_SERVER['DOCUMENT_ROOT'].'/head.php';

if($method === 'GET' && $is_local) {//Read
  http_response_code(501);//Not impemented
} else if($method === 'POST' && $is_local) {//Create
  http_response_code(501);//Not impemented
} else if($method === 'PUT' && $is_local) {//Update
  parse_str(file_get_contents('php://input'), $_PUT);
  http_response_code(501);//Not impemented
} else if($method === 'DELETE' && $is_local) {//Delete
  parse_str(file_get_contents('php://input'), $_DELETE);
  http_response_code(501);//Not impemented
} else {
  http_response_code(405);//Not allowed
}
?>