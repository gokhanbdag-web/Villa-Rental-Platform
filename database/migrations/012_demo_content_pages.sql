-- English demonstration pages for a fresh installation.
INSERT IGNORE INTO cms_pages (language_code,title,slug,content,status,meta_title,meta_description) VALUES
('en','About Us','about-us','VillaStay helps travelers discover private villas and memorable stays. Browse detailed listings, compare amenities and request a reservation with confidence. This sample page should be updated with your company''s details before launch.','published','About Us | VillaStay','About Us and information from VillaStay.'),
('en','Contact Us','contact','Questions about a villa or a reservation? Contact our support team at info@example.com. Replace this sample email and your business details before publishing.','published','Contact Us | VillaStay','Contact Us and information from VillaStay.'),
('en','How It Works','how-it-works','Explore available properties, compare amenities and dates, submit a booking request and follow its status from your account. Payment options depend on the property''s booking terms.','published','How It Works | VillaStay','How It Works and information from VillaStay.'),
('en','Frequently Asked Questions','faq','How do I book? Choose a property and submit a request. When is payment due? Review the booking terms and payment instructions provided at checkout. How do I get help? Use the contact page.','published','Frequently Asked Questions | VillaStay','Frequently Asked Questions and information from VillaStay.'),
('en','Rental Terms','rental-terms','Review the property description, booking conditions, check-in instructions and cancellation policy before confirming a reservation. Replace this demonstration text with your official terms.','published','Rental Terms | VillaStay','Rental Terms and information from VillaStay.'),
('en','Privacy Policy','privacy-policy','We respect your privacy. Describe your actual personal data processing, retention, cookie usage and contact details here before launching the website.','published','Privacy Policy | VillaStay','Privacy Policy and information from VillaStay.'),
('en','Cancellation and Refunds','cancellation-and-refunds','Cancellation and refund eligibility depend on the booking policy displayed for each property. Contact support for assistance. Replace this sample page with your actual policy.','published','Cancellation and Refunds | VillaStay','Cancellation and Refunds and information from VillaStay.'),
('en','For Property Owners','for-property-owners','List your property, manage availability and respond to booking requests. On marketplace installations, property owners can apply for an account and manage their own listings after approval.','published','For Property Owners | VillaStay','For Property Owners and information from VillaStay.'),
('en','Safe Booking','safe-booking','Review listing details and house rules before booking. Use the official checkout process, keep your booking reference and never share passwords or payment credentials in messages.','published','Safe Booking | VillaStay','Safe Booking and information from VillaStay.'),
('en','Help Center','help-center','Need assistance with bookings, listings or your account? Contact support at support@example.com. Replace this sample contact address before launch.','published','Help Center | VillaStay','Help Center and information from VillaStay.');

INSERT IGNORE INTO navigation_items (location,language_code,label,url,target,sort_order,is_active) VALUES
('header','en','About Us','/page/about-us','_self',10,1),
('header','en','Contact Us','/page/contact','_self',20,1),
('header','en','How It Works','/page/how-it-works','_self',30,1),
('footer','en','Frequently Asked Questions','/page/faq','_self',40,1),
('footer','en','Rental Terms','/page/rental-terms','_self',50,1),
('footer','en','Privacy Policy','/page/privacy-policy','_self',60,1),
('footer','en','Cancellation and Refunds','/page/cancellation-and-refunds','_self',70,1),
('footer','en','For Property Owners','/page/for-property-owners','_self',80,1),
('footer','en','Safe Booking','/page/safe-booking','_self',90,1),
('footer','en','Help Center','/page/help-center','_self',100,1);
