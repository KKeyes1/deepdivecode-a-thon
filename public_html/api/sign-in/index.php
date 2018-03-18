
<?php
require_once dirname(__DIR__, 3) . "/vendor/autoload.php";
require_once dirname(__DIR__, 3) . "/php/classes/autoload.php";
require_once dirname(__DIR__, 3) . "/php/lib/xsrf.php";
require_once dirname(__DIR__, 3) . "/php/lib/jwt.php";
require_once ("/etc/apache2/capstone-mysql/encrypted-config.php");
use Edu\Cnm\Forum\Profile;
/**
 * api for handling sign-in
 **/
//prepare an empty reply
$reply = new stdClass();
$reply->status = 200;
$reply->data = null;
try {
	//start session
	if(session_status() !== PHP_SESSION_ACTIVE) {
		session_start();
	}
	//grab mySQL statement
	$pdo = connectToEncryptedMySQL("/etc/apache2/capstone-mysql/_CHANGE_NAME_.ini");
	//determine which HTTP method is being used
	$method = array_key_exists("HTTP_X_HTTP_METHOD", $_SERVER) ? $_SERVER["HTTP_X_HTTP_METHOD"] : $_SERVER["REQUEST_METHOD"];
	//If method is post handle the sign in logic
	if($method === "POST") {
		//make sure the XSRF Token is valid
		verifyXsrf();
		//process the request content and decode the json object into a php object
		$requestContent = file_get_contents("php://input");
		$requestObject = json_decode($requestContent);
		//check to make sure the password and email field is not empty.
		if(empty($requestObject->profileEmail) === true) {
			throw(new \InvalidArgumentException("Wrong email address.", 401));
		} else {
			$profileEmail = filter_var($requestObject->profileEmail, FILTER_SANITIZE_EMAIL);
		}
		if(empty($requestObject->profilePassword) === true) {
			throw(new \InvalidArgumentException("Must enter a password.", 401));
		} else {
			$profilePassword = $requestObject->profilePassword;
		}
		//grab the profile from the database by the email provided
		$profile = Profile::getProfileByProfileEmail($pdo, $profileEmail);
		if(empty($profile) === true) {
			throw(new \InvalidArgumentException("Invalid Email", 401));
		}
		//if the profile activation is not null throw an error
		if($profile->getProfileActivationToken() !== null){
			throw (new \InvalidArgumentException ("you are not allowed to sign in unless you have activated your account", 403));
		}
		//hash the password given to make sure it matches.
		$hash = hash_pbkdf2("sha512", $profilePassword, $profile->getProfileSalt(), 262144);
		//verify hash is correct
		if($hash !== $profile->getProfileHash()) {
			throw(new \InvalidArgumentException("Password or email is incorrect.", 401));
		}
		if($profile->getProfilePrivilege() == "1") {
			$_SESSION["profile"] = $profile;
			$authObject = (object) [
				"profileId" =>$profile->getProfileId(),
				"profilePrivilege" => $profile->getProfilePrivilege()
			];
			// create and set th JWT TOKEN
			setJwtAndAuthHeader("auth",$authObject);
			$reply->message = "Sign in was successful.";
		} else {
			//grab profile from database and put into a session
			$profile = Profile::getProfileByProfileId($pdo, $profile->getProfileId());
			$_SESSION["profile"] = $profile;
			//create the Auth payload
			$authObject = (object) [
				"profileId" =>$profile->getProfileId(),
				"profileEmail" => $profile->getProfileEmail()
			];
			// create and set th JWT TOKEN
			setJwtAndAuthHeader("auth",$authObject);
			$reply->message = "Sign in was successful.";
		}
	} else {
		throw(new \InvalidArgumentException("Invalid HTTP method request", 418));
	}
	// if an exception is thrown update the
} catch(Exception | TypeError $exception) {
	$reply->status = $exception->getCode();
	$reply->message = $exception->getMessage();
}
header("Content-type: application/json");
echo json_encode($reply);