#
# Table structure for table 'tx_nkwgok_data'
#
CREATE TABLE tx_nkwgok_data (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	gok varchar(255) DEFAULT '' NOT NULL,
	ppn varchar(255) DEFAULT '' NOT NULL,
	search text,
	descr text,
	descr_en text,
	tags text,
	fromOpac BOOL,
	parent varchar(255) DEFAULT '' NOT NULL,
	hierarchy int(11) DEFAULT '0' NOT NULL,
	childcount int(11) DEFAULT '0' NOT NULL,
	hitcount int(11) DEFAULT '0' NOT NULL,
	PRIMARY KEY (uid),
	KEY parent (pid)
);