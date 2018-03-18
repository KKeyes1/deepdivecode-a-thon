<?php

namespace Edu\Cnm\Kmaru\Test;

use Edu\Cnm\kmaru\Profile;

// grab class
require_once(dirname(__DIR__) . "/autoload.php");
// grab the uuid generator
require_once(dirname(__DIR__, 2) . "/lib/uuid.php");
/**
 * Full PHPUnit test for the Profile class
 *
 * This is a complete PHPUnit test of the Profile class. It is complete because *ALL* mySQL/PDO enabled methods
 * are tested for both invalid and valid inputs.
 *
 * @see Profile
 * @author Kenneth Keyes kkeyes1@cnm.edu
 **/

class ProfileTest extends KmaruTest {
	/**
	 * placeholder until account activation is created
	 * @var string $VALID_ACTIVATION
	 */
	protected $VALID_ACTIVATION;
	/**
	 * valid email to use
	 * @var string $VALID_EMAIL
	 **/
	protected $VALID_EMAIL = "kkeyes1@cnm.edu";
	/**
	 * valid hash to use
	 * @var $VALID_HASH
	 */
	protected $VALID_HASH;
	/**
	 * valid salt to use to create the profile object to own the test
	 * @var string $VALID_SALT
	 */
	protected $VALID_SALT;
	/**
	 * valid username to use
	 * @var string $VALID_USERNAME
	 **/
	protected $VALID_USERNAME = "brignat91";
	/**
	 * second valid username to use
	 * @var string $VALID_USERNAME2
	 **/
	protected $VALID_USERNAME2 = "kkeyes1";
	/**
	 * run the default setup operation to create salt and hash.
	 */
	public final function setUp() : void {
		parent::setUp();
		$password = "master";
		$this->VALID_SALT = bin2hex(random_bytes(32));
		$this->VALID_HASH = hash_pbkdf2("sha512", $password, $this->VALID_SALT, 262144);
		$this->VALID_ACTIVATION = bin2hex(random_bytes(16));
	}
	/**
	 * test inserting a valid Profile and verify that the actual mySQL data matches
	 **/
	public function testInsertValidProfile() : void {
		// count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("profile");
		$profileId = generateUuidV4();
		$profile = new Profile($profileId, $this->VALID_ACTIVATION, $this->VALID_EMAIL, $this->VALID_HASH, $this->VALID_SALT, $this->VALID_USERNAME);
		$profile->insert($this->getPDO());
		// grab the data from mySQL and enforce the fields match our expectations
		$pdoProfile = Profile::getProfileByProfileId($this->getPDO(), $profile->getProfileId());
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("profile"));
		$this->assertEquals($pdoProfile->getProfileId(), $profileId);
		$this->assertEquals($pdoProfile->getProfileActivationToken(), $this->VALID_ACTIVATION);
		$this->assertEquals($pdoProfile->getProfileEmail(), $this->VALID_EMAIL);
		$this->assertEquals($pdoProfile->getProfileHash(), $this->VALID_HASH);
		$this->assertEquals($pdoProfile->getProfileSalt(), $this->VALID_SALT);
		$this->assertEquals($pdoProfile->getProfileUsername(), $this->VALID_USERNAME);
	}
	/**
	 * test inserting a Profile, editing it, and then updating it
	 **/
	public function testUpdateValidProfile() {
		// count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("profile");
		// create a new Profile and insert to into mySQL
		$profileId = generateUuidV4();
		$profile = new Profile($profileId, $this->VALID_ACTIVATION, $this->VALID_EMAIL, $this->VALID_HASH, $this->VALID_SALT, $this->VALID_USERNAME);
		$profile->insert($this->getPDO());
		// edit the Profile and update it in mySQL
		$profile->setProfileUsername($this->VALID_USERNAME2);
		$profile->update($this->getPDO());
		// grab the data from mySQL and enforce the fields match our expectations
		$pdoProfile = Profile::getProfileByProfileId($this->getPDO(), $profile->getProfileId());
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("profile"));
		$this->assertEquals($pdoProfile->getProfileId(), $profileId);
		$this->assertEquals($pdoProfile->getProfileActivationToken(), $this->VALID_ACTIVATION);
		$this->assertEquals($pdoProfile->getProfileEmail(), $this->VALID_EMAIL);
		$this->assertEquals($pdoProfile->getProfileHash(), $this->VALID_HASH);
		$this->assertEquals($pdoProfile->getProfileSalt(), $this->VALID_SALT);
		$this->assertEquals($pdoProfile->getProfileUsername(), $this->VALID_USERNAME2);
	}
	/**
	 * test creating a Profile and then deleting it
	 **/
	public function testDeleteValidProfile() : void {
		// count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("profile");
		$profileId = generateUuidV4();
		$profile = new Profile($profileId, $this->VALID_ACTIVATION, $this->VALID_EMAIL, $this->VALID_HASH, $this->VALID_SALT, $this->VALID_USERNAME);
		$profile->insert($this->getPDO());
		// delete the Profile from mySQL
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("profile"));
		$profile->delete($this->getPDO());
		// grab the data from mySQL and enforce the Profile does not exist
		$pdoProfile = Profile::getProfileByProfileId($this->getPDO(), $profile->getProfileId());
		$this->assertNull($pdoProfile);
		$this->assertEquals($numRows, $this->getConnection()->getRowCount("profile"));
	}
	/**
	 * test inserting a Profile and regrabbing it from mySQL
	 **/
	public function testGetValidProfileByProfileId() : void {
		// count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("profile");
		$profileId = generateUuidV4();
		$profile = new Profile($profileId, $this->VALID_ACTIVATION, $this->VALID_EMAIL, $this->VALID_HASH, $this->VALID_SALT, $this->VALID_USERNAME);
		$profile->insert($this->getPDO());
		// grab the data from mySQL and enforce the fields match our expectations
		$pdoProfile = Profile::getProfileByProfileId($this->getPDO(), $profile->getProfileId());
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("profile"));
		$this->assertEquals($pdoProfile->getProfileId(), $profileId);
		$this->assertEquals($pdoProfile->getProfileActivationToken(), $this->VALID_ACTIVATION);
		$this->assertEquals($pdoProfile->getProfileEmail(), $this->VALID_EMAIL);
		$this->assertEquals($pdoProfile->getProfileHash(), $this->VALID_HASH);
		$this->assertEquals($pdoProfile->getProfileSalt(), $this->VALID_SALT);
		$this->assertEquals($pdoProfile->getProfileUsername(), $this->VALID_USERNAME);
	}
	/**
	 * test grabbing a Profile that does not exist
	 **/
	public function testGetInvalidProfileByProfileId() : void {
		// grab a profile id that exceeds the maximum allowable profile id
		$fakeProfileId = generateUuidV4();
		$profile = Profile::getProfileByProfileId($this->getPDO(), $fakeProfileId );
		$this->assertNull($profile);
	}
	public function testGetValidProfileByUsername() {
		// count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("profile");
		$profileId = generateUuidV4();
		$profile = new Profile($profileId, $this->VALID_ACTIVATION, $this->VALID_EMAIL, $this->VALID_HASH, $this->VALID_SALT, $this->VALID_USERNAME);
		$profile->insert($this->getPDO());
		//grab the data from MySQL
		$results = Profile::getProfileByProfileUsername($this->getPDO(), $this->VALID_USERNAME);
		$this->assertEquals($numRows +1, $this->getConnection()->getRowCount("profile"));
		//enforce no other objects are bleeding into profile
		$this->assertContainsOnlyInstancesOf("Edu\\CNM\\Kmaru\\Profile", $results);
		//enforce the results meet expectations
		$pdoProfile = $results[0];
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("profile"));
		$this->assertEquals($pdoProfile->getProfileId(), $profileId);
		$this->assertEquals($pdoProfile->getProfileActivationToken(), $this->VALID_ACTIVATION);
		$this->assertEquals($pdoProfile->getProfileEmail(), $this->VALID_EMAIL);
		$this->assertEquals($pdoProfile->getProfileHash(), $this->VALID_HASH);
		$this->assertEquals($pdoProfile->getProfileSalt(), $this->VALID_SALT);
		$this->assertEquals($pdoProfile->getProfileUsername(), $this->VALID_USERNAME);
	}
	/**
	 * test grabbing a Profile by username that does not exist
	 **/
	public function testGetInvalidProfileByUsername() : void {
		// grab a username that does not exist
		$profile = Profile::getProfileByProfileUsername($this->getPDO(), "starwarsrules");
		$this->assertCount(0, $profile);
	}
	/**
	 * test grabbing a Profile by email
	 **/
	public function testGetValidProfileByEmail() : void {
		// count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("profile");
		$profileId = generateUuidV4();
		$profile = new Profile($profileId, $this->VALID_ACTIVATION, $this->VALID_EMAIL, $this->VALID_HASH, $this->VALID_SALT, $this->VALID_USERNAME);
		$profile->insert($this->getPDO());
		// grab the data from mySQL and enforce the fields match our expectations
		$pdoProfile = Profile::getProfileByProfileEmail($this->getPDO(), $profile->getProfileEmail());
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("profile"));
		$this->assertEquals($pdoProfile->getProfileId(), $profileId);
		$this->assertEquals($pdoProfile->getProfileActivationToken(), $this->VALID_ACTIVATION);
		$this->assertEquals($pdoProfile->getProfileEmail(), $this->VALID_EMAIL);
		$this->assertEquals($pdoProfile->getProfileHash(), $this->VALID_HASH);
		$this->assertEquals($pdoProfile->getProfileSalt(), $this->VALID_SALT);
		$this->assertEquals($pdoProfile->getProfileUsername(), $this->VALID_USERNAME);
	}
	/**
	 * test grabbing a Profile by an email that does not exists
	 **/
	public function testGetInvalidProfileByEmail() : void {
		// grab an email that does not exist
		$profile = Profile::getProfileByProfileEmail($this->getPDO(), "name.lastname@host.com");
		$this->assertNull($profile);
	}
	/**
	 * test grabbing a profile by its activation
	 */
	public function testGetValidProfileByActivationToken() : void {
		// count the number of rows and save it for later
		$numRows = $this->getConnection()->getRowCount("profile");
		$profileId = generateUuidV4();
		$profile = new Profile($profileId, $this->VALID_ACTIVATION, $this->VALID_EMAIL, $this->VALID_HASH, $this->VALID_SALT, $this->VALID_USERNAME);
		$profile->insert($this->getPDO());
		// grab the data from mySQL and enforce the fields match our expectations
		$pdoProfile = Profile::getProfileByProfileActivationToken($this->getPDO(), $profile->getProfileActivationToken());
		$this->assertEquals($numRows + 1, $this->getConnection()->getRowCount("profile"));
		$this->assertEquals($pdoProfile->getProfileId(), $profileId);
		$this->assertEquals($pdoProfile->getProfileActivationToken(), $this->VALID_ACTIVATION);
		$this->assertEquals($pdoProfile->getProfileEmail(), $this->VALID_EMAIL);
		$this->assertEquals($pdoProfile->getProfileHash(), $this->VALID_HASH);
		$this->assertEquals($pdoProfile->getProfileSalt(), $this->VALID_SALT);
		$this->assertEquals($pdoProfile->getProfileUsername(), $this->VALID_USERNAME);
	}
	/**
	 * test grabbing a Profile by an email that does not exists
	 **/
	public function testGetInvalidProfileActivation() : void {
		// grab an email that does not exist
		$profile = Profile::getProfileByProfileActivationToken($this->getPDO(), "5bba7afb6cd846bdb963eab152f2473c");
		$this->assertNull($profile);
	}

}