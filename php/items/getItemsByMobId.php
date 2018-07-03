<?php
header( "Access-Control-Allow-Origin: legendhub.org" );
header( "Content-Type: application/json; charset=UTF-8" );

$root = realpath($_SERVER["DOCUMENT_ROOT"]);
require_once("$root/php/common/config.php");
$pdo = getPDO();

$postdata = json_decode(file_get_contents("php://input"));
$MobId = $postdata->MobId;

$sql = "SELECT * FROM Items WHERE MobId = :MobId ORDER BY Name ASC";

$query = $pdo->prepare($sql);
$query->execute(array("MobId" => $MobId));
$result = $query->fetchAll(PDO::FETCH_OBJ);

echo(json_encode($result))
?>
