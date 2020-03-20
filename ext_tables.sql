#
# Table structure for table 'tx_nkwgok_data'
#
CREATE TABLE tx_nkwgok_data
(
    uid                INT(11)                  NOT NULL AUTO_INCREMENT,
    pid                INT(11)      DEFAULT '0' NOT NULL,
    tstamp             INT(11)      DEFAULT '0' NOT NULL,
    crdate             INT(11)      DEFAULT '0' NOT NULL,
    cruser_id          INT(11)      DEFAULT '0' NOT NULL,
    sys_language_uid   INT(11)      DEFAULT '0' NOT NULL,

    notation           VARCHAR(255) DEFAULT ''  NOT NULL,
    ppn                VARCHAR(255) DEFAULT ''  NOT NULL,
    search             TEXT,
    descr              TEXT,
    descr_en           TEXT,
    descr_alternate    TEXT,
    descr_alternate_en TEXT,
    tags               TEXT,
    type               VARCHAR(255) DEFAULT ''  NOT NULL,
    parent             VARCHAR(255) DEFAULT ''  NOT NULL,
    hierarchy          INT(11)      DEFAULT '0' NOT NULL,
    childcount         INT(11)      DEFAULT '0' NOT NULL,
    hitcount           INT(11)      DEFAULT '0' NOT NULL,
    totalhitcount      INT(11)      DEFAULT '0' NOT NULL,
    statusID           TINYINT(1)   DEFAULT '0',

    PRIMARY KEY (uid),
    KEY parent (pid)
);
