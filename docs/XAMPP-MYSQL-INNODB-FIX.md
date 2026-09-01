# XAMPP MySQL Won't Start – InnoDB Log/Tablespace Mismatch

## What’s going wrong

Your MySQL (MariaDB 10.4) logs show:

- **Earlier:** `Missing MLOG_CHECKPOINT` → InnoDB failed to start.
- **Later:** InnoDB log files were recreated (new LSN ~140309), but the **system tablespace** (`ibdata1`) still has pages written with the **old** log files (LSN in the millions).
- **Result:** InnoDB reports “log sequence number … is in the future” and MySQL fails to start or runs in a broken state.

So the problem is a **mismatch between InnoDB log files and the data files** (log files were reset without resetting the tablespace).

---

## Fix: Reset InnoDB and re-import your DB (recommended)

If you’re fine re-creating the database from your SQL dumps (e.g. `SARS.sql`, `gene_1_25.sql`, `cov_comp_25.sql`), this is the most reliable fix.

### 1. Stop MySQL

- In XAMPP Control Panel, click **Stop** for MySQL.
- Make sure no other MySQL/MariaDB service is running.

### 2. Back up the current data directory

Copy the whole folder so you can roll back if needed:

- **From:** `C:\xampp\mysql\data`
- **To:** e.g. `C:\xampp\mysql\data_backup_YYYYMMDD`

(Replace `YYYYMMDD` with today’s date.)

### 3. Remove InnoDB and database files

In `C:\xampp\mysql\data` **delete**:

- `ibdata1`
- `ib_logfile0`
- `ib_logfile1`
- `ibtmp1` (if present)
- The folder **`app_sarsntdb`** (your project database)

**Do not delete:**

- The **`mysql`** folder (system database – users, etc.)
- **`aria_log.*`** and **`aria_log_control`** (leave them unless you know what you’re doing)

If you prefer a full clean slate (and will recreate the MySQL user afterwards), you can instead:

- Rename `data` to `data_old` and create a new empty `data` folder, then copy back **only** the `mysql` folder from `data_old` into the new `data`.  
- Or use a fresh XAMPP MySQL data directory and then run the MySQL installer/setup to recreate the system DB.  
For most cases, deleting only the InnoDB files and `app_sarsntdb` is enough.

### 4. Start MySQL

- In XAMPP, click **Start** for MySQL.
- MySQL will recreate `ibdata1`, `ib_logfile0`, `ib_logfile1` with a consistent state.
- Check the MySQL log in XAMPP: it should start without the “future LSN” errors.

**If MySQL still shuts down unexpectedly:** The `mysql` and `phpmyadmin` folders in `data` contain InnoDB tables (`.ibd` files) that were written with the old log files. Those "future LSN" pages cause InnoDB to abort. Use the **Full data-directory reset** below instead.

---

## Full data-directory reset (when steps 1–4 still fail)

XAMPP ships a clean copy of the MySQL data directory. Use it to replace your broken `data` folder so **all** InnoDB files and system databases match.

### A. Stop MySQL

- In XAMPP Control Panel, click **Stop** for MySQL.

### B. Replace `data` with the clean backup

1. **Rename** the current data folder so you don't lose it:
   - Rename `C:\xampp\mysql\data` → `C:\xampp\mysql\data_old`
2. **Copy** the contents of XAMPP's backup into a new `data` folder:
   - Create folder: `C:\xampp\mysql\data`
   - Copy **everything inside** `C:\xampp\mysql\backup` into `C:\xampp\mysql\data`  
     (so `data` contains: `ibdata1`, `ib_logfile0`, `ib_logfile1`, `ibtmp1`, `mysql`, `performance_schema`, `phpmyadmin`, `test`)

### C. Start MySQL

- In XAMPP, click **Start** for MySQL.
- MySQL should start and stay running (the backup is a known-good, consistent set of files).

### D. Recreate the project database and user

1. Open **phpMyAdmin**: http://localhost/phpmyadmin  
2. Create database **`app_sarsntdb`**.  
3. Add user **`app_sarsntdb`** with the same password as in your project's `connection.php`, and grant it full privileges on `app_sarsntdb`.  
4. Import your project SQL in order, e.g.:
   - `SARS.sql`
   - `gene_1_25.sql`
   - `cov_comp_25.sql`

### E. Test the app

- Open http://localhost/SARSNTDB/GenomeSearch.php and confirm it loads.

---

## If phpMyAdmin says "Index for table 'db' is corrupt"

After a data-directory reset, the **mysql.db** table (user privileges) can be corrupt. Repair it before adding users.

### Option 1: Repair from phpMyAdmin

1. In the left sidebar, click the **`mysql`** database (not "User accounts").
2. Open the **SQL** tab at the top.
3. Run:
   ```sql
   REPAIR TABLE db;
   ```
4. If you see "OK" or "status: OK", refresh the **User accounts** page and try adding the user again.

### Option 2: Repair from command line

1. Open a command prompt and run:
   ```bat
   C:\xampp\mysql\bin\mysql.exe -u root -e "REPAIR TABLE mysql.db;"
   ```
   (If root has a password, use `-p` and enter it when prompted.)
