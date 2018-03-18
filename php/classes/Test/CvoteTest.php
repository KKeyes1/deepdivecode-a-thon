<?php
namespace Edu\Cnm\Forum\Test;

use Edu\Cnm\Forum\Community;
use Edu\Cnm\Forum\Comment;
use Edu\Cnm\Forum\Profile;
use Edu\Cnm\Forum\Post;
use Edu\Cnm\Forum\Cvote;

//grab the class under scrutiny: Cvote
require_once(dirname(__DIR__) . "/autoload.php");

//grab the uuid generator
require_once(dirname(__DIR__, 2) . "/lib/uuid.php");

/**
 * Full PHPUnit text for the Cvote class. It is complete
 * because *ALL* mySQL/PDO enabled methods are tested for both
 * invalid and valid inputs.
 *
 * @see Cvote
 **/
class CvoteTest extends ForumTest {
	/**
	 * comment linked to the post; this is for foreign key relations
	 * @var Community
	 **/
	protected $comment = null;
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

		//create and insert a Profile to own this test Cvote
		$this->profile = new Profile(generateUuidV4(), null, "email@cvote.com", $this->VALID_PROFILE_HASH, "John Smith", 3,$this->VALID_PROFILE_SALT, "jsmith");
		$this->profile->insert($this->getPDO());

		// create and insert a community to own the test post
		$this->community = new Community(generateUuidV4(), $this->profile->getProfileId(), "Health care");
		$this->community->insert($this->getPDO());

		// create and insert a post to own the test post
		$this->post = new Post(generateUuidV4(), $this->community->getCommunityId(), $this->profile->getProfileId(), $this->VALID_CONTENT);
		$this->post->insert($this->getPDO());

		// create and insert a comment
		$this->comment = new Comment(generateUuidV4(), $this->post->getPostId(), $this->profile->getProfileId(), $this->VALID_CONTENT);
		$this->comment->insert($this->getPDO());
	}
	/**
	 * test inserting a valid Cvote and verify that the actual mySQL data matches
	 **/
	public function testInsertValidCvote() : void {
		// count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("cvote");


		// create a new Cvote and insert to into mySQL
		$cvoteId = generateUuidV4();
		$cvote = new Cvote($cvoteId, $this->comment->getCommentId(), $this->profile->getProfileId());
		$cvote->insert($this->getPDO());

		//grab the data from mySQL and enforce the fields match our expectations
		$pdoCvote = Cvote::getCvoteByCvoteId($this->getPDO(), $cvote->getCvoteId());
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("cvote"));
		$this->assertEquals($pdoCvote->getCvoteId(), $cvote->getCvoteId());
		$this->assertEquals($pdoCvote->getCvoteCommentId(), $this->comment->getCommentId());
		$this->assertEquals($pdoCvote->getCvoteProfileId(), $this->profile->getProfileId());
	}
	/**
	 * test creating a Cvote and then deleting it
	 **/
	public function testDeleteValidCvote() : void {
		//count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("cvote");

		//create a new Cvote and insert into mySQL
		$cvoteId = generateUuidV4();
		$cvote = new Cvote($cvoteId, $this->comment->getCommentId(), $this->profile->getProfileId());
		$cvote->insert($this->getPDO());

		//delete the Cvote from mySQL
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("cvote"));
		$cvote->delete($this->getPDO());

		//grab the data from mySQL and enforce the Cvote does not exist
		$pdoCvote = Cvote::getCvoteByCvoteId($this->getPDO(), $cvote->getCvoteId());
		$this->assertNull($pdoCvote);
		$this->assertEquals($numRows, $this->getConnection()->getRowCount("cvote"));
	}

	/**
	 * test grabbing a Cvote that does not exist
	 **/
	public function testGetInvalidCvoteByCvoteId() : void {
		//grab a cvote id that exceeds the maximum allowable cvote id
		$cvote = Cvote::getCvoteByCvoteId($this->getPDO(), generateUuidV4());
		$this->assertNull($cvote);
	}
	/**
	 * test inserting a Cvote and re-grabbing it from mySQL
	 **/
	public function testGetValidCvoteIdByCvoteCommentId() {
		//count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("cvote");

		//create a new Cvote and insert into mySQL
		$cvoteId = generateUuidV4();
		$cvote = new Cvote($cvoteId, $this->comment->getCommentId(), $this->profile->getProfileId());
		$cvote->insert($this->getPDO());

		//grab the data from mySQL and enforce the fields match our expectations
		$results = Cvote::getCvoteByCvoteCommentId($this->getPDO(), $this->comment->getCommentId());
		$this->assertEquals($numRows +1, $this->getConnection()->getRowCount("cvote"));
		$this->assertCount(1, $results);
		$this->assertContainsOnlyInstancesOf("Edu\\Cnm\\Forum\\Cvote", $results);

		//grab the result from the array and validate it
		$pdoCvote = $results[0];

		$this->assertEquals($pdoCvote->getCvoteId(), $cvoteId);
		$this->assertEquals($pdoCvote->getCvoteCommentId(), $this->comment->getCommentId());
		$this->assertEquals($pdoCvote->getCvoteProfileId(), $this->profile->getProfileId());
	}

	/**
	 * test grabbing a Cvote that does not exist
	 **/
	public function testGetInvalidCvoteByCvoteCommentId(): void {
		// grab a profile id that exceeds the maximum allowable profile id
		$cvote = Cvote::getCvoteByCvoteCommentId($this->getPDO(), generateUuidV4());
		$this->assertCount(0, $cvote);
	}
	/**
	 * test inserting a Cvote and re-grabbing it from mySQL
	 **/
	public function testGetValidCvoteIdByCvoteProfileId() {
		//count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("cvote");

		//create a new Cvote and insert into mySQL
		$cvoteId = generateUuidV4();
		$cvote = new Cvote($cvoteId, $this->comment->getCommentId(), $this->profile->getProfileId());
		$cvote->insert($this->getPDO());

		//grab the data from mySQL and enforce the fields match our expectations
		$results = Cvote::getCvoteByCvoteProfileId($this->getPDO(), $this->profile->getProfileId());
		$this->assertEquals($numRows +1, $this->getConnection()->getRowCount("cvote"));
		$this->assertCount(1, $results);
		$this->assertContainsOnlyInstancesOf("Edu\\Cnm\\Forum\\Cvote", $results);

		//grab the result from the array and validate it
		$pdoCvote = $results[0];

		$this->assertEquals($pdoCvote->getCvoteId(), $cvoteId);
		$this->assertEquals($pdoCvote->getCvoteCommentId(), $this->comment->getCommentId());
		$this->assertEquals($pdoCvote->getCvoteProfileId(), $this->profile->getProfileId());
	}

	/**
	 * test grabbing a Cvote that does not exist
	 **/
	public function testGetInvalidCvoteByCvoteProfileId(): void {
		// grab a profile id that exceeds the maximum allowable profile id
		$cvote = Cvote::getCvoteByCvoteProfileId($this->getPDO(), generateUuidV4());
		$this->assertCount(0, $cvote);
	}


}


?>