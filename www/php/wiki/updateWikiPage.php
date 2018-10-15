<?php
session_start();

header( "Access-Control-Allow-Origin: legendhub.org" );
header( "Content-Type: application/json; charset=UTF-8" );

$postdata = json_decode(file_get_contents("php://input"));

$root = realpath(getenv("DOCUMENT_ROOT"));
require_once("$root/php/common/permissions.php");
$pdo = getPDO();

if (!Permissions::Check("Wiki", false, false, true, false)) {
	header("HTTP/1.1 401 Unauthorized");
	return;
}

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

$query = $pdo->prepare("SELECT Locked FROM WikiPages WHERE Id = :id");
$query->execute(array("id" => $postdata->Id));
if ($res = $query->fetchAll(PDO::FETCH_CLASS)[0]) {
	if ($res->Locked) {
		echo($postdata->Id);
		return;
	}
}

$sql = "UPDATE WikiPages SET ModifiedOn = NOW(),
			ModifiedBy = :ModifiedBy,
			ModifiedByIP = :ModifiedByIP,
			ModifiedByIPForward = :ModifiedByIPForward,
			Title = :Title,
			CategoryId = :CategoryId,
			SubCategoryId = :SubCategoryId,
			Tags = :Tags,
			Content = :Content
	WHERE Id = :Id";
$query = $pdo->prepare($sql);
$query->execute(array("ModifiedBy" => $_SESSION['Username'],
			"ModifiedByIP" => getenv('REMOTE_ADDR'),
			"ModifiedByIPForward" => getenv('HTTP_X_FORWARDED_FOR'),
			"Title" => $postdata->Title,
			"CategoryId" => $postdata->CategoryId,
			"SubCategoryId" => $postdata->SubCategoryId,
			"Tags" => $postdata->Tags,
			"Content" => $postdata->Content,
			"Id" => $postdata->Id));

echo($postdata->Id);

?>