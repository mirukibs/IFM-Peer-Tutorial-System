# 🎓 **IFM Peer Tutoring System**
A role-based academic support platform built for the **Institute of Finance Management (IFM)** to connect **students**, **tutors**, and **admins**.  
The system enables learners to request sessions, tutors to host academic help, and admins to manage the overall learning workflow.

**✨ Now running with SQLite!** - No MySQL/MariaDB server required!

---

## 📌 Features (Based on Database Design)

### 👨‍🎓 **Students**
- Register, login, and verify account  
- Request tutoring sessions  
- Join available sessions  
- Receive notifications  
- Rate tutors after sessions

### 👨‍🏫 **Tutors**
- Register and configure subjects they teach  
- Accept or reject session requests  
- Host tutoring sessions  
- Manage session capacity  
- Manage availability schedule
- Receive system notifications  
- View feedback from learners

### 🛡️ **Admin**
- Full user management  
- Activate / suspend / deactivate users  
- Assign roles (student / tutor / admin)  
- Monitor tutoring activities  
- Handle session moderation

---

## 🛠 Technologies Used

| Component | Technology |
|----------|------------|
| Backend  | **PHP 8.3+ (Native PHP with PDO/mysqli)** |
| Frontend | HTML, CSS, Bootstrap, JavaScript |
| Database | **SQLite 3** (default) or **MySQL/MariaDB** |
| Server   | PHP Built-in Server (or Apache) |
| Authentication | Hashed passwords (bcrypt) |
| User Roles | student, tutor, admin |

**💡 Flexible Database Support:**
- Default: SQLite (zero configuration)
- Optional: MySQL/MariaDB (for production)
- Switch by editing one line in `config/db.php`
- See [DATABASE_SWITCHING.md](DATABASE_SWITCHING.md) for details

---

## 🚀 Installation Guide (Linux - Ubuntu/Debian)

### **1️⃣ Prerequisites**

First, ensure you have PHP and SQLite installed:

```bash
# Update package list
sudo apt update

# Install PHP and required extensions
sudo apt install php php-cli php-sqlite3 sqlite3

# Verify installations
php -v
sqlite3 --version
```

---

### **2️⃣ Clone/Download the Project**

```bash
cd ~/Development
git clone https://github.com/holysinner22/IFM-Peer-Tutorial-System.git
cd IFM-Peer-Tutorial-System
```

Or download ZIP → extract to your preferred location.

---

### **3️⃣ Database Setup (SQLite)**

The SQLite database file (`peer_tutoring.db`) is already created with sample data. If you need to recreate it:

```bash
# Remove existing database (optional)
rm -f peer_tutoring.db

# Create fresh database from schema
sqlite3 peer_tutoring.db < peer_tutoring.sqlite.sql
```

Set proper permissions:

```bash
# Make database writable
chmod 666 peer_tutoring.db

# Make uploads directory writable
chmod 777 uploads/profile_pics/
```

**Note:** The database is a single file (`peer_tutoring.db`) - no server configuration needed!

---

### **4️⃣ Database Connection**

The system is pre-configured to use SQLite. Check `config/db.php`:

```php
<?php
$db_path = __DIR__ . '/../peer_tutoring.db';
$pdo = new PDO("sqlite:$db_path");
// ... compatibility layer for mysqli-style code
?>
```

**No configuration changes needed!** The system automatically uses the SQLite database.

---

### **5️⃣ Run the System**

#### **Option A: PHP Built-in Server (Recommended for Development)**

```bash
cd ~/Development/IFM-Peer-Tutorial-System
php -S localhost:8000
```

Then open your browser and visit:
```
http://localhost:8000
```

#### **Option B: Apache Web Server**

If you prefer Apache:

```bash
# Install Apache
sudo apt install apache2

# Create symbolic link
sudo ln -s ~/Development/IFM-Peer-Tutorial-System /var/www/html/peer-tutoring

# Set permissions
sudo chown -R www-data:www-data ~/Development/IFM-Peer-Tutorial-System/peer_tutoring.db
sudo chown -R www-data:www-data ~/Development/IFM-Peer-Tutorial-System/uploads

# Start Apache
sudo systemctl start apache2
```

Then visit:
```
http://localhost/peer-tutoring
```

---

## 🔑 Default Test Accounts

The database includes pre-configured accounts:

| Role | Email | Password | Notes |
|------|-------|----------|-------|
| **Admin** | admin@ifm.ac.tz | *(hashed - register new admin)* | Full system access |
| **Tutor** | kemmy@ifm.ac.tz | *(hashed - use password recovery)* | Can create sessions |
| **Tutor** | eugen@ifm.ac.tz | *(hashed - use password recovery)* | Has subjects configured |
| **Student** | dave@ifm.ac.tz | *(hashed - use password recovery)* | Can request sessions |
| **Student** | kibwana@ifm.ac.tz | *(hashed - use password recovery)* | Active learner |

