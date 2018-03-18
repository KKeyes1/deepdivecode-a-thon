<?php
namespace Edu\Cnm\Kmaru;



require_once("autoload.php");
require_once(dirname(__DIR__, 2) . "/vendor/autoload.php");

use PHPUnit\Exception;
use Ramsey\Uuid\Uuid;

/**
 * sponsor
 *
 * This is what data is stored when an subscriber sponsorss on a post
 *
 **/

class Sponsor implements \JsonSerializable {
	use ValidateUuid;

	/**
	 * id for this Sponsor; this is the primary key
	 * @var Uuid $sponsorId
	 **/
	private $sponsorId;
	/**
	 * id of the Profile that created this sponsors; this is a foreign key
	 * @var Uuid $sponsorProfileId
	 **/
	private $sponsorProfileId;
	/**
	 * id of the post
	 * @var string $sponsorPostId
	 **/
	private $sponsorPostId;

	/**
	 * constructor for this post sponsors
	 *
	 * @param Uuid|string $newSponsorId id of this sponsors or null if new sponsors
	 * @param Uuid|string $newSponsorProfileId id of the Profile of the creator of this sponsors
	 * @param string $newSponsorPostId the id of post this sponsors is tied to
	 * @throws \InvalidArgumentException if data types are not valid
	 * @throws \RangeException if data values are out of bounds(ie strings too long, integers negative)
	 * @throws \TypeError if data types violates type hints
	 * @throws \Exception if some other exception occurs
	 * @Documentation https://php.net/manual/en/language.oop5.decon.php (constructors and destructors)
	 **/
	public function __construct($newSponsorId, $newSponsorProfileId, $newSponsorPostId) {
		try {
			$this->setSponsorId($newSponsorId);
			$this->setSponsorProfileId($newSponsorProfileId);
			$this->setSponsorPostId($newSponsorPostId);
		}
			//determine what exception type was thrown
		catch(\InvalidArgumentException | \RangeException | \TypeError | \Exception $exception) {
			$exceptionType = get_class($exception);
			throw(new $exceptionType($exception->getMessage(), 0, $exception));
		}
	}
	/**
	 * accessor method for sponsor id
	 *
	 * @return Uuid value of the sponsor id
	 **/
	public function getSponsorId() : Uuid {
		return($this->sponsorId);
	}
	/**
	 * mutator method for sponsor id
	 *
	 * @param Uuid|string $newSponsorId
	 * @throws \RangeException if $newSponsorId is not positive
	 * @throws \TypeError if $newSponsorId is not a uuid or string
	 **/
	public function setSponsorId($newSponsorId) : void {
		try {
			$uuid = self::validateUuid($newSponsorId);
		} catch(\InvalidArgumentException | \RangeException | \Exception | \TypeError $exception) {
			$exceptionType = get_class($exception);
			throw(new $exceptionType($exception->getMessage(), 0, $exception));
		}
		//convert and store the sponsor id
		$this->sponsorId = $uuid;
	}
	/**
	 * accessor method for sponsor profile id
	 *
	 * @return Uuid value of sponsor profile id
	 **/
	public function getSponsorProfileId() : Uuid {
		return($this->sponsorProfileId);
	}
	/**
	 * mutator method for sponsor profile id
	 *
	 * @param Uuid|string $newSponsorProfileId new value of sponsor profile id
	 * @throws \RangeException if $newSponsorProfileId is not positive
	 * @throws \TypeError if the $newSponsorProfileId is not a uuid or string
	 **/
	public function setSponsorProfileId($newSponsorProfileId) : void {
		try {
			$uuid = self::validateUuid($newSponsorProfileId);
		} catch(\InvalidArgumentException | \RangeException |\Exception | \TypeError $exception) {
			$exceptionType = get_class($exception->getMessage(), 0, $exception);
		}
		//convert and store the sponsor profile id
		$this->sponsorProfileId = $uuid;
	}

