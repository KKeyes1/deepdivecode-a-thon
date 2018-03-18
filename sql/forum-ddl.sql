ALTER DATABASE whoisken_2018_hack_a_thon CHARACTER SET utf8 COLLATE utf8_unicode_ci;
DROP TABLE IF EXISTS sponsor;
DROP TABLE IF EXISTS cvote;
DROP TABLE IF EXISTS pvote;
DROP TABLE IF EXISTS comment;
DROP TABLE IF EXISTS post;
DROP TABLE IF EXISTS community;
DROP TABLE IF EXISTS profile;

-- profile table:
CREATE TABLE profile(
	-- attribute for primary key:
	profileId BINARY(16) NOT NULL,
	-- attributes for profile:
	profileActivationToken CHAR(32),
	profileEmail VARCHAR(128) NOT NULL,
	profileHash CHAR(128) NOT NULL,
	profileSalt CHAR(64) NOT NULL,
	profileUsername VARCHAR(50),
	-- unique index created:
	UNIQUE (profileEmail),
	UNIQUE (profileUsername),
	-- Primary key:
	PRIMARY KEY(profileId)
);

-- community table:
CREATE TABLE community(
	-- attribute for primary key:
	communityId BINARY(16) NOT NULL,
	-- attribute for foreign key:
	communityProfileId BINARY(16) NOT NULL,
	-- attributes for community;
	communityName VARCHAR(64) NOT NULL,
	-- unique index created:
	INDEX (communityProfileId),
	INDEX (communityName),
	-- create foreign keys and relationships:
	FOREIGN KEY (communityProfileId) REFERENCES profile(profileId),
	-- Primary key:
	PRIMARY KEY(communityId)
);

-- post table:
CREATE TABLE post(
	-- attribute for primary key:
	postId BINARY(16) NOT NULL,
	-- attribute for foreign keys:
	postCommunityId BINARY(16) NOT NULL,
	postProfileId BINARY (16) NOT NULL,
	-- attribute for post:
	postContent VARCHAR(40000),
	-- unique index created:
	INDEX (postCommunityId),
	INDEX (postProfileId),
	-- create foreign keys and relationsships:
	FOREIGN KEY (postCommunityId) REFERENCES community(communityId),
	FOREIGN KEY (postProfileId) REFERENCES profile(profileId),
	-- primary key:
	PRIMARY KEY (postId)
);

-- comment table:
CREATE TABLE comment(
	-- attribute for primary key:
	commentId BINARY(16) NOT NULL,
	-- attribute for foreign keys:
	commentPostId BINARY(16) NOT NULL,
	commentProfileId BINARY(16) NOT NULL,
	-- attribute for comment:
	commentContent VARCHAR(40000),
	-- unique index created:
	INDEX (commentPostId),
	INDEX (commentProfileId),
	-- create foreign keys and relationships:
	FOREIGN KEY (commentPostId) REFERENCES post(postId),
	FOREIGN KEY (commentProfileId) REFERENCES profile(profileId),
	-- primary key:
	PRIMARY KEY (commentId)
);

-- post vote table:
CREATE TABLE pvote(
	-- attribute for primary key:
	pvoteId BINARY(16) NOT NULL,
	-- attribute for foreign keys:
	pvotePostId BINARY(16) NOT NULL,
	pvoteProfileId BINARY(16) NOT NULL,
	-- unique index created:
	INDEX (pvotePostId),
	INDEX (pvoteProfileId),
	-- create foreign keys and relationships:
	FOREIGN KEY (pvotePostId) REFERENCES post(postId),
	FOREIGN KEY (pvoteProfileId) REFERENCES profile(profileId),
	-- primary key:
	PRIMARY KEY (pvoteId)
);

-- comment vote table:
CREATE TABLE cvote(
	-- attribute for primary key:
	cvoteId BINARY(16) NOT NULL,
	-- attribute for foreign keys:
	cvoteCommentId BINARY(16) NOT NULL,
	cvoteProfileId BINARY(16) NOT NULL,
	-- unique index created:
	INDEX (cvoteCommunityId),
	INDEX (cvoteProfileId),
	-- create foreign keys and relationships:
	FOREIGN KEY (cvoteCommentId) REFERENCES comment(commentId),
	FOREIGN KEY (cvoteProfileId) REFERENCES profile(profileId),
	-- primary key:
	PRIMARY KEY (cvoteId)
);

-- sponsor post table:
CREATE TABLE sponsor(
	-- attribute for primary key:
	sponsorId BINARY(16) NOT NULL,
	-- attribute for foreign key:
	sponsorPostId BINARY(16) NOT NULL,
	sponsorProfileId BINARY(16) NOT NULL,
	-- unique index created:
	INDEX (sponsorPostId),
	INDEX (sponsorProfileId),
	-- create foreign keys and relationships:
	FOREIGN KEY (sponsorPostId) REFERENCES post(postId),
	FOREIGN KEY (sponsorProfileId) REFERENCES profile(profileId),
	-- primary key:
	PRIMARY KEY (sponsorId)
);