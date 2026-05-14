# Home Park Real Estate - Functionality Framework

## Table of Contents
1. [System Architecture Overview](#system-architecture-overview)
2. [Database Structure](#database-structure)
3. [User Roles & Permissions](#user-roles--permissions)
4. [Frontend Functionality](#frontend-functionality)
5. [Admin Panel Functionality](#admin-panel-functionality)
6. [Payment Integration](#payment-integration)
7. [File Directory Structure](#file-directory-structure)
8. [Key Workflows](#key-workflows)
9. [Technology Stack](#technology-stack)
10. [Environment Configuration](#environment-configuration)

---

## System Architecture Overview

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        HOME PARK REAL ESTATE SYSTEM                        │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌─────────────────────────┐        ┌─────────────────────────────────┐   │
│  │     PUBLIC FRONTEND     │        │         ADMIN PANEL            │   │
│  │   (Root Directory)      │        │       (admin/ Directory)        │   │
│  ├─────────────────────────┤        ├─────────────────────────────────┤   │
│  │ • index.php             │        │ • dashboard.php                │   │
│  │ • login.php             │        │ • property management           │   │
│  │ • register.php          │        │ • user management               │   │
│  │ • property.php         │        │ • city/state management         │   │
│  │ • propertydetail.php   │        │ • content management             │   │
│  │ • propertygrid.php     │        │ • feedback management           │   │
│  │ • submitproperty.php   │        │ • reports & analytics            │   │
│  │ • contact.php          │        │                                 │   │
│  │ • about.php            │        │                                 │   │
│  │ • feature.php          │        │                                 │   │
│  │ • profile.php          │        │                                 │   │
│  │ • calc.php              │        │                                 │   │
│  └───────────┬─────────────┘        └────────────┬────────────────────┘   │
│              │                                  │                         │
│              └──────────────┬───────────────────┘                         │
│                             │                                              │
│                    ┌────────▼────────┐                                     │
│                    │   MySQL Database │                                    │
│                    │  (realestatephp)  │                                    │
│                    └──────────────────┘                                    │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Database Structure

### Core Tables

| Table Name | Purpose |
|------------|---------|
| `users` | Store user accounts (admin, agent, builder, user) |
| `property` | Store property listings |
| `state` | Store property states/regions |
| `city` | Store property cities |
| `contact` | Store contact form submissions |
| `feedback` | Store user feedback/testimonials |
| `about` | Store about page content |
| `admin` | Store admin accounts |

### Database Connection

```php
// config.php & admin/config.php
$con = mysqli_connect("localhost", "root", "", "realestatephp");
```

---

## User Roles & Permissions

```
┌─────────────────────────────────────────────────────────────────┐
│                      USER ROLES HIERARCHY                       │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌──────────────┐                                               │
│  │    ADMIN     │ ◄── Full System Access                       │
│  │   (Level 1)  │     - All CRUD Operations                    │
│  └──────┬───────┘     - User Management                         │
│         │           - System Configuration                      │
│  ┌──────▼───────┐                                               │
│  │    AGENT     │ ◄── Property Management                      │
│  │  (Level 2)   │     - Submit/Edit Properties                  │
│  └──────┬───────┘     - View Own Listings                      │
│         │                                                       │
│  ┌──────▼───────┐                                               │
│  │   BUILDER    │ ◄── Property Management                      │
│  │  (Level 3)   │     - Submit/Edit Properties                  │
│  └──────┬───────┘     - View Own Listings                      │
│         │                                                       │
│  ┌──────▼───────┐                                               │
│  │     USER     │ ◄── Basic Access                              │
│  │  (Level 4)   │     - Browse Properties                       │
│  └──────────────┘     - Contact Agents                          │
│                       - Submit Feedback                          │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## Frontend Functionality

### Public Pages Flow

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                          FRONTEND PAGE FLOW                                 │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│   index.php (Home)                                                          │
│       │                                                                     │
│       ├──► propertygrid.php (Property Listings)                            │
│       │       │                                                             │
│       │       └──► propertydetail.php (Property Details)                   │
│       │                                                                     │
│       ├──► submitproperty.php (Submit Property - Logged In Users)          │
│       │       │                                                             │
│       │       ├──► submitpropertyupdate.php (Update Property)              │
│       │       │                                                             │
│       │       └──► submitpropertydelete.php (Delete Property)               │
│       │                                                                     │
│       ├──► feature.php (Featured Properties)                                │
│       │                                                                     │
│       ├──► stateproperty.php (Properties by State)                         │
│       │                                                                     │
│       ├──► calc.php (Mortgage Calculator)                                   │
│       │                                                                     │
│       ├──► contact.php (Contact Form)                                      │
│       │                                                                     │
│       ├──► about.php (About Page)                                          │
│       │                                                                     │
│       ├──► login.php (User Login)                                          │
│       │       │                                                             │
│       │       └──► profile.php (User Profile - After Login)                │
│       │                                                                     │
│       └──► register.php (User Registration)                                │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Key Features (Frontend)

| Feature | File | Description |
|---------|------|-------------|
| **Property Listing** | [`propertygrid.php`](propertygrid.php) | Grid view of all properties with filters |
| **Property Details** | [`propertydetail.php`](propertydetail.php) | Full property information with images |
| **Property Search** | [`property.php`](property.php) | Search properties by various criteria |
| **Featured Properties** | [`feature.php`](feature.php) | Display featured/highlighted properties |
| **State-based Properties** | [`stateproperty.php`](stateproperty.php) | Filter properties by state/region |
| **Submit Property** | [`submitproperty.php`](submitproperty.php) | Users can submit new property listings |
| **Mortgage Calculator** | [`calc.php`](calc.php) | Calculate loan installments |
| **Contact Form** | [`contact.php`](contact.php) | Send inquiries to administrators |
| **User Registration** | [`register.php`](register.php) | Register as User/Agent/Builder |
| **User Login** | [`login.php`](login.php) | Authenticate users |
| **User Profile** | [`profile.php`](profile.php) | View and manage user profile |

---

## Admin Panel Functionality

### Admin Panel Structure

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                         ADMIN PANEL STRUCTURE                             │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│   admin/index.php (Login)                                                   │
│       │                                                                     │
│       └──► admin/dashboard.php (Main Dashboard)                           │
│                   │                                                         │
│                   ├── Property Management                                  │
│                   │   ├── propertyadd.php (Add Property)                   │
│                   │   ├── propertyedit.php (Edit Property)                 │
│                   │   ├── propertyview.php (View Property)                  │
│                   │   └── propertydelete.php (Delete Property)             │
│                   │                                                         │
│                   ├── User Management                                      │
│                   │   ├── userlist.php (List Users)                         │
│                   │   ├── userdelete.php (Delete User)                      │
│                   │   ├── useragent.php (Manage Agents)                     │
│                   │   ├── userbuilder.php (Manage Builders)                 │
│                   │   └── adminlist.php (List Admins)                       │
│                   │                                                         │
│                   ├── Location Management                                   │
│                   │   ├── stateadd.php (Add State)                          │
│                   │   ├── stateedit.php (Edit State)                        │
│                   │   ├── statedelete.php (Delete State)                    │
│                   │   ├── cityadd.php (Add City)                             │
│                   │   ├── cityedit.php (Edit City)                          │
│                   │   └── citydelete.php (Delete City)                      │
│                   │                                                         │
│                   ├── Content Management                                    │
│                   │   ├── aboutadd.php (Add About Content)                   │
│                   │   ├── aboutedit.php (Edit About)                         │
│                   │   ├── aboutview.php (View About)                        │
│                   │   ├── aboutdelete.php (Delete About)                    │
│                   │                                                         │
│                   ├── Communication                                         │
│                   │   ├── contactview.php (View Contact Messages)           │
│                   │   ├── contactdelete.php (Delete Contact)                │
│                   │   ├── feedbackview.php (View Feedback)                  │
│                   │   ├── feedbackedit.php (Edit Feedback)                  │
│                   │   └── feedbackdelete.php (Delete Feedback)              │
│                   │                                                         │
│                   └── Profile                                               │
│                       └── profile.php (Admin Profile)                       │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Admin Features

| Module | Files | Description |
|--------|-------|-------------|
| **Dashboard** | [`dashboard.php`](admin/dashboard.php) | Overview of system stats, recent activities |
| **Property Management** | `propertyadd`, `propertyedit`, `propertyview`, `propertydelete` | Full CRUD for properties |
| **User Management** | `userlist`, `userdelete`, `useragent`, `userbuilder`, `adminlist` | Manage all user types |
| **State Management** | `stateadd`, `stateedit`, `statedelete` | Manage property states/regions |
| **City Management** | `cityadd`, `cityedit`, `citydelete` | Manage property cities |
| **About Page** | `aboutadd`, `aboutedit`, `aboutview`, `aboutdelete` | Manage about page content |
| **Contact Management** | `contactview`, `contactdelete` | View/delete contact submissions |
| **Feedback Management** | `feedbackview`, `feedbackedit`, `feedbackdelete` | Manage user feedback/testimonials |

---

## Payment Integration

### Supported Payment Methods

| Payment Method | Region | Description |
|----------------|--------|-------------|
| **M-Pesa** | Kenya | Mobile money payments via Safaricom Daraja API |
| **Stripe** | International | Credit/debit card payments |

### Payment Features

- Secure Payment Processing
- Transaction History & Logging
- Payment Verification (M-Pesa STK Push & Stripe webhooks)
- Refund Processing
- Subscription Plans (Basic, Premium, Enterprise)
- Payout Management (for agents/builders)
- Invoice Generation
- Webhook Notifications

### Payment Classes Architecture

```php
// includes/payments/MpesaPayment.php
class MpesaPayment {
    // STK Push - Send payment prompt to user's phone
    // C2B - Customer to Business (receive payments)
    // B2C - Business to Customer (payouts)
    // Transaction Status Lookup
    // Balance Inquiry
}

// includes/payments/StripePayment.php
class StripePayment {
    // Create Payment Intent
    // Process Card Payments
    // Handle 3D Secure
    // Subscription Management
    // Refund Processing
    // Webhook Handling
}
```

### Subscription Plans

| Plan | Price | Features |
|------|-------|----------|
| Basic | Free | 1 property listing |
| Premium | KES 2,999/month | 5 property listings, featured properties |
| Enterprise | KES 9,999/month | Unlimited listings, analytics, priority support |

### Environment Variables (to be configured in .env)

```env
# Database
DB_HOST=localhost
DB_NAME=realestatephp
DB_USER=root
DB_PASS=

# Application
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# M-Pesa (Safaricom Daraja API)
MPESA_CONSUMER_KEY=your_consumer_key
MPESA_CONSUMER_SECRET=your_consumer_secret
MPESA_SHORTCODE=your_shortcode
MPESA_PASSKEY=your_passkey
MPESA_CALLBACK_URL=https://yourdomain.com/api/mpesa/callback
MPESA_ENV=sandbox

# Stripe
STRIPE_KEY=pk_test_xxxxx
STRIPE_SECRET=sk_test_xxxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxxx
STRIPE_CURRENCY=usd

# Payment Settings
PAYMENT_CURRENCY=KES
PAYMENT_CURRENCY_SYMBOL=KSh
SUBSCRIPTION_BASIC_PRICE=0
SUBSCRIPTION_PREMIUM_PRICE=2999
SUBSCRIPTION_ENTERPRISE_PRICE=9999
```

---

## File Directory Structure

```
home-park-real-estate/
│
├── 📄 CONFIGURATION
│   ├── config.php                 # Frontend database config
│   ├── admin/config.php           # Admin panel database config
│   ├── .env                       # Environment variables (production)
│   ├── .env.example               # Environment template
│   └── includes/
│       ├── EnvLoader.php          # Environment variable loader
│       └── helpers.php            # Helper functions
│
├── 💳 PAYMENT PROCESSING
│   ├── includes/
│   │   ├── payments/
│   │   │   ├── PaymentInterface.php    # Payment interface
│   │   │   ├── MpesaPayment.php        # M-Pesa integration
│   │   │   ├── StripePayment.php       # Stripe integration
│   │   │   └── PaymentFactory.php      # Payment factory
│   │   └── database/
│   │       └── migrations/             # Database migrations
│
│   ├── api/
│   │   ├── mpesa/
│   │   │   ├── callback.php            # M-Pesa webhook
│   │   │   └── stkpush.php             # STK Push handler
│   │   └── stripe/
│   │       └── webhook.php             # Stripe webhook
│
├── 🌐 PUBLIC PAGES (Frontend)
│   ├── index.php                  # Homepage
│   ├── login.php                  # User login
│   ├── register.php               # User registration
│n│   ├── logout.php                 # User logout
│   ├── property.php               # Property search
│   ├── propertygrid.php           # Property grid view
│   ├── propertydetail.php         # Property details
│   ├── submitproperty.php         # Submit new property
│   ├── submitpropertyupdate.php   # Update property
│   ├── submitpropertydelete.php   # Delete property
│   ├── feature.php                # Featured properties
│   ├── stateproperty.php          # Properties by state
│   ├── profile.php                # User profile
│   ├── contact.php                # Contact form
│   ├── about.php                  # About page
│   ├── calc.php                   # Mortgage calculator
│   └── subscribe.php              # Subscription plans & payment
│
├── ⚙️ ADMIN PANEL
│   ├── index.php                  # Admin login
│   ├── dashboard.php              # Admin dashboard
│   ├── profile.php                # Admin profile
│   ├── logout.php                 # Admin logout
│   │
│   ├── 📊 PROPERTY MANAGEMENT
│   │   ├── propertyadd.php        # Add property
│   │   ├── propertyedit.php       # Edit property
│   │   ├── propertyview.php       # View property
│   │   └── propertydelete.php     # Delete property
│   │
│   ├── 💳 PAYMENT MANAGEMENT
│   │   ├── paymentview.php        # View all payments
│   │   ├── paymentdetails.php     # Payment details
│   │   ├── refund.php             # Process refunds
│   │   └── payout.php             # Payout management
│   │
│   ├── 👥 USER MANAGEMENT
│   │   ├── userlist.php           # List all users
│   │   ├── userdelete.php         # Delete user
│   │   ├── useragent.php          # Manage agents
│   │   ├── userbuilder.php        # Manage builders
│   │   └── adminlist.php          # List admins
│
│   ├── 📍 LOCATION MANAGEMENT
│   │   ├── stateadd.php           # Add state
│   │   ├── stateedit.php          # Edit state
│   │   ├── statedelete.php        # Delete state
│   │   ├── cityadd.php            # Add city
│   │   ├── cityedit.php           # Edit city
│   │   └── citydelete.php         # Delete city
│
│   ├── 📝 CONTENT MANAGEMENT
│   │   ├── aboutadd.php           # Add about content
│   │   ├── aboutedit.php          # Edit about content
│   │   ├── aboutview.php          # View about content
│   │   └── aboutdelete.php        # Delete about content
│
│   ├── 💬 COMMUNICATION
│   │   ├── contactview.php         # View contact messages
│   │   ├── contactdelete.php      # Delete contact message
│   │   ├── feedbackview.php       # View feedback
│   │   ├── feedbackedit.php       # Edit feedback
│   │   └── feedbackdelete.php     # Delete feedback
│
│   └── 🎨 ASSETS
│       ├── css/                   # Stylesheets
│       ├── js/                    # JavaScript files
│       ├── fonts/                 # Font files
│       ├── img/                   # Images
│       └── plugins/               # Third-party plugins
│
└── 📖 DOCUMENTATION
    ├── README.md                  # Project documentation
    └── FUNCTIONALITY_FRAMEWORK.md # This file
```
│   ├── about.php                  # About page
│   └── calc.php                   # Mortgage calculator
│
├── ⚙️ ADMIN PANEL
│   ├── index.php                  # Admin login
│   ├── dashboard.php              # Admin dashboard
│   ├── profile.php                # Admin profile
│   ├── logout.php                 # Admin logout
│   │
│   ├── 📊 PROPERTY MANAGEMENT
│   │   ├── propertyadd.php        # Add property
│   │   ├── propertyedit.php       # Edit property
│   │   ├── propertyview.php       # View property
│   │   └── propertydelete.php     # Delete property
│   │
│   ├── 👥 USER MANAGEMENT
│   │   ├── userlist.php           # List all users
│   │   ├── userdelete.php         # Delete user
│   │   ├── useragent.php          # Manage agents
│   │   ├── userbuilder.php        # Manage builders
│   │   └── adminlist.php          # List admins
│   │
│   ├── 📍 LOCATION MANAGEMENT
│   │   ├── stateadd.php           # Add state
│   │   ├── stateedit.php          # Edit state
│   │   ├── statedelete.php        # Delete state
│   │   ├── cityadd.php            # Add city
│   │   ├── cityedit.php           # Edit city
│   │   └── citydelete.php         # Delete city
│   │
│   ├── 📝 CONTENT MANAGEMENT
│   │   ├── aboutadd.php           # Add about content
│   │   ├── aboutedit.php          # Edit about content
│   │   ├── aboutview.php          # View about content
│   │   └── aboutdelete.php        # Delete about content
│   │
│   ├── 💬 COMMUNICATION
│   │   ├── contactview.php         # View contact messages
│   │   ├── contactdelete.php      # Delete contact message
│   │   ├── feedbackview.php       # View feedback
│   │   ├── feedbackedit.php       # Edit feedback
│   │   └── feedbackdelete.php     # Delete feedback
│   │
│   └── 🎨 ASSETS
│       ├── css/                   # Stylesheets
│       ├── js/                    # JavaScript files
│       ├── fonts/                 # Font files
│       ├── img/                   # Images
│       └── plugins/               # Third-party plugins
│
└── 📖 DOCUMENTATION
    └── README.md                  # Project documentation
```

---

## Key Workflows

### Workflow 1: User Registration & Login

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                     USER REGISTRATION & LOGIN FLOW                          │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  1. User visits register.php                                               │
│  2. Fills registration form (name, email, password, user type)            │
│  3. Data validated and stored in 'users' table                             │
│  4. User redirected to login.php                                           │
│  5. User enters credentials                                                 │
│  6. System validates against 'users' table                                 │
│  7. On success: Session created, redirect to profile.php                  │
│  8. On failure: Error message displayed                                     │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Workflow 2: Property Submission

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        PROPERTY SUBMISSION FLOW                             │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  1. Logged-in user visits submitproperty.php                               │
│  2. Fills property details form:                                           │
│     - Property title, description, price                                   │
│     - Location (state, city)                                                │
│     - Property type, bedrooms, bathrooms                                   │
│     - Area, amenities, images                                              │
│  3. Data submitted to database 'property' table                             │
│  4. Admin reviews property (via admin dashboard)                           │
│  5. Admin can approve/reject or feature the property                       │
│  6. Property appears in frontend listings                                   │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Workflow 3: Admin Property Management

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                      ADMIN PROPERTY MANAGEMENT FLOW                        │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  1. Admin logs into admin/index.php                                         │
│  2. Access dashboard.php to view overview                                  │
│  3. Navigate to property management:                                        │
│     - Add: propertyadd.php - Create new property listing                   │
│     - Edit: propertyedit.php - Modify existing property                   │
│     - View: propertyview.php - View property details                       │
│     - Delete: propertydelete.php - Remove property                          │
│  4. Admin can set property status (active/inactive)                         │
│  5. Admin can feature/unfeature properties                                  │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Workflow 4: Contact & Feedback

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        CONTACT & FEEDBACK FLOW                              │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  CONTACT FORM:                                                              │
│  1. Visitor fills contact.php form                                         │
│  2. Data stored in 'contact' table                                         │
│  3. Admin views via admin/contactview.php                                   │
│  4. Admin can delete contact entries                                       │
│                                                                             │
│  FEEDBACK:                                                                 │
│  1. User submits feedback                                                  │
│  2. Stored in 'feedback' table                                             │
│  3. Admin views/edits via admin/feedbackview.php                           │
│  4. Approved feedback displayed on site                                    │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Workflow 5: Subscription & Payment Flow

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                   SUBSCRIPTION & PAYMENT WORKFLOW                            │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  SUBSCRIPTION PLANS:                                                       │
│  ┌──────────┬─────────────┬──────────────────────────────────────────┐      │
│  │  Basic   │    Free     │  1 Property Listing                     │      │
│  ├──────────┼─────────────┼──────────────────────────────────────────┤      │
│  │ Premium  │  KES 2,999  │  5 Properties + Featured Listings        │      │
│  ├──────────┼─────────────┼──────────────────────────────────────────┤      │
│  │Enterprise│  KES 9,999  │  Unlimited + Priority Support            │      │
│  └──────────┴─────────────┴──────────────────────────────────────────┘      │
│                                                                             │
│  M-PESA PAYMENT FLOW:                                                      │
│  1. User visits subscribe.php                                             │
│  2. Selects plan and payment method (M-Pesa)                              │
│  3. Enters phone number                                                    │
│  4. System initiates STK Push                                             │
│  5. User receives prompt on phone, enters PIN                             │
│  6. Safaricom processes payment                                           │
│  7. M-Pesa sends callback to api/mpesa/callback.php                     │
│  8. System verifies payment, activates subscription                      │
│                                                                             │
│  STRIPE PAYMENT FLOW:                                                      │
│  1. User visits subscribe.php                                             │
│  2. Selects plan and payment method (Stripe)                              │
│  3. System creates Payment Intent                                         │
│  4. User redirected to Stripe Checkout                                    │
│  5. User enters card details                                               │
│  6. Stripe processes payment                                              │
│  7. Stripe sends webhook to api/stripe/webhook.php                        │
│  8. System verifies, activates subscription                               │
│                                                                             │
│  ADMIN PAYMENT MANAGEMENT:                                                 │
│  1. Admin logs into admin panel                                           │
│  2. Navigates to paymentview.php                                          │
│  3. Views all transactions with filters                                   │
│  4. Can process refunds for completed payments                            │
│  5. Exports payment reports                                               │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Technology Stack

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        TECHNOLOGY STACK                                     │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐         │
│  │   FRONTEND     │    │    BACKEND      │    │   DATABASE     │         │
│  ├─────────────────┤    ├─────────────────┤    ├─────────────────┤         │
│  │ HTML5          │    │ PHP 7.x+        │    │ MySQL          │         │
│  │ CSS3           │    │                 │    │                │         │
│  │ Bootstrap 4    │    │                 │    │                │         │
│  │ JavaScript     │    │                 │    │                │         │
│  │ jQuery         │    │                 │    │                │         │
│  └─────────────────┘    └─────────────────┘    └─────────────────┘         │
│                                                                             │
│  ADDITIONAL TOOLS:                                                          │
│  • DataTables (jQuery plugin for tables)                                   │
│  • TinyMCE (Rich text editor)                                              │
│  • Morris Charts (Analytics charts)                                        │
│  • LightGallery (Image gallery)                                            │
│  • Font Awesome (Icons)                                                    │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Installation Requirements

1. **Web Server**: Apache (XAMPP/WAMP/LAMP)
2. **PHP Version**: PHP 7.x or higher
3. **MySQL Version**: MySQL 5.x or higher
4. **Browser**: Modern browsers (Chrome, Firefox, Edge)

### Database Setup

1. Create database: `realestatephp`
2. Import `realestatephp.sql` from DATABASE folder
3. Configure connection in `config.php` and `admin/config.php`

---

## Summary

This Real Estate Management System provides a complete platform for:

- **Property Listing**: Browse, search, and filter properties
- **User Management**: Different user roles with specific permissions
- **Property Submission**: Agents/builders can submit properties
- **Admin Control**: Full administrative control over all content
- **Communication**: Contact forms and feedback systems
- **Content Management**: Dynamic about page and featured properties

For additional support or customization, please refer to the main README.md file.

---

## Environment Configuration

### Setting Up Environment Variables

1. Copy `.env.example` to `.env`:
```bash
cp .env.example .env
```

2. Edit `.env` with your actual API credentials

### Required API Keys

#### M-Pesa (Safaricom Daraja API)
1. Register at https://developer.safaricom.co.ke
2. Create an app to get Consumer Key and Secret
3. Generate Passkey from your app
4. Configure Sandbox/Live credentials

#### Stripe
1. Register at https://stripe.com
2. Get API keys from Dashboard > Developers > API Keys
3. Configure webhook secret for production

### Database Migration

Run the payment migration SQL:
```sql
-- Import from includes/database/migrations/001_payments_migration.sql
```

### File Structure Summary

| Directory | Purpose |
|-----------|---------|
| `includes/` | Core PHP classes and utilities |
| `includes/payments/` | Payment gateway classes |
| `includes/database/migrations/` | Database migration files |
| `api/` | API endpoints for payment callbacks |
| `admin/` | Admin panel pages |

