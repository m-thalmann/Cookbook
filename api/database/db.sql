CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `email` varchar(100) NOT NULL,
  `name` varchar(20) NOT NULL,
  `password` varchar(64) NOT NULL,
  `passwordSalt` varchar(32) NOT NULL,
  `verifyEmailCode` varchar(5),
  `lastUpdated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE(`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `reset_password` (
  `userId` int(11) NOT NULL,
  `token` varchar(32) NOT NULL,
  `expires` datetime NOT NULL,

  UNIQUE(userId, token),

  FOREIGN KEY (userId) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `recipes` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `userId` int(11) NOT NULL,
  `public` boolean NOT NULL DEFAULT 0,
  `name` varchar(50) NOT NULL,
  `description` text,
  `category` varchar(10),
  `portions` int(11),
  `difficulty` int(11),
  `preparation` text,
  `preparationTime` int(11),
  `restTime` int(11),
  `cookTime` int(11),
  `publishDate` datetime NOT NULL,

  FOREIGN KEY (userId) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `ingredients` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `recipeId` int(11) NOT NULL,
  `amount` float,
  `unit` varchar(10),
  `name` varchar(20),

  UNIQUE(`recipeId`, `name`),

  FOREIGN KEY (recipeId) REFERENCES recipes(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `recipe_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `recipeId` int(11) NOT NULL,
  `path` text NOT NULL,
  `mimeType` varchar(20) NOT NULL,

  FOREIGN KEY (recipeId) REFERENCES recipes(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;