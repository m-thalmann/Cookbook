CREATE TABLE `config` (
  `key` VARCHAR(50) NOT NULL PRIMARY KEY,
  `value` TEXT,
  `datatype` ENUM('string', 'boolean', 'integer', 'number')
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(100) NOT NULL,
  `name` VARCHAR(20) NOT NULL,
  `password` VARCHAR(64) NOT NULL,
  `passwordSalt` VARCHAR(32) NOT NULL,
  `languageCode` VARCHAR(5) NOT NULL,
  `verifyEmailCode` VARCHAR(5),
  `verifyEmailCodeExpires` DATETIME,
  `isAdmin` BOOLEAN NOT NULL DEFAULT 0,
  `lastUpdated` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `badLoginAttempts` INT(11) NOT NULL DEFAULT 0,

  UNIQUE(`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `reset_password` (
  `userId` INT(11) NOT NULL,
  `token` VARCHAR(32) NOT NULL,
  `expires` DATETIME NOT NULL,

  UNIQUE(userId, token),

  FOREIGN KEY (userId) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `recipes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `userId` INT(11) NOT NULL,
  `public` BOOLEAN NOT NULL DEFAULT 0,
  `languageCode` VARCHAR(5) NOT NULL,
  `name` VARCHAR(50) NOT NULL,
  `description` TEXT,
  `category` VARCHAR(10),
  `portions` INT(11),
  `difficulty` INT(11),
  `preparation` TEXT,
  `preparationTime` INT(11),
  `restTime` INT(11),
  `cookTime` INT(11),
  `publishDate` DATETIME NOT NULL,

  FOREIGN KEY (userId) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `ingredients` (
  `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `recipeId` INT(11) NOT NULL,
  `amount` FLOAT,
  `unit` VARCHAR(20),
  `name` VARCHAR(40) NOT NULL,
  `group` VARCHAR(20) NOT NULL,

  UNIQUE(`recipeId`, `name`, `group`),

  FOREIGN KEY (recipeId) REFERENCES recipes(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `recipe_images` (
  `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `recipeId` INT(11) NOT NULL,
  `fileName` TEXT NOT NULL,
  `mimeType` VARCHAR(20) NOT NULL,

  FOREIGN KEY (recipeId) REFERENCES recipes(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;