<?php
namespace Edu\Cnm\Kmaru;



require_once("autoload.php");
require_once(dirname(__DIR__, 2) . "/vendor/autoload.php");

use PHPUnit\Exception;
use Ramsey\Uuid\Uuid;

/**
 * post
 *
 * This is what data is stored when an subscriber creates a new post
 *
 **/

class Post implements \JsonSerializable {
	use ValidateUuid;

	/**
	 * id for this Post; this is the primary key
	 * @var Uuid $postId
	 **/
	private $postId;
	/**
	 * id of the community that created this post; this is a foreign key
	 * @var Uuid $postCommunityId
	 **/
	private $postCommunityId;
	/**
	 * id of the Profile that created this post; this is a foreign key
	 * @var Uuid $postProfileId
	 **/
	private $postProfileId;
	/**
	 * name of the post
	 * @var string $postContent
	 **/
	private $postContent;

	/**
	 * constructor for this post
	 *
	 * @param Uuid|string $newPostId id of this post or null if new post
	 * @param Uuid|string $newPostProfileId id of the Profile of the creator of this Post
	 * @param string $newPostContent the Content of this Post
	 * @throws \InvalidArgumentException if data types are not valid
	 * @throws \RangeException if data values are out of bounds(ie strings too long, integers negative)
	 * @throws \TypeError if data types violates type hints
	 * @throws \Exception if some other exception occurs
	 * @Documentation https://php.net/manual/en/language.oop5.decon.php (constructors and destructors)
	 **/
	public function __construct($newPostId, $newPostProfileId, $newPostCommunityId, string $newPostContent) {
		try {
			$this->setPostId($newPostId);
			$this->setPostCommunityId($newPostCommunityId);
			$this->setPostProfileId($newPostProfileId);
			$this->setPostContent($newPostContent);
		}
			//determine what exception type was thrown
		catch(\InvalidArgumentException | \RangeException | \TypeError | \Exception $exception) {
			$exceptionType = get_class($exception);
			throw(new $exceptionType($exception->getMessage(), 0, $exception));
		}
	}
	/**
	 * accessor method for post id
	 *
	 * @return Uuid value of the post id
	 **/
	public function getPostId() : Uuid {
		return($this->postId);
	}
	/**
	 * mutator method for post id
	 *
	 * @param Uuid|string $newPostId
	 * @throws \RangeException if $newPostId is not positive
	 * @throws \TypeError if $newPostId is not a uuid or string
	 **/
	public function setPostId($newPostId) : void {
		try {
			$uuid = self::validateUuid($newPostId);
		} catch(\InvalidArgumentException | \RangeException | \Exception | \TypeError $exception) {
			$exceptionType = get_class($exception);
			throw(new $exceptionType($exception->getMessage(), 0, $exception));
		}
		//convert and store the post id
		$this->postId = $uuid;
	}
	/**
	 * accessor method for post community id
	 *
	 * @return Uuid value of post community id
	 **/
	public function getPostCommunityId() : Uuid {
		return($this->postCommunityId);
	}
	/**
	 * mutator method for post community id
	 *
	 * @param Uuid|string $newPostCommunityId new value of post community id
	 * @throws \RangeException if $newPostCommunityId is not positive
	 * @throws \TypeError if the $newPostCommunityId is not a uuid or string
	 **/
	public function setPostCommunityId($newPostCommunityId) : void {
		try {
			$uuid = self::validateUuid($newPostCommunityId);
		} catch(\InvalidArgumentException | \RangeException |\Exception | \TypeError $exception) {
			$exceptionType = get_class($exception->getMessage(), 0, $exception);
		}
		//convert and store the post community id
		$this->postCommunityId = $uuid;
	}
	/**
	 * accessor method for post profile id
	 *
	 * @return Uuid value of post profile id
	 **/
	public function getPostProfileId() : Uuid {
		return($this->postProfileId);
	}
	/**
	 * mutator method for post profile id
	 *
	 * @param Uuid|string $newPostProfileId new value of post profile id
	 * @throws \RangeException if $newPostProfileId is not positive
	 * @throws \TypeError if the $newPostProfileId is not a uuid or string
	 **/
	public function setPostProfileId($newPostProfileId) : void {
		try {
			$uuid = self::validateUuid($newPostProfileId);
		} catch(\InvalidArgumentException | \RangeException |\Exception | \TypeError $exception) {
			$exceptionType = get_class($exception->getMessage(), 0, $exception);
		}
		//convert and store the post profile id
		$this->postProfileId = $uuid;
	}
	/**
	 * accessor method for post Content
	 * @return string value of post Content
	 **/
	public function getPostContent() : string {
		return($this->postContent);
	}
	/**
	 * mutator method for post Content
	 *
	 * @param string $newPostContent new value of post Content
	 * @throws \InvalidArgumentException if $newPostContent is not a string or insecure
	 * @throws \RangeException if $newPostContent is >40000 characters
	 * @throws \TypeError if $newPostContent is not a string
	 **/
	public function setPostContent(string $newPostContent) : void {
		//verify the post Content is secure
		$newPostContent = trim($newPostContent);
		$newPostContent = filter_var($newPostContent, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
		if(empty($newPostContent) === true) {
			throw(new \InvalidArgumentException("post Content is empty or insecure"));
		}
		//verify the post Content will fit in the database
		if(strlen($newPostContent) > 40000) {
			throw(new \RangeException("post too long"));
		}
		//store the post name
		$this->postContent = $newPostContent;
	}
	/**
	 * inserts this post into mySQL
	 *
	 * @param \PDO $pdo PDO connection object
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError if $pdo is not a PDO connection object
	 **/
	public function insert(\PDO $pdo) : void {

		//create query template
		$query = "INSERT INTO post(postId, postCommunityId, postProfileId, postContent) VALUES(:postId, :postCommunityId, :postProfileId, :postContent)";
		$statement = $pdo->prepare($query);

		//bind the member variables to the place-holders on the template
		$parameters = ["postId" => $this->postId->getBytes(), "postCommunityId" => $this->postCommunityId, "postProfileId" => $this->postProfileId->getBytes(), "postContent" => $this->postContent];
		$statement->execute($parameters);
	}
	/**
	 * deletes this post from mySQL
	 *
	 * @param \PDO $pdo PDO connection object
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError if $pco is not a PDO connection object
	 **/
	public function delete(\PDO $pdo) : void {
		//create query template
		$query = "DELETE FROM post WHERE postId = :postId";
		$statement = $pdo->prepare($query);
		//bind the member variables to the place holder in the template
		$parameters =["postId" => $this->postId->getBytes()];
		$statement->execute($parameters);
	}
	/**
	 * updates this post in mySQL
	 *
	 * @param \PDO $pdo PDO connection object
	 * @throws \PDOException when mySQL related error occurs
	 * @throws \TypeError if $pdo is not a PDO connection object
	 **/
	public function update(\PDO $pdo) : void {
		//create query template
		$query = "UPDATE post SET postCommunityId = :postCommunityId, postProfileId = :postProfileId, postContent = :postContent WHERE postId = :postId";
		$statement = $pdo->prepare($query);
		//bind the member variables to the place-holder in the template
		$parameters = ["postId" => $this->postId->getBytes(), "postCommunityId" => $this->postCommunityId, "postProfileId" => $this->postProfileId->getBytes(), "postContent" => $this->postContent];
		$statement->execute($parameters);
	}
	/**
	 * gets the post by postId
	 *
	 * @param \PDO $pdo PDO connection object
	 * @param Uuid|string $postId post id to search for
	 * @return Post|null post found or null if not found
	 * @throws \PDOException when mySQL related error occurs
	 * @throws \TypeError when a variable is not correct data type
	 **/
	public static function getPostByPostId(\PDO $pdo, $postId) : ?Post {
		//sanitize the string before searching
		try{
			$postId = self::validateUuid($postId);
		} catch(\InvalidArgumentException | \RangeException | \Exception | \TypeError $exception) {
			throw(new \PDOException($exception->getMessage(), 0, $exception));
		}
		//create query template
		$query = "SELECT postId, postCommunityId, postProfileId, postContent FROM post WHERE postId = :postId";
		$statement = $pdo->prepare($query);
		//bind the post id to the place holder in the template
		$parameters = ["postId" => $postId->getBytes()];
		$statement->execute($parameters);
		//grab the post from mySQL
		try {
			$post = null;
			$statement->setFetchMode(\PDO::FETCH_ASSOC);
			$row = $statement->fetch();
			if($row !== false) {
				$post = new Post($row["postId"], $row["postCommunityId"], $row["postProfileId"], $row["postContent"]);
			}
		} catch(\Exception $exception) {
			//if the row couldn't be converted, then rethrow it
			throw(new \PDOException($exception->getMessage(), 0, $exception));
		}
		return($post);
	}
	/**
	 * gets the post by community id
	 *
	 * @param \PDO $pdo PDO connection object
	 * @param Uuid|string $postCommunityId post community id to search by
	 * @return \SplFixedArray SplFixedArray of posts found
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError when variables are not the correct data type
	 */
	public static function getPostByPostCommunityId(\PDO $pdo, $postCommunityId) : \SplFixedArray {
		try {
			$postCommunityId = self::validateUuid($postCommunityId);
		} catch(\InvalidArgumentException | \RangeException | \Exception | \TypeError $exception) {
			throw(new \PDOException($exception->getMessage(), 0, $exception));
		}
		// create query template
		$query = "SELECT postId, postCommunityId, postProfileId, postContent FROM post WHERE postCommunityId = :postCommunityId";
		$statement = $pdo->prepare($query);
		//bind the postCommunityId to the place holder in the template
		$parameters = ["postCommunityId" => $postCommunityId->getBytes()];
		$statement->execute($parameters);
		//build an array of posts
		$posts = new \SplFixedArray($statement->rowCount());
		$statement->setFetchMode(\PDO::FETCH_ASSOC);
		while(($row = $statement->fetch()) !== false) {
			try {
				$post = new Post($row["postId"], $row["postCommunityId"], $row["postProfileId"], $row["postContent"]);
				$posts[$posts->key()] = $post;
				$posts->next();
			} catch(\Exception $exception) {
				//if the row could not be converted, rethrow it
				throw(new \PDOException($exception->getMessage(), 0, $exception));
			}
		}
		return($posts);
	}
	/**
	 * gets the post by profile id
	 *
	 * @param \PDO $pdo PDO connection object
	 * @param Uuid|string $postProfileId post profile id to search by
	 * @return \SplFixedArray SplFixedArray of posts found
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError when variables are not the correct data type
	 */
	public static function getPostByPostProfileId(\PDO $pdo, $postProfileId) : \SplFixedArray {
		try {
			$postProfileId = self::validateUuid($postProfileId);
		} catch(\InvalidArgumentException | \RangeException | \Exception | \TypeError $exception) {
			throw(new \PDOException($exception->getMessage(), 0, $exception));
		}
		// create query template
		$query = "SELECT postId, postCommunityId, postProfileId, postContent FROM post WHERE postProfileId = :postProfileId";
		$statement = $pdo->prepare($query);
		//bind the postProfileId to the place holder in the template
		$parameters = ["postProfileId" => $postProfileId->getBytes()];
		$statement->execute($parameters);
		//build an array of posts
		$posts = new \SplFixedArray($statement->rowCount());
		$statement->setFetchMode(\PDO::FETCH_ASSOC);
		while(($row = $statement->fetch()) !== false) {
			try {
				$post = new Post($row["postId"], $row["postCommunityId"], $row["postProfileId"], $row["postContent"]);
				$posts[$posts->key()] = $post;
				$posts->next();
			} catch(\Exception $exception) {
				//if the row could not be converted, rethrow it
				throw(new \PDOException($exception->getMessage(), 0, $exception));
			}
		}
		return($posts);
	}
	/**
	 * gets all posts
	 *
	 * @param \PDO $pdo PDO connection object
	 * @return \SplFixedArray SplFixedArray of posts found or null if not fund
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError when variables are not the correct data type
	 **/
	public static function getAllPosts(\PDO $pdo) : \SPLFixedArray {
		//create query template
		$query = "SELECT postId, postCommunityId, postProfileId, postContent FROM post";
		$statement = $pdo->prepare($query);
		$statement->execute();
		//built and array of posts
		$posts = new \SplFixedArray(($statement->rowCount()));
		$statement->setFetchMode(\PDO::FETCH_ASSOC);
		while(($row = $statement->fetch()) !== false) {
			try {
				$post = new Post($row["postId"], $row["postCommunityId"], $row["postProfileId"], $row["postContent"]);
				$posts[$posts->key()] = $post;
				$posts->next();
			} catch(\Exception $exception) {
				//if the row couldn't be converted, rethrow it
				throw(new \PDOException($exception->getMessage(), 0, $exception));
			}
			return ($posts);
		}
	}
	/**
	 * formats the state variables for JSON serialization
	 *
	 * @return array result in state variables to serialize
	 **/
	public function jsonSerialize() {
		$fields = get_object_vars($this);

		$fields["postId"] = $this->postId->toString();
		$fields["postProfileId"] = $this->postProfileId->toString();

		return($fields);
	}






}
?>