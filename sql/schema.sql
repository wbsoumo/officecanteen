-- Office Canteen Database Schema & Seed Data
-- Database Name: office_canteen

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `inventory`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `food_images`;
DROP TABLE IF EXISTS `foods`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `admins`;
DROP TABLE IF EXISTS `employees`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Employees Table
CREATE TABLE `employees` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` VARCHAR(50) UNIQUE NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `department` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `floor` INT NOT NULL,
  `cabin` VARCHAR(50) DEFAULT NULL,
  `desk_number` VARCHAR(50) DEFAULT NULL,
  `wallet_balance` DECIMAL(10,2) DEFAULT 500.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Admins Table (Admin, Chef, Kitchen Staff)
CREATE TABLE `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) UNIQUE NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'chef', 'kitchen') NOT NULL DEFAULT 'admin',
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Categories Table
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `icon` VARCHAR(50) NOT NULL, -- FontAwesome class
  `sort_order` INT DEFAULT 0,
  `visibility` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Foods Table
CREATE TABLE `foods` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `ingredients` TEXT,
  `calories` INT DEFAULT 0,
  `spice_level` INT DEFAULT 0, -- 0 to 3
  `price` DECIMAL(10,2) NOT NULL,
  `prep_time` INT NOT NULL, -- in minutes
  `category_id` INT,
  `veg_nonveg` ENUM('veg', 'nonveg') NOT NULL DEFAULT 'veg',
  `is_popular` TINYINT(1) DEFAULT 0,
  `is_featured` TINYINT(1) DEFAULT 0,
  `stock_status` ENUM('available', 'unavailable', 'out_of_stock') DEFAULT 'available',
  `image_url` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Food Images Table (Supporting additional images if needed)
CREATE TABLE `food_images` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `food_id` INT NOT NULL,
  `image_url` VARCHAR(255) NOT NULL,
  `is_primary` TINYINT(1) DEFAULT 0,
  FOREIGN KEY (`food_id`) REFERENCES `foods`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Orders Table
CREATE TABLE `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_number` VARCHAR(50) UNIQUE NOT NULL,
  `employee_id` INT NOT NULL,
  `department` VARCHAR(100) NOT NULL,
  `floor` INT NOT NULL,
  `cabin` VARCHAR(50) DEFAULT NULL,
  `desk_number` VARCHAR(50) DEFAULT NULL,
  `delivery_date` DATE NOT NULL,
  `delivery_time` TIME NOT NULL,
  `special_instructions` TEXT,
  `subtotal` DECIMAL(10,2) NOT NULL,
  `gst` DECIMAL(10,2) NOT NULL,
  `grand_total` DECIMAL(10,2) NOT NULL,
  `status` ENUM('received', 'confirmed', 'preparing', 'ready', 'out_of_delivery', 'delivered', 'cancelled') DEFAULT 'received',
  `payment_method` VARCHAR(50) DEFAULT 'cash_on_delivery',
  `is_agreed` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Order Items Table
CREATE TABLE `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `food_id` INT NOT NULL,
  `food_name` VARCHAR(100) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `quantity` INT NOT NULL,
  `special_notes` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`food_id`) REFERENCES `foods`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Inventory Table
CREATE TABLE `inventory` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `food_id` INT UNIQUE NOT NULL,
  `current_stock` INT DEFAULT 100,
  `last_restocked` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` ENUM('available', 'unavailable', 'out_of_stock') DEFAULT 'available',
  FOREIGN KEY (`food_id`) REFERENCES `foods`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Settings Table
