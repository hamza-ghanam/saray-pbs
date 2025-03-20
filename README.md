# Saray Property Booking System

A robust API-based backend for managing property bookings and unit transactions. Built with Laravel 11 and powered by MySQL, this system streamlines the entire booking process—from building and unit management to sales approvals, document generation, and final booking.

## Overview

The Property Booking System is designed for real estate management with a comprehensive workflow involving multiple user roles.
## Main Features

- **Building & Unit Management:**
    - Create and manage buildings.
    - Add new units under a building with an initial "Pending" status.
    - Approve units to transition them to "Available" status before they can be booked.

- **Unit Booking Workflow:**
    - Search and filter units based on unit or building attributes.
    - View detailed information about buildings and units.
    - Book units by uploading customer identification (passport) and extracting MRZ data.
    - Edit extracted customer data when necessary.
    - Upload payment receipts to initiate the booking process.
    - Transition unit status to "Pre-Booked" during the approval stage.
    - Generate and process Reservation Forms and Sales and Purchase Agreements (SPA) for finalized bookings.
    - Automatically revert unit status to "Available" with a cancellation note if payment conditions (e.g., 20% payment within 14 days) are not met.

- **Unit Holding & Updates:**
    - Place a temporary hold on a unit (24 hours), making it unavailable for others.
    - Allow updates to units through text descriptions and attachments.

- **User Registration & Management:**
    - Generate one-time links for user registration.
    - Enable users to complete registration by uploading required documents.
    - Support administrative tasks such as user approvals and management.

## Tech Stack

- **Backend Framework:** Laravel 12
- **Database:** MySQL
- **API:** RESTful endpoints secured via Laravel Sanctum
- **Authentication:** Sanctum
- **Authorization:** Spatie Roles & Permissions

## Installation

### Prerequisites

- PHP >= 8.1
- Composer
- MySQL

### Setup Instructions

1. **Clone the Repository:**

   ```bash
   git clone https://github.com/hamza-ghanam/saray-pbs.git
   cd saray-pbs
