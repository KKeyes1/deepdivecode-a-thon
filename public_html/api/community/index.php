
<?php
require_once(dirname(__DIR__, 3) . "/vendor/autoload.php");
require_once(dirname(__DIR__, 3) . "/php/classes/autoload.php");
require_once(dirname(__DIR__, 3) . "/php/lib/jwt.php");
require_once(dirname(__DIR__, 3) . "/php/lib/xsrf.php");
require_once(dirname(__DIR__, 3) . "/php/lib/uuid.php");
require_once("/etc/apache2/capstone-mysql/encrypted-config.php");
use Edu\Cnm\Forum\Community;

/**
 * API for Community
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
	$communityProfileId = filter_input(INPUT_GET, "communityProfileId", FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
	$communityName = filter_input(INPUT_GET, "communityName", FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
	// make sure the id is valid for methods that require it
	if(($method === "DELETE" || $method === "PUT") && (empty($id) === true )) {
		throw(new InvalidArgumentException("id cannot be empty or negative", 405));
	}
	if($method === "GET") {
		//set XSRF cookie
		setXsrfCookie();
		//gets a post by content
		if(empty($id) === false) {
			$community = Community::getCommunityByCommunityId($pdo, $id);
			if($community !== null) {
				$reply->data = $community;
			}
		} else if(empty($communityProfileId) === false) {
			$communityProfileId = Community::getCommunityByCommunityProfileId($pdo, $communityProfileId)->toArray();
			if($communityProfileId !== null) {
				$reply->data = $communityProfileId;
			}
		} else if(empty($communityName) === false) {
			$communityName = Community::getCommunityByCommunityName($pdo, $communityName);
			if($communityName !== null) {
				$reply->data = $communityName;
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

		if($method === "PUT") {
			//enforce the user is signed in and only trying to edit their own community
			if(empty($_SESSION["community"]) === true || $_SESSION["community"]->getCommunityId()->toString() !== $id) {
				throw(new \InvalidArgumentException("You are not allowed to access this community", 403));
			}

			//retrieve the community to be updated
			$community = Community::getCommunityByCommunityId($pdo, $id);
			if($community === null) {
				throw(new RuntimeException("Community does not exist", 404));
			}
			//community profile id
			if(empty($requestObject->communityProfileId) === true) {
				throw(new \InvalidArgumentException ("No community profile", 405));
			}
			//community name is a required field
			if(empty($requestObject->communityName) === true) {
				throw(new \InvalidArgumentException ("No community name present", 405));
			}
			$community->setCommunityProfileId($requestObject->communityProfileId);
			$community->setCommunityName($requestObject->communityName);
			$community->update($pdo);
			// update reply
			$reply->message = "Community information updated";

		} elseif($method === "POST") {

			// enforce the user is signed in
			if(empty($_SESSION["profile"]) === true) {
				throw(new \InvalidArgumentException("you must be logged in to create a community", 403));
			}

			// create a new community and insert it into the database
			$community = new Community(generateUuidV4(), $_SESSION["profile"]->getProfileId(), $requestObject->communityName);
			$community->insert($pdo);

			// update reply
			$reply->message = "Community created OK";
		}

		// delete method
	} elseif($method === "DELETE") {
		//verify the XSRF Token
		verifyXsrf();
		//enforce the end user has a JWT token
		//validateJwtHeader();
		$community = Community::getCommunityByCommunityId($pdo, $id);
		if($community === null) {
			throw (new RuntimeException("Community does not exist"));
		}
		//enforce the user is signed in and only trying to edit their own community
		if(empty($_SESSION["community"]) === true || $_SESSION["community"]->getCommunityId()->toString() !== $community->getCommunityId()->toString()) {
			throw(new \InvalidArgumentException("You are not allowed to access this community", 403));
		}
		validateJwtHeader();
		//delete the community from the database
		$community->delete($pdo);
		$reply->message = "Community Deleted";
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