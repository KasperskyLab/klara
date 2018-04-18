-- --------------------------------------------------------

--
-- Table structure for table `agents`
--

CREATE TABLE `agents` (
  `id` int(11) NOT NULL,
  `description` varchar(63) NOT NULL,
  `auth` varchar(63) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `ci_logs`
--

CREATE TABLE `ci_logs` (
  `cnt` int(11) NOT NULL,
  `type` varchar(63) NOT NULL,
  `module` varchar(63) NOT NULL,
  `data` varchar(511) NOT NULL,
  `ip` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `ci_sessions`
--

CREATE TABLE `ci_sessions` (
  `id` varchar(128) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `timestamp` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `data` blob NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` int(11) NOT NULL,
  `description` varchar(1000) NOT NULL,
  `results` mediumblob NOT NULL,
  `rules` mediumblob NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'new',
  `matched_files` int(11) NOT NULL DEFAULT '-1',
  `owner` varchar(127) NOT NULL,
  `owner_id` int(11) NOT NULL DEFAULT '-1',
  `owner_group_id` int(11) NOT NULL DEFAULT '-1',
  `agent_id` int(11) NOT NULL DEFAULT '-1',
  `start_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `finish_time` varchar(31) NOT NULL,
  `share_key` varchar(65) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `jobs_hashes`
--

CREATE TABLE `jobs_hashes` (
  `cnt` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `hash_md5` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `scan_filesets`
--

CREATE TABLE `scan_filesets` (
  `id` int(11) NOT NULL,
  `entry` varchar(127) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `scan_filesets`
--

INSERT INTO `scan_filesets` (`id`, `entry`) VALUES
(1, '/virus_repository'),
(2, '/_clean');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `cnt` int(10) UNSIGNED NOT NULL,
  `username` varchar(63) NOT NULL,
  `pass` varchar(63) NOT NULL,
  `auth` int(11) NOT NULL DEFAULT '0',
  `desc` varchar(127) NOT NULL,
  `api_auth_code` varchar(127) DEFAULT NULL,
  `api_perms` varchar(511) NOT NULL,
  `api_status` int(10) NOT NULL DEFAULT '0',
  `group_cnt` int(11) NOT NULL DEFAULT '1',
  `notify_email` varchar(127) NOT NULL,
  `quota_searches` int(11) NOT NULL DEFAULT '0',
  `quota_curr_month` varchar(15) NOT NULL,
  `searches_curr_month` int(11) NOT NULL DEFAULT '0',
  `dateadded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_last_login` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`cnt`, `username`, `pass`, `auth`, `desc`, `api_auth_code`, `api_perms`, `api_status`, `group_cnt`, `notify_email`, `quota_searches`, `quota_curr_month`, `searches_curr_month`, `dateadded`, `ip_last_login`) VALUES
(1, 'api', 'Please do not remove. Needed by users_api', 0, 'API Special User', NULL, '', 0, 1, '', 0, '', 0, '2018-04-18 01:03:19', 0),
(2, 'admin', '$2y$10$Kc51V1hnE9XnZAeBqWOChu7kwIq/3dA8ehmQu5XJrnaLQNcFrRxHm', 16, 'Administrator Account', NULL, '', 0, 2, '', 0, '', 0, '2018-04-18 01:19:18', 0),
(3, 'john', '$2y$10$omkUdYZebNqe.VgNqTgelupbNu.EIB8hVAF1V200R6R32nRsmCGZe', 4, 'Regular User with 1000 scans quota', NULL, '', 0, 1, '', 1000, '', 0, '2018-04-18 01:20:22', 0);

-- --------------------------------------------------------

--
-- Table structure for table `users_groups`
--

CREATE TABLE `users_groups` (
  `cnt` int(11) NOT NULL,
  `name` varchar(63) COLLATE utf8_unicode_ci NOT NULL,
  `scan_filesets_list` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
  `jail_users` int(11) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `users_groups`
--

INSERT INTO `users_groups` (`cnt`, `name`, `scan_filesets_list`, `jail_users`) VALUES
(1, 'main', '[1,2]', 0),
(2, 'admins', '[1,2]', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `agents`
--
ALTER TABLE `agents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ci_logs`
--
ALTER TABLE `ci_logs`
  ADD PRIMARY KEY (`cnt`);

--
-- Indexes for table `ci_sessions`
--
ALTER TABLE `ci_sessions`
  ADD PRIMARY KEY (`id`,`ip_address`),
  ADD KEY `ci_sessions_timestamp` (`timestamp`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `share_key` (`share_key`);

--
-- Indexes for table `jobs_hashes`
--
ALTER TABLE `jobs_hashes`
  ADD PRIMARY KEY (`cnt`),
  ADD UNIQUE KEY `job_id` (`job_id`,`hash_md5`);

--
-- Indexes for table `scan_filesets`
--
ALTER TABLE `scan_filesets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`cnt`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `api_auth_code` (`api_auth_code`);

--
-- Indexes for table `users_groups`
--
ALTER TABLE `users_groups`
  ADD PRIMARY KEY (`cnt`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `agents`
--
ALTER TABLE `agents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `ci_logs`
--
ALTER TABLE `ci_logs`
  MODIFY `cnt` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `jobs_hashes`
--
ALTER TABLE `jobs_hashes`
  MODIFY `cnt` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `scan_filesets`
--
ALTER TABLE `scan_filesets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `cnt` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT for table `users_groups`
--
ALTER TABLE `users_groups`
  MODIFY `cnt` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;