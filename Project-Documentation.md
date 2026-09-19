# ParkNexa – Project Documentation

## Project Title
**Smart Parking Reservation System – ParkNexa**

## Project Type
M.Tech II Year – III Semester  
Web Development – Project 2: Web-Based Application Development

## 1. Introduction

ParkNexa is a web-based application designed to simplify parking search and reservation. It allows users to discover parking locations, check available slots, reserve a suitable space, receive a booking confirmation, and manage reservations online.

The project also includes an administrator interface for managing parking locations, slots, reservations, and users.

## 2. Objectives

The project demonstrates the practical implementation of:

- Web page design
- Responsive interface development
- Authentication and session management
- Forms and validation
- CRUD operations
- Search and filtering
- Database integration
- Reservation processing
- Role-based access control
- Administrative management

## 3. Users

### User
A customer who searches parking and manages reservations.

### Administrator
A privileged user who maintains parking, slot, reservation, and user records.

## 4. Modules

1. Home
2. Registration
3. Login / Logout
4. User Dashboard
5. Find Parking
6. Parking Details and Slot Selection
7. Reservation
8. Booking Confirmation / Ticket
9. My Reservations
10. Admin Dashboard
11. Parking CRUD
12. Slot CRUD
13. Reservation Management
14. User Management

## 5. Technologies

- HTML5
- CSS3
- JavaScript
- PHP
- MySQL
- PDO
- XAMPP / Apache

## 6. Database

Database:

`smart_parking`

Tables:

- `users`
- `parking_locations`
- `parking_slots`
- `reservations`

## 7. Reservation Logic

A user selects a parking location and available slot, enters vehicle details and a valid time range, and submits the reservation.

The system checks:

- User authentication
- Location validity
- Slot availability
- Slot status
- Reservation time validity
- Reservation conflicts
- Operating hours
- Reservation duration

A valid reservation is stored in MySQL, a booking code is generated, and the user is redirected to the booking confirmation/ticket page.

## 8. Administrative Logic

The Admin module provides CRUD management while applying safety checks so that records with important dependencies are not removed or changed unsafely.

## 9. Validation

Important forms validate:

- Name
- Email
- Phone
- Password
- Vehicle number
- Vehicle type
- Date/time
- Numeric values
- Required fields

## 10. Responsive Design

The interface adapts to desktop, laptop, tablet, and mobile screen sizes.

## 11. Map

The current classroom/demo version uses a local artificial map preview so that the project can be demonstrated without Google Cloud billing.

## 12. Sample Admin Credentials

```text
Email: admin@parkeasy.local
Password: Admin@123
```

## 13. Local URL

```text
http://localhost/Smart%20Parking%20Reservation%20System/
```

## 14. Expected Demonstration

The complete demonstration should show:

```text
Register
→ Login
→ Search Parking
→ View Slots
→ Reserve
→ Booking Confirmation
→ My Reservations
→ Cancel
→ Admin Login
→ Admin CRUD
```

## 15. Submission Notes

Include:

- Source code
- SQL database script
- Screenshots
- README/documentation
- Module list
- Technology list
- Sample credentials
- Repository/deployment links when required
