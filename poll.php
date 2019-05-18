<?php
include_once $_SERVER['DOCUMENT_ROOT'].'/head.php';

if($method === 'GET') {//Read
  http_response_code(501);
  //TODO: generate public key & get other params
  //TODO THINK Одинаковый secret по конкретному aid. Что делать в таком случае?
} else if($method === 'POST') {//Create
  http_response_code(501);
  //TODO: do voting (poll)
} else {
  http_response_code(405);//Not allowed
}