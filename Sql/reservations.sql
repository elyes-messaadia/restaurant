CREATE TABLE `reservations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `restaurant_id` INT NOT NULL,
    `date` DATE NOT NULL,
    `couverts` INT NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`)
);