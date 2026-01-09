# Pharmacy Database Coding Standards and Rules

## 1. PHP Coding Standards

### File Structure
- Start PHP files with `<?php` and `session_start()` if sessions are used
- Include database configuration with `define('DB_ACCESS', true); require_once '../database/db_config.php';`
- Use `require_once` for file includes
- End PHP files without closing `?>` tag to prevent whitespace issues

### Naming Conventions
- **Variables**: camelCase (e.g., `$firstName`, `$userRole`)
- **Functions**: camelCase (e.g., `sendError()`, `generateAccountId()`)
- **Constants**: UPPER_CASE with underscores (e.g., `DB_HOST`, `PASSWORD_BCRYPT`)
- **Database columns**: camelCase (e.g., `accountId`, `firstName`)
- **Table names**: lowercase plural (e.g., `accounts`, `inventory`)

### Code Style
- Use 4 spaces for indentation (no tabs)
- Use single quotes for strings unless interpolation is needed
- Use double quotes only when necessary (variable interpolation)
- Add comments for complex logic
- Use meaningful variable names
- Keep functions focused on single responsibility

### Security Practices
- Always use prepared statements with `bind_param()`
- Sanitize user input with `sanitizeInput()` function
- Use `htmlspecialchars()` for output to prevent XSS
- Hash passwords with `password_hash(PASSWORD_BCRYPT)`
- Validate input data thoroughly
- Use `session_regenerate_id()` for security

### Error Handling
- Use try-catch blocks for database operations
- Return JSON responses with consistent structure: `{'success': boolean, 'message': string, 'data': mixed}`
- Log errors to error_log() for debugging
- Provide user-friendly error messages

### Database Operations
- Use helper functions from `db_config.php` (`executeQuery()`, `getDBConnection()`)
- Always check connection before operations
- Use transactions for multi-step operations
- Log all activities with `logActivity()` function

## 2. JavaScript Coding Standards

### Naming Conventions
- **Variables**: camelCase (e.g., `currentPage`, `allInventory`)
- **Functions**: camelCase (e.g., `loadInventory()`, `submitProduct()`)
- **Constants**: UPPER_CASE (e.g., `ITEMS_PER_PAGE`)

### Code Style
- Use async/await for asynchronous operations
- Use `fetch()` API for HTTP requests
- Use template literals for string interpolation
- Use `const` and `let` instead of `var`
- Use arrow functions when appropriate
- Add event listeners properly with `addEventListener()`

### DOM Manipulation
- Use `document.getElementById()` for single elements
- Use `document.querySelector()` for complex selectors
- Cache DOM elements when used multiple times
- Use innerHTML carefully (prefer textContent for text-only content)

### Error Handling
- Use try-catch blocks for async operations
- Provide user feedback for errors
- Log errors to console for debugging

## 3. HTML Coding Standards

### Structure
- Use HTML5 semantic elements (`<header>`, `<main>`, `<section>`, etc.)
- Include proper DOCTYPE and meta tags
- Use consistent indentation (2 spaces)
- Keep HTML clean and semantic

### Forms
- Use appropriate input types (`email`, `date`, `number`, etc.)
- Include `required` attribute for mandatory fields
- Use `autocomplete` attributes for better UX
- Group related fields with `<fieldset>` when appropriate

### Accessibility
- Include `alt` attributes for images
- Use proper heading hierarchy (h1, h2, h3, etc.)
- Include `aria-label` where needed
- Ensure keyboard navigation works

## 4. CSS Coding Standards

### Naming Conventions
- **Classes**: kebab-case (e.g., `.form-container`, `.btn-primary`)
- **IDs**: camelCase (e.g., `#loginForm`, `#userModal`)
- **CSS Variables**: kebab-case with `--` prefix (e.g., `--primary-color`)

### Code Style
- Use consistent indentation (2 spaces)
- Group related properties together
- Use shorthand properties when possible
- Add comments for complex styles
- Use relative units (rem, em) over fixed units (px) when appropriate

