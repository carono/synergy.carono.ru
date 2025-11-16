-- Таблица сотрудников (справочник)
CREATE TABLE employees (
    employee_id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL COMMENT 'Имя сотрудника',
    last_name VARCHAR(50) NOT NULL COMMENT 'Фамилия сотрудника',
    position VARCHAR(50) NOT NULL COMMENT 'Должность',
    phone VARCHAR(20) COMMENT 'Телефон сотрудника'
);

-- Таблица клиентов (справочник)
CREATE TABLE clients (
    client_id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL COMMENT 'Имя клиента',
    last_name VARCHAR(50) NOT NULL COMMENT 'Фамилия клиента',
    passport_series VARCHAR(10) NOT NULL COMMENT 'Серия паспорта',
    passport_number VARCHAR(10) NOT NULL COMMENT 'Номер паспорта',
    phone VARCHAR(20) COMMENT 'Телефон клиента'
);

-- Таблица туров (справочник)
CREATE TABLE tours (
    tour_id INT PRIMARY KEY AUTO_INCREMENT,
    tour_name VARCHAR(100) NOT NULL COMMENT 'Название тура',
    country VARCHAR(50) NOT NULL COMMENT 'Страна назначения',
    duration_days INT NOT NULL COMMENT 'Продолжительность тура (дни)',
    base_price DECIMAL(10,2) NOT NULL COMMENT 'Базовая стоимость тура'
);

-- Таблица услуг (справочник)
CREATE TABLE services (
    service_id INT PRIMARY KEY AUTO_INCREMENT,
    service_name VARCHAR(100) NOT NULL COMMENT 'Название услуги',
    description TEXT COMMENT 'Описание услуги',
    price DECIMAL(10,2) NOT NULL COMMENT 'Стоимость услуги'
);

-- Таблица заказов (основная таблица переменной информации)
CREATE TABLE orders (
    order_id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL COMMENT 'ID клиента',
    employee_id INT NOT NULL COMMENT 'ID сотрудника',
    tour_id INT NOT NULL COMMENT 'ID выбранного тура',
    order_date DATE NOT NULL COMMENT 'Дата оформления заказа',
    total_price DECIMAL(10,2) NOT NULL COMMENT 'Итоговая стоимость',
    FOREIGN KEY (client_id) REFERENCES clients(client_id),
    FOREIGN KEY (employee_id) REFERENCES employees(employee_id),
    FOREIGN KEY (tour_id) REFERENCES tours(tour_id)
);

-- Сводная таблица для связи заказов и дополнительных услуг
CREATE TABLE order_services (
    order_id INT NOT NULL COMMENT 'ID заказа',
    service_id INT NOT NULL COMMENT 'ID услуги',
    quantity INT DEFAULT 1 COMMENT 'Количество услуг',
    PRIMARY KEY (order_id, service_id),
    FOREIGN KEY (order_id) REFERENCES orders(order_id),
    FOREIGN KEY (service_id) REFERENCES services(service_id)
);