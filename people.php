<?php
include_once $_SERVER['DOCUMENT_ROOT'].'/head.php';

if($method === 'GET' && $is_local) {//Read
  
} else if($method === 'POST' && $is_local) {//Create
  
} else if($method === 'PUT' && $is_local) {//Update
  parse_str(file_get_contents('php://input'), $_PUT);
  
} else if($method === 'DELETE' && $is_local) {//Delete
  parse_str(file_get_contents('php://input'), $_DELETE);

} else {
  http_response_code(405);//Not allowed
}
?>