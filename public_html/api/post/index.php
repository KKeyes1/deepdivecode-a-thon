
<?php
require_once(dirname(__DIR__, 3) . "/vendor/autoload.php");
require_once(dirname(__DIR__, 3) . "/php/classes/autoload.php");
require_once(dirname(__DIR__, 3) . "/php/lib/jwt.php");
require_once(dirname(__DIR__, 3) . "/php/lib/xsrf.php");
require_once(dirname(__DIR__, 3) . "/php/lib/uuid.php");
require_once("/etc/apache2/capstone-mysql/encrypted-config.php");
use Edu\Cnm\Forum\Post;

/**
 * API for Post
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
	$postCommunityId = filter_input(INPUT_GET, "postCommunityId", FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
	$postProfileId = filter_input(INPUT_GET, "postProfileId", FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
	$postContent = filter_input(INPUT_GET, "postContent", FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
	// make sure the id is valid for methods that require it
	if(($method === "DELETE" || $method === "PUT") && (empty($id) === true )) {
		throw(new InvalidArgumentException("id cannot be empty or negative", 405));
	}
	if($method === "GET") {
		//set XSRF cookie
		setXsrfCookie();
		//gets a post by content
		if(empty($id) === false) {
			$post = Post::getPostByPostId($pdo, $id);
			if($post !== null) {
				$reply->data = $post;
			}
		} else if(empty($postCommunityId) === false) {
			$postCommunityId = Post::getPostByPostCommunityId($pdo, $postCommunityId)->toArray();
			if($postCommunityId !== null) {
				$reply->data = $postCommunityId;
			}
		}else if(empty($postProfileId) === false) {
			$postProfileId = Post::getPostByPostProfileId($pdo, $postProfileId)->toArray();
			if($postProfileId !== null) {
				$reply->data = $postProfileId;
			}
		} else if(empty($postContent) === false) {
			$postContent = Post::getPostByPostContent($pdo, $postContent);
			if($postContent !== null) {
				$reply->data = $postContent;
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
			//enforce the user is signed in and only trying to edit their own post
			if(empty($_SESSION["post"]) === true || $_SESSION["post"]->getPostId()->toString() !== $id) {
				throw(new \InvalidArgumentException("You are not allowed to access this post", 403));
			}

			//retrieve the post to be updated
			$post = Post::getPostByPostId($pdo, $id);
			if($post === null) {
				throw(new RuntimeException("Post does not exist", 404));
			}
			//post profile id
			if(empty($requestObject->postProfileId) === true) {
				throw(new \InvalidArgumentException ("No post profile", 405));
			}
			//post content is a required field
			if(empty($requestObject->postContent) === true) {
				throw(new \InvalidArgumentException ("No post content present", 405));
			}
			$post->setPostProfileId($requestObject->postProfileId);
			$post->setPostContent($requestObject->postContent);
			$post->update($pdo);
			// update reply
			$reply->message = "Post information updated";

		} elseif($method === "POST") {

			// enforce the user is signed in
			if(empty($_SESSION["profile"]) === true) {
				throw(new \InvalidArgumentException("you must be logged in to create a post", 403));
			}

			// create a new post and insert it into the database
			$post = new Post(generateUuidV4(), $_SESSION["profile"]->getProfileId(), $requestObject->postContent);
			$post->insert($pdo);

			// update reply
			$reply->message = "Post created OK";
		}

		// delete method
	} elseif($method === "DELETE") {
		//verify the XSRF Token
		verifyXsrf();
		//enforce the end user has a JWT token
		//validateJwtHeader();
		$post = Post::getPostByPostId($pdo, $id);
		if($post === null) {
			throw (new RuntimeException("Post does not exist"));
		}
		//enforce the user is signed in and only trying to edit their own post
		if(empty($_SESSION["post"]) === true || $_SESSION["post"]->getPostId()->toString() !== $post->getPostId()->toString()) {
			throw(new \InvalidArgumentException("You are not allowed to access this post", 403));
		}
		validateJwtHeader();
		//delete the post from the database
		$post->delete($pdo);
		$reply->message = "Post Deleted";
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