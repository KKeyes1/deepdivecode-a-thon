
<?php
require_once(dirname(__DIR__, 3) . "/vendor/autoload.php");
require_once(dirname(__DIR__, 3) . "/php/classes/autoload.php");
require_once(dirname(__DIR__, 3) . "/php/lib/jwt.php");
require_once(dirname(__DIR__, 3) . "/php/lib/xsrf.php");
require_once(dirname(__DIR__, 3) . "/php/lib/uuid.php");
require_once("/etc/apache2/capstone-mysql/encrypted-config.php");
use Edu\Cnm\Forum\Cvote;

/**
 * API for Cvote
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
	$cvoteCommentId = filter_input(INPUT_GET, "cvoteCommentId", FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
	$cvoteProfileId = filter_input(INPUT_GET, "cvoteProfileId", FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
	// make sure the id is valid for methods that require it
	if(($method === "DELETE" || $method === "PUT") && (empty($id) === true )) {
		throw(new InvalidArgumentException("id cannot be empty or negative", 405));
	}
	if($method === "GET") {
		//set XSRF cookie
		setXsrfCookie();
		//gets a cvote by content
		if(empty($id) === false) {
			$cvote = Cvote::getCvoteByCvoteId($pdo, $id);
			if($cvote !== null) {
				$reply->data = $cvote;
			}
		} else if(empty($cvoteCommentId) === false) {
			$cvoteCommentId = Cvote::getCvoteByCvoteCommentId($pdo, $cvoteCommentId)->toArray();
			if($cvoteCommentId !== null) {
				$reply->data = $cvoteCommentId;
			}
		}else if(empty($cvoteProfileId) === false) {
			$cvoteProfileId = Cvote::getCvoteByCvoteProfileId($pdo, $cvoteProfileId)->toArray();
			if($cvoteProfileId !== null) {
				$reply->data = $cvoteProfileId;
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
				throw(new \InvalidArgumentException("you must be logged in to create a cvote", 403));
			}

			// create a new cvote and insert it into the database
			$cvote = new Cvote(generateUuidV4(), $_SESSION["Comment"]->getCommentId(), $_SESSION["profile"]->getProfileId());
			$cvote->insert($pdo);

			// update reply
			$reply->message = "Cvote created OK";
		}

		// delete method
	} elseif($method === "DELETE") {
		//verify the XSRF Token
		verifyXsrf();
		//enforce the end user has a JWT token
		//validateJwtHeader();
		$cvote = Cvote::getCvoteByCvoteId($pdo, $id);
		if($cvote === null) {
			throw (new RuntimeException("Cvote does not exist"));
		}
		//enforce the user is signed in and only trying to edit their own cvote
		if(empty($_SESSION["cvote"]) === true || $_SESSION["cvote"]->getCvoteId()->toString() !== $cvote->getCvoteId()->toString()) {
			throw(new \InvalidArgumentException("You are not allowed to access this cvote", 403));
		}
		validateJwtHeader();
		//delete the cvote from the database
		$cvote->delete($pdo);
		$reply->message = "Cvote Deleted";
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