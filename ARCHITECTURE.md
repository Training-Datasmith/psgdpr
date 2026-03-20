# Architecture: psgdpr

## Purpose

A PrestaShop module implementing GDPR (General Data Protection Regulation) compliance features: customer data export, anonymisation/deletion, consent management, and an audit log of data-related requests.

## Directory Structure

```
psgdpr.php                           - Module bootstrap; hook registration
controllers/front/
  gdpr.php                           - Front controller: customer GDPR request portal
  Export_Customer_Data.php           - Front controller: initiates data export
  Front_Ajax_Gdpr.php                - Front controller: AJAX responses for customer portal
src/
  Controller/Admin/
    Customer_Controller.php          - Admin CRUD for customer data requests
    Download_Customer_Invoices_Controller.php
  Entity/
    Psgdpr_Consent.php               - ObjectModel for consent records
    Psgdpr_Consent_Lang.php          - Translatable consent text
    Psgdpr_Log.php                   - Audit log entry ObjectModel
  Exception/                         - Domain-specific exception hierarchy
  Repository/                        - Repositories for Customer, Order, Cart, Consent, Log
  Service/
    BackResponder/                   - Strategy pattern: responds to back-office GDPR requests
    Export/                          - Strategy pattern: exports data as CSV, JSON, or PDF
    FrontResponder/                  - Strategy pattern: responds to front-office GDPR requests
    Customer_Service.php             - Orchestrates customer anonymisation and deletion
    Export_Service.php               - Orchestrates data export
    Logger_Service.php               - Writes audit log entries
    Pdf_Generator_Service.php        - Generates PDF export of customer data
views/
  templates/front/                   - Customer-facing GDPR portal templates
  templates/admin/                   - Admin GDPR management templates
upgrade/                             - SQL/PHP migration scripts
```

## Key Design Decisions

- **Strategy pattern for export/response**: Export format (CSV/JSON/PDF) and request response strategy are selected at runtime via Factory + Context classes, making it trivial to add new formats.
- **Audit logging**: Every data access, export, and deletion is logged to `Psgdpr_Log` to satisfy GDPR accountability requirements.
- **Domain exception hierarchy**: All exceptions extend `Psgdpr_Module_Exception`, enabling fine-grained catch clauses and error categorisation.
- **Symfony controllers for admin**: Uses PrestaShop's Symfony integration for admin-side controllers.

## Extension Points

- Implement `Export_Interface` to add a new export format (e.g., XML).
- Implement `Back_Responder_Interface` or `Front_Responder_Interface` to add custom request-handling strategies.
- Hook into `actionBeforeCustomerData*` hooks to exclude or enrich the exported data.

## Dependency Flow

```
psgdpr (Module)
  └─> Front-office portal
        └─> Front_Ajax_Gdpr → FrontResponder strategy
        └─> Export_Customer_Data → Export_Service → Export strategy (CSV/JSON/PDF)
  └─> Admin
        └─> Customer_Controller → Customer_Service (anonymise/delete)
                                → Logger_Service (audit log)
```
