# JobLead - Job Tracker for Consulting Leads

Internal tool for tracking potential consulting clients and managing outreach workflows.

## Features

- **JSON Import**: Upload job data via JSON format
- **Job Dashboard**: View all leads in a clean table format
- **Detailed Views**: See complete job information including contacts
- **Status Workflow**: Track lead status from discovery to response
- **Data Cleaning**: Automatically cleans LLM artifacts and formats URLs
- **Duplicate Detection**: Prevents duplicate entries by company + role

## Tech Stack

- PHP (vanilla)
- MySQL
- HTML/CSS
- JavaScript (vanilla)

## Local Development

### Requirements
- XAMPP (or similar) with PHP 7.4+ and MySQL
- Git

### Setup

1. Clone the repository:
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/
git clone YOUR_REPO_URL JobLead
```

2. Create database:
```bash
/Applications/XAMPP/xamppfiles/bin/mysql -u root -e "CREATE DATABASE joblead CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

3. Import schema:
```bash
/Applications/XAMPP/xamppfiles/bin/mysql -u root joblead < database/schema.sql
```

4. Start XAMPP and visit:
```
http://localhost/JobLead/public/
```

## Deployment

See [DEPLOYMENT.md](DEPLOYMENT.md) for complete deployment instructions to production.

Quick summary:
1. Create MySQL database in cPanel
2. Deploy via Git Version Control in cPanel
3. Configure `config/config.prod.php` with your database credentials
4. Import `database/schema.sql` via phpMyAdmin
5. Set `uploads/` folder permissions to 755

## Project Structure

```
JobLead/
├── config/
│   ├── config.php              # Main config (auto-detects environment)
│   ├── config.prod.php         # Production config (git-ignored)
│   └── database.php            # Database config loader
├── database/
│   ├── schema.sql              # Database schema
│   └── cleanup.php             # Data cleaning utilities
├── public/
│   ├── .htaccess               # URL rewriting rules
│   ├── index.php               # Application router
│   └── assets/
│       └── style.css           # All styling
├── src/
│   ├── Database.php            # Database connection class
│   ├── JobImporter.php         # JSON import and validation
│   ├── helpers.php             # Utility functions
│   └── pages/
│       ├── dashboard.php       # Job listing page
│       ├── details.php         # Job detail view
│       ├── upload.php          # JSON upload form
│       └── update_status.php   # Status update API
├── uploads/                    # JSON file storage
├── .htaccess                   # Root access control
├── .gitignore
├── DEPLOYMENT.md               # Deployment instructions
└── README.md                   # This file
```

## Usage

### Uploading Jobs

1. Visit Upload page
2. Paste JSON data (array of job objects)
3. Submit - duplicates are automatically detected

### Managing Status

Status workflow:
- **New** → **Awaiting approval** → **Create Email** → **Email sent** → **Email Opened** → **Responded to Email**
- Alternative: **Not interested**

Change status directly from the dashboard dropdown.

## Security

- Production config excluded from Git
- Directory listing disabled
- Sensitive files protected via .htaccess
- SQL injection protection via prepared statements
- XSS protection via proper escaping

## License

Internal use only.
