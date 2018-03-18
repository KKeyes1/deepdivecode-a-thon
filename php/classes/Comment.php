<?php
namespace Edu\Cnm\Kmaru;



require_once("autoload.php");
require_once(dirname(__DIR__, 2) . "/vendor/autoload.php");

use PHPUnit\Exception;
use Ramsey\Uuid\Uuid;

/**
 * comment
 *
 * This is what data is stored when an subscriber creates a new comment
 *
 **/

class Comment implements \JsonSerializable {
	use ValidateUuid;

	/**
	 * id for this Comment; this is the primary key
	 * @var Uuid $commentId
	 **/
	private $commentId;
	/**
	 * id of the community that created this comment; this is a foreign key
	 * @var Uuid $commentCommunityId
	 **/
	private $commentCommunityId;
	/**
	 * id of the Profile that created this comment; this is a foreign key
	 * @var Uuid $commentProfileId
	 **/
	private $commentProfileId;
	/**
	 * name of the comment
	 * @var string $commentContent
	 **/
	private $commentContent;

	/**
	 * constructor for this comment
	 *
	 * @param Uuid|string $newCommentId id of this comment or null if new comment
	 * @param Uuid|string $newCommentProfileId id of the Profile of the creator of this Comment
	 * @param string $newCommentContent the Content of this Comment
	 * @throws \InvalidArgumentException if data types are not valid
	 * @throws \RangeException if data values are out of bounds(ie strings too long, integers negative)
	 * @throws \TypeError if data types violates type hints
	 * @throws \Exception if some other exception occurs
	 * @Documentation https://php.net/manual/en/language.oop5.decon.php (constructors and destructors)
	 **/
	public function __construct($newCommentId, $newCommentProfileId, $newCommentCommunityId, string $newCommentContent) {
		try {
			$this->setCommentId($newCommentId);
			$this->setCommentCommunityId($newCommentCommunityId);
			$this->setCommentProfileId($newCommentProfileId);
			$this->setCommentContent($newCommentContent);
		}
			//determine what exception type was thrown
		catch(\InvalidArgumentException | \RangeException | \TypeError | \Exception $exception) {
			$exceptionType = get_class($exception);
			throw(new $exceptionType($exception->getMessage(), 0, $exception));
		}
	}
	/**
	 * accessor method for comment id
	 *
	 * @return Uuid value of the comment id
	 **/
	public function getCommentId() : Uuid {
		return($this->commentId);
	}
	/**
	 * mutator method for comment id
	 *
	 * @param Uuid|string $newCommentId
	 * @throws \RangeException if $newCommentId is not positive
	 * @throws \TypeError if $newCommentId is not a uuid or string
	 **/
	public function setCommentId($newCommentId) : void {
		try {
			$uuid = self::validateUuid($newCommentId);
		} catch(\InvalidArgumentException | \RangeException | \Exception | \TypeError $exception) {
			$exceptionType = get_class($exception);
			throw(new $exceptionType($exception->getMessage(), 0, $exception));
		}
		//convert and store the comment id
		$this->commentId = $uuid;
	}
	/**
	 * accessor method for comment community id
	 *
	 * @return Uuid value of comment community id
	 **/
	public function getCommentCommunityId() : Uuid {
		return($this->commentCommunityId);
	}
	/**
	 * mutator method for comment community id
	 *
	 * @param Uuid|string $newCommentCommunityId new value of comment community id
	 * @throws \RangeException if $newCommentCommunityId is not positive
	 * @throws \TypeError if the $newCommentCommunityId is not a uuid or string
	 **/
	public function setCommentCommunityId($newCommentCommunityId) : void {
		try {
			$uuid = self::validateUuid($newCommentCommunityId);
		} catch(\InvalidArgumentException | \RangeException |\Exception | \TypeError $exception) {
			$exceptionType = get_class($exception->getMessage(), 0, $exception);
		}
		//convert and store the comment community id
		$this->commentCommunityId = $uuid;
	}
	/**
	 * accessor method for comment profile id
	 *
	 * @return Uuid value of comment profile id
	 **/
	public function getCommentProfileId() : Uuid {
		return($this->commentProfileId);
	}
	/**
	 * mutator method for comment profile id
	 *
	 * @param Uuid|string $newCommentProfileId new value of comment profile id
	 * @throws \RangeException if $newCommentProfileId is not positive
	 * @throws \TypeError if the $newCommentProfileId is not a uuid or string
	 **/
	public function setCommentProfileId($newCommentProfileId) : void {
		try {
			$uuid = self::validateUuid($newCommentProfileId);
		} catch(\InvalidArgumentException | \RangeException |\Exception | \TypeError $exception) {
			$exceptionType = get_class($exception->getMessage(), 0, $exception);
		}
		//convert and store the comment profile id
		$this->commentProfileId = $uuid;
	}
	/**
	 * accessor method for comment Content
	 * @return string value of comment Content
	 **/
	public function getCommentContent() : string {
		return($this->commentContent);
	}
	/**
	 * mutator method for comment Content
	 *
	 * @param string $newCommentContent new value of comment Content
	 * @throws \InvalidArgumentException if $newCommentContent is not a string or insecure
	 * @throws \RangeException if $newCommentContent is >40000 characters
	 * @throws \TypeError if $newCommentContent is not a string
	 **/
	public function setCommentContent(string $newCommentContent) : void {
		//verify the comment Content is secure
		$newCommentContent = trim($newCommentContent);
		$newCommentContent = filter_var($newCommentContent, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
		if(empty($newCommentContent) === true) {
			throw(new \InvalidArgumentException("comment Content is empty or insecure"));
		}
		//verify the comment Content will fit in the database
		if(strlen($newCommentContent) > 40000) {
			throw(new \RangeException("comment too long"));
		}
		//store the comment name
		$this->commentContent = $newCommentContent;
	}
	/**
	 * inserts this comment into mySQL
	 *
	 * @param \PDO $pdo PDO connection object
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError if $pdo is not a PDO connection object
	 **/
	public function insert(\PDO $pdo) : void {

		//create query template
		$query = "INSERT INTO comment(commentId, commentCommunityId, commentProfileId, commentContent) VALUES(:commentId, :commentCommunityId, :commentProfileId, :commentContent)";
		$statement = $pdo->prepare($query);

		//bind the member variables to the place-holders on the template
		$parameters = ["commentId" => $this->commentId->getBytes(), "commentCommunityId" => $this->commentCommunityId, "commentProfileId" => $this->commentProfileId->getBytes(), "commentContent" => $this->commentContent];
		$statement->execute($parameters);
	}
	/**
	 * deletes this comment from mySQL
	 *
	 * @param \PDO $pdo PDO connection object
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError if $pco is not a PDO connection object
	 **/
	public function delete(\PDO $pdo) : void {
		//create query template
		$query = "DELETE FROM comment WHERE commentId = :commentId";
		$statement = $pdo->prepare($query);
		//bind the member variables to the place holder in the template
		$parameters =["commentId" => $this->commentId->getBytes()];
		$statement->execute($parameters);
	}
	/**
	 * updates this comment in mySQL
	 *
	 * @param \PDO $pdo PDO connection object
	 * @throws \PDOException when mySQL related error occurs
	 * @throws \TypeError if $pdo is not a PDO connection object
	 **/
	public function update(\PDO $pdo) : void {
		//create query template
		$query = "UPDATE comment SET commentCommunityId = :commentCommunityId, commentProfileId = :commentProfileId, commentContent = :commentContent WHERE commentId = :commentId";
		$statement = $pdo->prepare($query);
		//bind the member variables to the place-holder in the template
		$parameters = ["commentId" => $this->commentId->getBytes(), "commentCommunityId" => $this->commentCommunityId, "commentProfileId" => $this->commentProfileId->getBytes(), "commentContent" => $this->commentContent];
		$statement->execute($parameters);
	}
	/**
	 * gets the comment by commentId
	 *
	 * @param \PDO $pdo PDO connection object
	 * @param Uuid|string $commentId comment id to search for
	 * @return Comment|null comment found or null if not found
	 * @throws \PDOException when mySQL related error occurs
	 * @throws \TypeError when a variable is not correct data type
	 **/
	public static function getCommentByCommentId(\PDO $pdo, $commentId) : ?Comment {
		//sanitize the string before searching
		try{
			$commentId = self::validateUuid($commentId);
		} catch(\InvalidArgumentException | \RangeException | \Exception | \TypeError $exception) {
			throw(new \PDOException($exception->getMessage(), 0, $exception));
		}
		//create query template
		$query = "SELECT commentId, commentCommunityId, commentProfileId, commentContent FROM comment WHERE commentId = :commentId";
		$statement = $pdo->prepare($query);
		//bind the comment id to the place holder in the template
		$parameters = ["commentId" => $commentId->getBytes()];
		$statement->execute($parameters);
		//grab the comment from mySQL
		try {
			$comment = null;
			$statement->setFetchMode(\PDO::FETCH_ASSOC);
			$row = $statement->fetch();
			if($row !== false) {
				$comment = new Comment($row["commentId"], $row["commentCommunityId"], $row["commentProfileId"], $row["commentContent"]);
			}
		} catch(\Exception $exception) {
			//if the row couldn't be converted, then rethrow it
			throw(new \PDOException($exception->getMessage(), 0, $exception));
		}
		return($comment);
	}
	/**
	 * gets the comment by community id
	 *
	 * @param \PDO $pdo PDO connection object
	 * @param Uuid|string $commentCommunityId comment community id to search by
	 * @return \SplFixedArray SplFixedArray of comments found
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError when variables are not the correct data type
	 */
	public static function getCommentByCommentCommunityId(\PDO $pdo, $commentCommunityId) : \SplFixedArray {
		try {
			$commentCommunityId = self::validateUuid($commentCommunityId);
		} catch(\InvalidArgumentException | \RangeException | \Exception | \TypeError $exception) {
			throw(new \PDOException($exception->getMessage(), 0, $exception));
		}
		// create query template
		$query = "SELECT commentId, commentCommunityId, commentProfileId, commentContent FROM comment WHERE commentCommunityId = :commentCommunityId";
		$statement = $pdo->prepare($query);
		//bind the commentCommunityId to the place holder in the template
		$parameters = ["commentCommunityId" => $commentCommunityId->getBytes()];
		$statement->execute($parameters);
		//build an array of comments
		$comments = new \SplFixedArray($statement->rowCount());
		$statement->setFetchMode(\PDO::FETCH_ASSOC);
		while(($row = $statement->fetch()) !== false) {
			try {
				$comment = new Comment($row["commentId"], $row["commentCommunityId"], $row["commentProfileId"], $row["commentContent"]);
				$comments[$comments->key()] = $comment;
				$comments->next();
			} catch(\Exception $exception) {
				//if the row could not be converted, rethrow it
				throw(new \PDOException($exception->getMessage(), 0, $exception));
			}
		}
		return($comments);
	}
	/**
	 * gets the comment by profile id
	 *
	 * @param \PDO $pdo PDO connection object
	 * @param Uuid|string $commentProfileId comment profile id to search by
	 * @return \SplFixedArray SplFixedArray of comments found
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError when variables are not the correct data type
	 */
	public static function getCommentByCommentProfileId(\PDO $pdo, $commentProfileId) : \SplFixedArray {
		try {
			$commentProfileId = self::validateUuid($commentProfileId);
		} catch(\InvalidArgumentException | \RangeException | \Exception | \TypeError $exception) {
			throw(new \PDOException($exception->getMessage(), 0, $exception));
		}
		// create query template
		$query = "SELECT commentId, commentCommunityId, commentProfileId, commentContent FROM comment WHERE commentProfileId = :commentProfileId";
		$statement = $pdo->prepare($query);
		//bind the commentProfileId to the place holder in the template
		$parameters = ["commentProfileId" => $commentProfileId->getBytes()];
		$statement->execute($parameters);
		//build an array of comments
		$comments = new \SplFixedArray($statement->rowCount());
		$statement->setFetchMode(\PDO::FETCH_ASSOC);
		while(($row = $statement->fetch()) !== false) {
			try {
				$comment = new Comment($row["commentId"], $row["commentCommunityId"], $row["commentProfileId"], $row["commentContent"]);
				$comments[$comments->key()] = $comment;
				$comments->next();
			} catch(\Exception $exception) {
				//if the row could not be converted, rethrow it
				throw(new \PDOException($exception->getMessage(), 0, $exception));
			}
		}
		return($comments);
	}
	/**
	 * gets all comments
	 *
	 * @param \PDO $pdo PDO connection object
	 * @return \SplFixedArray SplFixedArray of comments found or null if not fund
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError when variables are not the correct data type
	 **/
	public static function getAllComments(\PDO $pdo) : \SPLFixedArray {
		//create query template
		$query = "SELECT commentId, commentCommunityId, commentProfileId, commentContent FROM comment";
		$statement = $pdo->prepare($query);
		$statement->execute();
		//built and array of comments
		$comments = new \SplFixedArray(($statement->rowCount()));
		$statement->setFetchMode(\PDO::FETCH_ASSOC);
		while(($row = $statement->fetch()) !== false) {
			try {
				$comment = new Comment($row["commentId"], $row["commentCommunityId"], $row["commentProfileId"], $row["commentContent"]);
				$comments[$comments->key()] = $comment;
				$comments->next();
			} catch(\Exception $exception) {
				//if the row couldn't be converted, rethrow it
				throw(new \PDOException($exception->getMessage(), 0, $exception));
			}
			return ($comments);
		}
	}
	/**
	 * formats the state variables for JSON serialization
	 *
	 * @return array result in state variables to serialize
	 **/
	public function jsonSerialize() {
		$fields = get_object_vars($this);

		$fields["commentId"] = $this->commentId->toString();
		$fields["commentProfileId"] = $this->commentProfileId->toString();

		return($fields);
	}






}
?>