	/**
	 * inserts this sponsor into mySQL
	 *
	 * @param \PDO $pdo PDO connection object
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError if $pdo is not a PDO connection object
	 **/
	public function insert(\PDO $pdo) : void {

		//create query template
		$query = "INSERT INTO sponsor(sponsorId, sponsorProfileId, sponsorPostId) VALUES(:sponsorId, :sponsorProfileId, :sponsorPostId)";
		$statement = $pdo->prepare($query);

		//bind the member variables to the place-holders on the template
		$parameters = ["sponsorId" => $this->sponsorId->getBytes(),"sponsorProfileId" => $this->sponsorProfileId->getBytes(), "sponsorPostId" => $this->sponsorPostId];
		$statement->execute($parameters);
	}
	/**
	 * deletes this sponsor from mySQL
	 *
	 * @param \PDO $pdo PDO connection object
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError if $pco is not a PDO connection object
	 **/
	public function delete(\PDO $pdo) : void {
		//create query template
		$query = "DELETE FROM sponsor WHERE sponsorId = :sponsorId";
		$statement = $pdo->prepare($query);
		//bind the member variables to the place holder in the template
		$parameters =["sponsorId" => $this->sponsorId->getBytes()];
		$statement->execute($parameters);
	}
	/**
	 * updates this sponsor in mySQL
	 *
	 * @param \PDO $pdo PDO connection object
	 * @throws \PDOException when mySQL related error occurs
	 * @throws \TypeError if $pdo is not a PDO connection object
	 **/
	public function update(\PDO $pdo) : void {
		//create query template
		$query = "UPDATE sponsor SET sponsorProfileId = :sponsorProfileId, sponsorPostId = :sponsorPostId WHERE sponsorId = :sponsorId";
		$statement = $pdo->prepare($query);
		//bind the member variables to the place-holder in the template
		$parameters = ["sponsorId" => $this->sponsorId->getBytes(),"sponsorProfileId" => $this->sponsorProfileId->getBytes(), "sponsorPostId" => $this->sponsorPostId];
		$statement->execute($parameters);
	}
	/**
	 * gets the sponsor by sponsorId
	 *
	 * @param \PDO $pdo PDO connection object
	 * @param Uuid|string $sponsorId sponsor id to search for
	 * @return Sponsor|null sponsor found or null if not found
	 * @throws \PDOException when mySQL related error occurs
	 * @throws \TypeError when a variable is not correct data type
	 **/
	public static function getSponsorBySponsorId(\PDO $pdo, $sponsorId) : ?Sponsor {
		//sanitize the string before searching
		try{
			$sponsorId = self::validateUuid($sponsorId);
		} catch(\InvalidArgumentException | \RangeException | \Exception | \TypeError $exception) {
			throw(new \PDOException($exception->getMessage(), 0, $exception));
		}
		//create query template
		$query = "SELECT sponsorId, sponsorProfileId, sponsorPostId FROM sponsor WHERE sponsorId = :sponsorId";
		$statement = $pdo->prepare($query);
		//bind the sponsor id to the place holder in the template
		$parameters = ["sponsorId" => $sponsorId->getBytes()];
		$statement->execute($parameters);
		//grab the sponsor from mySQL
		try {
			$sponsor = null;
			$statement->setFetchMode(\PDO::FETCH_ASSOC);
			$row = $statement->fetch();
			if($row !== false) {
				$sponsor = new Sponsor($row["sponsorId"], $row["sponsorProfileId"], $row["sponsorPostId"]);
			}
		} catch(\Exception $exception) {
			//if the row couldn't be converted, then rethrow it
			throw(new \PDOException($exception->getMessage(), 0, $exception));
		}
		return($sponsor);
	}
	/**
	 * gets the sponsor by profile id
	 *
	 * @param \PDO $pdo PDO connection object
	 * @param Uuid|string $sponsorProfileId sponsor profile id to search by
	 * @return \SplFixedArray SplFixedArray of sponsors found
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError when variables are not the correct data type
	 */
	public static function getSponsorBySponsorProfileId(\PDO $pdo, $sponsorProfileId) : \SplFixedArray {
		try {
			$sponsorProfileId = self::validateUuid($sponsorProfileId);
		} catch(\InvalidArgumentException | \RangeException | \Exception | \TypeError $exception) {
			throw(new \PDOException($exception->getMessage(), 0, $exception));
		}
		// create query template
		$query = "SELECT sponsorId, sponsorProfileId, sponsorPostId FROM sponsor WHERE sponsorProfileId = :sponsorProfileId";
		$statement = $pdo->prepare($query);
		//bind the sponsorProfileId to the place holder in the template
		$parameters = ["sponsorProfileId" => $sponsorProfileId->getBytes()];
		$statement->execute($parameters);
		//build an array of sponsors
		$sponsors = new \SplFixedArray($statement->rowCount());
		$statement->setFetchMode(\PDO::FETCH_ASSOC);
		while(($row = $statement->fetch()) !== false) {
			try {
				$sponsor = new Sponsor($row["sponsorId"], $row["sponsorProfileId"], $row["sponsorPostId"]);
				$sponsors[$sponsors->key()] = $sponsor;
				$sponsors->next();
			} catch(\Exception $exception) {
				//if the row could not be converted, rethrow it
				throw(new \PDOException($exception->getMessage(), 0, $exception));
			}
		}
		return($sponsors);
	}
	/**
	 * gets the sponsor by post id
	 *
	 * @param \PDO $pdo PDO connection object
	 * @param Uuid|string $sponsorPostId sponsor post id to search by
	 * @return \SplFixedArray SplFixedArray of sponsors found
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError when variables are not the correct data type
	 */
	public static function getSponsorBySponsorPostId(\PDO $pdo, $sponsorPostId) : \SplFixedArray {
		try {
			$sponsorPostId = self::validateUuid($sponsorPostId);
		} catch(\InvalidArgumentException | \RangeException | \Exception | \TypeError $exception) {
			throw(new \PDOException($exception->getMessage(), 0, $exception));
		}
		// create query template
		$query = "SELECT sponsorId, sponsorProfileId, sponsorPostId FROM sponsor WHERE sponsorPostId = :sponsorPostId";
		$statement = $pdo->prepare($query);
		//bind the sponsorPostId to the place holder in the template
		$parameters = ["sponsorPostId" => $sponsorPostId->getBytes()];
		$statement->execute($parameters);
		//build an array of sponsors
		$sponsors = new \SplFixedArray($statement->rowCount());
		$statement->setFetchMode(\PDO::FETCH_ASSOC);
		while(($row = $statement->fetch()) !== false) {
			try {
				$sponsor = new Sponsor($row["sponsorId"], $row["sponsorProfileId"], $row["sponsorPostId"]);
				$sponsors[$sponsors->key()] = $sponsor;
				$sponsors->next();
			} catch(\Exception $exception) {
				//if the row could not be converted, rethrow it
				throw(new \PDOException($exception->getMessage(), 0, $exception));
			}
		}
		return($sponsors);
	}
	/**
	 * formats the state variables for JSON serialization
	 *
	 * @return array result in state variables to serialize
	 **/
	public function jsonSerialize() {
		$fields = get_object_vars($this);

		$fields["sponsorId"] = $this->sponsorId->toString();
		$fields["sponsorProfileId"] = $this->postProfileId->toString();
		$fields["sponsorPostId"] = $this->postPostId->toString();

		return($fields);
	}






}
?>