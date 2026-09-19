# ParkNexa – Smart Parking Reservation System

## 1. Project Overview

**ParkNexa** is a responsive, database-driven Smart Parking Reservation System developed as a Web Development Project.

The application allows users to:

- Register and log in securely
- Search available parking locations
- Filter parking by city and maximum hourly rate
- View parking details and slot availability
- Select an available parking slot
- Enter vehicle and reservation details
- Confirm a parking reservation
- View a generated booking/ticket page
- View booking history
- Cancel eligible reservations

The system also provides an **Admin module** for managing parking locations, parking slots, reservations, and users through CRUD-based interfaces.

The current demo uses a **local artificial map preview**, so the project does not require Google Maps billing for normal demonstration. A Google Maps version can be enabled later when billing/API access is available.

---

## 2. Project Objective

The objective of this project is to design and develop a complete web-based parking reservation application that demonstrates:

- HTML5
- CSS3
- JavaScript
- Responsive Web Design
- Forms and validation
- Authentication
- CRUD operations
- Search and filtering
- Data management
- Database integration
- User and Admin workflows

These features align with the Project 2 Web-Based Application Development requirements.

---

## 3. Problem Statement

Finding a suitable parking space can take time, especially in busy areas. A user may need to search multiple locations before finding an available slot.

ParkNexa provides a centralized web application where users can search parking locations, compare rates, check availability, reserve a slot before arrival, and manage their reservations.

---

## 4. Major Users

### User / Customer

A normal registered user can:

- Create an account
- Log in and log out
- Search parking
- View parking details
- Select an available slot
- Create a reservation
- View booking confirmation
- View reservation history
- Cancel an eligible reservation

### Administrator

An administrator can:

- Access the Admin Dashboard
- Manage parking locations
- Manage parking slots
- View and manage reservations
- Manage registered users
- Perform CRUD operations
- Maintain system data

---

## 5. Functional Modules

### Module 1 – Home Page
Provides the application introduction, branding, primary navigation, feature overview, and entry points to parking search and account registration.

### Module 2 – User Registration
Allows new users to create an account with validated personal information and password.

### Module 3 – Login / Logout
Provides authentication and session-based access control.

### Module 4 – User Dashboard
Displays user-specific reservation information and a quick action for finding parking.

### Module 5 – Find Parking
Provides:

- City search
- Maximum hourly rate filter
- Sorting
- Active parking listings
- Availability indicators
- Parking photo
- List / Map presentation

### Module 6 – Parking Details and Slot Selection
Displays:

- Parking location information
- Address
- Operating hours
- Hourly rate
- Available slots
- Occupied slots
- Maintenance slots
- Reservation form
- Vehicle details
- Start and end time
- Estimated amount

### Module 7 – Reservation and Ticket
Creates a reservation and displays:

- Booking code
- Parking location
- Slot number
- Vehicle information
- Reservation time
- Duration
- Amount
- Booking status
- Print ticket option

### Module 8 – My Reservations
Provides reservation history with:

- Booking code
- Parking location
- Slot
- Vehicle
- Date/time
- Amount
- Status
- View Ticket
- Cancel Booking

### Module 9 – Admin Dashboard
Displays administrative information such as registered users, parking locations, slots, active reservations, cancelled reservations, and revenue information.

### Module 10 – Admin Parking Management
CRUD operations for parking locations.

### Module 11 – Admin Slot Management
CRUD operations for parking slots and slot status management.

### Module 12 – Admin Reservation Management
View, search, filter, and manage reservation records and statuses.

### Module 13 – Admin User Management
View and manage registered users while protecting administrator accounts from unsafe deletion.

---

## 6. Technology Stack

| Area | Technology |
|---|---|
| Front End | HTML5, CSS3, JavaScript |
| Back End | PHP |
| Database | MySQL |
| Database Access | PDO |
| Local Server | XAMPP / Apache |
| Development Environment | localhost |
| UI Theme | ParkNexa Plum + Orange |
| Map Demo | Artificial/local map preview |

