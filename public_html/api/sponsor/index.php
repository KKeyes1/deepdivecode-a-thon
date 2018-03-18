
<?php
require_once(dirname(__DIR__, 3) . "/vendor/autoload.php");
require_once(dirname(__DIR__, 3) . "/php/classes/autoload.php");
require_once(dirname(__DIR__, 3) . "/php/lib/jwt.php");
require_once(dirname(__DIR__, 3) . "/php/lib/xsrf.php");
require_once(dirname(__DIR__, 3) . "/php/lib/uuid.php");
require_once("/etc/apache2/capstone-mysql/encrypted-config.php");
use Edu\Cnm\Forum\Sponsor;

/**
 * API for Sponsor
 */
//verify the session, if it is not active start it
if(session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}
//prepare an empty reply
$reply = new stdClass();
$reply->status = 200;
$reply->data = null;

try {
	//grab the mySQL connection
	$pdo = connectToEncryptedMySQL("/etc/apache2/capstone-mysql/_CHANGE_NAME_.ini");
	//determine which HTTP method was used
	$method = array_key_exists("HTTP_X_HTTP_METHOD", $_SERVER) ? $_SERVER["HTTP_X_HTTP_METHOD"] : $_SERVER["REQUEST_METHOD"];
	// sanitize input
	$id = filter_input(INPUT_GET, "id", FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
	$sponsorPostId = filter_input(INPUT_GET, "sponsorPostId", FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
	$sponsorProfileId = filter_input(INPUT_GET, "sponsorProfileId", FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
	// make sure the id is valid for methods that require it
	if(($method === "DELETE" || $method === "PUT") && (empty($id) === true )) {
		throw(new InvalidArgumentException("id cannot be empty or negative", 405));
	}
	if($method === "GET") {
		//set XSRF cookie
		setXsrfCookie();
		//gets a sponsor by content
		if(empty($id) === false) {
			$sponsor = Sponsor::getSponsorBySponsorId($pdo, $id);
			if($sponsor !== null) {
				$reply->data = $sponsor;
			}
		} else if(empty($sponsorPostId) === false) {
			$sponsorPostId = Sponsor::getSponsorBySponsorPostId($pdo, $sponsorPostId)->toArray();
			if($sponsorPostId !== null) {
				$reply->data = $sponsorPostId;
			}
		}else if(empty($sponsorProfileId) === false) {
			$sponsorProfileId = Sponsor::getSponsorBySponsorProfileId($pdo, $sponsorProfileId)->toArray();
			if($sponsorProfileId !== null) {
				$reply->data = $sponsorProfileId;
			}
		}
	} elseif($method === "PUT" || $method === "POST") {
		//enforce that the XSRF token is present in the header
		verifyXsrf();
		//enforce the end user has a JWT token
		validateJwtHeader();
		//decode the response from the front end
		$requestContent = file_get_contents("php://input");
		$requestObject = json_decode($requestContent);

		if($method === "POST") {

			// enforce the user is signed in
			if(empty($_SESSION["profile"]) === true) {
				throw(new \InvalidArgumentException("you must be logged in to create a sponsor", 403));
			}

			// create a new sponsor and insert it into the database
			$sponsor = new Sponsor(generateUuidV4(), $_SESSION["Post"]->getPostId(), $_SESSION["profile"]->getProfileId());
			$sponsor->insert($pdo);

			// update reply
			$reply->message = "Sponsor created OK";
		}

		// delete method
	} elseif($method === "DELETE") {
		//verify the XSRF Token
		verifyXsrf();
		//enforce the end user has a JWT token
		//validateJwtHeader();
		$sponsor = Sponsor::getSponsorBySponsorId($pdo, $id);
		if($sponsor === null) {
			throw (new RuntimeException("Sponsor does not exist"));
		}
		//enforce the user is signed in and only trying to edit their own sponsor
		if(empty($_SESSION["sponsor"]) === true || $_SESSION["sponsor"]->getSponsorId()->toString() !== $sponsor->getSponsorId()->toString()) {
			throw(new \InvalidArgumentException("You are not allowed to access this sponsor", 403));
		}
		validateJwtHeader();
		//delete the sponsor from the database
		$sponsor->delete($pdo);
		$reply->message = "Sponsor Deleted";
	} else {
		throw (new InvalidArgumentException("Invalid HTTP request", 400));
	}
	// catch any exceptions that were thrown and update the status and message state variable fields
} catch(\Exception | \TypeError $exception) {
	$reply->status = $exception->getCode();
	$reply->message = $exception->getMessage();
}

header("Content-type: application/json");
if($reply->data === null) {
	unset($reply->data);
}

// encode and return reply to front end caller
echo json_encode($reply);