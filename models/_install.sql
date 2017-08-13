CREATE TABLE `voters` (

  -- indexes
  `rkid` int(255) NOT NULL,
  `rk_campaignid` int(255) NOT NULL,
  `vanid` varchar(255) NOT NULL,

  -- districting
  `cd` varchar(255) NOT NULL,
  `sd` varchar(255) NOT NULL,
  `hd` varchar(255) NOT NULL,

  -- statuses
  `active` int(11) NOT NULL DEFAULT '0',
  `status` varchar(255) NOT NULL,
  `closed` int(11) NOT NULL,
  `volunteer` varchar(11) NOT NULL,
  `wants_sign` int(11) NOT NULL,
  `callcount` int(11) NOT NULL,

  -- name
  `firstname` varchar(255) NOT NULL,
  `lastname` varchar(255) NOT NULL,
  `middlename` varchar(255) NOT NULL,
  `suffix` varchar(255) NOT NULL,
  `salutation` varchar(255) NOT NULL,

  -- vitals
  `enroll` varchar(255) NOT NULL,
  `dob` varchar(255) NOT NULL, -- needs formatting
  `sex` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `phoneType` varchar(31) NOT NULL,
  `profession` varchar(255) NOT NULL,
  `employer` varchar(255) NOT NULL,
  `bio` text NOT NULL,

  -- voting address (could be in an address table)
  `address1` varchar(255) NOT NULL,
  `stnum` int(255) NOT NULL,
  `stname1` varchar(255) NOT NULL,
  `unit` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL,
  `state` varchar(255) NOT NULL,
  `zip` varchar(255) NOT NULL,

  -- mailing address (could be in an address table)
  `mailaddress1` varchar(255) NOT NULL,
  `mailstnum` varchar(255) NOT NULL,
  `mailstname1` varchar(255) NOT NULL,
  `mailunit` varchar(255) NOT NULL,
  `mailcity` varchar(255) NOT NULL,
  `mailstate` varchar(255) NOT NULL,
  `mailzip` varchar(255) NOT NULL,

  -- voting history (could be in its own table)
  `datereg` varchar(255) NOT NULL, -- needs formatting
  `votedin2011` int(11) NOT NULL,
  `votedin2013` int(11) NOT NULL,

  -- geocoding
  `lat` float NOT NULL,
  `lon` float NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=11343 DEFAULT CHARSET=latin1;