2. Restart MySQL in XAMPP if the repair suggests it, then open phpMyAdmin → User accounts and add the user.

If **REPAIR TABLE** fails (e.g. "126 when fixing table" or "Table is marked as crashed") or the error persists, the `mysql` system database is damaged and must be reinitialized.

### Option 3: Reinitialize the system database (when repair fails)

This replaces the broken `mysql` folder with a fresh one. Your **app_sarsntdb** database and its tables are in a separate folder, so they are not touched. You will need to add the **app_sarsntdb** user again after this.

1. **Stop MySQL** in the XAMPP Control Panel.

2. **Rename** the current system database folder (so you can roll back if needed):
   - Go to `C:\xampp\mysql\data\`
   - Rename **`mysql`** to **`mysql_crashed`**

3. **Reinitialize** the system database. Open **Command Prompt** (cmd.exe) and try one of these:

   **Option A – run from the MySQL bin folder** (recommended; avoids "path specified" errors):
   ```bat
   cd /d C:\xampp\mysql\bin
   mysql_install_db.exe --datadir=C:\xampp\mysql\data
   ```

   **Option B – if Option A fails, use quoted paths and forward slashes:**
   ```bat
   "C:\xampp\mysql\bin\mysql_install_db.exe" --datadir="C:/xampp/mysql/data"
   ```

   **Option C – relative datadir from bin folder:**
   ```bat
   cd /d C:\xampp\mysql\bin
   mysql_install_db.exe --datadir=..\data
   ```

   **If you get "Data directory ... is not empty":** `mysql_install_db` only works on an **empty** directory, but we must not clear your real `data` (that would delete `app_sarsntdb` and everything else). Use a **temporary empty folder**, then copy only the new `mysql` folder into your real data:

   1. Create an empty folder, e.g. `C:\xampp\mysql\data_temp`.
   2. Run (from `C:\xampp\mysql\bin`):
      ```bat
      mysql_install_db.exe --datadir=C:\xampp\mysql\data_temp
      ```
   3. Rename your current broken system DB out of the way: in `C:\xampp\mysql\data\`, rename **`mysql`** to **`mysql_crashed`** (if it exists).
   4. Copy the new system DB into place: copy the folder **`data_temp\mysql`** to **`data\mysql`** (so `C:\xampp\mysql\data\mysql` contains all the files from `data_temp\mysql`).
   5. Delete the temporary folder **`data_temp`** (or leave it for a while if you want a backup).
   6. Start MySQL in XAMPP and continue from step 5 below (log in as root, add user, etc.).

   **If you get "The system cannot find the path specified":** Your XAMPP MySQL may be in a different location. In Command Prompt run:
   ```bat
   dir C:\xampp
   ```
   and, if you see a `mysql` folder:
   ```bat
   dir C:\xampp\mysql
   ```
   If `C:\xampp\mysql` doesn't exist, XAMPP might be on another drive (e.g. `D:\xampp`) or use a different name (e.g. `mariadb`). Check where you installed XAMPP and use that path in the commands above (e.g. `D:\xampp\mysql\bin` and `D:\xampp\mysql\data`).

   **Alternative when `mysql_install_db` is missing or path can't be found:** Use a **clean `mysql` folder from a fresh XAMPP copy** – see **Option 3b** below.

4. **Start MySQL** in XAMPP.

5. **Log in to phpMyAdmin** as **root**. By default after reinstall, root often has **no password** (leave the password field empty). If that fails, try the password you used before.

6. **Re-add the project user and privileges:**
   - Go to **User accounts** (the "crashed" error should be gone).
   - Click **Add user account**.
   - User name: **`app_sarsntdb`**
   - Host name: **Local** (or `localhost`)
   - Password: use the value from your project’s `connection.php` (e.g. `s0vPLBb(IGbs/mAC`).
   - Under "Database for user account", choose **Grant all privileges on database "app_sarsntdb"** (create the database first if it’s missing: **Databases** → Create **app_sarsntdb**, then add the user with privileges on it).
   - Click **Go**.

7. If **app_sarsntdb** is empty (no tables), **import** your SQL again: `SARS.sql`, then `gene_1_25.sql`, `cov_comp_25.sql`.

8. **Test:** open http://localhost/SARSNTDB/GenomeSearch.php

If you have another working XAMPP (same version) on a different machine, you can instead copy its `C:\xampp\mysql\data\mysql` folder over your `data\mysql` instead of running `mysql_install_db`.

### Option 3b: Use a clean `mysql` folder from a new XAMPP (when mysql_install_db path is not found)

If `C:\xampp\mysql\bin` doesn't exist on your PC (or the path is different and you can't find `mysql_install_db.exe`), you can get a **clean system database** from a fresh XAMPP package without reinstalling your current XAMPP.

1. **Download** the same XAMPP version you use from https://www.apachefriends.org/ (e.g. the same PHP/MySQL version).
2. **Extract** the downloaded ZIP to a **temporary** folder (e.g. `C:\xampp_temp`). Do **not** run the installer; just unpack.
3. **Stop MySQL** in your real XAMPP Control Panel.
4. In your **real** XAMPP data folder (where your `mysql_crashed` folder is), **delete** the current **`mysql`** folder if it exists (you already renamed the broken one to `mysql_crashed`, so there may be no `mysql` folder).
5. **Copy** the folder **`mysql`** from the extracted package’s backup into your real data folder:
   - **From:** `(extracted_folder)\mysql\backup\mysql`
   - **To:** `(your XAMPP)\mysql\data\mysql`  
   Example: if your XAMPP is `C:\xampp`, copy from `C:\xampp_temp\mysql\backup\mysql` to `C:\xampp\mysql\data\mysql` (create the `mysql` folder under `data` and put the contents of `backup\mysql` inside it).
6. **Start MySQL** in XAMPP. Log in to phpMyAdmin as **root** (often no password), then add the **app_sarsntdb** user and privileges as in steps 5–8 of Option 3 above.
7. You can delete the temporary extracted folder when done.

---

### 5. Recreate the database and user

1. Open **phpMyAdmin**: http://localhost/phpmyadmin  
2. Create database **`app_sarsntdb`** (if you deleted it).  
3. Create the MySQL user that matches `connection.php` (e.g. user **`app_sarsntdb`**, same password), and grant it full rights on `app_sarsntdb`.  
4. Import your SQL files in this order (or as your project instructs):
   - `SARS.sql` (or the main schema)
   - `gene_1_25.sql`
   - `cov_comp_25.sql`
   - Any other project SQL.

### 6. Test the app

- Open: http://localhost/SARSNTDB/GenomeSearch.php  
- Confirm the app connects and data loads.

---

## MySQL “shutdown unexpectedly” right after “Server socket created”

If the log shows a **successful** startup (InnoDB started, “Server socket created on IP: '::'”) but XAMPP still reports “MySQL shutdown unexpectedly”, the process is exiting **after** startup. The real error is usually in the **next lines** of the log.

1. **Open the full error log**  
   In XAMPP click **Logs** next to MySQL, or open `C:\xampp\mysql\data\mysql_error.log`. Scroll to the **very end** (after the last “Server socket created” line). Look for any **ERROR**, **Assertion**, or **exception** that appears right after that. That message is the cause of the crash.

2. **Check if port 3306 is in use**  
   In Command Prompt run:
   ```bat
   netstat -ano | findstr 3306
   ```
   If another process is using 3306, stop that process or change MySQL’s port in `C:\xampp\mysql\bin\my.ini` (and in your app’s config if needed).

3. **Try removing the buffer-pool dump (optional)**  
   Sometimes a stale `ib_buffer_pool` file can cause issues after a restart. Stop MySQL, then in `C:\xampp\mysql\data\` rename or delete **`ib_buffer_pool`** (not ibdata1 or the log files). Start MySQL again. It will recreate the file if needed.

4. **Shut down Windows cleanly**  
   After using MySQL, use **Shut down** (or **Sign out**) so that MySQL can flush and close files. Avoid force‑powering off or killing the XAMPP process; that can leave the data in a state that causes the next start to fail.

Once you have the exact error from step 1, you can target the fix (e.g. corrupt table, port conflict, or permission).

---

## Alternative: Try InnoDB force recovery (only if you have important data not in the SQL dumps)

If you have data in MySQL that is **not** in your SQL dumps and you want to try to recover it:

1. Open **`C:\xampp\mysql\bin\my.ini`** (or the `my.ini` that XAMPP uses for MySQL).  
2. Under the **`[mysqld]`** section, add:
   ```ini
   innodb_force_recovery = 1
   ```
3. Save, then start MySQL from XAMPP.  
4. If it still doesn’t start, try **2**, then **3**, up to **6** (higher = more aggressive recovery, more risk of corruption).  
5. If MySQL starts:
   - Use phpMyAdmin or `mysqldump` to **export** all databases you care about.  
   - Then **remove** the line `innodb_force_recovery = 1` from `my.ini`.  
   - Follow the “Reset InnoDB and re-import” steps above (delete InnoDB files and `app_sarsntdb`, start MySQL, recreate DB and user, import your dumps).  
   - Re-import the dumps you just made.

**Note:** With force recovery, some tables may be corrupt or unreadable. Use it only to try to salvage data before doing the reset.

---

## Summary

| Cause | InnoDB log files and system tablespace are out of sync (logs were recreated, data wasn’t). |
|-------|-------------------------------------------------------------------------------------------|
| Reliable fix | Back up `mysql\data`, delete `ibdata1`, `ib_logfile0`, `ib_logfile1`, `ibtmp1`, and the `app_sarsntdb` folder; start MySQL; recreate DB and user; re-import project SQL. |
| Optional | Use `innodb_force_recovery = 1` (up to 6) only to try to start and dump data before doing the reset. |

After the reset, keep a copy of your `SARS.sql` (and other SQL dumps) so you can always re-import the project database if something goes wrong again.
