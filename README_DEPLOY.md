# Deployment Guide for Agricultural Market System

Your project is now fully prepared for live deployment. Follow these steps to get your system running on the web.

## Prerequisites
- A **Railway.app** account (linked to your GitHub).
- The latest code pushed to your GitHub repository: `Mathews-JB/Aggricultural_Market`.

---

## 1. Setup the MySQL Database
1. Go to your [Railway Dashboard](https://railway.app/dashboard).
2. Click **+ New Service** -> **Database** -> **Add MySQL**.
3. Once deployed, click on the **MySQL** service.
4. Go to the **Variables** tab. You will need these values later:
   - `MYSQLHOST`
   - `MYSQLUSER`
   - `MYSQLPASSWORD`
   - `MYSQLPORT`
   - `MYSQLDATABASE`

---

## 2. Import Your Data
1. While still in the **MySQL** service on Railway, click the **Data** tab.
2. Click **Import SQL**.
3. Upload the `agrimarket_db.sql` file located in your project root.
   - *Note:* If you see errors about "CREATE DATABASE", you can open the SQL file and remove the first two lines (`CREATE DATABASE...` and `USE...`) before uploading.

---

## 3. Deploy the PHP Website
1. Click **+ New Service** -> **GitHub Repo**.
2. Select your repository: `Mathews-JB/Aggricultural_Market`.
3. Railway will detect the `composer.json` and `Procfile` and start the build.

---

## 4. Link the Database to the Website (CRITICAL)
Your `config.php` looks for specific environment variables. You must add them to your Web Service:
1. Click on your **Web Service** (the one from your GitHub repo).
2. Go to the **Variables** tab.
3. Click **New Variable** and add the following (copy the values from your MySQL Variables tab):
   - `DB_HOST` -> (Value of `MYSQLHOST`)
   - `DB_USER` -> (Value of `MYSQLUSER`)
   - `DB_PASS` -> (Value of `MYSQLPASSWORD`)
   - `DB_NAME` -> (Value of `MYSQLDATABASE`)
   - `ENVIRONMENT` -> `production`

---

## 5. View Your Live Site
1. Go to the **Settings** tab of your Web Service.
2. Under **Networking**, click **Generate Domain**.
3. Click the link provided (e.g., `agri-market.up.railway.app`).

**Congratulations! Your Agriculture Market is now live for the world to see!**
