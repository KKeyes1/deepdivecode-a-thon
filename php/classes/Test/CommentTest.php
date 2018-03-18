<?php
namespace Edu\Cnm\Forum\Test;
use Edu\Cnm\Forum\{Post, Comment, Profile};
// grab the class under scrutiny
require_once(dirname(__DIR__) . "/autoload.php");
// grab the uuid generator
require_once(dirname(__DIR__, 2) . "/lib/uuid.php");
/**
 * Full PHPUnit test for the Comment class
 *
 * This is a complete PHPUnit test of the Comment class. It is complete because *ALL* mySQL/PDO enabled methods
 * are tested for both invalid and valid inputs.
 *
 * @see Comment
 **/
class CommentTest extends ForumTest {
	/**
	 * profile id; this is for foreign key relations
	 * @var Uuid comment profile
	 **/
	protected $profile;
	/**
	 * Post Id; this is for foreign key relations
	 * @var  Uuid comment post
	 **/
	protected $post;
	/**
	 * valid content to use
	 * @var string $VALID_CONTENT
	 */
	protected $VALID_CONTENT = "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.";
	/**
	 * content for the updated comment
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

		//create and insert a Profile to own this test Post
		$this->profile = new Profile(generateUuidV4(), null, "@handle", $this->VALID_PROFILE_HASH, "harvey dent", "1", $this->VALID_PROFILE_SALT,"happygirl");
		$this->profile->insert($this->getPDO());

		// create and insert a post to own the test comment
		$this->post = new Post(generateUuidV4(), $this->profile->getProfileId(), "CSS");
		$this->post->insert($this->getPDO());
	}
	/**
	 * test inserting a valid Comment and verify that the actual mySQL data matches
	 **/
	public function testInsertValidComment(): void {
		// count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("comment");
		// create a new Comment and insert to into mySQL
		$commentId = generateUuidV4();
		$comment = new Comment($commentId, $this->post->getPostId(), $this->profile->getProfileId(), $this->VALID_CONTENT);
		$comment->insert($this->getPDO());
		// grab the data from mySQL and enforce the fields match our expectations
		$pdoComment = Comment::getCommentByCommentId($this->getPDO(), $comment->getCommentId());
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("comment"));
		$this->assertEquals($pdoComment->getCommentId(), $commentId);
		$this->assertEquals($pdoComment->getCommentPostId(), $this->post->getPostId());
		$this->assertEquals($pdoComment->getCommentProfileId(), $this->profile->getProfileId());
		$this->assertEquals($pdoComment->getCommentContent(), $this->VALID_CONTENT);
	}
	/**
	 * test updating a valid Comment and verify that the actual mySQL data matches
	 **/
	public function testUpdateValidComment(): void {
		// count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("comment");
		// create a new Comment and insert to into mySQL
		$commentId = generateUuidV4();
		$comment = new Comment($commentId, $this->post->getPostId(), $this->profile->getProfileId(), $this->VALID_CONTENT);
		$comment->insert($this->getPDO());
		$comment->insert($this->getPDO());
		// edit the comment and update it in mySQL
		$comment->setCommentContent($this->VALID_CONTENT2);
		$comment->update($this->getPDO());
		// grab the data from mySQL and enforce the fields match our expectations
		$pdoComment = Comment::getCommentByCommentId($this->getPDO(), $comment->getCommentId());
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("comment"));
		$this->assertEquals($pdoComment->getCommentId(), $commentId);
		$this->assertEquals($pdoComment->getCommentPostId(), $this->post->getPostId());
		$this->assertEquals($pdoComment->getCommentProfileId(), $this->profile->getProfileId());
		$this->assertEquals($pdoComment->getCommentContent(), $this->VALID_CONTENT2);
	}
	/**
	 * test creating a Comment and then deleting it
	 **/
	public function testDeleteValidComment(): void {
		// count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("comment");
		// create a new Comment and insert to into mySQL


		$commentId = generateUuidV4();
		$comment = new Comment($commentId, $this->post->getPostId(), $this->profile->getProfileId(), $this->VALID_CONTENT);
		$comment->insert($this->getPDO());



		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("comment"));
		$comment->delete($this->getPDO());

		$pdoComment = Comment::getCommentByCommentId($this->getPDO(), $comment->getCommentId());
		$this->assertNull($pdoComment);
		$this->assertEquals($numRows, $this->getConnection()->getRowCount("comment"));


	}


	/**
	 * test inserting a Comment and regrabbing it from mySQL
	 **/
	public function testGetValidCommentByCommentPostId() {
		// count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("comment");

		// create a new Comment and insert to into mySQL
		$commentId = generateUuidV4();
		$comment = new Comment($commentId, $this->post->getPostId(), $this->profile->getProfileId(), $this->VALID_CONTENT);
		$comment->insert($this->getPDO());

		// grab the data from mySQL and enforce the fields match our expectations
		$results = Comment::getCommentByCommentPostId($this->getPDO(), $comment->getCommentPostId());
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("comment"));
		$this->assertCount(1, $results);
		$this->assertContainsOnlyInstancesOf("Edu\\Cnm\\Forum\\Comment", $results);

		// grab the result from the array and validate it
		$pdoComment = $results[0];
		$this->assertEquals($pdoComment->getCommentId(), $commentId);
		$this->assertEquals($pdoComment->getCommentPostId(), $this->post->getPostId());
		$this->assertEquals($pdoComment->getCommentProfileId(), $this->profile->getProfileId());
		$this->assertEquals($pdoComment->getCommentContent(), $this->VALID_CONTENT);

	}

	/**
	 * test grabbing a Comment by commentPostId that does not exist
	 **/
	public function testGetInvalidCommentByCommentPostId() : void {
		// grab a comment post id that does not exist
		$fakeCommentPostId = generateUuidV4();
		$comment = Comment::getCommentByCommentPostId($this->getPDO(), generateUuidV4());
		$this->assertCount(0, $comment);
	}


	/**
	 * test inserting a Comment and regrabbing it from mySQL
	 **/
	public function testGetValidCommentByCommentId() {
		// count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("comment");

		// create a new Comment and insert to into mySQL
		$commentId = generateUuidV4();
		$comment = new Comment($commentId, $this->post->getPostId(), $this->profile->getProfileId(), $this->VALID_CONTENT);
		$comment->insert($this->getPDO());

		// grab the result from the array and validate it
		$pdoComment = Comment::getCommentByCommentId($this->getPDO(), $comment->getCommentId());
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("comment"));
		$this->assertEquals($pdoComment->getCommentId(), $commentId);
		$this->assertEquals($pdoComment->getCommentPostId(), $this->post->getPostId());
		$this->assertEquals($pdoComment->getCommentProfileId(), $this->profile->getProfileId());
		$this->assertEquals($pdoComment->getCommentContent(), $this->VALID_CONTENT);


	}

	/**
	 * test grabbing a Comment that does not exist
	 **/
	public function testGetInvalidCommentByCommentId() : void {
		// grab a comment post id that does not exist
		$comment = Comment::getCommentByCommentId($this->getPDO(), generateUuidV4());
		$this->assertEquals(0, $comment);
	}






}