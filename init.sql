CREATE DATABASE chreboot;

use chreboot;

CREATE TABLE orders (
	id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY, 
	symbol VARCHAR(10) NOT NULL,
	buy DOUBLE NOT NULL,
	buydate DATETIME NOT NULL,
	sell DOUBLE,
    selldate DATETIME,
    prozent FLOAT	
);