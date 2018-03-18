<?php
namespace Edu\Cnm\Forum;



require_once("autoload.php");
require_once(dirname(__DIR__, 2) . "/vendor/autoload.php");

use PHPUnit\Exception;
use Ramsey\Uuid\Uuid;

/**
 * community
 *
 * This is what data is stored when an subscriber creates a new community
 *
 **/

class Community implements \JsonSerializable {
	use ValidateUuid;

	/**
	 * id for this Community; this is the primary key
	 * @var Uuid $communityId
	 **/
	private $communityId;
	/**
	 * id of the Profile that created this community; this is a foreign key
	 * @var Uuid $communityProfileId
	 **/
	private $communityProfileId;
	/**
	 * name of the community
	 * @var string $communityName
	 **/
	private $communityName;

	/**
	 * constructor for this community
	 *
	 * @param Uuid|string $newCommunityId id of this community or null if new community
	 * @param Uuid|string $newCommunityProfileId id of the Profile of the creator of this Community
	 * @param string $newCommunityName the Name of this Community
	 * @throws \InvalidArgumentException if data types are not valid
	 * @throws \RangeException if data values are out of bounds(ie strings too long, integers negative)
	 * @throws \TypeError if data types violates type hints
	 * @throws \Exception if some other exception occurs
	 * @Documentation https://php.net/manual/en/language.oop5.decon.php (constructors and destructors)
	 **/
	public function __construct($newCommunityId, $newCommunityProfileId, string $newCommunityName) {
		try {
			$this->setCommunityId($newCommunityId);
			$this->setCommunityProfileId($newCommunityProfileId);
			$this->setCommunityName($newCommunityName);
		}
			//determine what exception type was thrown
		catch(\InvalidArgumentException | \RangeException | \TypeError | \Exception $exception) {
			$exceptionType = get_class($exception);
			throw(new $exceptionType($exception->getMessage(), 0, $exception));
		}
	}
	/**
	 * accessor method for community id
	 *
	 * @return Uuid value of the community id
	 **/
	public function getCommunityId() : Uuid {
		return($this->communityId);
	}
	/**
	 * mutator method for community id
	 *
	 * @param Uuid|string $newCommunityId
	 * @throws \RangeException if $newCommunityId is not positive
	 * @throws \TypeError if $newCommunityId is not a uuid or string
	 **/
	public function setCommunityId($newCommunityId) : void {
		try {
			$uuid = self::validateUuid($newCommunityId);
		} catch(\InvalidArgumentException | \RangeException | \Exception | \TypeError $exception) {
			$exceptionType = get_class($exception);
			throw(new $exceptionType($exception->getMessage(), 0, $exception));
		}
		//convert and store the community id
		$this->communityId = $uuid;
	}
	/**
	 * accessor method for community profile id
	 *
	 * @return Uuid value of community profile id
	 **/
	public function getCommunityProfileId() : Uuid {
		return($this->communityProfileId);
	}
	/**
	 * mutator method for community profile id
	 *
	 * @param Uuid|string $newCommunityProfileId new value of community profile id
	 * @throws \RangeException if $newCommunityProfileId is not positive
	 * @throws \TypeError if the $newCommunityProfileId is not a uuid or string
	 **/
	public function setCommunityProfileId($newCommunityProfileId) : void {
		try {
			$uuid = self::validateUuid($newCommunityProfileId);
		} catch(\InvalidArgumentException | \RangeException |\Exception | \TypeError $exception) {
			$exceptionType = get_class($exception->getMessage(), 0, $exception);
		}
		//convert and store the community profile id
		$this->communityProfileId = $uuid;
	}
	/**
	 * accessor method for community name
	 * @return string value of community name
	 **/
	public function getCommunityName() : string {
		return($this->communityName);
	}
	/**
	 * mutator method for community name
	 *
	 * @param string $newCommunityName new value of community name
	 * @throws \InvalidArgumentException if $newCommunityName is not a string or insecure
	 * @throws \RangeException if $newCommunityName is >64 characters
	 * @throws \TypeError if $newCommunityName is not a string
	 **/
	public function setCommunityName(string $newCommunityName) : void {
		//verify the community name is secure
		$newCommunityName = trim($newCommunityName);
		$newCommunityName = filter_var($newCommunityName, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
		if(empty($newCommunityName) === true) {
			throw(new \InvalidArgumentException("community name is empty or insecure"));
		}
		//verify the community name will fit in the database
		if(strlen($newCommunityName) > 64) {
			throw(new \RangeException("community name too long"));
		}
		//store the community name
		$this->communityName = $newCommunityName;
	}
	/**
	 * inserts this community into mySQL
	 *
	 * @param \PDO $pdo PDO connection object
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError if $pdo is not a PDO connection object
	 **/
	public function insert(\PDO $pdo) : void {

		//create query template
		$query = "INSERT INTO community(communityId, communityProfileId, communityName) VALUES(:communityId, :communityProfileId, :communityName)";
		$statement = $pdo->prepare($query);

		//bind the member variables to the place-holders on the template
		$parameters = ["communityId" => $this->communityId->getBytes(),"communityProfileId" => $this->communityProfileId->getBytes(), "communityName" => $this->communityName];
		$statement->execute($parameters);
	}
	/**
	 * deletes this community from mySQL
	 *
	 * @param \PDO $pdo PDO connection object
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError if $pco is not a PDO connection object
	 **/
	public function delete(\PDO $pdo) : void {
		//create query template
		$query = "DELETE FROM community WHERE communityId = :communityId";
		$statement = $pdo->prepare($query);
		//bind the member variables to the place holder in the template
		$parameters =["communityId" => $this->communityId->getBytes()];
		$statement->execute($parameters);
	}
	/**
	 * updates this community in mySQL
	 *
	 * @param \PDO $pdo PDO connection object
	 * @throws \PDOException when mySQL related error occurs
	 * @throws \TypeError if $pdo is not a PDO connection object
	 **/
	public function update(\PDO $pdo) : void {
		//create query template
		$query = "UPDATE community SET communityProfileId = :communityProfileId, communityName = :communityName WHERE communityId = :communityId";
		$statement = $pdo->prepare($query);
		//bind the member variables to the place-holder in the template
		$parameters = ["communityId" => $this->communityId->getBytes(),"communityProfileId" => $this->communityProfileId->getBytes(), "communityName" => $this->communityName];
		$statement->execute($parameters);
	}
	/**
	 * gets the community by communityId
	 *
	 * @param \PDO $pdo PDO connection object
	 * @param Uuid|string $communityId community id to search for
	 * @return Community|null community found or null if not found
	 * @throws \PDOException when mySQL related error occurs
	 * @throws \TypeError when a variable is not correct data type
	 **/
	public static function getCommunityByCommunityId(\PDO $pdo, $communityId) : ?Community {
		//sanitize the string before searching
		try{
			$communityId = self::validateUuid($communityId);
		} catch(\InvalidArgumentException | \RangeException | \Exception | \TypeError $exception) {
			throw(new \PDOException($exception->getMessage(), 0, $exception));
		}
		//create query template
		$query = "SELECT communityId, communityProfileId, communityName FROM community WHERE communityId = :communityId";
		$statement = $pdo->prepare($query);
		//bind the community id to the place holder in the template
		$parameters = ["communityId" => $communityId->getBytes()];
		$statement->execute($parameters);
		//grab the community from mySQL
		try {
			$community = null;
			$statement->setFetchMode(\PDO::FETCH_ASSOC);
			$row = $statement->fetch();
			if($row !== false) {
				$community = new Community($row["communityId"], $row["communityProfileId"], $row["communityName"]);
			}
		} catch(\Exception $exception) {
			//if the row couldn't be converted, then rethrow it
			throw(new \PDOException($exception->getMessage(), 0, $exception));
		}
		return($community);
	}
	/**
	 * gets the community by profile id
	 *
	 * @param \PDO $pdo PDO connection object
	 * @param Uuid|string $communityProfileId community profile id to search by
	 * @return \SplFixedArray SplFixedArray of communities found
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError when variables are not the correct data type
	 */
	public static function getCommunityByCommunityProfileId(\PDO $pdo, $communityProfileId) : \SplFixedArray {
		try {
			$communityProfileId = self::validateUuid($communityProfileId);
		} catch(\InvalidArgumentException | \RangeException | \Exception | \TypeError $exception) {
			throw(new \PDOException($exception->getMessage(), 0, $exception));
		}
		// create query template
		$query = "SELECT communityId, communityProfileId, communityName FROM community WHERE communityProfileId = :communityProfileId";
		$statement = $pdo->prepare($query);
		//bind the communityProfileId to the place holder in the template
		$parameters = ["communityProfileId" => $communityProfileId->getBytes()];
		$statement->execute($parameters);
		//build an array of communities
		$communities = new \SplFixedArray($statement->rowCount());
		$statement->setFetchMode(\PDO::FETCH_ASSOC);
		while(($row = $statement->fetch()) !== false) {
			try {
				$community = new Community($row["communityId"], $row["communityProfileId"], $row["communityName"]);
				$communities[$communities->key()] = $community;
				$communities->next();
			} catch(\Exception $exception) {
				//if the row could not be converted, rethrow it
				throw(new \PDOException($exception->getMessage(), 0, $exception));
			}
		}
		return($communities);
	}
	/**
	 * gets the community by community name
	 *
	 * @param |PDO $pdo PDO connection object
	 * @param string $communityName community name to search by
	 * @return \SplFixedArray SplFixedArray of communities found
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError when variables are not the correct data type
	 */
	public static function getCommunityByCommunityName(\PDO $pdo, $communityName) : \SplFixedArray {
		//saintize the strin before searching
		$communityName = trim($communityName);
		$communityName = filter_var($communityName, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
		if(empty($communityName) === true) {
			throw(new \PDOException("not a valid name"));
		}
		// create query template
		$query = "SELECT communityId, communityProfileId, communityName FROM community WHERE communityName = :communityName";
		$statement = $pdo->prepare($query);
		//bind the community name to the place holder in the template
		$parameters = ["communityName" => $communityName];
		$statement->execute($parameters);
		//build an array of communities
		$communities = new \SplFixedArray($statement->rowCount());
		$statement->setFetchMode(\PDO::FETCH_ASSOC);
		while(($row = $statement->fetch()) !== false) {
			try {
				$community = new Community($row["communityId"], $row["communityProfileId"], $row["communityName"]);
				$communities[$communities->key()] = $community;
				$communities->next();
			} catch(\Exception $exception) {
				//if the row could not be converted, rethrow it
				throw(new \PDOException($exception->getMessage(), 0, $exception));
			}
		}
		return($communities);
	}
	/**
	 * gets all communities
	 *
	 * @param \PDO $pdo PDO connection object
	 * @return \SplFixedArray SplFixedArray of communities found or null if not fund
	 * @throws \PDOException when mySQL related errors occur
	 * @throws \TypeError when variables are not the correct data type
	 **/
	public static function getAllCommunities(\PDO $pdo) : \SPLFixedArray {
		//create query template
		$query = "SELECT communityId, communityProfileId, communityName FROM community";
		$statement = $pdo->prepare($query);
		$statement->execute();
		//built and array of communities
		$communities = new \SplFixedArray(($statement->rowCount()));
		$statement->setFetchMode(\PDO::FETCH_ASSOC);
		while(($row = $statement->fetch()) !== false) {
			try {
				$community = new Community($row["communityId"], $row["communityProfileId"], $row["communityName"]);
				$communities[$communities->key()] = $community;
				$communities->next();
			} catch(\Exception $exception) {
				//if the row couldn't be converted, rethrow it
				throw(new \PDOException($exception->getMessage(), 0, $exception));
			}
			return ($communities);
		}
	}
	/**
	 * formats the state variables for JSON serialization
	 *
	 * @return array result in state variables to serialize
	 **/
	public function jsonSerialize() {
		$fields = get_object_vars($this);

		$fields["communityId"] = $this->communityId->toString();
		$fields["communityProfileId"] = $this->communityProfileId->toString();

		return($fields);
	}






}
?>