# Product Requirements Document (PRD)
## KingdomVitals - Multi-Tenant Church Management System

**Version:** 1.1
**Date:** December 30, 2025
**Status:** Draft
**Last Updated:** Multi-Branch/Multi-Campus Management feature added

---

## Table of Contents
1. [Executive Summary](#executive-summary)
2. [Product Vision](#product-vision)
3. [Target Audience](#target-audience)
4. [System Architecture](#system-architecture)
5. [Functional Requirements](#functional-requirements)
   - 1. [Dashboard Module](#1-dashboard-module)
   - 2. [Members Module](#2-members-module)
   - 3. [Visitors Module](#3-visitors-module)
   - 4. [Attendance Module](#4-attendance-module)
   - 5. [Finance Module](#5-finance-module)
   - 6. [Bulk SMS Module](#6-bulk-sms-module-texttango-integration)
   - 7. [Equipment Module](#7-equipment-module)
   - 8. [Report Module](#8-report-module)
   - 9. [Settings Module](#9-settings-module)
   - 10. [Cluster Follow-up Module](#10-cluster-follow-up-module)
   - 11. [**Multi-Branch/Multi-Campus Management**](#11-multi-branchmulti-campus-management) *(New)*
6. [Technical Requirements](#technical-requirements)
7. [Security & Compliance](#security--compliance)
8. [Integration Requirements](#integration-requirements)
9. [Performance Requirements](#performance-requirements)
10. [Success Metrics](#success-metrics)
11. [Development Roadmap](#development-roadmap)
12. [Appendices](#appendices)

---

## Executive Summary

KingdomVitals is a modern, multi-tenant church management system designed to streamline church operations, enhance member engagement, and provide powerful analytics for data-driven decision-making. Built on Laravel 12 with Livewire 3 and Flux UI, the system offers a comprehensive suite of tools for member management, financial operations, communication, and reporting.

**New in Version 1.1:** Comprehensive multi-branch/multi-campus management capabilities enabling churches with multiple physical locations to manage all sites within a unified system while maintaining appropriate data isolation and financial autonomy per location.

### Key Objectives
- Provide an intuitive, accessible platform for churches of all sizes
- Enable data-driven ministry decisions through powerful analytics
- Streamline administrative tasks and reduce manual workload
- Enhance member engagement and communication
- Ensure enterprise-grade security and data protection
- Support multi-tenancy for scalable SaaS deployment
- **Support multi-branch/multi-campus churches with 2-50+ locations** *(New)*
- **Provide configurable terminology (branches/campuses/locations/centers)** *(New)*
- **Enable full branch financial autonomy with separate budgets and P&L** *(New)*

---

## Product Vision

### Mission Statement
To empower churches worldwide with modern technology that simplifies administration, deepens member connections, and enables ministry leaders to focus on their calling rather than administrative burdens.

### Core Values
- **Simplicity**: Intuitive interfaces that require minimal training
- **Security**: Enterprise-grade protection of sensitive church data
- **Scalability**: Support churches from 50 to 50,000+ members
- **Accessibility**: Mobile-first, responsive design for all devices
- **Innovation**: Leveraging modern technology for ministry effectiveness

---

## Target Audience

### Primary Users
1. **Church Administrators**
   - Full system access and configuration
   - Financial management oversight
   - Report generation and analysis
   - User management and permissions

2. **Ministry Leaders**
   - Department-specific access
   - Member information management
   - Attendance tracking
   - Communication tools

3. **Finance Team**
   - Donation processing
   - Expense management
   - Financial reporting
   - Budget tracking

4. **Members**
   - Self-service profile updates
   - Online giving
   - Prayer requests
   - Event registration

### Church Profiles
- **Small Churches**: 50-200 members, basic needs
- **Medium Churches**: 200-1,000 members, multiple ministries
- **Large Churches**: 1,000-5,000 members, complex operations
- **Mega Churches**: 5,000+ members, multiple campuses

---

## System Architecture

### Technology Stack

#### Frontend
- **Framework**: HTML5, Tailwind CSS v4, Alpine.js
- **UI Components**: Livewire 3, Flux UI (Free Edition)
- **Responsive Design**: Mobile-first approach
- **PWA**: Progressive Web App capabilities
- **Browser Support**: Modern browsers (Chrome, Firefox, Safari, Edge)

#### Backend
- **Framework**: Laravel 12 (PHP 8.3)
- **Authentication**: Laravel Fortify
- **Real-time**: Livewire 3
- **Queue System**: Laravel Queues
- **Cache**: Redis (optional)
- **Search**: Database full-text search

#### Database
- **Primary**: MySQL 8.0+
- **Architecture**: Multi-tenant database design
- **Optimization**: Indexed queries, stored procedures
- **Backup**: Automated daily backups
- **Partitioning**: Data partitioning for large datasets

#### Infrastructure
- **Deployment**: Laravel Sail, Docker containers
- **Storage**: Cloud storage (AWS S3, DigitalOcean Spaces)
- **CDN**: Content delivery for static assets
- **SSL/TLS**: Encrypted connections
- **Monitoring**: Application performance monitoring

### Multi-Tenancy Architecture

```
┌─────────────────────────────────────────┐
│         Application Layer               │
│   (Shared Laravel Application)          │
└─────────────────────────────────────────┘
                    │
        ┌───────────┴───────────┐
        │                       │
┌───────▼─────┐         ┌───────▼─────┐
│  Tenant A   │         │  Tenant B   │
│  Database   │         │  Database   │
└─────────────┘         └─────────────┘
```

**Tenant Isolation Strategy:**
- Database per tenant approach
- Subdomain-based tenant identification (church1.kingdomvitals.com)
- Separate data storage per tenant
- Shared application codebase
- Tenant-specific customizations via settings

---

## Functional Requirements

### 1. Dashboard Module

#### 1.1 Executive Dashboard
**Priority:** High
**User Roles:** Administrators, Church Leaders

**Features:**
- **Branch Context Selector** *(Multi-Branch Feature)*
  - Global branch selector in navigation
  - "All [Branches]" consolidated view option
  - Branch-specific dashboard view
  - Visual indicator of current branch context
  - Quick branch switcher for multi-branch users

- **Real-time Statistics**
  - Total members count with growth percentage (filtered by branch context)
  - Weekly attendance trends (per branch or consolidated)
  - Monthly giving summary (branch-specific or organization-wide)
  - Visitor conversion rate (by branch)
  - Active cluster groups (filtered by branch)
  - **Branch count** (number of active locations)

- **Visual Analytics**
  - Attendance charts (line/bar graphs) with branch comparison option
  - Financial giving trends (per branch or consolidated)
  - Member growth visualization (branch vs. organization)
  - Demographic breakdowns (branch-specific)
  - Ministry participation rates (by branch)
  - **Branch performance comparison widget** *(Multi-Branch Feature)*

- **Quick Actions**
  - Add new member (auto-assigned to current branch)
  - Record attendance (for current branch services)
  - Process donation (tagged to current branch)
  - Send bulk SMS (scoped to current branch)
  - Generate reports (branch-filtered)
  - **Create new branch** *(Multi-Branch Feature, Admin only)*

- **Upcoming Events Widget**
  - Calendar integration (branch-filtered)
  - Event countdown
  - RSVP tracking (by branch)
  - Reminder notifications

- **Alerts & Notifications**
  - Low attendance warnings (per branch)
  - Financial milestones (branch and organization)
  - Member birthdays (today, filtered by branch)
  - System notifications
  - Pending approvals
  - **Branch transfer requests** *(Multi-Branch Feature)*

**Multi-Branch Dashboard Views:**
- **Single-Branch View:** Shows metrics for selected branch only
- **Consolidated View:** Shows organization-wide metrics across all branches
- **Comparison View:** Side-by-side branch performance comparison

**Acceptance Criteria:**
- Dashboard loads within 2 seconds (regardless of branch context)
- Real-time data updates every 30 seconds
- Responsive design for mobile/tablet/desktop
- Customizable widget arrangement
- Export dashboard data to PDF (includes branch context)
- Branch selector loads in < 500ms
- Clear visual distinction between single-branch and all-branch views

---

### 2. Members Module

#### 2.1 Member Profile System
**Priority:** High
**User Roles:** Administrators, Ministry Leaders

**Features:**

**Personal Information Management**
- Full name (first, middle, last)
- Date of birth (with age calculation)
- Gender selection
- Marital status
- Profile photo upload (max 2MB)
- Contact information:
  - Primary phone number
  - Secondary phone number
  - Email address
  - Physical address (street, city, state, zip)
  - Country selection

**Family Information**
- Spouse details (if married)
- Children records
- Family relationships mapping
- Household grouping

**Emergency Contacts**
- Contact name
- Relationship
- Phone number
- Email address
- Multiple emergency contacts support

**Membership Details**
- Member ID (auto-generated)
- Join date
- Membership status (Active, Inactive, Transferred, Deceased)
- Membership class completion
- Baptism date and certificate
- Confirmation date
- **Primary branch/campus assignment** *(Multi-Branch Feature)*
- **Branch transfer history** *(Multi-Branch Feature)*
- **Multi-campus attendance pattern** *(Multi-Branch Feature)*

**Ministry Involvement**
- Department assignments
- Ministry roles
- Service teams
- Skill sets and talents
- Volunteer preferences

**Custom Fields**
- Configurable custom fields per tenant
- Support for text, date, dropdown, checkbox
- Field validation rules
- Required/optional field settings

**Document Attachments**
- Baptism certificates
- Marriage certificates
- Transfer letters
- ID documents
- Custom document types

**Acceptance Criteria:**
- Create member profile in under 2 minutes
- Photo upload with automatic resizing
- Duplicate detection by phone/email
- Audit trail for all profile changes
- Export member data to Excel/CSV

#### 2.2 Member Directory
**Priority:** High
**User Roles:** All authenticated users (role-based visibility)

**Features:**
- **Search & Filter**
  - Name search (fuzzy matching)
  - Phone/email search
  - Filter by status, department, age group
  - **Filter by primary branch/campus** *(Multi-Branch Feature)*
  - Advanced filter combinations
  - Save search filters

- **List Views**
  - Grid view with photos
  - Table view with details
  - Compact list view
  - Print-friendly view

- **Bulk Actions**
  - Bulk SMS to selected members
  - Bulk email
  - Export selected members
  - Assign to groups
  - Update status

- **Privacy Controls**
  - Role-based visibility
  - Hide sensitive information
  - Member consent management
  - Public directory toggle

**Acceptance Criteria:**
- Search returns results within 1 second
- Support for 10,000+ member directories
- Mobile-responsive directory
- Configurable privacy settings
- GDPR-compliant data handling

#### 2.3 Member Groups & Tags
**Priority:** Medium
**User Roles:** Administrators, Ministry Leaders

**Features:**
- **Smart Groups**
  - Age-based groups (Youth, Adults, Seniors)
  - Ministry-based groups
  - Location-based groups
  - Custom criteria groups

- **Tagging System**
  - Custom tags creation
  - Tag assignment to members
  - Tag-based filtering
  - Tag analytics

- **Group Management**
  - Create/edit/delete groups
  - Group leaders assignment
  - Group activities tracking
  - Group communication

**Acceptance Criteria:**
- Unlimited groups per tenant
- Real-time group member count
- Export group member lists
- Group-based permissions

---

### 3. Visitors Module

#### 3.1 Visitor Registration
**Priority:** High
**User Roles:** Administrators, Welcome Team

**Features:**
- **Quick Capture Form**
  - Full name
  - Phone number
  - Email address
  - Visit date
  - Service attended
  - How they heard about us
  - Visit purpose
  - Prayer requests
  - Notes

- **Digital Check-in**
  - QR code visitor registration
  - Tablet-based check-in kiosks
  - Mobile self-check-in
  - Guest WiFi integration

- **Follow-up Assignment**
  - Auto-assign to follow-up team
  - Follow-up task creation
  - Scheduled contact reminders
  - Follow-up history tracking

**Acceptance Criteria:**
- Registration form completes in under 1 minute
- Duplicate visitor detection
- Automatic welcome email/SMS
- Mobile-optimized check-in form

#### 3.2 Visitor Tracking
**Priority:** High
**User Roles:** Administrators, Follow-up Team

**Features:**
- **Visit History**
  - All visits logged
  - Service attendance patterns
  - Engagement level scoring
  - Conversion probability

- **Follow-up Management**
  - Follow-up tasks dashboard
  - Contact attempt tracking
  - Follow-up notes
  - Status updates (New, Contacted, Engaged, Converted, Lost)

- **Conversion Pipeline**
  - Visitor → Regular Attender → Member
  - Stage-based workflow
  - Conversion metrics
  - Bottleneck identification

- **Analytics**
  - Visitor trends over time
  - Conversion rates
  - Source effectiveness
  - Follow-up effectiveness

**Acceptance Criteria:**
- Track unlimited visitor visits
- Automated follow-up reminders
- Conversion reporting
- Export visitor data

---

### 4. Attendance Module

#### 4.1 Digital Check-in System
**Priority:** High
**User Roles:** Administrators, Attendance Team

**Features:**

**Multiple Check-in Methods**
- **QR Code Check-in**
  - Personal QR codes for members
  - QR code generation
  - Scanner app integration
  - Kiosk mode for tablets

- **Manual Check-in**
  - Quick name search
  - Touch-friendly interface
  - Bulk check-in for families
  - Guest check-in

- **Mobile App Check-in**
  - Member self-check-in via mobile
  - Location verification
  - Check-in confirmation
  - Check-in history

- **RFID/NFC (Future)**
  - Card-based check-in
  - Wearable device support
  - Automated counting

**Service Tracking**
- Multiple services per day
- Different service types (Sunday, Midweek, Special)
- Service locations (Main Campus, Satellite)
- Children's ministry check-in
- Department-specific attendance

**Acceptance Criteria:**
- Check-in completes in under 5 seconds
- Support for 1,000+ simultaneous check-ins
- Offline mode with sync
- Real-time attendance counter
- Security check-out for children's ministry

#### 4.2 Attendance Reporting
**Priority:** High
**User Roles:** Administrators, Church Leaders

**Features:**
- **Real-time Dashboard**
  - Current service attendance
  - Live attendance counter
  - Comparison to previous week
  - Capacity percentage

- **Historical Reports**
  - Weekly attendance trends
  - Monthly comparisons
  - Year-over-year growth
  - Service-wise breakdown
  - Department attendance

- **Absence Tracking**
  - Identify absent members (3+ weeks)
  - Automated absence notifications
  - Follow-up task creation
  - Re-engagement campaigns

- **Analytics**
  - Attendance patterns by day/time
  - Seasonal trends
  - Growth forecasting
  - First-time visitor counts
  - Regular attender identification

**Acceptance Criteria:**
- Generate reports in under 10 seconds
- Export to PDF/Excel
- Automated weekly email reports
- Customizable date ranges
- Drill-down capabilities

---

### 5. Finance Module

#### 5.1 Donation Management
**Priority:** High
**User Roles:** Administrators, Finance Team

**Features:**

**Online Giving Platform**
- **Payment Methods**
  - Credit/Debit cards (via Paystack)
  - Bank transfers
  - Mobile money
  - Recurring donations (weekly, monthly, annually)
  - One-time donations

- **Giving Categories**
  - Tithes
  - Offerings
  - Building fund
  - Missions
  - Special projects
  - Custom categories

- **Donor Experience**
  - Guest checkout
  - Member login for history
  - Save payment methods
  - Donation receipts (email/PDF)
  - Tax deductible statements

- **Campaign-specific Giving**
  - Create fundraising campaigns
  - Campaign progress tracking
  - Goal visualization
  - Campaign-specific reporting
  - Donor recognition tiers

**Offline Donations**
- Cash/check recording
- Batch entry for offering counts
- Envelope number tracking
- Cash count verification
- Multi-currency support

**Donor Management**
- Donor profiles
- Giving history
- Lifetime giving totals
- Donor segmentation (major, regular, occasional)
- Donor communication

**Acceptance Criteria:**
- PCI-DSS compliant payment processing
- Transaction completion in under 30 seconds
- Automated receipt generation
- Real-time donation recording
- Failed payment retry logic

#### 5.2 Pledge Management System
**Priority:** Medium
**User Roles:** Administrators, Finance Team

**Features:**

**Pledge Campaigns**
- Create pledge drives
- Campaign duration settings
- Pledge goals (amount/participants)
- Campaign categories
- Campaign progress dashboard

**Pledge Tracking**
- Member pledge commitments
- Pledge amounts and duration
- Payment schedule (weekly, monthly, annually)
- Pledge fulfillment tracking
- Outstanding pledge balance

**Reminders & Notifications**
- Automated pledge reminders
- Payment due notifications
- Fulfillment milestones
- Campaign updates
- Thank you messages

**Pledge Reporting**
- Pledge vs. actual contributions
- Campaign progress reports
- Individual pledge statements
- Unfulfilled pledges report
- Historical pledge data

**Acceptance Criteria:**
- Support unlimited concurrent campaigns
- Automated reminder scheduling
- Real-time pledge progress updates
- Export pledge data
- Member pledge portal

#### 5.3 Expense Management
**Priority:** High
**User Roles:** Administrators, Finance Team, Department Heads

**Features:**

**Expense Recording**
- Expense categories (Utilities, Salaries, Maintenance, etc.)
- Expense entry form
- Receipt upload
- Vendor management
- Payment methods
- Expense approval workflow

**Budget Management**
- Annual budget creation
- Department budget allocation
- Budget categories
- Budget vs. actual tracking
- Budget variance alerts
- Budget amendments

**Approval Workflow**
- Multi-level approvals
- Approval routing rules
- Email notifications
- Approval history
- Rejected expense handling

**Vendor Management**
- Vendor database
- Payment history
- Outstanding payments
- Vendor contact information

**Acceptance Criteria:**
- Expense entry in under 2 minutes
- Receipt image upload (max 5MB)
- Automated approval routing
- Budget overspend warnings
- Audit trail for all expenses

#### 5.4 Financial Reporting
**Priority:** High
**User Roles:** Administrators, Finance Team, Church Leaders

**Features:**

**Standard Reports**
- Income statement
- Cash flow statement
- Balance sheet
- Budget vs. actual report
- Donor contribution statements
- Department expense reports
- Monthly financial summary

**Custom Reports**
- Report builder interface
- Custom date ranges
- Filter by categories
- Multi-criteria reporting
- Saved report templates

**Analytics Dashboard**
- Giving trends visualization
- Expense trends
- Year-over-year comparisons
- Quarterly analysis
- Forecast projections

**Export & Distribution**
- PDF export
- Excel export
- CSV export
- Scheduled email reports
- Secure report sharing

**Acceptance Criteria:**
- Reports generate in under 15 seconds
- Accurate financial calculations
- GAAP/IFRS compliant reporting
- Role-based report access
- Automated monthly report distribution

---

### 6. Bulk SMS Module (TextTango Integration)

#### 6.1 SMS Management
**Priority:** High
**User Roles:** Administrators, Communication Team

**Features:**

**Bulk Messaging**
- **Recipient Selection**
  - Send to all members
  - Send to specific groups
  - Send to departments
  - Send to attendance filters (e.g., absent 3+ weeks)
  - Send to custom member selections
  - Import phone numbers from CSV

- **Message Composition**
  - Message templates library
  - Template variables (name, event, date)
  - Character counter
  - Preview before send
  - SMS personalization
  - Unicode/emoji support

- **Scheduled Messaging**
  - Schedule for future send
  - Recurring messages
  - Time zone handling
  - Schedule management

- **Delivery Management**
  - Delivery reports
  - Failed message retry
  - Bounced number handling
  - Credit balance monitoring
  - Cost estimation before send

**Message Templates**
- Pre-built templates:
  - Birthday wishes
  - Event reminders
  - Service cancellations
  - Welcome messages
  - Follow-up messages
  - Donation thank you
- Custom template creation
- Template categories
- Template versioning

**Opt-out Management**
- Automated opt-out keyword (STOP)
- Opt-out list maintenance
- Re-opt-in functionality
- Compliance tracking
- Do-not-disturb periods

**SMS Analytics**
- Messages sent counter
- Delivery rate
- Failure analysis
- Cost tracking
- Engagement metrics
- Response rate tracking

**Acceptance Criteria:**
- Send to 1,000+ recipients simultaneously
- Integration with TextTango API (https://app.texttango.com/api/v1)
- Delivery confirmation within 5 minutes
- Automated retry for failed messages
- SMS credit balance alerts

#### 6.2 TextTango API Integration Specifications

**API Endpoint:** `https://app.texttango.com/api/v1`

**Required Features:**
- API authentication
- Send single SMS
- Send bulk SMS
- Check delivery status
- Get account balance
- Retrieve delivery reports
- Handle webhook callbacks

**Error Handling:**
- API timeout handling
- Retry logic for failures
- Error logging
- User-friendly error messages
- Fallback mechanisms

**Acceptance Criteria:**
- 99.9% API call success rate
- Response time under 3 seconds
- Secure API key storage
- Rate limiting compliance
- Webhook signature verification

---

### 7. Equipment Module

#### 7.1 Equipment Inventory Management
**Priority:** Medium
**User Roles:** Administrators, Equipment Managers

**Features:**

**Equipment Registration**
- Equipment name/description
- Equipment category (Audio, Video, Musical, Furniture, etc.)
- Serial number
- Purchase date
- Purchase price
- Vendor/supplier
- Warranty information
- Equipment photo
- Location/storage
- Condition status (New, Good, Fair, Poor, Damaged)

**Check-out/Check-in System**
- Member equipment checkout
- Checkout approval workflow
- Due date tracking
- Overdue notifications
- Return condition logging
- Checkout history

**Maintenance Tracking**
- Maintenance schedule
- Maintenance history
- Service due reminders
- Repair requests
- Maintenance costs
- Service provider records

**Equipment Analytics**
- Equipment utilization reports
- Maintenance cost analysis
- Equipment lifecycle tracking
- Depreciation calculations
- Replacement planning

**Acceptance Criteria:**
- Support 1,000+ equipment items
- Barcode/QR code support
- Automated checkout reminders
- Maintenance calendar integration
- Export inventory reports

---

### 8. Report Module

#### 8.1 Report Center
**Priority:** High
**User Roles:** Administrators, Church Leaders

**Features:**

**Pre-built Reports**
- **Membership Reports**
  - Member directory
  - New members report
  - Inactive members
  - Member demographics
  - Member growth trends

- **Attendance Reports**
  - Weekly attendance summary
  - Monthly attendance comparison
  - Service-wise attendance
  - Absent member report
  - First-time visitors

- **Financial Reports**
  - Income statement
  - Expense report
  - Donor statements
  - Pledge progress
  - Budget vs. actual

- **Ministry Reports**
  - Department participation
  - Ministry team rosters
  - Volunteer hours
  - Event attendance

- **Communication Reports**
  - SMS delivery reports
  - Email campaign analytics
  - Engagement metrics

**Custom Report Builder**
- Drag-and-drop report designer
- Field selection
- Filter criteria
- Grouping and sorting
- Calculated fields
- Chart generation

**Report Distribution**
- Email reports automatically
- Schedule recurring reports
- Share via secure link
- Role-based access
- Report subscriptions

**Data Visualization**
- Interactive charts
- Graphs (line, bar, pie, area)
- Trend analysis
- Comparison views
- Drill-down capabilities

**Acceptance Criteria:**
- Generate any report in under 20 seconds
- Export to PDF, Excel, CSV
- Print-optimized formatting
- Mobile-responsive report viewing
- Save custom report templates

---

### 9. Settings Module

#### 9.1 Church Configuration
**Priority:** High
**User Roles:** Administrators

**Features:**

**Organization Settings**
- Church name
- Logo upload (header, favicon)
- Address and contact information
- Time zone configuration
- Language preferences
- Currency settings

**Service Schedule**
- Service times configuration
- Multiple services per day
- Service types (Sunday, Midweek, Youth)
- Service locations
- Service capacity

**Email Configuration**
- SMTP settings
- Email templates
- From name and address
- Email signature
- Email notification preferences

**SMS Configuration**
- TextTango API credentials
- SMS sender ID
- Default SMS templates
- SMS notification settings
- Credit balance threshold alerts

**Payment Gateway Configuration**
- Paystack API keys (test/live)
- Payment methods enabled
- Currency settings
- Transaction fees handling
- Receipt customization

**Custom Fields Configuration**
- Add custom fields to member profiles
- Field types (text, number, date, dropdown)
- Required/optional settings
- Field ordering
- Field visibility

**Acceptance Criteria:**
- Settings saved instantly
- Validation for all configuration fields
- Test connection buttons for integrations
- Configuration backup/restore
- Audit log for settings changes

#### 9.2 User Management
**Priority:** High
**User Roles:** Administrators

**Features:**

**User Roles & Permissions**
- Pre-defined roles:
  - Super Admin (full access)
  - Administrator
  - Finance Team
  - Ministry Leader
  - Attendance Team
  - Communication Team
  - Read-only User

- Custom role creation
- Granular permissions:
  - Module-level access
  - Feature-level permissions
  - Read/Write/Delete permissions
  - Approval permissions

**User Management**
- Create/edit/deactivate users
- Assign roles to users
- Multiple roles per user
- Password reset
- Force password change
- Account lockout after failed attempts

**Activity Logs**
- User login history
- Action audit trail
- Data modification logs
- Export logs
- Log retention policy

**Acceptance Criteria:**
- Role-based access enforcement
- Secure password requirements
- Session timeout (30 minutes)
- Two-factor authentication support
- User activity reporting

#### 9.3 System Preferences
**Priority:** Medium
**User Roles:** Administrators

**Features:**

**Notification Settings**
- Email notifications toggle
- SMS notifications toggle
- In-app notifications
- Notification frequency
- Notification templates

**Data Management**
- Data retention policies
- Automated data cleanup
- Data export
- Data import (CSV)
- Database backup schedule

**Branding Customization**
- Color scheme (primary, secondary)
- Custom CSS
- Login page customization
- Email template branding
- Print template headers/footers

**Integration Settings**
- Enable/disable integrations
- API credentials management
- Webhook configurations
- Third-party service connections

**Acceptance Criteria:**
- Real-time preview for branding
- Validation for all settings
- Rollback capability
- Settings import/export

---

### 10. Cluster Follow-up Module

#### 10.1 Cluster Management
**Priority:** Medium
**User Roles:** Administrators, Cluster Leaders

**Features:**

**Cluster Configuration**
- Create cluster groups
- Geographic-based clustering
- Department-based clustering
- Custom cluster criteria
- Cluster capacity limits

**Cluster Leadership**
- Assign cluster leaders
- Co-leader support
- Leader contact information
- Leader responsibilities
- Leadership hierarchy

**Member Assignment**
- Assign members to clusters
- Auto-assignment based on criteria
- Bulk member transfer
- Member cluster history
- Cluster balancing

**Cluster Analytics**
- Cluster size distribution
- Cluster growth tracking
- Cluster engagement metrics
- Leader performance

**Acceptance Criteria:**
- Support unlimited clusters
- Automated member distribution
- Leader dashboard access
- Export cluster rosters

#### 10.2 Follow-up System
**Priority:** High
**User Roles:** Administrators, Cluster Leaders

**Features:**

**Follow-up Task Management**
- Create follow-up tasks
- Assign to cluster leaders
- Task categories (Pastoral care, New member, Absent member)
- Task priority levels
- Due date tracking
- Task completion status

**Contact Tracking**
- Log contact attempts
- Contact methods (Visit, Phone, SMS, Email)
- Contact outcomes
- Follow-up notes
- Next action planning

**Prayer Request Management**
- Submit prayer requests
- Categorize requests (Personal, Family, Health, etc.)
- Privacy settings (Public, Private, Leaders only)
- Prayer chain distribution
- Answered prayer tracking
- Prayer updates

**Cluster Communication**
- Cluster-specific messaging
- Leader announcements
- Event notifications
- Cluster meetings scheduling

**Cluster Reporting**
- Follow-up completion rates
- Contact statistics
- Engagement levels
- Member retention by cluster
- Leader activity reports

**Acceptance Criteria:**
- Automated follow-up reminders
- Mobile access for leaders
- Overdue task alerts
- Prayer request notifications
- Export cluster reports

---

### 11. Multi-Branch/Multi-Campus Management

#### 11.1 Overview
**Priority:** High
**User Roles:** Administrators, Church Leaders, Branch Staff

**Description:**
The multi-branch feature enables churches with multiple physical locations (campuses, branches, locations, or centers) to manage all their sites within a single unified system while maintaining appropriate data isolation and autonomy per location. This feature supports churches ranging from 2 locations to 50+ campuses.

**Architecture Approach:**
Hierarchical single-tenant model where each church tenant can manage multiple branches within their organization, with configurable terminology and full branch financial autonomy.

**Key Design Decisions:**
- **Terminology**: Configurable (churches choose: branches/campuses/locations/centers)
- **Default Setup**: Auto-create "Main Campus" for all new churches
- **Finance Model**: Full branch autonomy with separate budgets and P&L statements
- **Communication Scope**: Defaults to current branch context for staff

---

#### 11.2 Branch Configuration & Management
**Priority:** High
**User Roles:** Administrators

**Features:**

**Branch CRUD Operations**
- Create new branch/campus
- Edit branch details
- Deactivate/archive branches
- Delete branches (with data migration safeguards)
- Designate main/headquarters branch
- Branch status management (Active, Inactive, Planning)

**Branch Information**
- Branch name (e.g., "Downtown Campus", "Northside Location")
- Unique slug for URLs
- Physical address (street, city, state, zip, country)
- Contact information (phone, email)
- Capacity (maximum attendance)
- Timezone configuration
- Branch-specific logo (optional)
- Branch color scheme (optional)
- Notes and description

**Terminology Configuration**
- Organization-level setting to choose terminology
- Options: Branches, Campuses, Locations, Centers, Sites
- Terminology applied throughout entire UI
- Pluralization handled automatically
- Can be changed at any time

**Default Branch Setup**
- Every new church auto-creates "Main [Campus/Branch/Location]"
- All existing data assigned to main branch during migration
- Main branch cannot be deleted (can transfer data first)
- Indicator showing which branch is designated as main

**Acceptance Criteria:**
- Create branch in under 1 minute
- Branch slug auto-generated from name
- Duplicate branch name detection within tenant
- Audit trail for all branch changes
- Cannot delete branch with active members/data without confirmation

---

#### 11.3 Branch Selection & Context
**Priority:** High
**User Roles:** All Users

**Features:**

**Global Branch Selector**
- Dropdown in navigation bar showing current branch context
- Lists all branches user has access to
- "All [Branches]" option for consolidated view
- Visual indicator of currently selected branch
- Quick branch switcher (keyboard shortcut support)
- Branch icon/color coding for easy identification

**Session-based Context**
- Selected branch persists across page navigation
- Stored in user session
- Remembers last selected branch on login
- Different users can be in different branch contexts simultaneously

**Branch Context Indicators**
- Page title shows current branch
- Breadcrumbs include branch name
- Dashboard header displays branch
- Color-coded branch indicator
- Clear visual distinction between single-branch and all-branch views

**Permissions-based Access**
- Single-branch users only see their branch
- Multi-branch users see branch selector
- Organization admins see all branches
- Branch access controlled via user_branch_access table

**Acceptance Criteria:**
- Branch selector loads in under 500ms
- Context changes update UI immediately
- No data leakage between branches
- Branch permissions enforced at database level
- Mobile-friendly branch selector

---

#### 11.4 Multi-Branch Data Relationships

**Data Shared Across All Branches:**
- Member core identity (name, contact, family)
- User accounts (staff can access multiple branches)
- Financial giving history (consolidated donor records)
- Membership status
- Family relationships
- Document attachments
- Prayer requests (with privacy settings)

**Data Isolated Per Branch:**
- Branch-specific attendance records
- Local equipment inventory
- Branch-specific events
- Local expenses (with branch_id)
- Branch staff assignments
- Service schedules
- Branch-specific budgets
- Cluster groups (optional branch assignment)

**Configurable Data:**
- Visitors (can be branch-specific or org-wide)
- Small groups/clusters (can be single-branch or cross-branch)
- Follow-up tasks (assignable per branch)
- Announcements (global or branch-specific)

**Acceptance Criteria:**
- Clear documentation of data sharing model
- Database foreign keys enforce relationships
- Queries automatically filter by branch context
- Cross-branch reporting available when needed
- No orphaned records when branch is deleted

---

#### 11.5 Member-Branch Relationships
**Priority:** High
**User Roles:** Administrators, Ministry Leaders

**Features:**

**Primary Branch Assignment**
- Every member assigned to a "home" branch/campus
- Displayed prominently on member profile
- Required field (defaults to main branch)
- Dropdown selector showing all active branches
- Visual indicator of member's home campus

**Branch Transfer Functionality**
- Transfer member to different branch
- Transfer reasons (Moved, Preference, Ministry)
- Transfer effective date
- Transfer approval workflow (optional)
- Bulk transfer for families
- Transfer history log with audit trail

**Multi-Campus Attendance Tracking**
- Members can attend any branch
- Attendance tagged with specific branch attended
- "Home campus" vs. "visiting campus" distinction
- Campus attendance pattern analysis
- Cross-campus attendance frequency

**Campus Attendance History**
- Show all branches member has attended
- Frequency count per campus
- Identify primary attendance location
- Visiting campus statistics
- Attendance loyalty metrics

**Family Transfers**
- Transfer entire family as a unit
- Option to transfer individuals separately
- Family member confirmation
- Preserve family relationships across transfer

**Acceptance Criteria:**
- Member profile shows primary branch clearly
- Transfer completes with full audit trail
- Historical attendance data preserved
- Transfer notifications sent automatically
- Family transfers maintain relationships

---

#### 11.6 Branch-Specific Services & Attendance
**Priority:** High
**User Roles:** Administrators, Attendance Team

**Features:**

**Service Schedule Management**
- Define services per branch
- Service name (e.g., "Sunday 9am", "Midweek Prayer")
- Day of week and time
- Service type (Sunday, Midweek, Special, Youth)
- Service capacity
- Active/inactive status
- Multiple services per branch per day

**Branch-Specific Check-in**
- Check-in kiosk mode shows only current branch's services
- Service selection filtered by branch
- Automatic branch tagging on attendance record
- Guest check-in includes branch assignment
- Cross-campus visitor tracking

**Attendance Recording**
- All attendance records tagged with branch_id
- Service_id links to specific service at specific branch
- Member can attend any branch
- Historical attendance by branch
- Visiting member identification

**Real-time Branch Attendance**
- Live counter for current service at each branch
- Branch-specific capacity tracking
- Branch comparison dashboard
- Service-wise breakdown per branch

**Acceptance Criteria:**
- Services managed independently per branch
- Check-in defaults to user's assigned branch
- Attendance records properly tagged
- Cross-branch attendance supported
- Real-time counters accurate

---

#### 11.7 Branch Financial Management
**Priority:** High
**User Roles:** Administrators, Finance Team, Branch Leaders

**Features:**

**Branch-Specific Budgets**
- Create annual budget per branch
- Budget categories per branch
- Branch expense allocation
- Department budgets within branch
- Budget amendments and revisions

**Income Tracking by Branch**
- All donations tagged with branch_id
- Track where donation was given
- Member giving history shows all branches (consolidated)
- Donor statements include all branches
- Branch-specific giving campaigns

**Expense Management by Branch**
- Expenses assigned to specific branch
- Organization-wide expenses (branch_id = null)
- Branch expense approval workflows
- Vendor management per branch
- Receipt uploads tagged to branch

**Branch P&L Statements**
- Individual profit & loss per branch
- Branch-specific income statement
- Branch-specific cash flow
- Branch expense reports
- Branch budget vs. actual

**Consolidated Financial Reports**
- Organization-wide income statement
- Combined cash flow across all branches
- Total giving and expenses
- Branch contribution to overall finances
- Consolidated budget vs. actual

**Inter-Branch Fund Transfers**
- Transfer funds between branches
- Transfer approval workflow
- Transfer documentation
- Audit trail for transfers
- Financial reconciliation

**Branch Financial Dashboard**
- Branch financial health indicators
- Branch performance metrics
- Branch-to-branch comparison
- Financial trends per branch
- Resource allocation visualization

**Acceptance Criteria:**
- Each branch maintains separate budget
- Consolidated reports accurate
- Branch P&L generates correctly
- Inter-branch transfers tracked
- Donor statements show all branches

---

#### 11.8 Branch-Based Permissions & Access Control
**Priority:** High
**User Roles:** Administrators

**Features:**

**User-Branch Access Management**
- Assign users to single or multiple branches
- Define access level per branch (Viewer, Editor, Manager)
- Primary branch assignment for staff
- Multi-branch access via junction table
- Branch-specific role assignments

**Permission Levels**
1. **Single-Branch User**
   - Assigned to one branch only
   - Can only view/edit data for their branch
   - Cannot see other branches
   - Cannot switch branch context

2. **Multi-Branch User**
   - Access to selected branches
   - Can switch between authorized branches
   - May have different roles per branch
   - Can view cross-branch data for assigned branches

3. **Organization Administrator**
   - Full access to all branches
   - Can create/modify/delete branches
   - Sees consolidated views
   - Manages branch assignments
   - Configures organization-wide settings

**Branch Access Management UI**
- List user's branch assignments
- Add/remove branch access
- Set permissions per branch
- Bulk assignment changes
- Access audit logging

**Permission Enforcement**
- Database-level branch filtering
- Middleware enforces branch access
- Laravel policies check branch permissions
- Scoped queries based on user access
- API endpoints respect branch permissions

**Acceptance Criteria:**
- Permissions enforced consistently
- No data leakage between branches
- Branch access changes logged
- Performance not degraded by permission checks
- Clear error messages for access denied

---

#### 11.9 Branch-Specific Communication
**Priority:** High
**User Roles:** Communication Team, Branch Leaders

**Features:**

**Branch-Filtered Recipients**
- Bulk SMS defaults to current branch context
- Branch-level staff see only their branch members
- Org admins explicitly select branches
- "All [Branches]" option available
- Multi-branch selection supported

**Branch-Specific Message Templates**
- Global templates (all branches)
- Branch-specific templates
- Template categorization
- Branch-customized messaging

**Communication Scope Defaults**
- Branch staff → their branch only
- Multi-branch staff → current branch context
- Org admins → explicit selection required
- Clear recipient count shown before sending

**Branch Communication Analytics**
- SMS delivery by branch
- Email engagement by branch
- Communication costs per branch
- Branch comparison metrics

**Acceptance Criteria:**
- Recipients correctly filtered by branch
- Default scope prevents accidental org-wide sends
- Clear visual indicator of scope
- Branch selection required for admins
- Delivery reports segmented by branch

---

#### 11.10 Branch Equipment Management
**Priority:** Medium
**User Roles:** Equipment Managers, Branch Staff

**Features:**

**Branch Equipment Assignment**
- All equipment assigned to specific branch
- Equipment location tracking
- Branch equipment inventory
- Branch-specific equipment categories

**Cross-Branch Equipment Transfers**
- Transfer equipment between branches
- Transfer approval workflow
- Transfer documentation
- Equipment transfer history
- Automatic location updates

**Branch Equipment Reports**
- Equipment inventory per branch
- Equipment utilization by branch
- Maintenance costs per branch
- Equipment condition by branch
- Cross-branch equipment sharing tracking

**Acceptance Criteria:**
- Equipment clearly assigned to branch
- Transfers properly documented
- Branch inventory accurate
- Transfer history maintained
- Reports filter by branch

---

#### 11.11 Branch Reporting & Analytics
**Priority:** High
**User Roles:** Administrators, Church Leaders

**Features:**

**Branch Comparison Reports**
- Side-by-side branch metrics
- Attendance comparison across branches
- Financial performance by branch
- Growth rates per branch
- Member distribution across branches

**Branch-Specific Reports**
- Individual branch dashboards
- Branch performance metrics
- Branch trends over time
- Branch demographics
- Branch ministry participation

**Consolidated Organization Reports**
- Total organization metrics
- Combined attendance across all branches
- Consolidated financial statements
- Organization-wide growth trends
- Resource allocation across branches

**Branch Selector on All Reports**
- Every report includes branch filter
- Options: Specific branch, multiple branches, or all branches
- Branch comparison mode
- Saved report preferences
- Export includes branch information

**Branch Analytics Dashboard**
- Branch performance scorecard
- Branch health indicators
- Branch growth tracking
- Resource needs by branch
- Branch ranking and comparison

**Acceptance Criteria:**
- All reports support branch filtering
- Comparison reports accurate
- Consolidated reports include all branches
- Performance not degraded with many branches
- Export includes branch context

---

#### 11.12 Branch Settings & Configuration
**Priority:** Medium
**User Roles:** Administrators, Branch Leaders

**Features:**

**Organization-Level Settings** (Apply to all branches)
- Church name and branding
- Payment gateway credentials
- SMS API credentials
- Email configuration
- User roles and permissions
- Branch terminology preference

**Branch-Level Settings** (Unique per branch)
- Branch service times and schedule
- Branch capacity and facilities
- Branch-specific announcements
- Branch contact information
- Branch-specific ministry teams
- Branch operating hours

**Branch Customization**
- Optional branch logo
- Branch color scheme
- Branch-specific email signatures
- Branch social media links
- Branch website URL

**Settings Inheritance**
- Branch inherits org settings by default
- Option to override specific settings per branch
- Clear indication of inherited vs. custom settings
- Reset to organization defaults option

**Acceptance Criteria:**
- Clear distinction between org and branch settings
- Settings changes save immediately
- Inheritance model works correctly
- Branch customizations persist
- Settings export/import supported

---

#### 11.13 Branch Migration & Data Management
**Priority:** High
**User Roles:** Administrators

**Features:**

**Single-to-Multi-Branch Migration**
- Automatic creation of "Main [Campus]" for existing churches
- Bulk assignment of existing data to main branch
- Data integrity validation
- Migration rollback capability
- Migration completion report

**Branch Data Migration**
- Move members between branches (bulk)
- Migrate equipment to different branch
- Transfer financial data
- Reassign services
- Update historical records

**Branch Deactivation**
- Soft delete (deactivate) branches
- Prevent deletion with active data
- Data migration wizard before deletion
- Archive branch data
- Reactivate deactivated branches

**Data Integrity Checks**
- Ensure all records have branch assignment
- Validate branch foreign keys
- Check for orphaned records
- Generate data integrity reports
- Automated cleanup scripts

**Acceptance Criteria:**
- Migration completes without data loss
- All existing data assigned to branches
- Branch deletion prevented if data exists
- Data integrity maintained
- Migration audit trail complete

---

#### 11.14 Branch-Specific Visitors & Follow-up
**Priority:** Medium
**User Roles:** Welcome Team, Follow-up Team

**Features:**

**Visitor Branch Assignment**
- Visitors automatically tagged with branch visited
- Multi-campus visitor tracking
- Identify visitors who've attended multiple branches
- Branch-specific visitor follow-up teams

**Branch Follow-up Workflows**
- Follow-up tasks assigned to branch team
- Branch-specific follow-up templates
- Cross-branch visitor coordination
- Visitor conversion tracking by branch

**Branch Visitor Analytics**
- Visitor counts per branch
- Visitor sources by branch
- Conversion rates per branch
- Branch visitor trends

**Acceptance Criteria:**
- Visitors properly assigned to branches
- Follow-up routed to correct branch team
- Multi-campus visitors identified
- Analytics segmented by branch

---

#### 11.15 Technical Implementation Specifications

**Database Schema Additions:**

```sql
-- Branches table
branches
  - id (primary key)
  - tenant_id (foreign key)
  - name (varchar 100)
  - slug (varchar 100, unique per tenant)
  - is_main (boolean)
  - address, city, state, zip, country
  - phone, email
  - capacity (integer)
  - timezone (varchar 50)
  - status (enum: active, inactive, planning)
  - logo_url (varchar 255, nullable)
  - color_primary (varchar 7, nullable)
  - settings (json)
  - created_at, updated_at

-- Services table (per branch)
services
  - id (primary key)
  - tenant_id (foreign key)
  - branch_id (foreign key)
  - name (varchar 100)
  - day_of_week (tinyint 0-6)
  - time (time)
  - service_type (enum: sunday, midweek, special, youth)
  - capacity (integer)
  - is_active (boolean)
  - created_at, updated_at

-- User-branch access (junction table)
user_branch_access
  - id (primary key)
  - user_id (foreign key)
  - branch_id (foreign key)
  - role (varchar 50, nullable)
  - can_manage (boolean)
  - created_at

-- Modified existing tables (add branch_id foreign key)
members
  - primary_branch_id (foreign key to branches)

attendance
  - branch_id (foreign key to branches)
  - service_id (foreign key to services)

visitors
  - branch_id (foreign key to branches)

equipment
  - branch_id (foreign key to branches)

expenses
  - branch_id (foreign key to branches, nullable for org-wide)

donations
  - branch_id (foreign key to branches, nullable)

clusters
  - branch_id (foreign key to branches, nullable for cross-branch)

-- Tenant settings addition
tenants
  - settings (json) includes:
    - branch_type_term (default: "Campus")
    - multi_branch_enabled (boolean)
```

**Performance Optimization:**
- Composite indexes: (tenant_id, branch_id, date) on attendance
- Composite indexes: (tenant_id, branch_id) on all branch-related tables
- Branch context caching in session
- Query scopes for automatic branch filtering
- Eager loading of branch relationships

**API Endpoints:**
- `GET /api/branches` - List all branches
- `POST /api/branches` - Create branch
- `GET /api/branches/{id}` - Get branch details
- `PUT /api/branches/{id}` - Update branch
- `DELETE /api/branches/{id}` - Delete branch (with safeguards)
- `POST /api/branches/{id}/transfer-members` - Bulk member transfer
- `GET /api/users/{id}/branches` - Get user's branch access
- `POST /api/users/{id}/branches` - Assign branch access

**Acceptance Criteria:**
- All database migrations run successfully
- Indexes improve query performance
- API endpoints properly secured
- Branch context enforced in all queries
- Foreign key constraints prevent orphaned data

---

#### 11.16 Branch Feature Acceptance Criteria Summary

**Functional Requirements:**
- ✅ Churches can configure custom terminology (branches/campuses/locations)
- ✅ New churches auto-create "Main [Campus]" with all data assigned
- ✅ Users can create, edit, deactivate, and manage branches
- ✅ Branch selector available to multi-branch users
- ✅ Branch context persists across sessions
- ✅ Members assigned to primary branch with transfer capability
- ✅ Attendance tracked per branch with cross-branch support
- ✅ Each branch maintains separate budget and P&L
- ✅ Consolidated financial reporting across all branches
- ✅ Branch-specific permissions enforced
- ✅ Communication defaults to branch context
- ✅ Equipment assigned and transferred between branches
- ✅ Comprehensive branch reporting and analytics
- ✅ Data migration for single-to-multi-branch churches

**Technical Requirements:**
- ✅ Database schema supports unlimited branches per tenant
- ✅ Performance remains optimal with 20+ branches
- ✅ Branch filtering adds < 50ms query overhead
- ✅ All branch operations logged in audit trail
- ✅ Zero data loss during branch operations
- ✅ Mobile-responsive branch selector
- ✅ API endpoints for branch management

**User Experience:**
- ✅ Branch selector intuitive and accessible
- ✅ Clear visual indication of current branch context
- ✅ "All Branches" consolidated view available
- ✅ Branch transfers maintain complete history
- ✅ Reports offer both individual and consolidated views
- ✅ Permission model clear and manageable
- ✅ Error messages guide users appropriately

---

## Technical Requirements

### 6.1 Performance Requirements

**Response Time:**
- Page load: < 2 seconds
- API responses: < 500ms
- Database queries: < 100ms
- Search results: < 1 second
- Report generation: < 20 seconds

**Scalability:**
- Support 100+ concurrent tenants
- Support 10,000+ members per tenant
- Handle 1,000+ simultaneous users
- Process 10,000+ transactions/day
- Store 1TB+ of data

**Availability:**
- 99.9% uptime SLA
- Scheduled maintenance windows
- Automated failover
- Database replication
- Load balancing

**Acceptance Criteria:**
- Load testing for 1,000 concurrent users
- Stress testing for peak loads
- Performance monitoring dashboard
- Automated alerts for degradation

### 6.2 Browser & Device Compatibility

**Supported Browsers:**
- Chrome 100+ (desktop & mobile)
- Firefox 100+ (desktop & mobile)
- Safari 15+ (desktop & mobile)
- Edge 100+

**Device Support:**
- Desktop (1920x1080 and above)
- Laptop (1366x768 and above)
- Tablet (768x1024)
- Mobile (375x667 and above)

**Progressive Web App (PWA):**
- Installable on mobile devices
- Offline capability for critical features
- Push notifications
- Home screen icon

**Acceptance Criteria:**
- Cross-browser testing
- Responsive design verification
- Accessibility compliance (WCAG 2.1 AA)
- Touch-friendly interfaces

### 6.3 Database Schema Design

**Multi-Tenancy Schema:**
- Tenant isolation: Separate database per tenant
- Shared tables: Users, tenants, subscriptions
- Tenant-specific tables: Members, donations, attendance, etc.

**Key Tables:**
- `tenants` - Church organizations
- `users` - System users with roles
- **`branches`** - Church branches/campuses/locations *(Multi-Branch)*
- **`services`** - Service schedules per branch *(Multi-Branch)*
- **`user_branch_access`** - Multi-branch user permissions *(Multi-Branch)*
- `members` - Church members (with primary_branch_id)
- `visitors` - Guest visitors (with branch_id)
- `attendance` - Attendance records (with branch_id and service_id)
- `donations` - Financial contributions (with branch_id)
- `expenses` - Church expenses (with branch_id)
- `pledges` - Member pledges
- `equipment` - Equipment inventory (with branch_id)
- `clusters` - Cluster groups (with optional branch_id)
- `follow_ups` - Follow-up tasks
- `prayer_requests` - Prayer requests
- `sms_logs` - SMS delivery logs
- `activity_logs` - Audit trail

**Database Optimization:**
- Indexed foreign keys
- Composite indexes for common queries
- Full-text search indexes
- Query result caching
- Database connection pooling

**Acceptance Criteria:**
- Normalized database design (3NF)
- Referential integrity enforcement
- Automated backup every 24 hours
- Point-in-time recovery capability
- Database migration versioning

---

## Security & Compliance

### 7.1 Authentication & Authorization

**Authentication System (Laravel Fortify):**
- Email/password login
- "Remember me" functionality
- Password reset via email
- Email verification for new users
- Account lockout after 5 failed attempts
- Session timeout after 30 minutes of inactivity

**Two-Factor Authentication (2FA):**
- Optional 2FA enrollment
- TOTP-based (Google Authenticator, Authy)
- QR code setup
- Recovery codes
- 2FA enforcement for admin roles

**Role-Based Access Control (RBAC):**
- Granular permissions per module
- Role inheritance
- Permission caching
- Dynamic permission checking
- Audit trail for permission changes

**Acceptance Criteria:**
- Password complexity requirements (min 8 chars, uppercase, number, symbol)
- Secure password hashing (bcrypt)
- CSRF protection on all forms
- Rate limiting on authentication endpoints
- Session hijacking prevention

### 7.2 Data Protection & Privacy

**Data Encryption:**
- SSL/TLS for all connections (HTTPS)
- Encryption at rest for sensitive data
- Encrypted database backups
- Secure API communication
- PCI-DSS compliance for payment data

**Privacy Controls:**
- GDPR compliance
- Data retention policies
- Right to be forgotten (data deletion)
- Data export for members
- Consent management
- Privacy policy acceptance

**Data Backup & Recovery:**
- Automated daily backups
- Off-site backup storage
- Backup encryption
- 30-day backup retention
- Disaster recovery plan
- Backup restoration testing

**Acceptance Criteria:**
- All PII (Personally Identifiable Information) encrypted
- GDPR data processing agreement
- Automated backup verification
- Recovery time objective (RTO): 4 hours
- Recovery point objective (RPO): 24 hours

### 7.3 Application Security

**Security Measures:**
- **Input Validation:**
  - Server-side validation
  - Client-side validation
  - Whitelist input filtering
  - File upload restrictions

- **XSS Protection:**
  - Output escaping
  - Content Security Policy (CSP)
  - HTTPOnly cookies
  - Sanitized user inputs

- **CSRF Protection:**
  - CSRF tokens on all forms
  - Same-site cookie attribute
  - Referer validation

- **SQL Injection Prevention:**
  - Parameterized queries
  - ORM usage (Eloquent)
  - Prepared statements
  - Input sanitization

- **API Security:**
  - API authentication (Bearer tokens)
  - Rate limiting (60 requests/minute)
  - API versioning
  - Request/response encryption

**Vulnerability Management:**
- Regular security audits
- Dependency vulnerability scanning
- Automated security testing
- Penetration testing (annual)
- Security patch management

**Acceptance Criteria:**
- OWASP Top 10 compliance
- Security headers (X-Frame-Options, X-Content-Type-Options)
- Regular security scans (monthly)
- Vulnerability disclosure policy
- Security incident response plan

### 7.4 Audit & Compliance

**Activity Logging:**
- User login/logout
- Data modifications (create, update, delete)
- Permission changes
- Failed authentication attempts
- API access logs
- Financial transactions
- Export activities

**Audit Trail Requirements:**
- Who (user ID, name)
- What (action performed)
- When (timestamp)
- Where (IP address, location)
- Why (reason, if applicable)
- Before/after values for data changes

**Compliance Standards:**
- GDPR (General Data Protection Regulation)
- PCI-DSS (Payment Card Industry Data Security Standard)
- SOC 2 Type II (future consideration)
- Data residency requirements

**Acceptance Criteria:**
- Tamper-proof audit logs
- Log retention for 7 years
- Log export functionality
- Real-time audit alerts for critical actions
- Compliance reporting dashboard

---

## Integration Requirements

### 8.1 Payment Gateway Integration (Paystack)

**Integration Scope:**
- Accept online donations
- Process recurring payments
- Handle payment webhooks
- Refund processing
- Payment verification

**Required Features:**
- Paystack Inline SDK integration
- Secure API key management
- Transaction logging
- Failed payment handling
- Payment receipt generation

**Webhooks to Handle:**
- `charge.success` - Payment successful
- `charge.failed` - Payment failed
- `subscription.create` - Recurring payment setup
- `subscription.disable` - Recurring payment cancelled

**Acceptance Criteria:**
- PCI-DSS Level 1 compliance
- Test mode for development
- Live mode for production
- Transaction reconciliation
- Automated webhook retry logic

### 8.2 SMS Gateway Integration (TextTango)

**API Endpoint:** `https://app.texttango.com/api/v1`

**Integration Scope:**
- Send single SMS
- Send bulk SMS
- Check delivery status
- Retrieve account balance
- Handle delivery reports

**API Methods Required:**
- `POST /send` - Send SMS
- `POST /send-bulk` - Send bulk SMS
- `GET /delivery-report/{id}` - Get delivery status
- `GET /balance` - Check account balance

**Webhook Integration:**
- Delivery receipt webhooks
- Failed message notifications

**Acceptance Criteria:**
- API authentication via API key
- Rate limiting compliance
- Error handling and retry logic
- SMS credit balance monitoring
- Delivery report parsing

### 8.3 Email Service Integration

**Email Provider Options:**
- SMTP (custom mail server)
- SendGrid (recommended)
- Mailgun
- Amazon SES

**Email Types:**
- Transactional emails (password reset, receipts)
- Notification emails (reminders, alerts)
- Bulk emails (newsletters, announcements)

**Features Required:**
- Email templating
- Personalization
- Bounce handling
- Unsubscribe management
- Email analytics (open rate, click rate)

**Acceptance Criteria:**
- Email delivery rate > 95%
- Email queuing for bulk sends
- HTML and plain-text versions
- SPF and DKIM authentication
- Complaint handling

### 8.4 Cloud Storage Integration

**Storage Provider Options:**
- AWS S3
- DigitalOcean Spaces
- Google Cloud Storage
- Local storage (development)

**Storage Use Cases:**
- Member profile photos
- Document attachments
- Equipment photos
- Receipt images
- Database backups
- Export files

**Features Required:**
- Secure file upload
- File type validation
- File size limits
- CDN integration
- Signed URLs for private files

**Acceptance Criteria:**
- 99.99% file availability
- Automatic file compression for images
- Virus scanning for uploads
- File lifecycle policies
- Backup redundancy

### 8.5 API Architecture

**RESTful API Design:**
- Resource-based endpoints
- HTTP verb usage (GET, POST, PUT, DELETE)
- JSON request/response format
- Pagination for large datasets
- Versioned endpoints (`/api/v1/`)

**Authentication:**
- Bearer token authentication
- API key authentication (for integrations)
- OAuth 2.0 (future consideration)

**Rate Limiting:**
- 60 requests per minute per user
- 1000 requests per hour per tenant
- Rate limit headers in response
- 429 Too Many Requests response

**API Documentation:**
- OpenAPI (Swagger) specification
- Interactive API documentation
- Code examples
- Postman collection

**Acceptance Criteria:**
- Consistent error responses
- API versioning strategy
- Deprecation notices
- Automated API testing
- API usage analytics

---

## Performance Requirements

### 9.1 Application Performance

**Page Load Times:**
- Dashboard: < 2 seconds
- Member list (100 records): < 1.5 seconds
- Member profile: < 1 second
- Reports: < 10 seconds (simple), < 20 seconds (complex)
- Search results: < 1 second

**Database Performance:**
- Query execution: < 100ms (95th percentile)
- Connection pooling
- Query result caching
- Eager loading to prevent N+1 queries

**Frontend Performance:**
- First Contentful Paint (FCP): < 1.5 seconds
- Time to Interactive (TTI): < 3 seconds
- Lighthouse performance score: > 90
- Asset minification and bundling
- Lazy loading for images
- Code splitting

**API Performance:**
- Response time: < 500ms
- Throughput: 1000 requests/second
- Concurrent connections: 10,000+

**Acceptance Criteria:**
- Performance monitoring (New Relic, Laravel Telescope)
- Automated performance testing
- Performance budgets
- CDN for static assets
- Database query optimization

### 9.2 Scalability

**Horizontal Scaling:**
- Stateless application design
- Load balancer support
- Session storage in Redis
- Queue workers scaling

**Database Scaling:**
- Read replicas for reporting
- Database sharding (future)
- Connection pooling
- Query caching

**Tenant Scaling:**
- Support 100+ tenants initially
- Scale to 1,000+ tenants
- Tenant isolation
- Resource quotas per tenant

**Acceptance Criteria:**
- Auto-scaling based on load
- Zero-downtime deployments
- Database migration strategy
- Tenant onboarding automation

### 9.3 Reliability & Availability

**Uptime SLA:**
- 99.9% uptime (43 minutes downtime/month)
- Scheduled maintenance windows
- Downtime notifications

**Fault Tolerance:**
- Graceful error handling
- Circuit breaker pattern for external services
- Retry logic with exponential backoff
- Fallback mechanisms

**Monitoring & Alerting:**
- Application performance monitoring (APM)
- Error tracking (Sentry, Bugsnag)
- Uptime monitoring
- Server resource monitoring
- Automated alerts (email, SMS, Slack)

**Acceptance Criteria:**
- Health check endpoints
- Automated incident response
- Post-mortem documentation
- Mean Time To Recovery (MTTR) < 1 hour

---

## Success Metrics

### 10.1 Product Metrics

**Adoption Metrics:**
- Number of tenants onboarded
- Active users per tenant
- User login frequency
- Feature adoption rates
- Mobile vs. desktop usage

**Engagement Metrics:**
- Daily/Weekly/Monthly Active Users (DAU/WAU/MAU)
- Average session duration
- Pages per session
- Feature usage frequency
- Member self-service adoption

**Retention Metrics:**
- Tenant retention rate (95%+ target)
- User retention rate
- Churn rate
- Reactivation rate

**Performance Metrics:**
- Average page load time
- API response time
- Error rate (< 0.1% target)
- Uptime percentage

**Acceptance Criteria:**
- Analytics dashboard
- Monthly metrics reporting
- User feedback collection
- NPS (Net Promoter Score) tracking

### 10.2 Business Metrics

**Revenue Metrics (if SaaS):**
- Monthly Recurring Revenue (MRR)
- Annual Recurring Revenue (ARR)
- Average Revenue Per User (ARPU)
- Customer Acquisition Cost (CAC)
- Customer Lifetime Value (LTV)

**Growth Metrics:**
- New tenant sign-ups per month
- Conversion rate (trial to paid)
- Expansion revenue
- Upgrade rate

**Operational Metrics:**
- Support ticket volume
- Average resolution time
- Customer satisfaction score
- Feature request volume

**Acceptance Criteria:**
- Business intelligence dashboard
- Automated financial reporting
- Subscription management
- Payment processing automation

---

## Development Roadmap

### 11.1 Phase 1: Foundation (Months 1-3)

**Objectives:**
- Establish multi-tenant infrastructure
- Implement core authentication and authorization
- Build basic member management
- Set up CI/CD pipeline

**Deliverables:**
- [x] Multi-tenant database architecture
- [x] Laravel 12 application setup
- [x] User authentication (Fortify)
- [x] Role-based access control
- [ ] Member CRUD operations
- [ ] Basic dashboard
- [ ] Responsive layout (Flux UI)
- [ ] Deployment pipeline

**Acceptance Criteria:**
- All automated tests passing
- Code coverage > 80%
- Security audit passed
- Performance benchmarks met

### 11.2 Phase 2: Core Features (Months 4-6)

**Objectives:**
- Complete member management
- Implement visitor tracking
- Build attendance system
- Develop financial management basics

**Deliverables:**
- [ ] Advanced member profiles
- [ ] Visitor registration and follow-up
- [ ] Digital check-in system
- [ ] Attendance reporting
- [ ] Donation processing (Paystack integration)
- [ ] Expense management
- [ ] Basic financial reports
- [ ] Bulk SMS (TextTango integration)

**Acceptance Criteria:**
- Feature parity with competitor systems
- User acceptance testing completed
- Integration testing with external APIs
- Documentation completed

### 11.3 Phase 3: Advanced Features (Months 7-9)

**Objectives:**
- Implement pledge management
- Build equipment module
- Develop cluster follow-up system
- **Implement multi-branch/multi-campus management**
- Enhance reporting capabilities

**Deliverables:**
- [ ] Pledge campaigns and tracking
- [ ] Equipment inventory management
- [ ] Cluster management
- [ ] Follow-up task system
- [ ] Prayer request management
- [ ] **Multi-branch database schema and models**
- [ ] **Branch CRUD operations and management**
- [ ] **Branch selector and context management**
- [ ] **Branch-based permissions and access control**
- [ ] **Branch financial management (separate P&L per branch)**
- [ ] **Branch-specific reporting and analytics**
- [ ] **Member-branch assignments and transfers**
- [ ] Advanced report builder
- [ ] Data visualization dashboard
- [ ] Mobile PWA optimization

**Acceptance Criteria:**
- Beta testing with 10 churches (including multi-campus churches)
- Performance optimization completed
- Mobile app functionality verified
- Accessibility compliance (WCAG 2.1 AA)
- **Multi-branch support for 20+ campuses per church**
- **Branch filtering adds < 50ms query overhead**
- **Zero data loss during single-to-multi-branch migration**

### 11.4 Phase 4: Polish & Launch (Months 10-12)

**Objectives:**
- Refine user experience
- Complete all integrations
- Conduct thorough testing
- Prepare for production launch

**Deliverables:**
- [ ] UI/UX refinements
- [ ] Email service integration
- [ ] Cloud storage integration
- [ ] Two-factor authentication
- [ ] Comprehensive audit logging
- [ ] Admin training materials
- [ ] User documentation
- [ ] Marketing website

**Acceptance Criteria:**
- Load testing (1,000+ concurrent users)
- Security penetration testing
- Compliance verification (GDPR, PCI-DSS)
- Production launch readiness

### 11.5 Post-Launch (Ongoing)

**Objectives:**
- Support and maintain the system
- Implement user-requested features
- Optimize performance
- Expand integrations

**Deliverables:**
- [ ] Ongoing bug fixes
- [ ] Feature enhancements
- [ ] Performance monitoring
- [ ] Customer support system
- [ ] Regular security updates
- [ ] Quarterly feature releases

**Acceptance Criteria:**
- < 24 hour response time for critical issues
- Monthly feature updates
- Quarterly security audits
- User satisfaction > 4.5/5

---

## Appendices

### A. Glossary

**Terms:**
- **Tenant:** A church organization using the system
- **Member:** A registered church member
- **Visitor:** A first-time or occasional guest
- **Cluster:** A small group for pastoral care and follow-up
- **Pledge:** A financial commitment over time
- **Multi-tenancy:** Architecture supporting multiple independent customers (church-to-church isolation)
- **Branch/Campus/Location:** A physical church location within a single organization (configurable terminology)
- **Primary Branch:** A member's home campus where they regularly attend
- **Branch Transfer:** Moving a member's primary assignment from one campus to another
- **Consolidated View:** Dashboard or report showing data across all branches
- **Branch Context:** The currently selected branch filter applied to the user's view
- **Multi-Branch User:** A staff member with access to multiple campuses
- **Organization Admin:** A user with full access to all branches and settings

### B. Technical Dependencies

**Core Dependencies:**
- PHP 8.3.29
- Laravel 12
- Livewire 3
- Flux UI (Free Edition)
- MySQL 8.0+
- Tailwind CSS v4
- Alpine.js
- Laravel Fortify
- Pest (testing)
- Laravel Pint (code formatting)

**External Services:**
- Paystack (payments)
- TextTango (SMS)
- Email provider (SendGrid/SMTP)
- Cloud storage (AWS S3/DO Spaces)

### C. Competitive Analysis

**Competitors:**
- Breeze ChMS
- Planning Center
- Church Community Builder
- Elvanto
- Realm

**Differentiators:**
- Modern tech stack (Laravel 12, Livewire 3)
- African market focus (Paystack, TextTango)
- Affordable pricing for small churches
- Comprehensive cluster follow-up system
- Beautiful, intuitive UI (Flux)

### D. Risk Assessment

**Technical Risks:**
- Third-party API downtime (Mitigation: Fallback mechanisms)
- Database scaling challenges (Mitigation: Sharding strategy)
- Security vulnerabilities (Mitigation: Regular audits)

**Business Risks:**
- Low adoption rate (Mitigation: Beta testing, user feedback)
- High churn (Mitigation: Excellent support, training)
- Competitor pressure (Mitigation: Unique features, pricing)

**Operational Risks:**
- Inadequate support capacity (Mitigation: Scalable support system)
- Data loss (Mitigation: Robust backup strategy)
- Compliance violations (Mitigation: Legal review, compliance framework)

### E. Support & Maintenance Plan

**Support Tiers:**
- **Email Support:** 24-hour response time
- **Chat Support:** Business hours
- **Priority Support:** < 4 hour response (premium plans)
- **Emergency Support:** 24/7 for critical issues

**Maintenance Windows:**
- Scheduled: Sundays 2 AM - 4 AM (local time)
- Emergency: As needed with advance notice

**Update Cadence:**
- Security patches: As needed (immediate)
- Bug fixes: Weekly
- Minor features: Monthly
- Major releases: Quarterly

---

## Document Control

**Revision History:**

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2025-12-30 | System Architect | Initial PRD creation |
| 1.1 | 2025-12-30 | System Architect | Added comprehensive multi-branch/multi-campus management feature (Section 11). Updated Dashboard, Members, Database Schema, and Development Roadmap sections to include branch support. |

**Approvals:**

| Role | Name | Signature | Date |
|------|------|-----------|------|
| Product Owner | [Pending] | | |
| Technical Lead | [Pending] | | |
| Stakeholder | [Pending] | | |

**Next Review Date:** 2026-03-30

---

**End of Product Requirements Document**
