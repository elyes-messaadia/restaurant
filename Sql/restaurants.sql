CREATE TABLE `restaurants` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL,
    `city` VARCHAR(50) NOT NULL,
    `seats` INT NOT NULL CHECK(seats BETWEEN 1 AND 20),
    `cree_le` DATETIME NOT NULL,
    `modifie_le` DATETIME NOT NULL
);