---

## 7. Database

Database name:

```text
smart_parking
```

### Tables

#### users
Stores user and administrator account information.

Main fields:

- id
- name
- email
- phone
- password_hash
- role
- created_at

#### parking_locations
Stores parking location information.

Main fields:

- id
- name
- address
- city
- total_slots
- available_slots
- hourly_rate
- opening_time
- closing_time
- status
- created_at

#### parking_slots
Stores individual parking slot records.

Main fields:

- id
- location_id
- slot_number
- slot_type
- status

#### reservations
Stores parking booking information.

Main fields:

- id
- booking_code
- user_id
- location_id
- slot_id
- vehicle_number
- vehicle_type
- start_time
- end_time
- amount
- status
- created_at

---

## 8. Sample Parking Data

The demonstration database contains sample parking locations such as:

- Metro Central Parking – New Delhi
- Connaught Place Smart Parking – New Delhi
- Tech Park Parking – Noida
- Mall Road Parking – Ghaziabad

The locations contain sample slot and pricing data for demonstration and testing.

---

## 9. Project Structure

```text
Smart Parking Reservation System/
│
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── script.js
│   └── images/
│       └── parking-hero-reference.png
│
├── config/
│   ├── database.php
│   └── maps.php
│
├── includes/
│   ├── auth.php
│   ├── header.php
│   └── footer.php
│
├── database/
│   └── smart_parking.sql
│
├── index.php
├── register.php
├── login.php
├── logout.php
├── dashboard.php
├── search-parking.php
├── parking-details.php
├── create-reservation.php
├── booking-confirmation.php
├── my-reservations.php
│
├── admin-dashboard.php
├── admin-parking.php
├── admin-slots.php
├── admin-reservations.php
└── admin-users.php
```

---

## 10. Installation and Setup

### Step 1 – Install XAMPP

Install XAMPP with:

- Apache
- MySQL

Start both services.

### Step 2 – Copy the Project

Place the project folder exactly at:

```text
D:\xampp\htdocs\Smart Parking Reservation System
```

Do not rename the folder if using the provided local links.

### Step 3 – Create the Database

Open phpMyAdmin:

```text
http://localhost/phpmyadmin/
```

Create/import the database using:

```text
database/smart_parking.sql
```

The expected database name is:

```text
smart_parking
```

### Step 4 – Check Database Configuration

Open:

```text
config/database.php
```

Confirm that the MySQL connection settings match the local XAMPP installation.

### Step 5 – Open the Application

Use:

```text
http://localhost/Smart%20Parking%20Reservation%20System/
```

---

## 11. Sample Admin Login

Use the configured administrator account:

```text
Email: admin@parkeasy.local
Password: Admin@123
```

After login, the administrator should be directed to:

```text
http://localhost/Smart%20Parking%20Reservation%20System/admin-dashboard.php
```

For security, change the sample password before using the system outside a classroom/demo environment.

---

## 12. User Flow

```text
Home
  ↓
Register
  ↓
Login
  ↓
Dashboard
  ↓
Find Parking
  ↓
View Slots
  ↓
Select Slot
  ↓
Enter Vehicle + Time
  ↓
Reserve Slot
  ↓
Booking Confirmation / Ticket
  ↓
My Reservations
  ↓
Cancel Booking (when eligible)
```

---

## 13. Admin Flow

```text
Admin Login
  ↓
Admin Dashboard
  ├── Parking
  │    └── Create / Read / Update / Delete
  │
  ├── Slots
  │    └── Create / Read / Update / Delete
  │
  ├── Reservations
  │    └── View / Search / Filter / Status Management
  │
  └── Users
       └── Create / Read / Update / Delete
```

---

## 14. Validation and Security Features

The project includes validation and safety checks such as:

