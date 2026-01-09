# Deployment Instructions for JobLead

## Step 1: Create MySQL Database in cPanel

1. Log into your cPanel at your hosting provider
2. Find and open **MySQL Databases**
3. Create a new database:
   - Database name: `joblead` (or your preferred name)
   - Note: cPanel usually adds a prefix like `username_joblead`
4. Create a database user:
   - Username: Choose a username (e.g., `joblead_user`)
   - Password: Generate a strong password and **save it**
5. Add the user to the database:
   - Select the user you created
   - Select the database you created
   - Grant **ALL PRIVILEGES**
6. **Save these credentials** - you'll need them in Step 3

## Step 2: Deploy Code via Git

### Option A: Using cPanel Git Version Control (Recommended)

1. In cPanel, find **Git Version Control**
2. Click **Create** to add a new repository
3. Enter repository details:
   - Clone URL: Your Git repository URL
   - Repository Path: `/public_html/tools/apps` (or your preferred path)
   - Repository Name: `JobLead`
4. Click **Create**
5. cPanel will clone your repository

### Option B: Using Terminal (if available)

```bash
cd ~/public_html/tools
git clone YOUR_REPOSITORY_URL apps
```

## Step 3: Configure Production Settings

1. In cPanel **File Manager**, navigate to your app directory:
   - Go to: `/public_html/tools/apps/config/`
2. Open `config.prod.php` and update:
   - `BASE_PATH`: Your full server path (usually `/home/YOUR_USERNAME/public_html/tools/apps`)
   - `DB_HOST`: Usually `localhost`
   - `DB_USER`: The database username from Step 1
   - `DB_PASS`: The database password from Step 1
   - `DB_NAME`: The database name from Step 1
3. Save the file

## Step 4: Import Database Schema

### Option A: Using cPanel phpMyAdmin

1. In cPanel, open **phpMyAdmin**
2. Select your database from the left sidebar
3. Click the **Import** tab
4. Click **Choose File** and select `database/schema.sql` from your local copy
5. Click **Go** at the bottom
6. Verify tables `jobs` and `contacts` were created

### Option B: Manual SQL Execution

1. In cPanel phpMyAdmin, select your database
2. Click the **SQL** tab
3. Copy and paste the contents of `database/schema.sql`
4. Click **Go**

## Step 5: Set Directory Permissions

1. In cPanel **File Manager**:
2. Navigate to `/public_html/tools/apps/uploads/`
3. Right-click the `uploads` folder → **Change Permissions**
4. Set to `755` (or `775` if needed)
5. Ensure the folder is writable by the web server

## Step 6: Verify Installation

1. Visit: `https://tools.veerl.es/apps/`
2. You should see the JobLead homepage
3. Test the upload page: `https://tools.veerl.es/apps/public/?page=upload`
4. Test the dashboard: `https://tools.veerl.es/apps/public/?page=dashboard`

## Step 7: Future Updates

When you need to update the application:

### Using cPanel Git Version Control:
1. Go to **Git Version Control** in cPanel
2. Find your repository
3. Click **Manage**
4. Click **Pull or Deploy** → **Update from Remote**

### Using Terminal (if available):
```bash
cd ~/public_html/tools/apps
git pull origin main
```

## Troubleshooting

### White screen or 500 error:
- Check PHP error log in cPanel → **Errors**
- Verify `config.prod.php` has correct database credentials
- Ensure `uploads/` directory permissions are correct

### Database connection error:
- Double-check database credentials in `config.prod.php`
- Verify the database user has privileges on the database
- Confirm `DB_HOST` is `localhost` (most common)

### CSS/Assets not loading:
- Verify `.htaccess` files are present in root and `/public/`
- Check that `BASE_URL` in `config.prod.php` is correct
- Clear browser cache

### Page not found errors:
- Ensure `.htaccess` is in the `/public/` directory
- Verify mod_rewrite is enabled (contact hosting if needed)
- Check that `RewriteBase` in `.htaccess` matches your path

## Security Notes

- Never commit `config.prod.php` to Git (it's in .gitignore)
- Keep your database credentials secure
- Regularly backup your database through cPanel
- Monitor the PHP error log for any issues

## Support

If you encounter issues, check:
1. PHP error log in cPanel
2. Apache error log (if accessible)
3. Browser console for JavaScript errors
