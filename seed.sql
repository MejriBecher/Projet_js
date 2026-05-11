USE hotel_db;

INSERT INTO rooms (name, type, description, price, capacity, is_available) VALUES
('Deluxe Ocean View', 'deluxe', 'Spacious room with panoramic ocean views, king-size bed, and private balcony.', 250.00, 2, 1),
('Standard Double', 'standard', 'Comfortable room with two double beds, city view, and work desk.', 150.00, 2, 1),
('Family Suite', 'suite', 'Large suite with separate living area, kitchenette, and capacity for 4 guests.', 350.00, 4, 1),
('Single Economy', 'single', 'Cozy single room with twin bed, ideal for solo travelers.', 90.00, 1, 1),
('Premium Penthouse', 'deluxe', 'Top-floor penthouse with 360-degree views, jacuzzi, and butler service.', 500.00, 2, 1);

INSERT INTO services (name, description, price) VALUES
('Breakfast Buffet', 'Daily morning buffet with international cuisine', 25.00),
('Airport Transfer', 'One-way transfer in luxury vehicle', 40.00),
('Spa Access', 'Full-day access to spa and wellness center', 60.00),
('Laundry Service', 'Same-day laundry and dry cleaning', 20.00),
('Room Service', '24/7 in-room dining from our restaurant menu', 15.00);