- Required form fields
- Email validation
- Phone/mobile validation
- Password validation
- Vehicle number validation
- Numeric validation
- Date/time validation
- Reservation duration validation
- Session-based authentication
- Role-based authorization
- Admin-only page protection
- PDO prepared statements
- Password hashing
- CSRF protection in management actions
- Reservation conflict checks
- Slot locking during reservation
- Availability synchronization
- Protection against unsafe administrator deletion

---

## 15. Responsive Design

The interface is designed for:

- Desktop
- Laptop
- Tablet
- Mobile

Responsive behavior includes:

- Collapsible mobile navigation
- Adaptive card layouts
- Responsive forms
- Responsive slot grids
- Responsive admin tables
- Mobile-friendly booking controls

---

## 16. Current Map Implementation

### Current Demo

The Find Parking page currently uses a **local artificial map preview**.

Advantages:

- No Google API key required
- No Google Cloud billing required
- Works during classroom/demo setup
- Keeps parking/map UI visible for presentation

### Optional Google Maps

The project also includes the structure for a Google Maps implementation through:

```text
config/maps.php
```

When API access and billing are available, the map can be switched to the real Google Maps implementation.

---

## 17. Important Test Cases

### Registration

- Valid registration succeeds
- Duplicate email is rejected
- Invalid email is rejected
- Invalid phone is rejected
- Missing required fields show validation messages

### Login

- Correct credentials allow login
- Incorrect credentials are rejected
- Logout clears the session
- Unauthorized users cannot access protected pages

### Parking Search

- City filter works
- Maximum rate filter works
- Sorting works
- Active parking locations are displayed
- Empty-result state is displayed correctly

### Reservation

- Only available slots can be selected
- Occupied slots cannot be selected
- Maintenance slots cannot be selected
- Invalid time ranges are rejected
- Overlapping reservations are rejected
- Reservation amount is calculated
- Booking code is generated
- Availability is updated after confirmation

### Cancellation

- Eligible confirmed reservations can be cancelled
- Cancelled reservations cannot be cancelled again
- Slot availability is restored when appropriate
- Reservation status changes correctly

### Admin

- User pages are protected
- Parking CRUD works
- Slot CRUD works
- Reservation management works
- User management works
- Administrative safety checks work

---

## 18. Screenshots for Submission

Recommended screenshots:

1. Home Page
2. Registration Page
3. Login Page
4. User Dashboard
5. Find Parking Page
6. Parking Details / Slot Selection
7. Booking Confirmation / Ticket
8. My Reservations
9. Admin Dashboard
10. Admin Parking CRUD
11. Admin Slots CRUD
12. Admin Reservations
13. Admin Users

Screenshots should clearly show the application's UI, navigation, functionality, and responsive behavior where applicable.

---

## 19. Project Deliverables

Final submission should contain:

- Complete source code
- Database SQL script
- Important screenshots
- Project documentation / README
- Module list
- Technologies used
- Sample login credentials
- GitHub repository link, if required
- Deployed application link, if required

---

## 20. Conclusion

ParkNexa demonstrates a complete web-based Smart Parking Reservation System using PHP, MySQL, HTML5, CSS3, and JavaScript.

The project combines:

- Authentication
- Responsive UI
- Search and filtering
- Parking and slot management
- Reservation processing
- Booking confirmation
- Reservation history
- Cancellation
- Admin CRUD
- Database integration
- Form validation
- Role-based access control

The application is structured so that the major workflows can be demonstrated from registration through reservation and administrative management.

---

## 21. Suggested Viva / Demonstration Flow

For a project demonstration, use this order:

```text
1. Explain the problem
2. Show Home Page
3. Register a user
4. Login
5. Search parking
6. Explain filters
7. Open parking details
8. Select a slot
9. Create a reservation
10. Show booking confirmation
11. Show My Reservations
12. Cancel a reservation
13. Login as Admin
14. Show Admin Dashboard
15. Demonstrate Parking CRUD
16. Demonstrate Slot CRUD
17. Demonstrate Reservation Management
18. Demonstrate User Management
19. Explain database tables
20. Explain responsive design and validation
```
