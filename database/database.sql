CREATE TABLE `users` (
  `user_id` int PRIMARY KEY,
  `full_name` varchar(255),
  `email` varchar(255),
  `password` varchar(255),
  `avatar` varchar(255),
  `role` varchar(255),
  `created_at` datetime
);

CREATE TABLE `documents` (
  `document_id` int PRIMARY KEY,
  `user_id` int,
  `title` varchar(255),
  `description` text,
  `file_name` varchar(255),
  `file_type` varchar(255),
  `upload_date` datetime
);

CREATE TABLE `categories` (
  `category_id` int PRIMARY KEY,
  `category_name` varchar(255)
);

CREATE TABLE `document_categories` (
  `id` int PRIMARY KEY,
  `document_id` int,
  `category_id` int
);

CREATE TABLE `chat_history` (
  `chat_id` int PRIMARY KEY,
  `user_id` int,
  `question` text,
  `answer` text,
  `created_at` datetime
);

ALTER TABLE `documents` ADD FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

ALTER TABLE `chat_history` ADD FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

ALTER TABLE `document_categories` ADD FOREIGN KEY (`document_id`) REFERENCES `documents` (`document_id`);

ALTER TABLE `document_categories` ADD FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`);
