<?php
namespace Edu\Cnm\Kmaru;



require_once("autoload.php");
require_once(dirname(__DIR__, 2) . "/vendor/autoload.php");

use PHPUnit\Exception;
use Ramsey\Uuid\Uuid;

/**
 * pvote
 *
 * This is what data is stored when an subscriber votes on a post
 *
 **/

class Pvote implements \JsonSerializable {
	use ValidateUuid;

	/**
	 * id for this Pvote; this is the primary key
	 * @var Uuid $pvoteId
	 **/
	private $pvoteId;
	/**
	 * id of the Profile that created this vote; this is a foreign key
	 * @var Uuid $pvoteProfileId
	 **/
	private $pvoteProfileId;
	/**
	 * id of the post
	 * @var string $pvotePostId
	 **/
	private $pvotePostId;

	/**
	 * constructor for this post vote
	 *
	 * @param Uuid|string $newPvoteId id of this vote or null if new vote
	 * @param Uuid|string $newPvoteProfileId id of the Profile of the creator of this vote
	 * @param string $newPvotePostId the id of post this vote is tied to
	 * @throws \InvalidArgumentException if data types are not valid
	 * @throws \RangeException if data values are out of bounds(ie strings too long, integers negative)
	 * @throws \TypeError if data types violates type hints
	 * @throws \Exception if some other exception occurs
	 * @Documentation https://php.net/manual/en/language.oop5.decon.php (constructors and destructors)
	 **/
	public function __construct($newPvoteId, $newPvoteProfileId, $newPvotePostId) {
		try {
			$this->setPvoteId($newPvoteId);
			$this->setPvoteProfileId($newPvoteProfileId);
			$this->setPvotePostId($newPvotePostId);
		}
			//determine what exception type was thrown
		catch(\InvalidArgumentException | \RangeException | \TypeError | \Exception $exception) {
			$exceptionType = get_class($exception);
			throw(new $exceptionType($exception->getMessage(), 0, $exception));
		}
	}
	/**
	 * accessor method for pvote id
	 *
	 * @return Uuid value of the pvote id
	 **/
	public function getPvoteId() : Uuid {
		return($this->pvoteId);
	}
	/**
	 * mutator method for pvote id
	 *
	 * @param Uuid|string $newPvoteId
	 * @throws \RangeException if $newPvoteId is not positive
	 * @throws \TypeError if $newPvoteId is not a uuid or string
	 **/
	public function setPvoteId($newPvoteId) : void {
		try {
			$uuid = self::validateUuid($newPvoteId);
		} catch(\InvalidArgumentException | \RangeException | \Exception | \TypeError $exception) {
			$exceptionType = get_class($exception);
			throw(new $exceptionType($exception->getMessage(), 0, $exception));
		}
		//convert and store the pvote id
		$this->pvoteId = $uuid;
	}
	/**
	 * accessor method for pvote profile id
	 *
	 * @return Uuid value of pvote profile id
	 **/
	public function getPvoteProfileId() : Uuid {
		return($this->pvoteProfileId);
	}
	/**
	 * mutator method for pvote profile id
	 *
	 * @param Uuid|string $newPvoteProfileId new value of pvote profile id
	 * @throws \RangeException if $newPvoteProfileId is not positive
	 * @throws \TypeError if the $newPvoteProfileId is not a uuid or string
	 **/
	public function setPvoteProfileId($newPvoteProfileId) : void {
		try {
			$uuid = self::validateUuid($newPvoteProfileId);
		} catch(\InvalidArgumentException | \RangeException |\Exception | \TypeError $exception) {
			$exceptionType = get_class($exception->getMessage(), 0, $exception);
		}
		//convert and store the pvote profile id
		$this->pvoteProfileId = $uuid;
	}

