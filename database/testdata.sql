USE grooming_store;

INSERT INTO users
    (full_name, email, phone_number, password_hash, role)
VALUES
    ('Admin User', 'admin@gmail.com', '9800000001',
     'admin123', 'admin'),

    ('Aarav Sharma', 'aarav@example.com', '9800000002',
     'aarav123', 'customer'),

    ('Saanvi Thapa', 'saanvi@example.com', '9800000003',
     'saanvi123', 'customer');


INSERT INTO user_addresses
    (user_id, address_line, city, province, postal_code, is_default)
VALUES
    (2, 'Baneshwor', 'Kathmandu', 'Bagmati Province', '44600', 1),
    (3, 'New Road', 'Kathmandu', 'Bagmati Province', '44600', 1),
    (2, 'Shanti Marga', 'Damak', 'Koshi Province', '57217', 0);

INSERT INTO products
    (title, description, price, category, photo, stock_quantity, status)
VALUES
    ('Leather Wallet',
     'Premium full-grain leather wallet.',
     5200.00,
     'wallets',
     'uploads\products\leather-wallet.jpg',
     20,
     'active'),

    ('Santal Royal Eau de Parfum',
     'Luxury fragrance with rich woody notes.',
     14500.00,
     'fragrances',
     'uploads\products\santal-royal.jpg',
     15,
     'active'),

    ('Chronograph Minimalist Watch',
     'Elegant minimalist chronograph watch.',
     28500.00,
     'watches',
     'uploads\products\chronograph-watch.jpg',
     5,
     'active');


INSERT INTO orders
    (user_id, address_id, total_amount, order_status, payment_status)
VALUES
    (2, 1, 19700.00, 'Delivered', 'Completed'),
    (3, 2, 14500.00, 'On the Way', 'Completed'),
    (2, 1, 5200.00, 'Created', 'Pending');



INSERT INTO order_items
    (order_id, product_id, quantity, price)
VALUES
    (1, 1, 1, 5200.00),
    (1, 2, 1, 14500.00),
    (2, 2, 1, 14500.00),
    (3, 1, 1, 5200.00);


INSERT INTO payments
    (order_id, amount, payment_method, status)
VALUES
    (1, 19700.00, 'Card', 'Completed'),
    (2, 14500.00, 'eSewa', 'Completed'),
    (3, 5200.00, 'Cash on Delivery', 'Pending');

