<?php
namespace Edu\Cnm\Kmaru\Test;

use Edu\Cnm\Kmaru\Community;
use Edu\Cnm\Kmaru\Profile;
use Edu\Cnm\Kmaru\Post;

//grab the class under scrutiny: Sponsor
require_once(dirname(__DIR__) . "/autoload.php");

//grab the uuid generator
require_once(dirname(__DIR__, 2) . "/lib/uuid.php");

/**
 * Full PHPUnit text for the Sponsor class. It is complete
 * because *ALL* mySQL/PDO enabled methods are tested for both
 * invalid and valid inputs.
 *
 * @see Sponsor
 **/
class SponsorTest extends KmaruTest {
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

		//create and insert a Profile to own this test Sponsor
		$this->profile = new Profile(generateUuidV4(), null, "email@sponsor.com", $this->VALID_PROFILE_HASH, "John Smith", 3,$this->VALID_PROFILE_SALT, "jsmith");
		$this->profile->insert($this->getPDO());

		// create and insert a community to own the test post
		$this->community = new Community(generateUuidV4(), $this->profile->getProfileId(), "Health care");
		$this->community->insert($this->getPDO());

		// create and insert a post to own the test post
		$this->post = new Post(generateUuidV4(), $this->community->getCommunityId(), $this->profile->getProfileId(), $this->VALID_CONTENT);
		$this->post->insert($this->getPDO());
	}
	/**
	 * test inserting a valid Sponsor and verify that the actual mySQL data matches
	 **/
	public function testInsertValidSponsor() : void {
		// count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("sponsor");


		// create a new Sponsor and insert to into mySQL
		$sponsorId = generateUuidV4();
		$sponsor = new Sponsor($sponsorId, $this->post->getPostId(), $this->profile->getProfileId());
		$sponsor->insert($this->getPDO());

		//grab the data from mySQL and enforce the fields match our expectations
		$pdoSponsor = Sponsor::getSponsorBySponsorId($this->getPDO(), $sponsor->getSponsorId());
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("sponsor"));
		$this->assertEquals($pdoSponsor->getSponsorId(), $sponsor->getSponsorId());
		$this->assertEquals($pdoSponsor->getSponsorPostId(), $this->post->getPostId());
		$this->assertEquals($pdoSponsor->getSponsorProfileId(), $this->profile->getProfileId());
	}
	/**
	 * test creating a Sponsor and then deleting it
	 **/
	public function testDeleteValidSponsor() : void {
		//count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("sponsor");

		//create a new Sponsor and insert into mySQL
		$sponsorId = generateUuidV4();
		$sponsor = new Sponsor($sponsorId, $this->post->getPostId(), $this->profile->getProfileId());
		$sponsor->insert($this->getPDO());

		//delete the Sponsor from mySQL
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("sponsor"));
		$sponsor->delete($this->getPDO());

		//grab the data from mySQL and enforce the Sponsor does not exist
		$pdoSponsor = Sponsor::getSponsorBySponsorId($this->getPDO(), $sponsor->getSponsorId());
		$this->assertNull($pdoSponsor);
		$this->assertEquals($numRows, $this->getConnection()->getRowCount("sponsor"));
	}

	/**
	 * test grabbing a Sponsor that does not exist
	 **/
	public function testGetInvalidSponsorBySponsorId() : void {
		//grab a sponsor id that exceeds the maximum allowable sponsor id
		$sponsor = Sponsor::getSponsorBySponsorId($this->getPDO(), generateUuidV4());
		$this->assertNull($sponsor);
	}
	/**
	 * test inserting a Sponsor and re-grabbing it from mySQL
	 **/
	public function testGetValidSponsorIdBySponsorPostId() {
		//count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("sponsor");

		//create a new Sponsor and insert into mySQL
		$sponsorId = generateUuidV4();
		$sponsor = new Sponsor($sponsorId, $this->post->getPostId(), $this->profile->getProfileId());
		$sponsor->insert($this->getPDO());

		//grab the data from mySQL and enforce the fields match our expectations
		$results = Sponsor::getSponsorBySponsorPostId($this->getPDO(), $this->post->getPostId());
		$this->assertEquals($numRows +1, $this->getConnection()->getRowCount("sponsor"));
		$this->assertCount(1, $results);
		$this->assertContainsOnlyInstancesOf("Edu\\Cnm\\Kmaru\\Sponsor", $results);

		//grab the result from the array and validate it
		$pdoSponsor = $results[0];

		$this->assertEquals($pdoSponsor->getSponsorId(), $sponsorId);
		$this->assertEquals($pdoSponsor->getSponsorPostId(), $this->post->getPostId());
		$this->assertEquals($pdoSponsor->getSponsorProfileId(), $this->profile->getProfileId());
	}

	/**
	 * test grabbing a Sponsor that does not exist
	 **/
	public function testGetInvalidSponsorBySponsorPostId(): void {
		// grab a profile id that exceeds the maximum allowable profile id
		$sponsor = Sponsor::getSponsorBySponsorPostId($this->getPDO(), generateUuidV4());
		$this->assertCount(0, $sponsor);
	}
	/**
	 * test inserting a Sponsor and re-grabbing it from mySQL
	 **/
	public function testGetValidSponsorIdBySponsorProfileId() {
		//count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("sponsor");

		//create a new Sponsor and insert into mySQL
		$sponsorId = generateUuidV4();
		$sponsor = new Sponsor($sponsorId, $this->post->getPostId(), $this->profile->getProfileId());
		$sponsor->insert($this->getPDO());

		//grab the data from mySQL and enforce the fields match our expectations
		$results = Sponsor::getSponsorBySponsorProfileId($this->getPDO(), $this->profile->getProfileId());
		$this->assertEquals($numRows +1, $this->getConnection()->getRowCount("sponsor"));
		$this->assertCount(1, $results);
		$this->assertContainsOnlyInstancesOf("Edu\\Cnm\\Kmaru\\Sponsor", $results);

		//grab the result from the array and validate it
		$pdoSponsor = $results[0];

		$this->assertEquals($pdoSponsor->getSponsorId(), $sponsorId);
		$this->assertEquals($pdoSponsor->getSponsorPostId(), $this->post->getPostId());
		$this->assertEquals($pdoSponsor->getSponsorProfileId(), $this->profile->getProfileId());
	}

	/**
	 * test grabbing a Sponsor that does not exist
	 **/
	public function testGetInvalidSponsorBySponsorProfileId(): void {
		// grab a profile id that exceeds the maximum allowable profile id
		$sponsor = Sponsor::getSponsorBySponsorProfileId($this->getPDO(), generateUuidV4());
		$this->assertCount(0, $sponsor);
	}


}


?>