**💡 Tip:** The easiest way to get started is to **register a new account** with your own email!

### To Reset/Create Admin Password:

```bash
# Generate a bcrypt hash for your password
php -r "echo password_hash('your_password_here', PASSWORD_DEFAULT);"

# Update the database
sqlite3 peer_tutoring.db
UPDATE users SET password_hash='<paste_hash_here>' WHERE email='admin@ifm.ac.tz';
.quit
```

---

## 📂 Database Structure

### 🗄️ SQLite Tables

1. **users** - User accounts with personal info
2. **user_roles** - Multi-role support (user can be both student & tutor)
3. **tutor_subjects** - Subjects each tutor teaches
4. **tutor_availability** - Tutor availability schedule
5. **sessions** - Tutoring sessions with status tracking
6. **session_registrations** - Students enrolled in sessions
7. **notifications** - System notifications for users
8. **feedback** - Student ratings and comments for tutors

### Session Statuses:
- `requested` - Initial state
- `assigned` - Tutor assigned (if applicable)
- `accepted` - Tutor accepted the request
- `rejected` - Tutor declined
- `cancelled` - Session cancelled
- `completed` - Session finished

---

## 🧪 Testing the System

1. **Register** - Create a new account (student or tutor role)
2. **Login** - Access your role-specific dashboard
3. **As Student:**
   - Request a tutoring session
   - Join available sessions
   - View notifications
   - Leave feedback after sessions
4. **As Tutor:**
   - Configure subjects you teach
   - Set availability schedule
   - Accept/reject session requests
   - View student registrations
5. **As Admin:**
   - Manage user accounts
   - Monitor all sessions
   - Activate/suspend users

---

## 🛠 Troubleshooting

### "Database Connection Failed"

Check file permissions:
```bash
ls -l peer_tutoring.db
# Should show write permissions (rw-rw-rw- or similar)

# Fix if needed:
chmod 666 peer_tutoring.db
```

### "Permission denied" on uploads

```bash
chmod -R 777 uploads/profile_pics/
```

### PHP SQLite Extension Not Found

```bash
# Install PHP SQLite3 extension
sudo apt install php-sqlite3

# Restart PHP server
# Ctrl+C to stop, then restart with:
php -S localhost:8000
```

### Database is locked

```bash
# Check if another process is using the database
lsof peer_tutoring.db

# Kill the process if needed, or restart your PHP server
```

### CSS/JS not loading

Ensure you're accessing via HTTP (not opening files directly):
- ✅ `http://localhost:8000`
- ❌ `file:///path/to/index.php`

---

## 🔧 Technical Notes

### mysqli to PDO Compatibility Layer

The system includes a custom compatibility layer (`config/db.php`) that:
- ✅ Wraps PDO to support mysqli-style code
- ✅ Supports `bind_param()`, `execute()`, `fetch_assoc()`
- ✅ Provides `insert_id`, `affected_rows`, `num_rows`
- ✅ Handles prepared statements automatically
- ✅ No code changes needed in the application layer

### SQLite Adaptations

MySQL functions converted for SQLite:
- `INSERT IGNORE` → `INSERT OR IGNORE`
- `GROUP_CONCAT(col SEPARATOR ', ')` → `GROUP_CONCAT(col, ', ')`
- `FIELD()` function → `CASE` statement
- `AUTO_INCREMENT` → `AUTOINCREMENT`

---

## 📁 Project Structure

```
IFM-Peer-Tutorial-System/
├── admin/              # Admin user management
├── auth/               # Authentication (login, register, etc.)
├── availability/       # Tutor availability management
├── config/             # Database configuration
│   └── db.php         # SQLite connection + mysqli compatibility
├── dashboard/          # Role-based dashboards
├── feedback/           # Rating and feedback system
├── notifications/      # Notification system
├── profile/            # User profile management
├── sessions/           # Session management (request, accept, etc.)
├── student/            # Student-specific features
├── tutor/              # Tutor-specific features
├── uploads/            # User-uploaded files
│   └── profile_pics/  # Profile pictures
├── index.php           # Landing page
├── peer_tutoring.db    # SQLite database file
├── peer_tutoring.sqlite.sql  # Database schema
└── README.md           # This file
```

---

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

---

## 👨‍💻 Author

