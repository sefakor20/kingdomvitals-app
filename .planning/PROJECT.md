# KingdomVitals

## What This Is

A multi-tenant SaaS platform for church management. Churches (tenants) can manage members, track attendance, process donations, communicate via SMS, and generate reports. Each tenant gets an isolated database with plan-based feature access and quotas.

## Core Value

Churches can efficiently manage their congregation and finances from a single, intuitive platform.

## Requirements

### Validated

<!-- Shipped and confirmed valuable. -->

- ✓ Multi-tenant architecture with domain-based isolation
- ✓ Member management (CRUD, import/export, search)
- ✓ Visitor tracking with follow-up workflows
- ✓ Attendance tracking with QR check-in
- ✓ Donation/giving system with Paystack integration
- ✓ Expense tracking and budgets
- ✓ SMS communications via TextTango
- ✓ Branch/cluster organization structure
- ✓ Plan-based access control with quotas
- ✓ Super admin panel for tenant management
- ✓ Reporting and analytics

### Active

<!-- Current scope. Building toward these. -->

- [ ] Collapsible sidebar navigation with grouped sub-menus

### Out of Scope

<!-- Explicit boundaries. Includes reasoning to prevent re-adding. -->

(None defined yet)

## Context

- Laravel 12 with Livewire 4 and Flux UI Free for the frontend
- Stancl Tenancy for multi-tenant architecture
- Existing sidebar has 28 potential navigation items across 5 groups
- The "Branch" group alone has 13 items, "Financial" has 10

## Constraints

- **UI Framework**: Flux UI Free (v2) — must use available components
- **State Persistence**: Navigation collapse state should persist across page loads

## Key Decisions

<!-- Decisions that constrain future work. Add throughout project lifecycle. -->

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Use Flux navlist.group expandable | Built-in support, consistent UX | — Pending |

---
*Last updated: 2026-02-03 after project initialization*
