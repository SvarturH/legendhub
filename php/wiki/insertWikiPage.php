<?php
session_start();

header( "Access-Control-Allow-Origin: legendhub.org" );
header( "Content-Type: application/json; charset=UTF-8" );

$postdata = json_decode(file_get_contents("php://input"));

$root = realpath(getenv("DOCUMENT_ROOT"));
require_once("$root/php/common/config.php");
$pdo = getPDO();

if (isset($_SESSION['UserId'])) {
	$query = $pdo->prepare("SELECT Banned FROM Members WHERE Id = :id");
	$query->execute(array("id" => $_SESSION['UserId']));
	if ($res = $query->fetchAll(PDO::FETCH_CLASS)[0]) {
		if ($res->Banned) {			
			session_unset();
			session_destroy();
			
			return;
		}
	}
}
else {
	return;
}

$sql = "INSERT INTO WikiPages(Title, CategoryId, SubCategoryId, Tags, Content, ModifiedOn, ModifiedBy, ModifiedByIP, ModifiedByIPForward)
VALUES (:Title, :CategoryId, :SubCategoryId, :Tags, :Content, NOW(), :ModifiedBy, :ModifiedByIP, :ModifiedByIPForward)";
$query = $pdo->prepare($sql);
$query->execute(array("Title" => $postdata->Title,
			"CategoryId" => $postdata->CategoryId,
			"SubCategoryId" => $postdata->SubCategoryId,
			"Tags" => $postdata->Tags,
			"Content" => $postdata->Content,
			"ModifiedBy" => $_SESSION['Username'],
			"ModifiedByIP" => getenv('REMOTE_ADDR'),
			"ModifiedByIPForward" => getenv('HTTP_X_FORWARDED_FOR')));

echo($pdo->lastInsertId());

?>
