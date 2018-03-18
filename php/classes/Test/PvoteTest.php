<?php
namespace Edu\Cnm\Kmaru\Test;

use Edu\Cnm\Kmaru\Community;
use Edu\Cnm\Kmaru\Profile;
use Edu\Cnm\Kmaru\Post;
use Edu\Cnm\Kmaru\Pvote;

//grab the class under scrutiny: Pvote
require_once(dirname(__DIR__) . "/autoload.php");

//grab the uuid generator
require_once(dirname(__DIR__, 2) . "/lib/uuid.php");

/**
 * Full PHPUnit text for the Pvote class. It is complete
 * because *ALL* mySQL/PDO enabled methods are tested for both
 * invalid and valid inputs.
 *
 * @see Pvote
 **/
class PvoteTest extends KmaruTest {
	/**
	 * community linked to the post; this is for foreign key relations
	 * @var Community
	 **/
	protected $community = null;
	/**
	 * Profile that created the vote; this is for foreign key relations
	 * @var Profile profile
	 **/
	protected $profile = null;
	/**
	 * Post being voted on; this is for foreign key relations
	 * @var Profile profile
	 **/
	protected $post = null;
	/**
	 * valid content to use
	 * @var string $VALID_CONTENT
	 */
	protected $VALID_CONTENT = "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.";
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
	 * create dependent objects before running each test
	 **/
	public final function setUp() : void {
		// run the default setUp() method first
		parent::setUp();
		$password = "monkey";
		$this->VALID_PROFILE_SALT = bin2hex(random_bytes(32));
		$this->VALID_PROFILE_HASH = hash_pbkdf2("sha512", $password, $this->VALID_PROFILE_SALT, 262144);

		//create and insert a Profile to own this test Pvote
		$this->profile = new Profile(generateUuidV4(), null, "email@pvote.com", $this->VALID_PROFILE_HASH, "John Smith", 3,$this->VALID_PROFILE_SALT, "jsmith");
		$this->profile->insert($this->getPDO());

		// create and insert a community to own the test post
		$this->community = new Community(generateUuidV4(), $this->profile->getProfileId(), "Health care");
		$this->community->insert($this->getPDO());

		// create and insert a pvote to own the test post
		$this->post = new Post(generateUuidV4(), $this->community->getCommunityId(), $this->profile->getProfileId(), $this->VALID_CONTENT);
		$this->post->insert($this->getPDO());
	}
	/**
	 * test inserting a valid Pvote and verify that the actual mySQL data matches
	 **/
	public function testInsertValidPvote() : void {
		// count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("pvote");


		// create a new Pvote and insert to into mySQL
		$pvoteId = generateUuidV4();
		$pvote = new Pvote($pvoteId, $this->post->getPostId(), $this->profile->getProfileId());
		$pvote->insert($this->getPDO());

		//grab the data from mySQL and enforce the fields match our expectations
		$pdoPvote = Pvote::getPvoteByPvoteId($this->getPDO(), $pvote->getPvoteId());
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("pvote"));
		$this->assertEquals($pdoPvote->getPvoteId(), $pvote->getPvoteId());
		$this->assertEquals($pdoPvote->getPvotePostId(), $this->post->getPostId());
		$this->assertEquals($pdoPvote->getPvoteProfileId(), $this->profile->getProfileId());
	}
	/**
	 * test creating a Pvote and then deleting it
	 **/
	public function testDeleteValidPvote() : void {
		//count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("pvote");

		//create a new Pvote and insert into mySQL
		$pvoteId = generateUuidV4();
		$pvote = new Pvote($pvoteId, $this->post->getPostId(), $this->profile->getProfileId());
		$pvote->insert($this->getPDO());

		//delete the Pvote from mySQL
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("pvote"));
		$pvote->delete($this->getPDO());

		//grab the data from mySQL and enforce the Pvote does not exist
		$pdoPvote = Pvote::getPvoteByPvoteId($this->getPDO(), $pvote->getPvoteId());
		$this->assertNull($pdoPvote);
		$this->assertEquals($numRows, $this->getConnection()->getRowCount("pvote"));
	}

	/**
	 * test grabbing a Pvote that does not exist
	 **/
	public function testGetInvalidPvoteByPvoteId() : void {
		//grab a pvote id that exceeds the maximum allowable pvote id
		$pvote = Pvote::getPvoteByPvoteId($this->getPDO(), generateUuidV4());
		$this->assertNull($pvote);
	}
	/**
	 * test inserting a Pvote and re-grabbing it from mySQL
	 **/
	public function testGetValidPvoteIdByPvotePostId() {
		//count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("pvote");

		//create a new Pvote and insert into mySQL
		$pvoteId = generateUuidV4();
		$pvote = new Pvote($pvoteId, $this->post->getPostId(), $this->profile->getProfileId());
		$pvote->insert($this->getPDO());

		//grab the data from mySQL and enforce the fields match our expectations
		$results = Pvote::getPvoteByPvotePostId($this->getPDO(), $this->post->getPostId());
		$this->assertEquals($numRows +1, $this->getConnection()->getRowCount("pvote"));
		$this->assertCount(1, $results);
		$this->assertContainsOnlyInstancesOf("Edu\\Cnm\\Kmaru\\Pvote", $results);

		//grab the result from the array and validate it
		$pdoPvote = $results[0];

		$this->assertEquals($pdoPvote->getPvoteId(), $pvoteId);
		$this->assertEquals($pdoPvote->getPvotePostId(), $this->post->getPostId());
		$this->assertEquals($pdoPvote->getPvoteProfileId(), $this->profile->getProfileId());
	}

	/**
	 * test grabbing a Pvote that does not exist
	 **/
	public function testGetInvalidPvoteByPvotePostId(): void {
		// grab a profile id that exceeds the maximum allowable profile id
		$pvote = Pvote::getPvoteByPvotePostId($this->getPDO(), generateUuidV4());
		$this->assertCount(0, $pvote);
	}
	/**
	 * test inserting a Pvote and re-grabbing it from mySQL
	 **/
	public function testGetValidPvoteIdByPvoteProfileId() {
		//count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("pvote");

		//create a new Pvote and insert into mySQL
		$pvoteId = generateUuidV4();
		$pvote = new Pvote($pvoteId, $this->post->getPostId(), $this->profile->getProfileId());
		$pvote->insert($this->getPDO());

		//grab the data from mySQL and enforce the fields match our expectations
		$results = Pvote::getPvoteByPvoteProfileId($this->getPDO(), $this->profile->getProfileId());
		$this->assertEquals($numRows +1, $this->getConnection()->getRowCount("pvote"));
		$this->assertCount(1, $results);
		$this->assertContainsOnlyInstancesOf("Edu\\Cnm\\Kmaru\\Pvote", $results);

		//grab the result from the array and validate it
		$pdoPvote = $results[0];

		$this->assertEquals($pdoPvote->getPvoteId(), $pvoteId);
		$this->assertEquals($pdoPvote->getPvotePostId(), $this->post->getPostId());
		$this->assertEquals($pdoPvote->getPvoteProfileId(), $this->profile->getProfileId());
	}

	/**
	 * test grabbing a Pvote that does not exist
	 **/
	public function testGetInvalidPvoteByPvoteProfileId(): void {
		// grab a profile id that exceeds the maximum allowable profile id
		$pvote = Pvote::getPvoteByPvoteProfileId($this->getPDO(), generateUuidV4());
		$this->assertCount(0, $pvote);
	}


}


?>