	/**
	 * inserts this pvote into mySQL
	 *
	 * @param \PDO $pdo PDO connection object
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError if $pdo is not a PDO connection object
	 **/
	public function insert(\PDO $pdo) : void {

		//create query template
		$query = "INSERT INTO pvote(pvoteId, pvoteProfileId, pvotePostId) VALUES(:pvoteId, :pvoteProfileId, :pvotePostId)";
		$statement = $pdo->prepare($query);

		//bind the member variables to the place-holders on the template
		$parameters = ["pvoteId" => $this->pvoteId->getBytes(),"pvoteProfileId" => $this->pvoteProfileId->getBytes(), "pvotePostId" => $this->pvotePostId];
		$statement->execute($parameters);
	}
	/**
	 * deletes this pvote from mySQL
	 *
	 * @param \PDO $pdo PDO connection object
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError if $pco is not a PDO connection object
	 **/
	public function delete(\PDO $pdo) : void {
		//create query template
		$query = "DELETE FROM pvote WHERE pvoteId = :pvoteId";
		$statement = $pdo->prepare($query);
		//bind the member variables to the place holder in the template
		$parameters =["pvoteId" => $this->pvoteId->getBytes()];
		$statement->execute($parameters);
	}
	/**
	 * updates this pvote in mySQL
	 *
	 * @param \PDO $pdo PDO connection object
	 * @throws \PDOException when mySQL related error occurs
	 * @throws \TypeError if $pdo is not a PDO connection object
	 **/
	public function update(\PDO $pdo) : void {
		//create query template
		$query = "UPDATE pvote SET pvoteProfileId = :pvoteProfileId, pvotePostId = :pvotePostId WHERE pvoteId = :pvoteId";
		$statement = $pdo->prepare($query);
		//bind the member variables to the place-holder in the template
		$parameters = ["pvoteId" => $this->pvoteId->getBytes(),"pvoteProfileId" => $this->pvoteProfileId->getBytes(), "pvotePostId" => $this->pvotePostId];
		$statement->execute($parameters);
	}
	/**
	 * gets the pvote by pvoteId
	 *
	 * @param \PDO $pdo PDO connection object
	 * @param Uuid|string $pvoteId pvote id to search for
	 * @return Pvote|null pvote found or null if not found
	 * @throws \PDOException when mySQL related error occurs
	 * @throws \TypeError when a variable is not correct data type
	 **/
	public static function getPvoteByPvoteId(\PDO $pdo, $pvoteId) : ?Pvote {
		//sanitize the string before searching
		try{
			$pvoteId = self::validateUuid($pvoteId);
		} catch(\InvalidArgumentException | \RangeException | \Exception | \TypeError $exception) {
			throw(new \PDOException($exception->getMessage(), 0, $exception));
		}
		//create query template
		$query = "SELECT pvoteId, pvoteProfileId, pvotePostId FROM pvote WHERE pvoteId = :pvoteId";
		$statement = $pdo->prepare($query);
		//bind the pvote id to the place holder in the template
		$parameters = ["pvoteId" => $pvoteId->getBytes()];
		$statement->execute($parameters);
		//grab the pvote from mySQL
		try {
			$pvote = null;
			$statement->setFetchMode(\PDO::FETCH_ASSOC);
			$row = $statement->fetch();
			if($row !== false) {
				$pvote = new Pvote($row["pvoteId"], $row["pvoteProfileId"], $row["pvotePostId"]);
			}
		} catch(\Exception $exception) {
			//if the row couldn't be converted, then rethrow it
			throw(new \PDOException($exception->getMessage(), 0, $exception));
		}
		return($pvote);
	}
	/**
	 * gets the pvote by profile id
	 *
	 * @param \PDO $pdo PDO connection object
	 * @param Uuid|string $pvoteProfileId pvote profile id to search by
	 * @return \SplFixedArray SplFixedArray of pvotes found
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError when variables are not the correct data type
	 */
	public static function getPvoteByPvoteProfileId(\PDO $pdo, $pvoteProfileId) : \SplFixedArray {
		try {
			$pvoteProfileId = self::validateUuid($pvoteProfileId);
		} catch(\InvalidArgumentException | \RangeException | \Exception | \TypeError $exception) {
			throw(new \PDOException($exception->getMessage(), 0, $exception));
		}
		// create query template
		$query = "SELECT pvoteId, pvoteProfileId, pvotePostId FROM pvote WHERE pvoteProfileId = :pvoteProfileId";
		$statement = $pdo->prepare($query);
		//bind the pvoteProfileId to the place holder in the template
		$parameters = ["pvoteProfileId" => $pvoteProfileId->getBytes()];
		$statement->execute($parameters);
		//build an array of pvotes
		$pvotes = new \SplFixedArray($statement->rowCount());
		$statement->setFetchMode(\PDO::FETCH_ASSOC);
		while(($row = $statement->fetch()) !== false) {
			try {
				$pvote = new Pvote($row["pvoteId"], $row["pvoteProfileId"], $row["pvotePostId"]);
				$pvotes[$pvotes->key()] = $pvote;
				$pvotes->next();
			} catch(\Exception $exception) {
				//if the row could not be converted, rethrow it
				throw(new \PDOException($exception->getMessage(), 0, $exception));
			}
		}
		return($pvotes);
	}
	/**
	 * gets the pvote by post id
	 *
	 * @param \PDO $pdo PDO connection object
	 * @param Uuid|string $pvotePostId pvote post id to search by
	 * @return \SplFixedArray SplFixedArray of pvotes found
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError when variables are not the correct data type
	 */
	public static function getPvoteByPvotePostId(\PDO $pdo, $pvotePostId) : \SplFixedArray {
		try {
			$pvotePostId = self::validateUuid($pvotePostId);
		} catch(\InvalidArgumentException | \RangeException | \Exception | \TypeError $exception) {
			throw(new \PDOException($exception->getMessage(), 0, $exception));
		}
		// create query template
		$query = "SELECT pvoteId, pvoteProfileId, pvotePostId FROM pvote WHERE pvotePostId = :pvotePostId";
		$statement = $pdo->prepare($query);
		//bind the pvotePostId to the place holder in the template
		$parameters = ["pvotePostId" => $pvotePostId->getBytes()];
		$statement->execute($parameters);
		//build an array of pvotes
		$pvotes = new \SplFixedArray($statement->rowCount());
		$statement->setFetchMode(\PDO::FETCH_ASSOC);
		while(($row = $statement->fetch()) !== false) {
			try {
				$pvote = new Pvote($row["pvoteId"], $row["pvoteProfileId"], $row["pvotePostId"]);
				$pvotes[$pvotes->key()] = $pvote;
				$pvotes->next();
			} catch(\Exception $exception) {
				//if the row could not be converted, rethrow it
				throw(new \PDOException($exception->getMessage(), 0, $exception));
			}
		}
		return($pvotes);
	}
	/**
	 * formats the state variables for JSON serialization
	 *
	 * @return array result in state variables to serialize
	 **/
	public function jsonSerialize() {
		$fields = get_object_vars($this);

		$fields["pvoteId"] = $this->pvoteId->toString();
		$fields["pvoteProfileId"] = $this->postProfileId->toString();
		$fields["pvotePostId"] = $this->postPostId->toString();

		return($fields);
	}






}
?>