USE hotel_db;

INSERT INTO rooms (name, type, description, price, capacity, image, is_available) VALUES
('Deluxe Ocean View', 'deluxe', 'Spacious room with panoramic ocean views, king-size bed, and private balcony.', 250.00, 2, 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=800&q=80', 1),
('Standard Double', 'standard', 'Comfortable room with two double beds, city view, and work desk.', 150.00, 2, 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=800&q=80', 1),
('Family Suite', 'suite', 'Large suite with separate living area, kitchenette, and capacity for 4 guests.', 350.00, 4, 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=800&q=80', 1),
('Single Economy', 'single', 'Cozy single room with twin bed, ideal for solo travelers.', 90.00, 1, NULL, 1),
('Premium Penthouse', 'deluxe', 'Top-floor penthouse with 360-degree views, jacuzzi, and butler service.', 500.00, 2, 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=800&q=80', 1);

INSERT INTO services (name, description, price, image) VALUES
('Breakfast Buffet', 'Daily morning buffet with international cuisine', 25.00, 'https://images.unsplash.com/photo-1533777857889-4be7c70b33f7?w=800&q=80'),
('Airport Transfer', 'One-way transfer in luxury vehicle', 40.00, 'https://images.unsplash.com/photo-1449965408869-eaa3f722e40d?w=800&q=80'),
('Spa Access', 'Full-day access to spa and wellness center', 60.00, 'https://images.unsplash.com/photo-1571896349842-33c89424de2d?w=800&q=80'),
('Laundry Service', 'Same-day laundry and dry cleaning', 20.00, NULL),
('Room Service', '24/7 in-room dining from our restaurant menu', 15.00, NULL);

-- Hero image (homepage banner): https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=1400&q=85
-- Hotel exterior / About page:  https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1400&q=85
