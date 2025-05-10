-- Primary key for bank_info table
ALTER TABLE `bank_info`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

-- Auto increment for bank_info
ALTER TABLE `bank_info`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;
