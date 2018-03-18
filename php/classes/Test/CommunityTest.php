<?php
namespace Edu\Cnm\Kmaru\Test;

use Edu\Cnm\Kmaru\Profile;
use Edu\Cnm\Kmaru\Community;

//grab the class under scrutiny: Community
require_once(dirname(__DIR__) . "/autoload.php");

//grab the uuid generator
require_once(dirname(__DIR__, 2) . "/lib/uuid.php");

/**
 * Full PHPUnit text for the Community class. It is complete
 * because *ALL* mySQL/PDO enabled methods are tested for both
 * invalid and valid inputs.
 *
 * @see Community
 **/
class CommunityTest extends KmaruTest {
	/**
	 * Profile that created the Community; this is for foreign key relations
	 * @var Profile profile
	 **/
	protected $profile = null;

	/**
	 * valid profile hash to create the profile object to own the test
	 * @var $VALID_HASH
	 **/
	protected $VALID_PROFILE_HASH;

	/**
	 * valid salt to use to create the profile object to own the test
	 * @var string $VALID_SALT
	 */
	protected $VALID_PROFILE_SALT;

	/**
	 * name of the Community
	 * @var string $VALID_COMMUNITYNAME
	 **/
	protected $VALID_COMMUNITYNAME = "Income";

	/**
	 * name of the updated Community
	 * @var string $VALID_COMMUNITYNAME2
	 **/
	protected $VALID_COMMUNITYNAME2 = "Education";

