
<?php
require_once(dirname(__DIR__, 3) . "/vendor/autoload.php");
require_once(dirname(__DIR__, 3) . "/php/classes/autoload.php");
require_once(dirname(__DIR__, 3) . "/php/lib/jwt.php");
require_once(dirname(__DIR__, 3) . "/php/lib/xsrf.php");
require_once(dirname(__DIR__, 3) . "/php/lib/uuid.php");
require_once("/etc/apache2/capstone-mysql/encrypted-config.php");
use Edu\Cnm\Forum\Comment;

/**
 * API for Comment
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
	$commentPostId = filter_input(INPUT_GET, "commentPostId", FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
	$commentProfileId = filter_input(INPUT_GET, "commentProfileId", FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
	$commentContent = filter_input(INPUT_GET, "commentContent", FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
	// make sure the id is valid for methods that require it
	if(($method === "DELETE" || $method === "PUT") && (empty($id) === true )) {
		throw(new InvalidArgumentException("id cannot be empty or negative", 405));
	}
	if($method === "GET") {
		//set XSRF cookie
		setXsrfCookie();
		//gets a comment by content
		if(empty($id) === false) {
			$comment = Comment::getCommentByCommentId($pdo, $id);
			if($comment !== null) {
				$reply->data = $comment;
			}
		} else if(empty($commentPostId) === false) {
			$commentPostId = Comment::getCommentByCommentPostId($pdo, $commentPostId)->toArray();
			if($commentPostId !== null) {
				$reply->data = $commentPostId;
			}
		}else if(empty($commentProfileId) === false) {
			$commentProfileId = Comment::getCommentByCommentProfileId($pdo, $commentProfileId)->toArray();
			if($commentProfileId !== null) {
				$reply->data = $commentProfileId;
			}
		} else if(empty($commentContent) === false) {
			$commentContent = Comment::getCommentByCommentContent($pdo, $commentContent);
			if($commentContent !== null) {
				$reply->data = $commentContent;
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
			//enforce the user is signed in and only trying to edit their own comment
			if(empty($_SESSION["comment"]) === true || $_SESSION["comment"]->getCommentId()->toString() !== $id) {
				throw(new \InvalidArgumentException("You are not allowed to access this comment", 403));
			}

			//retrieve the comment to be updated
			$comment = Comment::getCommentByCommentId($pdo, $id);
			if($comment === null) {
				throw(new RuntimeException("Comment does not exist", 404));
			}
			//comment profile id
			if(empty($requestObject->commentProfileId) === true) {
				throw(new \InvalidArgumentException ("No comment profile", 405));
			}
			//comment content is a required field
			if(empty($requestObject->commentContent) === true) {
				throw(new \InvalidArgumentException ("No comment content present", 405));
			}
			$comment->setCommentProfileId($requestObject->commentProfileId);
			$comment->setCommentContent($requestObject->commentContent);
			$comment->update($pdo);
			// update reply
			$reply->message = "Comment information updated";

		} elseif($method === "POST") {

			// enforce the user is signed in
			if(empty($_SESSION["profile"]) === true) {
				throw(new \InvalidArgumentException("you must be logged in to create a comment", 403));
			}

			// create a new comment and insert it into the database
			$comment = new Comment(generateUuidV4(), $_SESSION["profile"]->getProfileId(), $requestObject->commentContent);
			$comment->insert($pdo);

			// update reply
			$reply->message = "Comment created OK";
		}

		// delete method
	} elseif($method === "DELETE") {
		//verify the XSRF Token
		verifyXsrf();
		//enforce the end user has a JWT token
		//validateJwtHeader();
		$comment = Comment::getCommentByCommentId($pdo, $id);
		if($comment === null) {
			throw (new RuntimeException("Comment does not exist"));
		}
		//enforce the user is signed in and only trying to edit their own comment
		if(empty($_SESSION["comment"]) === true || $_SESSION["comment"]->getCommentId()->toString() !== $comment->getCommentId()->toString()) {
			throw(new \InvalidArgumentException("You are not allowed to access this comment", 403));
		}
		validateJwtHeader();
		//delete the comment from the database
		$comment->delete($pdo);
		$reply->message = "Comment Deleted";
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