CREATE TABLE `settings` (
  `setting_key` VARCHAR(100) PRIMARY KEY,
  `setting_value` TEXT,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Notifications Table
CREATE TABLE `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL, -- references employees(id) or admins(id) based on role
  `user_role` ENUM('employee', 'admin', 'chef', 'kitchen') NOT NULL DEFAULT 'employee',
  `message` TEXT NOT NULL,
  `status` ENUM('unread', 'read') DEFAULT 'unread',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Activity Logs Table
CREATE TABLE `activity_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL, -- NULL for guests
  `user_role` VARCHAR(50) DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `details` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ========================================================
-- SEED DATA
-- ========================================================

-- Seed 10 Categories
INSERT INTO `categories` (`id`, `name`, `icon`, `sort_order`, `visibility`) VALUES
(1, 'Breakfast', 'fa-egg', 1, 1),
(2, 'Lunch', 'fa-bowl-rice', 2, 1),
(3, 'Dinner', 'fa-utensils', 3, 1),
(4, 'Snacks', 'fa-cookie', 4, 1),
(5, 'Beverages', 'fa-mug-hot', 5, 1),
(6, 'Desserts', 'fa-ice-cream', 6, 1),
(7, 'South Indian', 'fa-stroopwafel', 7, 1),
(8, 'North Indian', 'fa-dharmachakra', 8, 1),
(9, 'Chinese', 'fa-plate-wheat', 9, 1),
(10, 'Healthy', 'fa-seedling', 10, 1);

-- Seed 2 Admins (Password for both: password123)
-- Hash: $2y$12$ukLIgsN/crQJG1Rw5Zlv2OiqRKov4GFWxXWNzQx9MmBaHuQ4DbPgi
INSERT INTO `admins` (`id`, `username`, `name`, `email`, `password_hash`, `role`, `status`) VALUES
(1, 'admin', 'Admin Canteen Manager', 'admin@canteen.com', '$2y$12$ukLIgsN/crQJG1Rw5Zlv2OiqRKov4GFWxXWNzQx9MmBaHuQ4DbPgi', 'admin', 'active'),
(2, 'chef', 'Head Chef Vikram', 'chef@canteen.com', '$2y$12$ukLIgsN/crQJG1Rw5Zlv2OiqRKov4GFWxXWNzQx9MmBaHuQ4DbPgi', 'chef', 'active'),
(3, 'kitchen', 'Kitchen Staff Amit', 'kitchen@canteen.com', '$2y$12$ukLIgsN/crQJG1Rw5Zlv2OiqRKov4GFWxXWNzQx9MmBaHuQ4DbPgi', 'kitchen', 'active');

-- Seed 30 Employees (Password for all: password123)
-- Hash: $2y$12$ukLIgsN/crQJG1Rw5Zlv2OiqRKov4GFWxXWNzQx9MmBaHuQ4DbPgi
INSERT INTO `employees` (`id`, `employee_id`, `name`, `department`, `phone`, `email`, `password_hash`, `status`, `floor`, `cabin`, `desk_number`, `wallet_balance`) VALUES
(1, 'EMP001', 'Rajesh Kumar', 'IT Infrastructure', '9876543210', 'rajesh.k@office.com', '$2y$12$ukLIgsN/crQJG1Rw5Zlv2OiqRKov4GFWxXWNzQx9MmBaHuQ4DbPgi', 'active', 4, 'Cabin-402', 'Desk-402A', 450.00),
(2, 'EMP002', 'Priya Sharma', 'Human Resources', '9876543211', 'priya.s@office.com', '$2y$12$ukLIgsN/crQJG1Rw5Zlv2OiqRKov4GFWxXWNzQx9MmBaHuQ4DbPgi', 'active', 2, 'Cabin-210', 'Desk-210C', 720.50),
(3, 'EMP003', 'Amit Verma', 'Software Engineering', '9876543212', 'amit.v@office.com', '$2y$12$ukLIgsN/crQJG1Rw5Zlv2OiqRKov4GFWxXWNzQx9MmBaHuQ4DbPgi', 'active', 5, 'Cabin-501', 'Desk-501F', 120.00),
(4, 'EMP004', 'Sunita Rao', 'Finance & Accounts', '9876543213', 'sunita.r@office.com', '$2y$12$ukLIgsN/crQJG1Rw5Zlv2OiqRKov4GFWxXWNzQx9MmBaHuQ4DbPgi', 'active', 3, 'Cabin-305', 'Desk-305B', 680.00),
(5, 'EMP005', 'Vikram Singh', 'Marketing & Sales', '9876543214', 'vikram.s@office.com', '$2y$12$ukLIgsN/crQJG1Rw5Zlv2OiqRKov4GFWxXWNzQx9MmBaHuQ4DbPgi', 'active', 1, 'Cabin-103', 'Desk-103D', 240.25),
(6, 'EMP006', 'Ananya Patel', 'Design UI/UX', '9876543215', 'ananya.p@office.com', '$2y$12$ukLIgsN/crQJG1Rw5Zlv2OiqRKov4GFWxXWNzQx9MmBaHuQ4DbPgi', 'active', 5, 'Cabin-512', 'Desk-512B', 980.00),
(7, 'EMP007', 'Suresh Nair', 'Operations', '9876543216', 'suresh.n@office.com', '$2y$12$ukLIgsN/crQJG1Rw5Zlv2OiqRKov4GFWxXWNzQx9MmBaHuQ4DbPgi', 'active', 2, 'Cabin-202', 'Desk-202E', 350.00),
(8, 'EMP008', 'Kavita Joshi', 'Legal & Compliance', '9876543217', 'kavita.j@office.com', '$2y$12$ukLIgsN/crQJG1Rw5Zlv2OiqRKov4GFWxXWNzQx9MmBaHuQ4DbPgi', 'active', 3, 'Cabin-312', 'Desk-312A', 500.00),
(9, 'EMP009', 'Rahul Gupta', 'Software Engineering', '9876543218', 'rahul.g@office.com', '$2y$12$ukLIgsN/crQJG1Rw5Zlv2OiqRKov4GFWxXWNzQx9MmBaHuQ4DbPgi', 'active', 5, 'Cabin-504', 'Desk-504C', 15.00),
(10, 'EMP010', 'Neha Mehta', 'Human Resources', '9876543219', 'neha.m@office.com', '$2y$12$ukLIgsN/crQJG1Rw5Zlv2OiqRKov4GFWxXWNzQx9MmBaHuQ4DbPgi', 'active', 2, 'Cabin-210', 'Desk-210B', 300.00),
(11, 'EMP011', 'Sanjay Dutt', 'IT Support', '9876543220', 'sanjay.d@office.com', '$2y$12$ukLIgsN/crQJG1Rw5Zlv2OiqRKov4GFWxXWNzQx9MmBaHuQ4DbPgi', 'active', 4, 'Cabin-401', 'Desk-401B', 225.00),
(12, 'EMP012', 'Meenakshi Iyer', 'Finance & Accounts', '9876543221', 'meenakshi.i@office.com', '$2y$12$ukLIgsN/crQJG1Rw5Zlv2OiqRKov4GFWxXWNzQx9MmBaHuQ4DbPgi', 'active', 3, 'Cabin-304', 'Desk-304A', 410.00),
(13, 'EMP013', 'Arjun Kapoor', 'Product Management', '9876543222', 'arjun.k@office.com', '$2y$12$ukLIgsN/crQJG1Rw5Zlv2OiqRKov4GFWxXWNzQx9MmBaHuQ4DbPgi', 'active', 4, 'Cabin-415', 'Desk-415D', 1500.00),
(14, 'EMP014', 'Divya Teja', 'Data Science', '9876543223', 'divya.t@office.com', '$2y$12$ukLIgsN/crQJG1Rw5Zlv2OiqRKov4GFWxXWNzQx9MmBaHuQ4DbPgi', 'active', 5, 'Cabin-508', 'Desk-508A', 890.00),
(15, 'EMP015', 'Rohan Das', 'Marketing & Sales', '9876543224', 'rohan.d@office.com', '$2y$12$ukLIgsN/crQJG1Rw5Zlv2OiqRKov4GFWxXWNzQx9MmBaHuQ4DbPgi', 'active', 1, 'Cabin-105', 'Desk-105C', 70.00),
(16, 'EMP016', 'Shalini Sen', 'Customer Success', '9876543225', 'shalini.s@office.com', '$2y$12$ukLIgsN/crQJG1Rw5Zlv2OiqRKov4GFWxXWNzQx9MmBaHuQ4DbPgi', 'active', 1, 'Cabin-110', 'Desk-110A', 340.00),
(17, 'EMP017', 'Deepak Mishra', 'Quality Assurance', '9876543226', 'deepak.m@office.com', '$2y$12$ukLIgsN/crQJG1Rw5Zlv2OiqRKov4GFWxXWNzQx9MmBaHuQ4DbPgi', 'active', 5, 'Cabin-503', 'Desk-503D', 490.50),
(18, 'EMP018', 'Pooja Hegde', 'Public Relations', '9876543227', 'pooja.h@office.com', '$2y$12$ukLIgsN/crQJG1Rw5Zlv2OiqRKov4GFWxXWNzQx9MmBaHuQ4DbPgi', 'active', 2, 'Cabin-215', 'Desk-215A', 60.00),
(19, 'EMP019', 'Aditya Roy', 'Hardware Engineering', '9876543228', 'aditya.r@office.com', '$2y$12$ukLIgsN/crQJG1Rw5Zlv2OiqRKov4GFWxXWNzQx9MmBaHuQ4DbPgi', 'active', 4, 'Cabin-408', 'Desk-408C', 105.00),
(20, 'EMP020', 'Swati Reddy', 'Software Engineering', '9876543229', 'swati.r@office.com', '$2y$12$ukLIgsN/crQJG1Rw5Zlv2OiqRKov4GFWxXWNzQx9MmBaHuQ4DbPgi', 'active', 5, 'Cabin-501', 'Desk-501D', 80.00),
(21, 'EMP021', 'Karan Malhotra', 'Business Development', '9876543230', 'karan.m@office.com', '$2y$12$ukLIgsN/crQJG1Rw5Zlv2OiqRKov4GFWxXWNzQx9MmBaHuQ4DbPgi', 'active', 1, 'Cabin-101', 'Desk-101B', 430.00),
(22, 'EMP022', 'Shruti Hassan', 'Finance & Accounts', '9876543231', 'shruti.h@office.com', '$2y$12$ukLIgsN/crQJG1Rw5Zlv2OiqRKov4GFWxXWNzQx9MmBaHuQ4DbPgi', 'active', 3, 'Cabin-304', 'Desk-304D', 512.00),
(23, 'EMP023', 'Vivek Oberoi', 'IT Support', '9876543232', 'vivek.o@office.com', '$2y$12$ukLIgsN/crQJG1Rw5Zlv2OiqRKov4GFWxXWNzQx9MmBaHuQ4DbPgi', 'active', 4, 'Cabin-401', 'Desk-401C', 60.00),
(24, 'EMP024', 'Aishwarya Sen', 'Software Engineering', '9876543233', 'aishwarya.s@office.com', '$2y$12$ukLIgsN/crQJG1Rw5Zlv2OiqRKov4GFWxXWNzQx9MmBaHuQ4DbPgi', 'active', 5, 'Cabin-502', 'Desk-502A', 775.00),
(25, 'EMP025', 'Varun Dhawan', 'Design UI/UX', '9876543234', 'varun.d@office.com', '$2y$12$ukLIgsN/crQJG1Rw5Zlv2OiqRKov4GFWxXWNzQx9MmBaHuQ4DbPgi', 'active', 5, 'Cabin-512', 'Desk-512F', 92.00),
(26, 'EMP026', 'Kriti Sanon', 'Marketing & Sales', '9876543235', 'kriti.s@office.com', '$2y$12$ukLIgsN/crQJG1Rw5Zlv2OiqRKov4GFWxXWNzQx9MmBaHuQ4DbPgi', 'active', 1, 'Cabin-104', 'Desk-104B', 390.00),
(27, 'EMP027', 'Siddharth Roy', 'Operations', '9876543236', 'siddharth.r@office.com', '$2y$12$ukLIgsN/crQJG1Rw5Zlv2OiqRKov4GFWxXWNzQx9MmBaHuQ4DbPgi', 'active', 2, 'Cabin-201', 'Desk-201A', 220.00),
(28, 'EMP028', 'Jacqueline F', 'Legal & Compliance', '9876543237', 'jacqueline.f@office.com', '$2y$12$ukLIgsN/crQJG1Rw5Zlv2OiqRKov4GFWxXWNzQx9MmBaHuQ4DbPgi', 'active', 3, 'Cabin-312', 'Desk-312B', 150.00),
(29, 'EMP029', 'Ranbir Kapoor', 'Product Management', '9876543238', 'ranbir.k@office.com', '$2y$12$ukLIgsN/crQJG1Rw5Zlv2OiqRKov4GFWxXWNzQx9MmBaHuQ4DbPgi', 'active', 4, 'Cabin-415', 'Desk-415E', 810.00),
(30, 'EMP030', 'Alia Bhatt', 'Software Engineering', '9876543239', 'alia.b@office.com', '$2y$12$ukLIgsN/crQJG1Rw5Zlv2OiqRKov4GFWxXWNzQx9MmBaHuQ4DbPgi', 'active', 5, 'Cabin-502', 'Desk-502E', 450.00);

-- Seed 50 Foods (Indian food dataset with premium Unsplash images)
INSERT INTO `foods` (`id`, `name`, `description`, `ingredients`, `calories`, `spice_level`, `price`, `prep_time`, `category_id`, `veg_nonveg`, `is_popular`, `is_featured`, `stock_status`, `image_url`) VALUES
-- Category 1: Breakfast
(1, 'Masala Dosa', 'Crispy rice crepe filled with savory spiced potato mash, served with sambar and coconut chutney.', 'Rice, Lentils, Potato, Onion, Spices, Mustard Seeds', 350, 1, 99.00, 15, 7, 'veg', 1, 1, 'available', 'https://images.unsplash.com/photo-1668236543090-82eba5ee5976?w=600&auto=format&fit=crop&q=80'),
(2, 'Idli (2 Pcs)', 'Soft, fluffy steamed rice cakes made from fermented rice and lentil batter, served with chutneys.', 'Rice, Urad Dal, Salt, Fenugreek Seeds', 120, 0, 60.00, 10, 7, 'veg', 1, 0, 'available', 'https://images.unsplash.com/photo-1589301760014-d929f3979dbc?w=600&auto=format&fit=crop&q=80'),
(3, 'Medu Vada (2 Pcs)', 'Crispy, deep-fried lentil donuts flavored with peppercorns, curry leaves, and ginger.', 'Urad Dal, Peppercorn, Ginger, Curry Leaves, Green Chilli', 210, 1, 70.00, 12, 7, 'veg', 0, 0, 'available', 'https://images.unsplash.com/photo-1626132647523-66f5bf380027?w=600&auto=format&fit=crop&q=80'),
(4, 'Poori Bhaji', 'Puffed golden deep-fried whole wheat bread served with a mildly spiced dry potato curry.', 'Whole Wheat Flour, Potato, Ginger, Turmeric, Coriander', 380, 1, 90.00, 15, 1, 'veg', 0, 1, 'available', 'https://images.unsplash.com/photo-1645177625172-595e25c160ef?w=600&auto=format&fit=crop&q=80'),
(5, 'Aloo Paratha', 'Traditional North Indian flatbread stuffed with spiced potato mixture, served with butter and pickle.', 'Atta (Wheat Flour), Potato, Garam Masala, Amchur, Green Chilli', 310, 2, 80.00, 15, 8, 'veg', 1, 1, 'available', 'https://images.unsplash.com/photo-1601050690597-df056fb4ce78?w=600&auto=format&fit=crop&q=80'),
(6, 'Poha', 'Flattened rice flakes tempered with mustard seeds, turmeric, peanuts, and green peas.', 'Flattened Rice, Peanut, Onion, Curry Leaves, Mustard, Green Pea', 180, 1, 50.00, 8, 1, 'veg', 0, 0, 'available', 'https://images.unsplash.com/photo-1601050690597-df056fb4ce78?w=600&auto=format&fit=crop&q=80'),
(7, 'Chole Bhature', 'Spicy chickpea curry paired with deep-fried fluffy leavened bread, onions, and lemon.', 'Chickpeas, Maida (All Purpose Flour), Tomato, Onion, Ginger, Garlic, Chole Masala', 550, 3, 149.00, 20, 8, 'veg', 1, 1, 'available', 'https://images.unsplash.com/photo-1626132647523-66f5bf380027?w=600&auto=format&fit=crop&q=80'),
(8, 'Upma', 'Roasted semolina cooked to a thick porridge texture with vegetables, curry leaves, and mustard seeds.', 'Suji (Semolina), Onion, Mustard Seeds, Green Chilli, Carrot, Beans', 220, 1, 55.00, 10, 1, 'veg', 0, 0, 'available', 'https://images.unsplash.com/photo-1589301760014-d929f3979dbc?w=600&auto=format&fit=crop&q=80'),
(9, 'Bread Toast & Eggs', 'Double fried eggs or scrambled eggs served with buttered toasted brown bread slices.', 'Eggs, Bread, Butter, Salt, Pepper', 290, 0, 85.00, 8, 1, 'nonveg', 0, 0, 'available', 'https://images.unsplash.com/photo-1525351484163-7529414344d8?w=600&auto=format&fit=crop&q=80'),
(10, 'Veg Sandwich', 'Fresh club sandwich filled with sliced cucumber, tomato, potato, onion, and mint chutney.', 'Bread, Butter, Mint Chutney, Cucumber, Tomato, Potato', 180, 0, 75.00, 10, 10, 'veg', 0, 0, 'available', 'https://images.unsplash.com/photo-1509722747041-616f39b57569?w=600&auto=format&fit=crop&q=80'),

-- Category 2: Lunch
(11, 'Butter Chicken', 'Tender tandoori grilled chicken chunks simmered in a creamy, velvety tomato-butter gravy.', 'Chicken, Tomato Puree, Butter, Cream, Cashew Paste, Kasuri Methi', 480, 2, 280.00, 25, 8, 'nonveg', 1, 1, 'available', 'https://images.unsplash.com/photo-1603894584373-5ac82b2ae398?w=600&auto=format&fit=crop&q=80'),
(12, 'Paneer Butter Masala', 'Soft paneer cubes cooked in a rich, creamy, and sweet tomato-based gravy with butter.', 'Paneer, Butter, Cream, Tomato, Cashew, Spices', 420, 2, 220.00, 20, 8, 'veg', 1, 1, 'available', 'https://images.unsplash.com/photo-1631452180519-c014fe946bc7?w=600&auto=format&fit=crop&q=80'),
(13, 'Dal Makhani', 'Slow-cooked black lentils and kidney beans cooked overnight with butter, cream, and spices.', 'Black Urad Dal, Rajma, Butter, Cream, Tomato, Garlic', 340, 1, 160.00, 25, 8, 'veg', 1, 0, 'available', 'https://images.unsplash.com/photo-1546833999-b9f581a1996d?w=600&auto=format&fit=crop&q=80'),
(14, 'Chicken Biryani', 'Fragrant basmati rice layered with juicy spiced chicken, saffron, mint, and caramelized onions.', 'Basmati Rice, Chicken, Saffron, Yogurt, Mint, Rose Water, Spices', 650, 2, 290.00, 30, 2, 'nonveg', 1, 1, 'available', 'https://images.unsplash.com/photo-1633945274405-b6c8069047b0?w=600&auto=format&fit=crop&q=80'),
(15, 'Veg Biryani', 'Exotic garden fresh vegetables slow-cooked with basmati rice, mint, saffron, and biryani spices.', 'Basmati Rice, Carrot, Beans, Potato, Mint, Saffron, Yogurt, Spices', 480, 1, 199.00, 25, 2, 'veg', 0, 1, 'available', 'https://images.unsplash.com/photo-1563379091339-03b21ab4a4f8?w=600&auto=format&fit=crop&q=80'),
(16, 'Rajma Rice Meal', 'A comforting bowl of Punjabi style spiced red kidney bean curry served with steamed basmati rice.', 'Rajma, Onion, Tomato, Ginger, Garlic, Basmati Rice', 380, 2, 130.00, 15, 8, 'veg', 1, 0, 'available', 'https://images.unsplash.com/photo-1546833999-b9f581a1996d?w=600&auto=format&fit=crop&q=80'),
(17, 'Kadahi Paneer', 'Paneer cubes cooked with bell peppers, tomatoes, and freshly ground kadai spices.', 'Paneer, Capsicum, Onion, Tomato, Kadai Masala, Coriander', 390, 3, 210.00, 20, 8, 'veg', 0, 0, 'available', 'https://images.unsplash.com/photo-1631452180519-c014fe946bc7?w=600&auto=format&fit=crop&q=80'),
(18, 'Chicken Tikka Masala', 'Roasted marinated chicken chunks in a rich, creamy, and orange-colored spiced tomato sauce.', 'Chicken, Yogurt, Onion, Capsicum, Tomato, Cream, Tikka Masala', 460, 3, 260.00, 25, 8, 'nonveg', 0, 0, 'available', 'https://images.unsplash.com/photo-1565557623262-b51c2513a641?w=600&auto=format&fit=crop&q=80'),
(19, 'Yellow Dal Tadka', 'Simple, healthy yellow lentils cooked and tempered with cumin, garlic, and red dry chillies.', 'Arhar Dal, Garlic, Mustard Seeds, Red Chilli, Ghee', 220, 1, 120.00, 15, 10, 'veg', 1, 0, 'available', 'https://images.unsplash.com/photo-1546833999-b9f581a1996d?w=600&auto=format&fit=crop&q=80'),
(20, 'Premium Veg Thali', 'A full meal comprising Paneer subji, Dal, Mix Veg, Raita, Rice, 2 Butter Rotis, Salad, and Gulab Jamun.', 'Paneer, Dal, Wheat Flour, Yogurt, Rice, Sweets, Salad', 780, 2, 250.00, 25, 2, 'veg', 1, 1, 'available', 'https://images.unsplash.com/photo-1601050690597-df056fb4ce78?w=600&auto=format&fit=crop&q=80'),

-- Category 3: Dinner
(21, 'Egg Curry Meal', 'Two boiled eggs simmered in onion-tomato gravy, served with basmati rice or 2 chapatis.', 'Egg, Onion, Tomato, Ginger, Garlic, Cumin, Rice', 350, 2, 160.00, 20, 3, 'nonveg', 0, 0, 'available', 'https://images.unsplash.com/photo-1546833999-b9f581a1996d?w=600&auto=format&fit=crop&q=80'),
(22, 'Mutton Rogan Josh', 'Kashmiri delicacy slow-cooked mutton in a rich yogurt and saffron flavored red gravy.', 'Mutton, Yogurt, Fennel Powder, Ginger Powder, Kashmiri Mirch, Ghee', 590, 3, 380.00, 35, 3, 'nonveg', 1, 1, 'available', 'https://images.unsplash.com/photo-1565557623262-b51c2513a641?w=600&auto=format&fit=crop&q=80'),
(23, 'Bhindi Do Pyaza', 'Okra cooked with double amount of sauteed onions, ginger, and raw mango powder.', 'Bhindi (Okra), Onion, Amchur, Coriander, Turmeric', 210, 1, 130.00, 18, 3, 'veg', 0, 0, 'available', 'https://images.unsplash.com/photo-1601050690597-df056fb4ce78?w=600&auto=format&fit=crop&q=80'),
(24, 'Jeera Rice', 'A light dish of basmati rice tempered with cumin seeds and pure ghee.', 'Basmati Rice, Cumin Seeds, Ghee, Coriander Leaves', 260, 0, 99.00, 12, 3, 'veg', 1, 0, 'available', 'https://images.unsplash.com/photo-1563379091339-03b21ab4a4f8?w=600&auto=format&fit=crop&q=80'),
(25, 'Tandoori Roti', 'Whole wheat flatbread baked inside a traditional clay oven.', 'Atta (Wheat Flour), Ghee', 110, 0, 25.00, 8, 3, 'veg', 0, 0, 'available', 'https://images.unsplash.com/photo-1509722747041-616f39b57569?w=600&auto=format&fit=crop&q=80'),
(26, 'Butter Naan', 'Soft, fluffy refined flour flatbread brushed generously with melted butter.', 'Maida (All Purpose Flour), Yogurt, Yeast, Butter', 220, 0, 45.00, 10, 3, 'veg', 1, 0, 'available', 'https://images.unsplash.com/photo-1509722747041-616f39b57569?w=600&auto=format&fit=crop&q=80'),

-- Category 4: Snacks
(27, 'Samosa (2 Pcs)', 'Flaky pastry filled with spiced potato and green peas, served with mint and tamarind chutneys.', 'Maida, Potato, Green Peas, Ginger, Coriander Seeds', 260, 2, 40.00, 10, 4, 'veg', 1, 1, 'available', 'https://images.unsplash.com/photo-1601050690597-df056fb4ce78?w=600&auto=format&fit=crop&q=80'),
(28, 'Paneer Tikka Roll', 'Grilled paneer cubes marinated in tikka spices, wrapped in a paratha with mint sauce.', 'Paneer, Bell Pepper, Maida, Yogurt, Mint Sauce, Onion', 360, 2, 130.00, 15, 4, 'veg', 1, 0, 'available', 'https://images.unsplash.com/photo-1645177625172-595e25c160ef?w=600&auto=format&fit=crop&q=80'),
(29, 'Chicken Roll', 'Skewer-roasted chicken kebabs wrapped inside a soft paratha with sliced onions and egg layering.', 'Chicken, Paratha, Egg, Green Chilli, Onion, Chat Masala', 420, 2, 140.00, 15, 4, 'nonveg', 1, 0, 'available', 'https://images.unsplash.com/photo-1645177625172-595e25c160ef?w=600&auto=format&fit=crop&q=80'),
(30, 'Pav Bhaji', 'Thick vegetable mash cooked with spices and butter, served with soft toasted buns.', 'Potatoes, Green Peas, Cauliflower, Carrot, Butter, Pav (Bread)', 450, 2, 110.00, 15, 4, 'veg', 1, 1, 'available', 'https://images.unsplash.com/photo-1626132647523-66f5bf380027?w=600&auto=format&fit=crop&q=80'),
(31, 'Veg Hakka Noodles', 'Stir-fried noodles with crisp julienned vegetables and light soy-sauce dressing.', 'Noodles, Cabbage, Carrot, Capsicum, Onion, Soy Sauce', 320, 1, 120.00, 15, 9, 'veg', 1, 0, 'available', 'https://images.unsplash.com/photo-1585032226651-759b368d7246?w=600&auto=format&fit=crop&q=80'),
(32, 'Chicken Fried Rice', 'Stir-fried basmati rice cooked with eggs, chicken bits, spring onions, and oriental sauces.', 'Rice, Chicken, Egg, Spring Onion, Soy Sauce, Vinegar', 440, 1, 150.00, 15, 9, 'nonveg', 0, 0, 'available', 'https://images.unsplash.com/photo-1512058564366-18510be2db19?w=600&auto=format&fit=crop&q=80'),

-- Category 5: Beverages
(33, 'Masala Chai', 'Hot brewed milk tea infused with aromatic ginger, cardamom, cloves, and black pepper.', 'Tea Leaves, Milk, Ginger, Cardamom, Clove, Cinnamon', 90, 0, 25.00, 5, 5, 'veg', 1, 1, 'available', 'https://images.unsplash.com/photo-1576092768241-dec231879fc3?w=600&auto=format&fit=crop&q=80'),
(34, 'Filter Coffee', 'Traditional South Indian frothy milk coffee brewed in a brass filter.', 'Coffee Beans, Milk, Chicory', 110, 0, 30.00, 8, 5, 'veg', 1, 0, 'available', 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?w=600&auto=format&fit=crop&q=80'),
(35, 'Sweet Lassi', 'Chilled creamy yogurt beverage sweetened with sugar and flavored with cardamom.', 'Yogurt, Sugar, Cardamom, Pistachio', 220, 0, 70.00, 8, 5, 'veg', 1, 0, 'available', 'https://images.unsplash.com/photo-1576092768241-dec231879fc3?w=600&auto=format&fit=crop&q=80'),
(36, 'Cold Coffee', 'Thick, creamy blended milkshake made with espresso shots and vanilla ice cream.', 'Milk, Coffee Powder, Ice Cream, Sugar, Chocolate Syrup', 280, 0, 99.00, 10, 5, 'veg', 1, 1, 'available', 'https://images.unsplash.com/photo-1541167760496-1628856ab772?w=600&auto=format&fit=crop&q=80'),
(37, 'Fresh Lime Soda', 'Refreshing soda drink with fresh lime juice, salt or sugar as requested.', 'Lime juice, Soda water, Sugar, Salt', 70, 0, 45.00, 5, 5, 'veg', 0, 0, 'available', 'https://images.unsplash.com/photo-1513558161293-cdaf765ed2fd?w=600&auto=format&fit=crop&q=80'),

-- Category 6: Desserts
(38, 'Gulab Jamun (2 Pcs)', 'Soft milk-solid dumplings fried and soaked in cardamom-infused sugar syrup.', 'Khoya, Maida, Sugar, Rose Water, Cardamom', 320, 0, 60.00, 5, 6, 'veg', 1, 1, 'available', 'https://images.unsplash.com/photo-1589301760014-d929f3979dbc?w=600&auto=format&fit=crop&q=80'),
(39, 'Rasgulla (2 Pcs)', 'Spongy soft white cottage cheese balls soaked in a clear sweet sugar syrup.', 'Chhena (Paneer), Sugar, Water', 240, 0, 60.00, 5, 6, 'veg', 0, 0, 'available', 'https://images.unsplash.com/photo-1589301760014-d929f3979dbc?w=600&auto=format&fit=crop&q=80'),
(40, 'Kulfi', 'Traditional Indian rich condensed milk ice cream flavored with saffron and almonds.', 'Milk, Sugar, Saffron, Almonds, Pistachio', 190, 0, 80.00, 8, 6, 'veg', 1, 1, 'available', 'https://images.unsplash.com/photo-1572490122747-3968b75cc699?w=600&auto=format&fit=crop&q=80'),

-- Category 10: Healthy Choices
(41, 'Quinoa Veg Salad', 'Nutritious quinoa tossed with cherry tomatoes, cucumbers, bell peppers, and olive-lemon dressing.', 'Quinoa, Cherry Tomatoes, Cucumber, Olive Oil, Lemon, Parsley', 190, 0, 180.00, 12, 10, 'veg', 1, 1, 'available', 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=600&auto=format&fit=crop&q=80'),
(42, 'Paneer Tikka Salad', 'Tandoori paneer skewers tossed with capsicum, onions, salad greens, and mint vinaigrette.', 'Paneer, Lettuce, Onion, Mint, Vinegar, Chat Masala', 260, 1, 199.00, 15, 10, 'veg', 0, 1, 'available', 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=600&auto=format&fit=crop&q=80'),
(43, 'Oats Porridge', 'Warm breakfast rolled oats cooked in low-fat milk, topped with honey, banana, and almonds.', 'Oats, Low Fat Milk, Honey, Banana, Almonds', 210, 0, 95.00, 10, 10, 'veg', 0, 0, 'available', 'https://images.unsplash.com/photo-1525351484163-7529414344d8?w=600&auto=format&fit=crop&q=80'),

-- Category 7: South Indian
(44, 'Rava Khichdi', 'Suji cooked with vegetables and seasoned with ghee, cashew nuts, and curry leaves.', 'Rava, Cashew, Carrot, Ghee, Mustard, Curry Leaves', 290, 1, 80.00, 12, 7, 'veg', 0, 0, 'available', 'https://images.unsplash.com/photo-1589301760014-d929f3979dbc?w=600&auto=format&fit=crop&q=80'),
(45, 'Onion Uttapam', 'Thick rice-lentil pancake topped with finely chopped onions, green chillies, and fresh coriander.', 'Rice, Lentils, Onion, Green Chilli, Ghee', 320, 1, 110.00, 15, 7, 'veg', 0, 0, 'available', 'https://images.unsplash.com/photo-1668236543090-82eba5ee5976?w=600&auto=format&fit=crop&q=80'),

-- Category 8: North Indian
(46, 'Malai Kofta', 'Potato and paneer balls (koftas) cooked in a sweet, rich, and creamy cashew gravy.', 'Potato, Paneer, Cashew, Cream, Tomato, Melon Seeds', 450, 1, 230.00, 22, 8, 'veg', 1, 0, 'available', 'https://images.unsplash.com/photo-1631452180519-c014fe946bc7?w=600&auto=format&fit=crop&q=80'),
(47, 'Aloo Gobi', 'Simple dry subji made of potatoes and cauliflower florets seasoned with turmeric and cumin.', 'Potato, Cauliflower, Ginger, Turmeric, Cumin', 190, 1, 110.00, 15, 8, 'veg', 0, 0, 'available', 'https://images.unsplash.com/photo-1601050690597-df056fb4ce78?w=600&auto=format&fit=crop&q=80'),

-- Category 9: Chinese
(48, 'Veg Manchurian', 'Deep-fried vegetable balls cooked in a tangy, spicy, and glossy soy-chilli gravy.', 'Cabbage, Carrot, Cornflour, Garlic, Soy Sauce, Spring Onion', 280, 2, 140.00, 15, 9, 'veg', 1, 1, 'available', 'https://images.unsplash.com/photo-1585032226651-759b368d7246?w=600&auto=format&fit=crop&q=80'),
(49, 'Chilli Chicken', 'Batter-fried chicken chunks tossed in a spicy sauce with green bell peppers, garlic, and onions.', 'Chicken, Bell Pepper, Onion, Garlic, Green Chilli, Soy Sauce', 390, 3, 220.00, 20, 9, 'nonveg', 1, 1, 'available', 'https://images.unsplash.com/photo-1512058564366-18510be2db19?w=600&auto=format&fit=crop&q=80'),
(50, 'Spring Rolls (4 Pcs)', 'Golden-fried pastry skins filled with sauteed cabbage, carrots, and glass noodles.', 'Maida, Cabbage, Carrot, Onion, Garlic, White Pepper', 210, 1, 99.00, 12, 9, 'veg', 0, 0, 'available', 'https://images.unsplash.com/photo-1585032226651-759b368d7246?w=600&auto=format&fit=crop&q=80');


-- Seed Inventory
INSERT INTO `inventory` (`id`, `food_id`, `current_stock`, `status`) VALUES
(1, 1, 50, 'available'),
(2, 2, 80, 'available'),
(3, 3, 60, 'available'),
(4, 4, 30, 'available'),
(5, 5, 45, 'available'),
(6, 6, 40, 'available'),
(7, 7, 25, 'available'),
(8, 8, 35, 'available'),
(9, 9, 30, 'available'),
(10, 10, 50, 'available'),
(11, 11, 40, 'available'),
(12, 12, 50, 'available'),
(13, 13, 60, 'available'),
(14, 14, 15, 'available'),
(15, 15, 20, 'available'),
(16, 16, 45, 'available'),
(17, 17, 30, 'available'),
(18, 18, 25, 'available'),
(19, 19, 60, 'available'),
(20, 20, 15, 'available'),
(21, 21, 25, 'available'),
(22, 22, 10, 'available'),
(23, 23, 30, 'available'),
(24, 24, 80, 'available'),
(25, 25, 150, 'available'),
(26, 26, 100, 'available'),
(27, 27, 70, 'available'),
(28, 28, 40, 'available'),
(29, 29, 30, 'available'),
(30, 30, 20, 'available'),
(31, 31, 35, 'available'),
(32, 32, 25, 'available'),
(33, 33, 120, 'available'),
(34, 34, 100, 'available'),
(35, 35, 50, 'available'),
(36, 36, 45, 'available'),
(37, 37, 60, 'available'),
(38, 38, 80, 'available'),
(39, 39, 60, 'available'),
(40, 40, 40, 'available'),
(41, 41, 25, 'available'),
(42, 42, 20, 'available'),
(43, 43, 30, 'available'),
(44, 44, 40, 'available'),
(45, 45, 30, 'available'),
(46, 46, 25, 'available'),
(47, 47, 40, 'available'),
(48, 48, 30, 'available'),
(49, 49, 25, 'available'),
(50, 50, 40, 'available');

-- Seed settings
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('company_name', 'PixelTech Corporate Canteen'),
('gst_rate', '5'),
('delivery_charge', '15.00'),
('canteen_status', 'open'),
('canteen_address', 'Floor 4, Block-A, Tech Towers, Sector 62, Noida'),
('order_timings', '08:00 AM - 09:00 PM'),
('lunch_timings', '12:30 PM - 03:00 PM'),
('theme_primary', '#16A34A'),
('theme_accent', '#F59E0B');

-- Seed 100 Demo Orders
-- To make statistical charts look wonderful in the admin panel, we will seed orders across the last 7 days.
-- Let's define order states (received, confirmed, preparing, ready, out_of_delivery, delivered, cancelled)
-- Let's construct a loops or manually insert some orders.
-- Order dates: 2026-07-16, 2026-07-17, 2026-07-18, 2026-07-19, 2026-07-20, 2026-07-21, 2026-07-22

-- We'll write direct inserts.
-- Order 1 to 20: Delivered
INSERT INTO `orders` (`id`, `order_number`, `employee_id`, `department`, `floor`, `cabin`, `desk_number`, `delivery_date`, `delivery_time`, `special_instructions`, `subtotal`, `gst`, `grand_total`, `status`, `payment_method`, `created_at`) VALUES
(1, 'ORD-260716-101', 1, 'IT Infrastructure', 4, 'Cabin-402', 'Desk-402A', '2026-07-16', '13:00:00', 'No onions', 159.00, 7.95, 181.95, 'delivered', 'cash_on_delivery', '2026-07-16 12:45:00'),
(2, 'ORD-260716-102', 2, 'Human Resources', 2, 'Cabin-210', 'Desk-210C', '2026-07-16', '13:30:00', NULL, 280.00, 14.00, 309.00, 'delivered', 'cash_on_delivery', '2026-07-16 13:05:00'),
(3, 'ORD-260716-103', 3, 'Software Engineering', 5, 'Cabin-501', 'Desk-501F', '2026-07-16', '14:00:00', 'Spicy', 220.00, 11.00, 246.00, 'delivered', 'cash_on_delivery', '2026-07-16 13:30:00'),
(4, 'ORD-260716-104', 4, 'Finance & Accounts', 3, 'Cabin-305', 'Desk-305B', '2026-07-16', '13:15:00', NULL, 120.00, 6.00, 141.00, 'delivered', 'cash_on_delivery', '2026-07-16 12:50:00'),
(5, 'ORD-260717-101', 5, 'Marketing & Sales', 1, 'Cabin-103', 'Desk-103D', '2026-07-17', '09:00:00', NULL, 99.00, 4.95, 118.95, 'delivered', 'cash_on_delivery', '2026-07-17 08:35:00'),
(6, 'ORD-260717-102', 6, 'Design UI/UX', 5, 'Cabin-512', 'Desk-512B', '2026-07-17', '13:00:00', 'Extra butter', 250.00, 12.50, 277.50, 'delivered', 'cash_on_delivery', '2026-07-17 12:40:00'),
(7, 'ORD-260717-103', 7, 'Operations', 2, 'Cabin-202', 'Desk-202E', '2026-07-17', '13:15:00', NULL, 130.00, 6.50, 151.50, 'delivered', 'cash_on_delivery', '2026-07-17 12:50:00'),
(8, 'ORD-260717-104', 8, 'Legal & Compliance', 3, 'Cabin-312', 'Desk-312A', '2026-07-17', '16:30:00', NULL, 110.00, 5.50, 130.50, 'delivered', 'cash_on_delivery', '2026-07-17 16:10:00'),
(9, 'ORD-260718-101', 9, 'Software Engineering', 5, 'Cabin-504', 'Desk-504C', '2026-07-18', '13:00:00', 'No chillies', 199.00, 9.95, 223.95, 'delivered', 'cash_on_delivery', '2026-07-18 12:35:00'),
(10, 'ORD-260718-102', 10, 'Human Resources', 2, 'Cabin-210', 'Desk-210B', '2026-07-18', '13:30:00', NULL, 300.00, 15.00, 330.00, 'delivered', 'cash_on_delivery', '2026-07-18 13:10:00'),
(11, 'ORD-260718-103', 11, 'IT Support', 4, 'Cabin-401', 'Desk-401B', '2026-07-18', '20:00:00', NULL, 280.00, 14.00, 309.00, 'delivered', 'cash_on_delivery', '2026-07-18 19:40:00'),
(12, 'ORD-260719-101', 12, 'Finance & Accounts', 3, 'Cabin-304', 'Desk-304A', '2026-07-19', '13:00:00', NULL, 410.00, 20.50, 445.50, 'delivered', 'cash_on_delivery', '2026-07-19 12:40:00'),
(13, 'ORD-260719-102', 13, 'Product Management', 4, 'Cabin-415', 'Desk-415D', '2026-07-19', '13:30:00', 'Eco friendly packing', 199.00, 9.95, 223.95, 'delivered', 'cash_on_delivery', '2026-07-19 13:05:00'),
(14, 'ORD-260719-103', 14, 'Data Science', 5, 'Cabin-508', 'Desk-508A', '2026-07-19', '13:15:00', NULL, 150.00, 7.50, 172.50, 'delivered', 'cash_on_delivery', '2026-07-19 12:55:00'),
(15, 'ORD-260720-101', 15, 'Marketing & Sales', 1, 'Cabin-105', 'Desk-105C', '2026-07-20', '09:30:00', 'Hot', 85.00, 4.25, 104.25, 'delivered', 'cash_on_delivery', '2026-07-20 09:10:00'),
(16, 'ORD-260720-102', 16, 'Customer Success', 1, 'Cabin-110', 'Desk-110A', '2026-07-20', '13:00:00', NULL, 220.00, 11.00, 246.00, 'delivered', 'cash_on_delivery', '2026-07-20 12:35:00'),
(17, 'ORD-260720-103', 17, 'Quality Assurance', 5, 'Cabin-503', 'Desk-503D', '2026-07-20', '13:30:00', NULL, 250.00, 12.50, 277.50, 'delivered', 'cash_on_delivery', '2026-07-20 13:12:00'),
(18, 'ORD-260721-101', 18, 'Public Relations', 2, 'Cabin-215', 'Desk-215A', '2026-07-21', '13:00:00', NULL, 220.00, 11.00, 246.00, 'delivered', 'cash_on_delivery', '2026-07-21 12:40:00'),
(19, 'ORD-260721-102', 19, 'Hardware Engineering', 4, 'Cabin-408', 'Desk-408C', '2026-07-21', '13:15:00', NULL, 180.00, 9.00, 204.00, 'delivered', 'cash_on_delivery', '2026-07-21 12:55:00'),
(20, 'ORD-260721-103', 20, 'Software Engineering', 5, 'Cabin-501', 'Desk-501D', '2026-07-21', '13:30:00', 'Spicy', 260.00, 13.00, 288.00, 'delivered', 'cash_on_delivery', '2026-07-21 13:10:00');

-- Let's insert corresponding order items for 1 to 20
INSERT INTO `order_items` (`order_id`, `food_id`, `food_name`, `price`, `quantity`, `special_notes`) VALUES
(1, 1, 'Masala Dosa', 99.00, 1, 'No onion'),
(1, 2, 'Idli (2 Pcs)', 60.00, 1, NULL),
(2, 11, 'Butter Chicken', 280.00, 1, NULL),
(3, 12, 'Paneer Butter Masala', 220.00, 1, 'Spicy'),
(4, 19, 'Yellow Dal Tadka', 120.00, 1, NULL),
(5, 1, 'Masala Dosa', 99.00, 1, NULL),
(6, 20, 'Premium Veg Thali', 250.00, 1, 'Extra butter'),
(7, 23, 'Bhindi Do Pyaza', 130.00, 1, NULL),
(8, 30, 'Pav Bhaji', 110.00, 1, NULL),
(9, 15, 'Veg Biryani', 199.00, 1, 'No chillies'),
(10, 14, 'Chicken Biryani', 290.00, 1, NULL),
(10, 34, 'Filter Coffee', 30.00, 1, NULL),
(11, 11, 'Butter Chicken', 280.00, 1, NULL),
(12, 41, 'Quinoa Veg Salad', 180.00, 1, NULL),
(12, 46, 'Malai Kofta', 230.00, 1, NULL),
(13, 15, 'Veg Biryani', 199.00, 1, 'Eco friendly packing'),
(14, 32, 'Chicken Fried Rice', 150.00, 1, NULL),
(15, 9, 'Bread Toast & Eggs', 85.00, 1, 'Hot'),
(16, 12, 'Paneer Butter Masala', 220.00, 1, NULL),
(17, 20, 'Premium Veg Thali', 250.00, 1, NULL),
(18, 12, 'Paneer Butter Masala', 220.00, 1, NULL),
(19, 41, 'Quinoa Veg Salad', 180.00, 1, NULL),
(20, 18, 'Chicken Tikka Masala', 260.00, 1, 'Spicy');

-- 5 live orders for today (received, confirmed, preparing, ready, out_of_delivery)
INSERT INTO `orders` (`id`, `order_number`, `employee_id`, `department`, `floor`, `cabin`, `desk_number`, `delivery_date`, `delivery_time`, `special_instructions`, `subtotal`, `gst`, `grand_total`, `status`, `payment_method`, `created_at`) VALUES
(21, 'ORD-260722-101', 1, 'IT Infrastructure', 4, 'Cabin-402', 'Desk-402A', CURDATE(), '13:00:00', NULL, 159.00, 7.95, 181.95, 'received', 'cash_on_delivery', NOW() - INTERVAL 1 HOUR),
(22, 'ORD-260722-102', 2, 'Human Resources', 2, 'Cabin-210', 'Desk-210C', CURDATE(), '13:15:00', 'Send cutlery', 280.00, 14.00, 309.00, 'confirmed', 'cash_on_delivery', NOW() - INTERVAL 45 MINUTE),
(23, 'ORD-260722-103', 3, 'Software Engineering', 5, 'Cabin-501', 'Desk-501F', CURDATE(), '13:30:00', NULL, 220.00, 11.00, 246.00, 'preparing', 'cash_on_delivery', NOW() - INTERVAL 30 MINUTE),
(24, 'ORD-260722-104', 4, 'Finance & Accounts', 3, 'Cabin-305', 'Desk-305B', CURDATE(), '13:45:00', NULL, 149.00, 7.45, 171.45, 'ready', 'cash_on_delivery', NOW() - INTERVAL 20 MINUTE),
(25, 'ORD-260722-105', 5, 'Marketing & Sales', 1, 'Cabin-103', 'Desk-103D', CURDATE(), '14:00:00', 'Call when downstairs', 99.00, 4.95, 118.95, 'out_of_delivery', 'cash_on_delivery', NOW() - INTERVAL 10 MINUTE);

INSERT INTO `order_items` (`order_id`, `food_id`, `food_name`, `price`, `quantity`, `special_notes`) VALUES
(21, 1, 'Masala Dosa', 99.00, 1, NULL),
(21, 2, 'Idli (2 Pcs)', 60.00, 1, NULL),
(22, 11, 'Butter Chicken', 280.00, 1, NULL),
(23, 12, 'Paneer Butter Masala', 220.00, 1, NULL),
(24, 7, 'Chole Bhature', 149.00, 1, NULL),
(25, 1, 'Masala Dosa', 99.00, 1, NULL);

-- Let's put settings and activity log
INSERT INTO `activity_logs` (`user_id`, `user_role`, `action`, `details`) VALUES
(1, 'admin', 'Database Setup', 'System database initialized and seeded with base data.');