**Holysinner**  
GitHub: [https://github.com/holysinner22](https://github.com/holysinner22)

---

## 📜 License

This project is proprietary and intended for academic use at IFM (Institute of Finance Management).

---

## 🆘 Need Help?

**Common Entry Points:**
- Main Page: `http://localhost:8000/`
- Login: `http://localhost:8000/auth/login.php`
- Register: `http://localhost:8000/auth/register.php`
- Admin Dashboard: `http://localhost:8000/dashboard/admin.php`
- Tutor Dashboard: `http://localhost:8000/dashboard/tutor.php`
- Student Dashboard: `http://localhost:8000/dashboard/student.php`

**Quick Start:**
1. Start server: `php -S localhost:8000`
2. Open browser: `http://localhost:8000`
3. Click "Register here" → Create account
4. Login → Start using the system!

---

**🎉 Enjoy using the IFM Peer Tutoring System!**

### 📄 `config.php`

```php
<?php
$host = "localhost";
$user = "root";
$pass = "";   // set your MySQL password
$db   = "peer_tutoring";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
?>
```

If your MySQL password is NOT empty → update `$pass`.

---

## **5️⃣ Run the System**

Open browser and visit:

```
http://localhost/IFM-Peer-Tutorial-System/
```

Common entry pages:

* `/login.php`
* `/register.php`
* `/admin/`
* `/tutor/`
* `/student/`

---

# 📂 Database Structure (Accurate to your SQL dump)

## 🧑‍🎓 `users` table

Stores personal info + hashed password.

| Column           | Type    | Notes                                      |
| ---------------- | ------- | ------------------------------------------ |
| id               | INT     | Primary key                                |
| first_name       | VARCHAR |                                            |
| last_name        | VARCHAR |                                            |
| email            | VARCHAR | **Unique**                                 |
| phone            | VARCHAR |                                            |
| degree_programme | VARCHAR |                                            |
| year_of_study    | INT     |                                            |
| password_hash    | VARCHAR | bcrypt                                     |
| status           | ENUM    | pending / active / suspended / deactivated |
| profile_pic      | VARCHAR | file name                                  |

---

## 🛂 `user_roles`

* A user can have multiple roles (student + tutor)
* Unique constraint: `(user_id, role)`

---

## 📚 `tutor_subjects`

Tutors can attach:

* subject
* year_of_study
* degree_programme

---

## 🗓️ `sessions`

Stores tutoring sessions.

| Status Options |
| -------------- |
| requested      |
| assigned       |
| accepted       |
| rejected       |
| cancelled      |
| completed      |

Includes:

* tutor_id
* learner_id
* capacity
* timestamps

---

## 📝 `session_registrations`

Stores students who join a session.
Unique constraint prevents double registration.

---

## 🔔 `notifications`

Stores messages for users.

---

## ⭐ `feedback`

Students can rate tutors (1–5 stars) + comment.

---

# 🌐 Folder Structure (Typical)

```
IFM-Peer-Tutorial-System/
│── admin/
│── tutor/
│── student/
│── config.php
│── login.php
│── register.php
│── assets/
│── uploads/
│── README.md
│── peer_tutoring.sql
```

---

# 🔑 Default Accounts (From Your SQL Dump)

| Role    | Email                                     | Status |
| ------- | ----------------------------------------- | ------ |
| Admin   | [admin@ifm.ac.tz](mailto:admin@ifm.ac.tz) | active |
| Tutor   | [eugen@ifm.ac.tz](mailto:eugen@ifm.ac.tz) | active |
| Student | [kemmy@ifm.ac.tz](mailto:kemmy@ifm.ac.tz) | active |
| Student | [dave@ifm.ac.tz](mailto:dave@ifm.ac.tz)   | active |

Password hashes are bcrypt — use your known passwords.

---

# 🧪 Testing the System

1. Try registering a new IFM student
2. Login as tutor and accept a session
3. Login as learner and join a session
4. Admin can activate/suspend accounts
5. Leave feedback after session completion

---

# 🛠 Troubleshooting

### "Database Connection Failed"

Check:

* Database name = `peer_tutoring`
* MySQL user = root
* Password = (blank for XAMPP)

---

### CSS/JS not loading

You must visit via:

✔ `http://localhost/IFM-Peer-Tutorial-System/`
NOT by opening PHP files directly.

---

### 500 Internal Server Error

Enable debugging:

```php
ini_set("display_errors", 1);
error_reporting(E_ALL);
```

---

# 🤝 Contributing

1. Fork repo
2. Create branch
3. Commit changes
4. Open pull request

---

# 👨‍💻 Author

** (Holysinner)**
GitHub: [https://github.com/holysinner22](https://github.com/holysinner22)

---

# 📜 License

This project is proprietary and intended for academic use at IFM.