### Responsive Design
- Use mobile-first approach
- Include media queries for different screen sizes
- Test on multiple devices and browsers

### Performance
- Minimize CSS specificity conflicts
- Use CSS Grid and Flexbox for layouts
- Avoid !important unless absolutely necessary

## 5. SQL Coding Standards

### Database Design
- Use appropriate data types (VARCHAR with reasonable lengths)
- Add proper indexes for frequently queried columns
- Use foreign keys for referential integrity
- Normalize data appropriately

### Naming Conventions
- **Tables**: lowercase plural (e.g., `accounts`, `inventory`)
- **Columns**: camelCase (e.g., `accountId`, `firstName`)
- **Primary Keys**: `tableName + 'Id'` (e.g., `accountId`, `productId`)
- **Foreign Keys**: Same as referenced primary key

### Stored Procedures
- Use PascalCase for procedure names (e.g., `CreateSaleTransaction`)
- Include proper error handling with EXIT HANDLER
- Use meaningful parameter names with prefixes (e.g., `p_productId`)

### Views
- Prefix with `view_` (e.g., `view_sales_today`)
- Use for complex queries used frequently
- Include appropriate indexes

## 6. API Design Standards

### RESTful Principles
- Use appropriate HTTP methods (GET, POST, PUT, DELETE)
- Return consistent JSON response format
- Include proper HTTP status codes
- Handle CORS appropriately for development

### Endpoints
- Use lowercase with hyphens for readability (e.g., `/api/inventory.php`)
- Include version in URL if needed (e.g., `/api/v1/inventory`)
- Use query parameters for filtering and pagination

### Request/Response Format
- Send JSON data in request body for POST/PUT
- Return JSON with consistent structure
- Include timestamps in responses
- Handle validation errors gracefully

## 7. General Best Practices

### Version Control
- Commit frequently with meaningful messages
- Use feature branches for new development
- Follow conventional commit format

### Documentation
- Add PHPDoc comments for functions
- Document complex algorithms
- Keep README updated

### Performance
- Optimize database queries
- Use pagination for large datasets
- Cache frequently accessed data
- Minimize HTTP requests

### Testing
- Test all user inputs for edge cases
- Validate data integrity
- Test cross-browser compatibility
- Perform security testing

### Maintenance
- Keep dependencies updated
- Monitor error logs
- Regular code reviews
- Refactor when code becomes complex

## 8. File Organization

### Directory Structure
```
/
├── index.php                 # Main entry point
├── auth/                     # Authentication files
│   ├── login.php
│   ├── register.php
│   └── logout.php
├── api/                      # API endpoints
│   ├── inventory.php
│   ├── sales.php
│   └── accounts.php
├── css/                      # Stylesheets
├── js/                       # JavaScript files
├── database/                 # Database files
│   ├── db_config.php
│   └── pharmacy_mis.sql
└── Pharmacy Icons/           # Static assets
```

### File Naming
- Use lowercase with hyphens for HTML/CSS/JS files
- Use PascalCase for PHP class files
- Include version numbers for assets when needed
- Keep file names descriptive but concise

## 9. Security Checklist

- [ ] Input validation on all user inputs
- [ ] SQL injection prevention (prepared statements)
- [ ] XSS prevention (output escaping)
- [ ] CSRF protection on forms
- [ ] Secure password hashing
- [ ] Session management
- [ ] File upload security
- [ ] Error message sanitization
- [ ] HTTPS enforcement in production

## 10. Code Review Checklist

- [ ] Code follows established naming conventions
- [ ] Functions are properly documented
- [ ] Error handling is implemented
- [ ] Security best practices are followed
- [ ] Code is readable and maintainable
- [ ] No hardcoded values
- [ ] Database queries are optimized
- [ ] Responsive design is implemented
- [ ] Cross-browser compatibility tested
