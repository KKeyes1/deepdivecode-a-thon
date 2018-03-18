<?php
namespace Edu\Cnm\Kmaru;



require_once("autoload.php");
require_once(dirname(__DIR__, 2) . "/vendor/autoload.php");

use PHPUnit\Exception;
use Ramsey\Uuid\Uuid;

/**
 * cvote
 *
 * This is what data is stored when an subscriber votes on a comment
 *
 **/

class Cvote implements \JsonSerializable {
	use ValidateUuid;

	/**
	 * id for this Cvote; this is the primary key
	 * @var Uuid $cvoteId
	 **/
	private $cvoteId;
	/**
	 * id of the Profile that created this vote; this is a foreign key
	 * @var Uuid $cvoteProfileId
	 **/
	private $cvoteProfileId;
	/**
	 * id of the comment
	 * @var string $cvoteCommentId
	 **/
	private $cvoteCommentId;

	/**
	 * constructor for this comment vote
	 *
	 * @param Uuid|string $newCvoteId id of this vote or null if new vote
	 * @param Uuid|string $newCvoteProfileId id of the Profile of the creator of this vote
	 * @param string $newCvoteCommentId the id of comment this vote is tied to
	 * @throws \InvalidArgumentException if data types are not valid
	 * @throws \RangeException if data values are out of bounds(ie strings too long, integers negative)
	 * @throws \TypeError if data types violates type hints
	 * @throws \Exception if some other exception occurs
	 * @Documentation https://php.net/manual/en/language.oop5.decon.php (constructors and destructors)
	 **/
	public function __construct($newCvoteId, $newCvoteProfileId, $newCvoteCommentId) {
		try {
			$this->setCvoteId($newCvoteId);
			$this->setCvoteProfileId($newCvoteProfileId);
			$this->setCvoteCommentId($newCvoteCommentId);
		}
			//determine what exception type was thrown
		catch(\InvalidArgumentException | \RangeException | \TypeError | \Exception $exception) {
			$exceptionType = get_class($exception);
			throw(new $exceptionType($exception->getMessage(), 0, $exception));
		}
	}
	/**
	 * accessor method for cvote id
	 *
	 * @return Uuid value of the cvote id
	 **/
	public function getCvoteId() : Uuid {
		return($this->cvoteId);
	}
	/**
	 * mutator method for cvote id
	 *
	 * @param Uuid|string $newCvoteId
	 * @throws \RangeException if $newCvoteId is not positive
	 * @throws \TypeError if $newCvoteId is not a uuid or string
	 **/
	public function setCvoteId($newCvoteId) : void {
		try {
			$uuid = self::validateUuid($newCvoteId);
		} catch(\InvalidArgumentException | \RangeException | \Exception | \TypeError $exception) {
			$exceptionType = get_class($exception);
			throw(new $exceptionType($exception->getMessage(), 0, $exception));
		}
		//convert and store the cvote id
		$this->cvoteId = $uuid;
	}
	/**
	 * accessor method for cvote profile id
	 *
	 * @return Uuid value of cvote profile id
	 **/
	public function getCvoteProfileId() : Uuid {
		return($this->cvoteProfileId);
	}
	/**
	 * mutator method for cvote profile id
	 *
	 * @param Uuid|string $newCvoteProfileId new value of cvote profile id
	 * @throws \RangeException if $newCvoteProfileId is not positive
	 * @throws \TypeError if the $newCvoteProfileId is not a uuid or string
	 **/
	public function setCvoteProfileId($newCvoteProfileId) : void {
		try {
			$uuid = self::validateUuid($newCvoteProfileId);
		} catch(\InvalidArgumentException | \RangeException |\Exception | \TypeError $exception) {
			$exceptionType = get_class($exception->getMessage(), 0, $exception);
		}
		//convert and store the cvote profile id
		$this->cvoteProfileId = $uuid;
	}

