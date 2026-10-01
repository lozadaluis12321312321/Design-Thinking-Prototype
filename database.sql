-- phpMyAdmin SQL Dump
-- Database: `one_optics`

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Password is 'admin123'
INSERT INTO `users` (`username`, `password`) VALUES
('admin', '$2y$10$eO.x4Y5M/qf1zYI0kO68uOpM8H5m1YI4Q/x8ZlT3wV7w5vT4m1Z0a');

CREATE TABLE `doctors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL,
  `image` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `doctors` (`name`, `role`, `image`) VALUES
('Doctor Bong Tayamora', 'Optometrist / Doctor', 'https://via.placeholder.com/150/f8f9fa/333333?text=Dr.+Bong');

CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` varchar(100) DEFAULT NULL,
  `style` varchar(100) DEFAULT NULL,
  `color` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `availability` varchar(50) DEFAULT 'In Stock',
  `image` varchar(500) DEFAULT NULL,
  `stock` int(11) DEFAULT 10,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `products` (`name`, `type`, `style`, `color`, `price`, `description`, `availability`, `image`, `stock`) VALUES
('Classic Aviator', 'Full Rim', 'Aviator', 'Gold', 2500.00, 'Timeless classic aviator frames suitable for all face shapes.', 'In Stock', 'https://via.placeholder.com/400x300/f8f9fa/333333?text=Classic+Aviator', 15),
('Modern Rectangle', 'Half Rim', 'Rectangle', 'Black', 1800.00, 'Sleek and professional rectangle frames.', 'In Stock', 'https://via.placeholder.com/400x300/f8f9fa/333333?text=Modern+Rectangle', 20),
('Vintage Round', 'Full Rim', 'Round', 'Tortoiseshell', 2200.00, 'Retro-inspired round frames for a distinct look.', 'Low Stock', 'https://via.placeholder.com/400x300/f8f9fa/333333?text=Vintage+Round', 3),
('Elegant Cat-Eye', 'Full Rim', 'Cat-Eye', 'Red', 2800.00, 'Bold and elegant cat-eye frames.', 'In Stock', 'https://via.placeholder.com/400x300/f8f9fa/333333?text=Elegant+Cat-Eye', 12),
('Minimalist Rimless', 'Rimless', 'Rectangle', 'Silver', 3000.00, 'Ultra-lightweight rimless glasses.', 'Out of Stock', 'https://via.placeholder.com/400x300/f8f9fa/333333?text=Minimalist+Rimless', 0),
('Bold Square', 'Full Rim', 'Square', 'Clear', 2100.00, 'Contemporary clear square frames.', 'In Stock', 'https://via.placeholder.com/400x300/f8f9fa/333333?text=Bold+Square', 8);

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customerName` varchar(255) NOT NULL,
  `contactNumber` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `doctorId` int(11) DEFAULT NULL,
  `type` varchar(100) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `time` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `createdDate` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`doctorId`) REFERENCES `doctors`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customerName` varchar(255) NOT NULL,
  `contactNumber` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `totalAmount` decimal(10,2) NOT NULL,
  `date` datetime DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `paymentMethod` varchar(100) DEFAULT NULL,
  `paymentStatus` varchar(50) DEFAULT 'Pending',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `orderId` int(11) DEFAULT NULL,
  `productId` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`orderId`) REFERENCES `orders`(`id`),
  FOREIGN KEY (`productId`) REFERENCES `products`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
