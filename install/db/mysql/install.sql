CREATE TABLE `mixplat_payment` (
  `id` varchar(36)  NOT NULL,
  `order_id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `status` varchar(20)  NOT NULL,
  `status_extended` varchar(30)  NOT NULL,
  `date` datetime NOT NULL,
  `extra` text,
  `amount` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`)
);