<?php
namespace Edu\Cnm\Forum\Test;
use Edu\Cnm\Forum\{Community, Post, Profile};
// grab the class under scrutiny
require_once(dirname(__DIR__) . "/autoload.php");
// grab the uuid generator
require_once(dirname(__DIR__, 2) . "/lib/uuid.php");
/**
 * Full PHPUnit test for the Post class
 *
 * This is a complete PHPUnit test of the Post class. It is complete because *ALL* mySQL/PDO enabled methods
 * are tested for both invalid and valid inputs.
 *
 * @see Post
 **/
class PostTest extends ForumTest {
	/**
	 * profile id; this is for foreign key relations
	 * @var Uuid post profile
	 **/
	protected $profile;
	/**
	 * Community Id; this is for foreign key relations
	 * @var  Uuid post community
	 **/
	protected $community;
	/**
	 * valid content to use
	 * @var string $VALID_CONTENT
	 */
	protected $VALID_CONTENT = "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.";
	/**
	 * content for the updated post
	 * @var string $VALID_CONTENT2
	 */
	protected $VALID_CONTENT2 = "Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem. Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur? Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur?";
	/**
	 * create dependent objects before running each test
	 **/
	public final function setUp(): void {
		// run the default setUp() method first
		parent::setUp();

		$password = "qwertyuiop";
		$this->VALID_PROFILE_SALT = bin2hex(random_bytes(32));
		$this->VALID_PROFILE_HASH = hash_pbkdf2("sha512", $password, $this->VALID_PROFILE_SALT, 262144);

		//create and insert a Profile to own this test Community
		$this->profile = new Profile(generateUuidV4(), null, "@handle", $this->VALID_PROFILE_HASH, "harvey dent", "1", $this->VALID_PROFILE_SALT,"happygirl");
		$this->profile->insert($this->getPDO());

		// create and insert a community to own the test post
		$this->community = new Community(generateUuidV4(), $this->profile->getProfileId(), "CSS");
		$this->community->insert($this->getPDO());
	}
	/**
	 * test inserting a valid Post and verify that the actual mySQL data matches
	 **/
	public function testInsertValidPost(): void {
		// count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("post");
		// create a new Post and insert to into mySQL
		$postId = generateUuidV4();
		$post = new Post($postId, $this->community->getCommunityId(), $this->profile->getProfileId(), $this->VALID_CONTENT);
		$post->insert($this->getPDO());
		// grab the data from mySQL and enforce the fields match our expectations
		$pdoPost = Post::getPostByPostId($this->getPDO(), $post->getPostId());
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("post"));
		$this->assertEquals($pdoPost->getPostId(), $postId);
		$this->assertEquals($pdoPost->getPostCommunityId(), $this->community->getCommunityId());
		$this->assertEquals($pdoPost->getPostProfileId(), $this->profile->getProfileId());
		$this->assertEquals($pdoPost->getPostContent(), $this->VALID_CONTENT);
	}
	/**
	 * test updating a valid Post and verify that the actual mySQL data matches
	 **/
	public function testUpdateValidPost(): void {
		// count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("post");
		// create a new Post and insert to into mySQL
		$postId = generateUuidV4();
		$post = new Post($postId, $this->community->getCommunityId(), $this->profile->getProfileId(), $this->VALID_CONTENT);
		$post->insert($this->getPDO());
		$post->insert($this->getPDO());
		// edit the post and update it in mySQL
		$post->setPostContent($this->VALID_CONTENT2);
		$post->update($this->getPDO());
		// grab the data from mySQL and enforce the fields match our expectations
		$pdoPost = Post::getPostByPostId($this->getPDO(), $post->getPostId());
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("post"));
		$this->assertEquals($pdoPost->getPostId(), $postId);
		$this->assertEquals($pdoPost->getPostCommunityId(), $this->community->getCommunityId());
		$this->assertEquals($pdoPost->getPostProfileId(), $this->profile->getProfileId());
		$this->assertEquals($pdoPost->getPostContent(), $this->VALID_CONTENT2);
	}
	/**
	 * test creating a Post and then deleting it
	 **/
	public function testDeleteValidPost(): void {
		// count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("post");
		// create a new Post and insert to into mySQL


		$postId = generateUuidV4();
		$post = new Post($postId, $this->community->getCommunityId(), $this->profile->getProfileId(), $this->VALID_CONTENT);
		$post->insert($this->getPDO());



		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("post"));
		$post->delete($this->getPDO());

		$pdoPost = Post::getPostByPostId($this->getPDO(), $post->getPostId());
		$this->assertNull($pdoPost);
		$this->assertEquals($numRows, $this->getConnection()->getRowCount("post"));


	}


	/**
	 * test inserting a Post and regrabbing it from mySQL
	 **/
	public function testGetValidPostByPostCommunityId() {
		// count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("post");

		// create a new Post and insert to into mySQL
		$postId = generateUuidV4();
		$post = new Post($postId, $this->community->getCommunityId(), $this->profile->getProfileId(), $this->VALID_CONTENT);
		$post->insert($this->getPDO());

		// grab the data from mySQL and enforce the fields match our expectations
		$results = Post::getPostByPostCommunityId($this->getPDO(), $post->getPostCommunityId());
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("post"));
		$this->assertCount(1, $results);
		$this->assertContainsOnlyInstancesOf("Edu\\Cnm\\Forum\\Post", $results);

		// grab the result from the array and validate it
		$pdoPost = $results[0];
		$this->assertEquals($pdoPost->getPostId(), $postId);
		$this->assertEquals($pdoPost->getPostCommunityId(), $this->community->getCommunityId());
		$this->assertEquals($pdoPost->getPostProfileId(), $this->profile->getProfileId());
		$this->assertEquals($pdoPost->getPostContent(), $this->VALID_CONTENT);

	}

	/**
	 * test grabbing a Post by postCommunityId that does not exist
	 **/
	public function testGetInvalidPostByPostCommunityId() : void {
		// grab a post community id that does not exist
		$fakePostCommunityId = generateUuidV4();
		$post = Post::getPostByPostCommunityId($this->getPDO(), generateUuidV4());
		$this->assertCount(0, $post);
	}


	/**
	 * test inserting a Post and regrabbing it from mySQL
	 **/
	public function testGetValidPostByPostId() {
		// count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("post");

		// create a new Post and insert to into mySQL
		$postId = generateUuidV4();
		$post = new Post($postId, $this->community->getCommunityId(), $this->profile->getProfileId(), $this->VALID_CONTENT);
		$post->insert($this->getPDO());

		// grab the result from the array and validate it
		$pdoPost = Post::getPostByPostId($this->getPDO(), $post->getPostId());
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("post"));
		$this->assertEquals($pdoPost->getPostId(), $postId);
		$this->assertEquals($pdoPost->getPostCommunityId(), $this->community->getCommunityId());
		$this->assertEquals($pdoPost->getPostProfileId(), $this->profile->getProfileId());
		$this->assertEquals($pdoPost->getPostContent(), $this->VALID_CONTENT);


	}

	/**
	 * test grabbing a Post that does not exist
	 **/
	public function testGetInvalidPostByPostId() : void {
		// grab a post community id that does not exist
		$post = Post::getPostByPostId($this->getPDO(), generateUuidV4());
		$this->assertEquals(0, $post);
	}






}