	/**
	 * inserts this cvote into mySQL
	 *
	 * @param \PDO $pdo PDO connection object
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError if $pdo is not a PDO connection object
	 **/
	public function insert(\PDO $pdo) : void {

		//create query template
		$query = "INSERT INTO cvote(cvoteId, cvoteProfileId, cvoteCommentId) VALUES(:cvoteId, :cvoteProfileId, :cvoteCommentId)";
		$statement = $pdo->prepare($query);

		//bind the member variables to the place-holders on the template
		$parameters = ["cvoteId" => $this->cvoteId->getBytes(),"cvoteProfileId" => $this->cvoteProfileId->getBytes(), "cvoteCommentId" => $this->cvoteCommentId];
		$statement->execute($parameters);
	}
	/**
	 * deletes this cvote from mySQL
	 *
	 * @param \PDO $pdo PDO connection object
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError if $pco is not a PDO connection object
	 **/
	public function delete(\PDO $pdo) : void {
		//create query template
		$query = "DELETE FROM cvote WHERE cvoteId = :cvoteId";
		$statement = $pdo->prepare($query);
		//bind the member variables to the place holder in the template
		$parameters =["cvoteId" => $this->cvoteId->getBytes()];
		$statement->execute($parameters);
	}
	/**
	 * updates this cvote in mySQL
	 *
	 * @param \PDO $pdo PDO connection object
	 * @throws \PDOException when mySQL related error occurs
	 * @throws \TypeError if $pdo is not a PDO connection object
	 **/
	public function update(\PDO $pdo) : void {
		//create query template
		$query = "UPDATE cvote SET cvoteProfileId = :cvoteProfileId, cvoteCommentId = :cvoteCommentId WHERE cvoteId = :cvoteId";
		$statement = $pdo->prepare($query);
		//bind the member variables to the place-holder in the template
		$parameters = ["cvoteId" => $this->cvoteId->getBytes(),"cvoteProfileId" => $this->cvoteProfileId->getBytes(), "cvoteCommentId" => $this->cvoteCommentId];
		$statement->execute($parameters);
	}
	/**
	 * gets the cvote by cvoteId
	 *
	 * @param \PDO $pdo PDO connection object
	 * @param Uuid|string $cvoteId cvote id to search for
	 * @return Cvote|null cvote found or null if not found
	 * @throws \PDOException when mySQL related error occurs
	 * @throws \TypeError when a variable is not correct data type
	 **/
	public static function getCvoteByCvoteId(\PDO $pdo, $cvoteId) : ?Cvote {
		//sanitize the string before searching
		try{
			$cvoteId = self::validateUuid($cvoteId);
		} catch(\InvalidArgumentException | \RangeException | \Exception | \TypeError $exception) {
			throw(new \PDOException($exception->getMessage(), 0, $exception));
		}
		//create query template
		$query = "SELECT cvoteId, cvoteProfileId, cvoteCommentId FROM cvote WHERE cvoteId = :cvoteId";
		$statement = $pdo->prepare($query);
		//bind the cvote id to the place holder in the template
		$parameters = ["cvoteId" => $cvoteId->getBytes()];
		$statement->execute($parameters);
		//grab the cvote from mySQL
		try {
			$cvote = null;
			$statement->setFetchMode(\PDO::FETCH_ASSOC);
			$row = $statement->fetch();
			if($row !== false) {
				$cvote = new Cvote($row["cvoteId"], $row["cvoteProfileId"], $row["cvoteCommentId"]);
			}
		} catch(\Exception $exception) {
			//if the row couldn't be converted, then rethrow it
			throw(new \PDOException($exception->getMessage(), 0, $exception));
		}
		return($cvote);
	}
	/**
	 * gets the cvote by profile id
	 *
	 * @param \PDO $pdo PDO connection object
	 * @param Uuid|string $cvoteProfileId cvote profile id to search by
	 * @return \SplFixedArray SplFixedArray of cvotes found
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError when variables are not the correct data type
	 */
	public static function getCvoteByCvoteProfileId(\PDO $pdo, $cvoteProfileId) : \SplFixedArray {
		try {
			$cvoteProfileId = self::validateUuid($cvoteProfileId);
		} catch(\InvalidArgumentException | \RangeException | \Exception | \TypeError $exception) {
			throw(new \PDOException($exception->getMessage(), 0, $exception));
		}
		// create query template
		$query = "SELECT cvoteId, cvoteProfileId, cvoteCommentId FROM cvote WHERE cvoteProfileId = :cvoteProfileId";
		$statement = $pdo->prepare($query);
		//bind the cvoteProfileId to the place holder in the template
		$parameters = ["cvoteProfileId" => $cvoteProfileId->getBytes()];
		$statement->execute($parameters);
		//build an array of cvotes
		$cvotes = new \SplFixedArray($statement->rowCount());
		$statement->setFetchMode(\PDO::FETCH_ASSOC);
		while(($row = $statement->fetch()) !== false) {
			try {
				$cvote = new Cvote($row["cvoteId"], $row["cvoteProfileId"], $row["cvoteCommentId"]);
				$cvotes[$cvotes->key()] = $cvote;
				$cvotes->next();
			} catch(\Exception $exception) {
				//if the row could not be converted, rethrow it
				throw(new \PDOException($exception->getMessage(), 0, $exception));
			}
		}
		return($cvotes);
	}
	/**
	 * gets the cvote by comment id
	 *
	 * @param \PDO $pdo PDO connection object
	 * @param Uuid|string $cvoteCommentId cvote comment id to search by
	 * @return \SplFixedArray SplFixedArray of cvotes found
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError when variables are not the correct data type
	 */
	public static function getCvoteByCvoteCommentId(\PDO $pdo, $cvoteCommentId) : \SplFixedArray {
		try {
			$cvoteCommentId = self::validateUuid($cvoteCommentId);
		} catch(\InvalidArgumentException | \RangeException | \Exception | \TypeError $exception) {
			throw(new \PDOException($exception->getMessage(), 0, $exception));
		}
		// create query template
		$query = "SELECT cvoteId, cvoteProfileId, cvoteCommentId FROM cvote WHERE cvoteCommentId = :cvoteCommentId";
		$statement = $pdo->prepare($query);
		//bind the cvoteCommentId to the place holder in the template
		$parameters = ["cvoteCommentId" => $cvoteCommentId->getBytes()];
		$statement->execute($parameters);
		//build an array of cvotes
		$cvotes = new \SplFixedArray($statement->rowCount());
		$statement->setFetchMode(\PDO::FETCH_ASSOC);
		while(($row = $statement->fetch()) !== false) {
			try {
				$cvote = new Cvote($row["cvoteId"], $row["cvoteProfileId"], $row["cvoteCommentId"]);
				$cvotes[$cvotes->key()] = $cvote;
				$cvotes->next();
			} catch(\Exception $exception) {
				//if the row could not be converted, rethrow it
				throw(new \PDOException($exception->getMessage(), 0, $exception));
			}
		}
		return($cvotes);
	}
	/**
	 * formats the state variables for JSON serialization
	 *
	 * @return array result in state variables to serialize
	 **/
	public function jsonSerialize() {
		$fields = get_object_vars($this);

		$fields["cvoteId"] = $this->cvoteId->toString();
		$fields["cvoteProfileId"] = $this->commentProfileId->toString();
		$fields["cvoteCommentId"] = $this->commentCommentId->toString();

		return($fields);
	}






}
?>