	/**
	 * create dependent objects before running each test
	 **/
	public final function setUp() : void {
		// run the default setUp() method first
		parent::setUp();
		$password = "monkey";
		$this->VALID_PROFILE_SALT = bin2hex(random_bytes(32));
		$this->VALID_PROFILE_HASH = hash_pbkdf2("sha512", $password, $this->VALID_PROFILE_SALT, 262144);

		//create and insert a Profile to own this test Community
		$this->profile = new Profile(generateUuidV4(), null, "email@community.com", $this->VALID_PROFILE_HASH, "John Smith", 3,$this->VALID_PROFILE_SALT, "jsmith");
		$this->profile->insert($this->getPDO());
	}
	/**
	 * test inserting a valid Community and verify that the actual mySQL data matches
	 **/
	public function testInsertValidCommunity() : void {
		// count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("community");


		// create a new Community and insert to into mySQL
		$communityId = generateUuidV4();
		$community = new Community($communityId, $this->profile->getProfileId(), $this->VALID_COMMUNITYNAME);
		$community->insert($this->getPDO());

		//grab the data from mySQL and enforce the fields match our expectations
		$pdoCommunity = Community::getCommunityByCommunityId($this->getPDO(), $community->getCommunityId());
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("community"));
		$this->assertEquals($pdoCommunity->getCommunityId(), $community->getCommunityId());
		$this->assertEquals($pdoCommunity->getCommunityProfileId(), $this->profile->getProfileId());
		$this->assertEquals($pdoCommunity->getCommunityName(), $this->VALID_COMMUNITYNAME);
	}
	/**
	 * test inserting a Community, then editing it, and then updating it
	 **/
	public function testUpdateValidCommunity() : void {
		//count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("community");

		//create a new Community and insert into mySQL
		$communityId = generateUuidV4();
		$community = new Community($communityId, $this->profile->getProfileId(), $this->VALID_COMMUNITYNAME);
		$community->insert($this->getPDO());

		//edit the Community and update it in mySQL
		$community->setCommunityName($this->VALID_COMMUNITYNAME2);
		$community->update($this->getPDO());

		//grab the data from mySQL and enforce the fields match our expectations
		$pdoCommunity = Community::getCommunityByCommunityId($this->getPDO(), $community->getCommunityId());
		$this->assertEquals($pdoCommunity->getCommunityId(), $communityId);
		$this->assertEquals($numRows +1, $this->getConnection()->getRowCount("community"));
		$this->assertEquals($pdoCommunity->getCommunityProfileId(), $this->profile->getProfileId());
		$this->assertEquals($pdoCommunity->getCommunityName(), $this->VALID_COMMUNITYNAME2);
	}

	/**
	 * test creating a Community and then deleting it
	 **/
	public function testDeleteValidCommunity() : void {
		//count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("community");

		//create a new Community and insert into mySQL
		$communityId = generateUuidV4();
		$community = new Community($communityId, $this->profile->getProfileId(), $this->VALID_COMMUNITYNAME);
		$community->insert($this->getPDO());

		//delete the Community from mySQL
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("community"));
		$community->delete($this->getPDO());

		//grab the data from mySQL and enforce the Community does not exist
		$pdoCommunity = Community::getCommunityByCommunityId($this->getPDO(), $community->getCommunityId());
		$this->assertNull($pdoCommunity);
		$this->assertEquals($numRows, $this->getConnection()->getRowCount("community"));
	}

	/**
	 * test grabbing a Community that does not exist
	 **/
	public function testGetInvalidCommunityByCommunityId() : void {
		//grab a community id that exceeds the maximum allowable community id
		$community = Community::getCommunityByCommunityId($this->getPDO(), generateUuidV4());
		$this->assertNull($community);
	}

	/**
	 * test inserting a Community and re-grabbing it from mySQL
	 **/
	public function testGetValidCommunityIdByCommunityProfileId() {
		//count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("community");

		//create a new Community and insert into mySQL
		$communityId = generateUuidV4();
		$community = new Community($communityId, $this->profile->getProfileId(), $this->VALID_COMMUNITYNAME);
		$community->insert($this->getPDO());

		//grab the data from mySQL and enforce the fields match our expectations
		$results = Community::getCommunityByCommunityProfileId($this->getPDO(), $this->profile->getProfileId());
		$this->assertEquals($numRows +1, $this->getConnection()->getRowCount("community"));
		$this->assertCount(1, $results);
		$this->assertContainsOnlyInstancesOf("Edu\\Cnm\\Kmaru\\Community", $results);

		//grab the result from the array and validate it
		$pdoCommunity = $results[0];

		$this->assertEquals($pdoCommunity->getCommunityId(), $communityId);
		$this->assertEquals($pdoCommunity->getCommunityProfileId(), $this->profile->getProfileId());
		$this->assertEquals($pdoCommunity->getCommunityName(), $this->VALID_COMMUNITYNAME);
	}

	/**
	 * test grabbing a Community that does not exist
	 **/
	public function testGetInvalidCommunityByCommunityProfileId(): void {
		// grab a profile id that exceeds the maximum allowable profile id
		$community = Community::getCommunityByCommunityProfileId($this->getPDO(), generateUuidV4());
		$this->assertCount(0, $community);
	}

	/**
	 * test grabbing a Community by community name
	 **/

	public function testGetValidCommunityByCommunityName() : void {
	//count the number of rows and save for later
	$numRows = $this->getConnection()->getRowCount("community");

	//create a new Community and insert into mySQL
	$communityId = generateUuidV4();
	$community = new Community($communityId, $this->profile->getProfileId(), $this->VALID_COMMUNITYNAME);
	$community->insert($this->getPDO());

	//grab the data from mySQL and enforce the fields match our expectations
	$results = Community::getCommunityByCommunityName($this->getPDO(), $community->getCommunityName());
	$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("community"));
	$this->assertCount(1, $results);

	//enforce no other objects are bleeding into the test
	$this->assertContainsOnlyInstancesOf("Edu\\Cnm\\Kmaru\\Community", $results);

	//grab the result from the array and validate it
	$pdoCommunity = $results[0];
	$this->assertEquals($pdoCommunity->getCommunityId(), $communityId);
	$this->assertEquals($pdoCommunity->getCommunityProfileId(), $this->profile->getProfileId());
	$this->assertEquals($pdoCommunity->getCommunityName(), $this->VALID_COMMUNITYNAME);
	}


	/**
	 * test grabbing a Community by name that does not exist
	 **/
	public function testGetInvalidCommunityByCommunityName() : void {
	//grab a community by name that does not exist
	$community = Community::getCommunityByCommunityName($this->getPDO(), "Who is in the brig today?");
	$this->assertCount(0, $community);
	}


	/**
	 * test grabbing all Communitiess
	 **/
	public function testGetAllValidCommunitys(): void {
		//count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("community");

		//create a new Community and insert into mySQL
		$communityId = generateUuidV4();
		$community = new Community($communityId, $this->profile->getProfileId(), $this->VALID_COMMUNITYNAME);
		$community->insert($this->getPDO());

		//grab the data from mySQL and enforce the fields match our expectations
		$results = Community::getAllCommunitys($this->getPDO());
		$this->assertEquals($numRows +1, $this->getConnection()->getRowCount("community"));
		$this->assertCount(1, $results);
		$this->assertContainsOnlyInstancesOf("Edu\\Cnm\\Kmaru\\Community", $results);

		//grab the result from the array and validate it
		$pdoCommunity = $results[0];
		$this->assertEquals($pdoCommunity->getCommunityId(), $communityId);
		$this->assertEquals($pdoCommunity->getCommunityProfileId(), $this->profile->getProfileId());
		$this->assertEquals($pdoCommunity->getCommunityName(), $this->VALID_COMMUNITYNAME);
	}


}


?>